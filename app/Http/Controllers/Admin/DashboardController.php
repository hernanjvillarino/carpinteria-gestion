<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stages = Stage::query()->orderBy('position')->withCount('workOrders')->get();

        return view('admin.dashboard', [
            'stages' => $stages,
            'overdue' => WorkOrder::query()->with('client')
                ->whereNull('delivered_at')->whereDate('estimated_delivery', '<', today())
                ->orderBy('estimated_delivery')->get(),
            'unassigned' => WorkOrder::query()->with('client')->whereNull('delivered_at')->doesntHave('carpenters')->get(),
            'recentUpdates' => WorkOrderUpdate::query()->with(['workOrder', 'user', 'toStage'])->withCount('photos')
                ->latest()->latest('id')->limit(10)->get(),
        ]);
    }
}
