<?php

namespace App\Http\Controllers\Taller;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Stage;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Panel del carpintero: solo sus trabajos asignados. */
class WorkshopController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->assignedWorkOrders()
            ->with(['client.gatedCommunity', 'stage'])->withCount(['tasks as pending_tasks_count' => fn ($q) => $q->whereNull('done_at')])
            ->whereNull('delivered_at')
            ->orderByRaw("case priority when 'alta' then 0 when 'normal' then 1 else 2 end")
            ->orderBy('estimated_delivery')
            ->get();

        return view('taller.index', ['orders' => $orders]);
    }

    public function show(WorkOrder $trabajo): View
    {
        Gate::authorize('view', $trabajo);
        $trabajo->load(['client.gatedCommunity', 'stage', 'carpenters', 'designs', 'tasks.doneBy', 'stockMovements' => fn ($q) => $q->with(['material', 'user']), 'updates' => fn ($q) => $q->with(['user', 'fromStage', 'toStage', 'photos'])]);

        return view('trabajos.show', [
            'order' => $trabajo,
            'stages' => Stage::ordered(),
            'materials' => Material::query()->where('is_active', true)->orderBy('name')->get(),
            'isAdmin' => false,
        ]);
    }
}
