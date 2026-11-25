<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderRating;
use App\Models\WorkOrderRatingPhoto;
use App\Notifications\WorkOrderRated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Calificación del cliente al terminar el trabajo. */
class RatingController extends Controller
{
    public function store(Request $request, WorkOrder $trabajo): RedirectResponse
    {
        Gate::authorize('view', $trabajo);
        abort_unless($request->user()->role === Role::Cliente, 403);

        if (! $trabajo->delivered_at) {
            throw ValidationException::withMessages(['rating' => 'Vas a poder calificar el trabajo cuando esté entregado.']);
        }
        if ($trabajo->rating()->exists()) {
            throw ValidationException::withMessages(['rating' => 'Ya calificaste este trabajo. ¡Gracias!']);
        }

        $score = ['required', 'integer', 'between:1,5'];
        $data = $request->validate([
            ...array_fill_keys(array_keys(WorkOrderRating::CRITERIA), $score),
            'comment' => ['nullable', 'string', 'max:5000'],
            'photos' => ['array', 'max:6'],
            'photos.*' => ['image', 'max:8192'],
        ]);

        $rating = DB::transaction(function () use ($request, $trabajo, $data) {
            $rating = $trabajo->rating()->create([...collect($data)->except('photos')->all(), 'user_id' => $request->user()->id]);
            foreach ($request->file('photos', []) as $file) {
                $rating->photos()->create(['path' => $file->store("calificaciones/{$trabajo->id}", 'local')]);
            }

            return $rating;
        });

        Notification::send(User::query()->admins()->get(), new WorkOrderRated($rating));

        return redirect(route('portal.trabajos.show', $trabajo).'#calificar')
            ->with('status', '¡Gracias por tu calificación! Nos ayuda a mejorar.');
    }

    /** Fotos de la calificación: las ven el cliente que las subió y la administración. */
    public function photo(Request $request, WorkOrderRatingPhoto $photo): StreamedResponse
    {
        $order = $photo->rating->workOrder;
        abort_unless($request->user()->isAdmin() || $order->client?->user_id === $request->user()->id, 403);

        return Storage::disk('local')->response($photo->path);
    }
}
