<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Registrar un avance (comentario, fotos y cambio de etapa). Lo usan admin y carpinteros asignados. */
class WorkOrderUpdateController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('progress', $workOrder);

        $data = $request->validate([
            'stage_id' => ['nullable', 'exists:stages,id'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'photos' => ['array', 'max:10'],
            'photos.*' => ['image', 'max:8192'], // 8 MB por foto
            'visible_to_client' => ['boolean'],
        ]);

        $toStage = isset($data['stage_id']) ? Stage::find($data['stage_id']) : null;
        $changesStage = $toStage && $toStage->id !== $workOrder->stage_id;

        if (! $changesStage && blank($data['comment'] ?? null) && empty($data['photos'])) {
            throw ValidationException::withMessages(['comment' => 'Escribí un comentario, subí fotos o cambiá la etapa.']);
        }

        // Los carpinteros solo pueden avanzar a la etapa siguiente; el admin puede mover a cualquiera.
        if ($changesStage && ! $request->user()->isAdmin() && $workOrder->stage->next()?->id !== $toStage->id) {
            throw ValidationException::withMessages(['stage_id' => 'Solo podés pasar el trabajo a la etapa siguiente.']);
        }

        $photos = collect($request->file('photos', []))->map(fn ($file) => [
            'path' => $file->store("trabajos/{$workOrder->id}", 'local'),
        ])->all();

        // Solo el admin decide qué ve el cliente.
        $visible = $request->user()->isAdmin() && $request->boolean('visible_to_client');

        $workOrder->recordUpdate($request->user(), $changesStage ? $toStage : null, $data['comment'] ?? null, $visible, $photos);

        return back()->with('status', $changesStage ? "Trabajo pasado a «{$toStage->name}»." : 'Avance registrado.');
    }
}
