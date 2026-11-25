<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DesignStatus;
use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Quote;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DesignAnswered;
use App\Notifications\QuoteAnswered;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Portal del cliente: sus pedidos, el avance por etapas con fotos y la aprobación de diseños. */
class PortalController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->client?->workOrders()
            ->with(['stage'])
            ->withCount([
                'designs as pending_designs_count' => fn ($q) => $q->where('status', DesignStatus::Enviado),
                'quotes as pending_quotes_count' => fn ($q) => $q->where('status', QuoteStatus::Enviado)
                    ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today())),
            ])
            ->latest()->get() ?? collect();

        return view('portal.index', [
            'orders' => $orders,
            'stages' => Stage::query()->where('visible_to_client', true)->orderBy('position')->get(),
        ]);
    }

    public function show(WorkOrder $trabajo): View
    {
        Gate::authorize('view', $trabajo);

        $trabajo->load([
            'stage',
            'designs' => fn ($q) => $q->where('status', '!=', DesignStatus::Borrador),
            'quotes' => fn ($q) => $q->where('status', '!=', QuoteStatus::Borrador)->with('items'),
            // Cambios de etapa visibles, más los avances que la administración publicó.
            'updates' => fn ($q) => $q
                ->where(fn ($q) => $q->where('visible_to_client', true)
                    ->orWhereHas('toStage', fn ($q) => $q->where('visible_to_client', true)))
                ->with(['toStage', 'photos' => fn ($q) => $q->where('visible_to_client', true)]),
        ]);

        return view('portal.show', [
            'order' => $trabajo,
            'stages' => Stage::query()->where('visible_to_client', true)->orderBy('position')->get(),
        ]);
    }

    public function answerDesign(Request $request, Design $design): RedirectResponse
    {
        Gate::authorize('view', $design->workOrder);
        abort_unless($request->user()->role === Role::Cliente, 403);

        if (! $design->awaitsClient()) {
            throw ValidationException::withMessages(['design' => 'Esta versión del diseño ya fue respondida.']);
        }

        $data = $request->validate([
            'decision' => ['required', 'in:aprobar,cambios'],
            'feedback' => ['nullable', 'required_if:decision,cambios', 'string', 'max:5000'],
        ], ['feedback.required_if' => 'Contanos qué cambios querés.']);

        $approved = $data['decision'] === 'aprobar';
        $design->update([
            'status' => $approved ? DesignStatus::Aprobado : DesignStatus::Rechazado,
            'client_feedback' => $data['feedback'] ?? null,
            'responded_at' => now(),
            'approved_at' => $approved ? now() : null,
            'approved_by' => $approved ? $request->user()->id : null,
        ]);

        Notification::send(User::query()->where('role', Role::Admin)->where('is_active', true)->get(), new DesignAnswered($design));

        return back()->with('status', $approved
            ? '¡Gracias! Aprobaste el diseño. Ya podemos pasar a producción.'
            : 'Recibimos tus comentarios. Te vamos a enviar una nueva versión.');
    }

    public function answerQuote(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorizeClientQuote($request, $quote);

        if ($quote->status !== QuoteStatus::Enviado) {
            throw ValidationException::withMessages(['quote' => 'Este presupuesto ya no se puede responder.']);
        }
        if ($quote->isExpired()) {
            throw ValidationException::withMessages(['quote' => 'Este presupuesto venció. Pedinos uno actualizado.']);
        }

        $data = $request->validate([
            'decision' => ['required', 'in:aceptar,rechazar'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        $accepted = $data['decision'] === 'aceptar';

        $quote->update([
            'status' => $accepted ? QuoteStatus::Aceptado : QuoteStatus::Rechazado,
            'accepted_at' => $accepted ? now() : null,
            'client_feedback' => $data['feedback'] ?? null,
            'responded_at' => now(),
            'responded_by' => $request->user()->id,
        ]);

        Notification::send(User::query()->where('role', Role::Admin)->where('is_active', true)->get(), new QuoteAnswered($quote));

        return back()->with('status', $accepted
            ? '¡Gracias! Aceptaste el presupuesto. Nos ponemos en contacto para coordinar.'
            : 'Recibimos tu respuesta. Si querés, contanos qué ajustar y te mandamos otra versión.');
    }

    /** PDF para el cliente: sin precios unitarios ni costos internos. */
    public function quotePdf(Request $request, Quote $quote): Response
    {
        $this->authorizeClientQuote($request, $quote);
        $quote->load(['workOrder.client.gatedCommunity', 'items']);

        return Pdf::loadView('presupuestos.pdf', ['quote' => $quote, 'internal' => false])
            ->download("presupuesto-{$quote->workOrder->code}-v{$quote->version}.pdf");
    }

    private function authorizeClientQuote(Request $request, Quote $quote): void
    {
        Gate::authorize('view', $quote->workOrder);
        abort_unless($request->user()->role === Role::Cliente, 403);
        abort_if($quote->status === QuoteStatus::Borrador, 404);
    }
}
