<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Tareas / indicaciones de un trabajo. */
class WorkOrderTaskController extends Controller
{
    /** El dueño suma indicaciones desde la ficha del trabajo. */
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('update', $workOrder);
        $data = $request->validate([
            'instrucciones' => ['array'],
            'instrucciones.*' => ['integer', 'exists:instruction_templates,id'],
            'instrucciones_extra' => ['nullable', 'string', 'max:3000'],
        ]);
        $workOrder->addTasks($request->user(), $data['instrucciones'] ?? [], $data['instrucciones_extra'] ?? null);

        return back()->with('status', 'Indicaciones agregadas.');
    }

    /** El carpintero asignado (o el dueño) marca o desmarca una tarea como hecha. */
    public function toggle(Request $request, WorkOrder $workOrder, WorkOrderTask $task): RedirectResponse
    {
        abort_unless($task->work_order_id === $workOrder->id, 404);
        Gate::authorize('progress', $workOrder);

        $task->update($task->isDone()
            ? ['done_at' => null, 'done_by' => null]
            : ['done_at' => now(), 'done_by' => $request->user()->id]);

        return back()->with('status', $task->isDone() ? 'Tarea marcada como hecha.' : 'Tarea marcada como pendiente.');
    }

    public function destroy(WorkOrder $workOrder, WorkOrderTask $task): RedirectResponse
    {
        abort_unless($task->work_order_id === $workOrder->id, 404);
        Gate::authorize('update', $workOrder);
        $task->delete();

        return back()->with('status', 'Indicación eliminada.');
    }
}
