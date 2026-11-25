<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\InstructionTemplate;
use App\Models\Material;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'stage_id' => ['nullable', 'integer'],
            'carpintero_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['activos', 'entregados', 'todos'])],
        ]);
        $estado = $filters['estado'] ?? 'activos';

        $orders = WorkOrder::query()->with(['client.gatedCommunity', 'stage', 'carpenters'])
            ->when($estado === 'activos', fn ($q) => $q->whereNull('delivered_at'))
            ->when($estado === 'entregados', fn ($q) => $q->whereNotNull('delivered_at'))
            ->when($filters['stage_id'] ?? null, fn ($q, $id) => $q->where('stage_id', $id))
            ->when($filters['carpintero_id'] ?? null, fn ($q, $id) => $q->whereHas('carpenters', fn ($q) => $q->whereKey($id)))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$s}%")->orWhere('title', 'like', "%{$s}%")
                ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$s}%"))))
            ->orderByRaw("case priority when 'alta' then 0 when 'normal' then 1 else 2 end")
            ->orderBy('estimated_delivery')
            ->paginate(25)->withQueryString();

        return view('admin.trabajos.index', [
            'orders' => $orders,
            'stages' => Stage::ordered(),
            'carpinteros' => User::carpinteros()->orderBy('name')->get(),
            'filters' => $filters + ['estado' => $estado],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.trabajos.form', $this->formData(new WorkOrder([
            'client_id' => $request->integer('client_id') ?: null,
            'priority' => Priority::Normal,
            'lead_time_days' => config('carpinteria.default_lead_time_days'),
            'started_on' => today(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $order = DB::transaction(function () use ($data, $request) {
            $order = WorkOrder::create([
                ...$this->orderFields($data),
                'stage_id' => $data['stage_id'] ?? Stage::query()->orderBy('position')->value('id'),
                'created_by' => $request->user()->id,
            ]);
            $order->addTasks($request->user(), $data['instrucciones'] ?? [], $data['instrucciones_extra'] ?? null);
            $order->assignCarpenters($data['carpinteros'] ?? [], $this->leadId($data));

            return $order;
        });

        return redirect()->route('admin.trabajos.show', $order)->with('status', "Trabajo {$order->code} creado.");
    }

    public function show(WorkOrder $trabajo): View
    {
        $trabajo->load(['client.gatedCommunity', 'stage', 'carpenters', 'creator', 'designs', 'quotes', 'tasks.doneBy', 'rating.photos', 'rating.user', 'stockMovements' => fn ($q) => $q->with(['material', 'user']), 'updates' => fn ($q) => $q->with(['user', 'fromStage', 'toStage', 'photos'])]);

        return view('trabajos.show', [
            'order' => $trabajo,
            'stages' => Stage::ordered(),
            'materials' => Material::query()->where('is_active', true)->orderBy('name')->get(),
            'instructionTemplates' => InstructionTemplate::query()->active()->get(),
            'isAdmin' => true,
        ]);
    }

    public function edit(WorkOrder $trabajo): View
    {
        return view('admin.trabajos.form', $this->formData($trabajo->load(['carpenters', 'tasks'])));
    }

    public function update(Request $request, WorkOrder $trabajo): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($trabajo, $data, $request) {
            $newStage = isset($data['stage_id']) ? Stage::find($data['stage_id']) : null;
            $trabajo->update(collect($this->orderFields($data))->except('stage_id')->all());
            $trabajo->addTasks($request->user(), $data['instrucciones'] ?? [], $data['instrucciones_extra'] ?? null);
            $trabajo->assignCarpenters($data['carpinteros'] ?? [], $this->leadId($data));

            // Un cambio de etapa desde la edición también queda en el historial.
            if ($newStage && $newStage->id !== $trabajo->stage_id) {
                $trabajo->recordUpdate($request->user(), $newStage, 'Etapa cambiada desde la edición del trabajo.', false);
            }
        });

        return redirect()->route('admin.trabajos.show', $trabajo)->with('status', 'Trabajo actualizado.');
    }

    public function destroy(WorkOrder $trabajo): RedirectResponse
    {
        $trabajo->delete();

        return redirect()->route('admin.trabajos.index')->with('status', "Trabajo {$trabajo->code} archivado.");
    }

    private function formData(WorkOrder $order): array
    {
        return [
            'order' => $order,
            'clientes' => Client::query()->orderBy('name')->get(['id', 'name']),
            'stages' => Stage::ordered(),
            'carpinteros' => User::carpinteros()->orderBy('name')->get(),
            'priorities' => Priority::cases(),
            'instructionTemplates' => InstructionTemplate::query()->active()->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'install_address' => ['nullable', 'string', 'max:255'],
            'stage_id' => ['nullable', 'exists:stages,id'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:730'],
            'started_on' => ['required', 'date'],
            'instrucciones' => ['array'],
            'instrucciones.*' => ['integer', 'exists:instruction_templates,id'],
            'instrucciones_extra' => ['nullable', 'string', 'max:3000'],
            'carpinteros' => ['array'],
            'carpinteros.*' => [Rule::exists('users', 'id')->where('role', 'carpintero')],
            'lead_id' => ['nullable', 'integer', 'in_array:carpinteros.*'],
        ]);
    }

    private function orderFields(array $data): array
    {
        return collect($data)->except('carpinteros', 'lead_id', 'instrucciones', 'instrucciones_extra')->all();
    }

    private function leadId(array $data): ?int
    {
        return isset($data['lead_id']) ? (int) $data['lead_id'] : null;
    }
}
