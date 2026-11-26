<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DesignStatus;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Material;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderRating;
use App\Models\WorkOrderUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Tablero del dueño: estado de trabajos, carpinteros, línea de tiempo y actividad en vivo. */
class DashboardController extends Controller
{
    public function index(): View
    {
        $stages = Stage::query()->orderBy('position')->get();
        $active = WorkOrder::query()->with(['client', 'stage', 'carpenters'])
            ->withCount(['tasks', 'tasks as done_tasks_count' => fn ($q) => $q->whereNotNull('done_at')])
            ->whereNull('delivered_at')->orderBy('estimated_delivery')->get();

        $stageCounts = $active->countBy('stage_id');
        $carpinteros = $this->carpenters();

        return view('admin.dashboard', [
            'kpis' => [
                'activos' => $active->count(),
                'atrasados' => $active->filter(fn ($o) => $o->estimated_delivery?->lt(today()))->count(),
                'proximos' => $active->filter(fn ($o) => $o->estimated_delivery?->between(today(), today()->addDays(7)))->count(),
                'entregados_mes' => WorkOrder::query()->where('delivered_at', '>=', today()->startOfMonth())->count(),
                'ocupados' => $carpinteros->where('active_count', '>', 0)->count(),
                'carpinteros' => $carpinteros->count(),
            ],
            'stageBars' => $stages->map(fn ($s) => ['stage' => $s, 'count' => $stageCounts[$s->id] ?? 0]),
            'carpinteros' => $carpinteros,
            'timeline' => $this->timeline($active, $stages),
            'activityByDay' => $this->activityByDay(14),
            'unassigned' => $active->filter(fn ($o) => $o->carpenters->isEmpty()),
            'lowStock' => Material::query()->belowMinimum()->orderBy('name')->get(),
            'designResponses' => Design::query()->with('workOrder.client')
                ->whereIn('status', [DesignStatus::Aprobado, DesignStatus::Rechazado])
                ->where('responded_at', '>=', now()->subDays(14))
                ->latest('responded_at')->limit(5)->get(),
            'recentUpdates' => $this->recentUpdates(),
            'satisfaction' => $this->satisfaction(),
        ]);
    }

    /** Promedio de las calificaciones de clientes del último año, por rubro, y las más recientes. */
    private function satisfaction(): array
    {
        $ratings = WorkOrderRating::query()->with('workOrder.client')->where('created_at', '>=', now()->subYear())->latest()->get();

        return [
            'count' => $ratings->count(),
            'overall' => $ratings->isEmpty() ? null : round($ratings->avg(fn ($r) => $r->average()), 1),
            'criteria' => collect(WorkOrderRating::CRITERIA)->map(fn ($label, $field) => [
                'label' => $label,
                'avg' => $ratings->isEmpty() ? null : round($ratings->avg($field), 1),
            ]),
            'latest' => $ratings->take(3),
        ];
    }

    /** Fragmento de "actividad en vivo" que la página vuelve a pedir cada pocos segundos. */
    public function activity(): View
    {
        return view('admin.partials.activity', ['recentUpdates' => $this->recentUpdates()]);
    }

    private function recentUpdates(): Collection
    {
        return WorkOrderUpdate::query()->with(['workOrder', 'user', 'toStage'])->withCount('photos')
            ->latest()->latest('id')->limit(12)->get();
    }

    /** Carpinteros activos con su carga de trabajo: ocupado si tiene al menos un trabajo sin entregar. */
    private function carpenters(): Collection
    {
        return User::query()->carpinteros()->orderBy('name')
            ->withCount(['assignedWorkOrders as active_count' => fn ($q) => $q->whereNull('delivered_at')])
            ->withMax('workOrderUpdates as last_update_at', 'created_at')
            ->with(['assignedWorkOrders' => fn ($q) => $q->whereNull('delivered_at')->select('work_orders.id', 'code', 'title')])
            ->get();
    }

    /**
     * Línea de tiempo: cada trabajo activo como barra desde el inicio hasta la entrega estimada,
     * con el avance por etapa y una marca de "hoy".
     */
    private function timeline(Collection $orders, Collection $stages): array
    {
        $orders = $orders->filter(fn ($o) => $o->started_on && $o->estimated_delivery)->values();
        if ($orders->isEmpty()) {
            return ['rows' => [], 'from' => today(), 'to' => today(), 'todayPct' => 0, 'months' => []];
        }

        $from = $orders->min('started_on')->copy()->min(today()->subDays(7))->startOfDay();
        $to = $orders->max('estimated_delivery')->copy()->max(today()->addDays(14))->endOfDay();
        $span = max(1, $from->diffInDays($to));
        $pct = fn (Carbon $d) => round(max(0, min(100, $from->diffInDays($d) / $span * 100)), 2);
        $positions = $stages->pluck('position', 'id');

        $rows = $orders->map(function (WorkOrder $o) use ($pct, $positions, $stages) {
            $progress = $o->progressPct($stages->count(), (int) ($positions[$o->stage_id] ?? 1));
            $elapsed = $o->started_on->diffInDays(today()) / max(1, $o->started_on->diffInDays($o->estimated_delivery)) * 100;

            return [
                'order' => $o,
                'left' => $pct($o->started_on),
                'width' => max(1.5, $pct($o->estimated_delivery) - $pct($o->started_on)),
                'progress' => $progress,
                'status' => match (true) {
                    $o->estimated_delivery->lt(today()) => 'atrasado',
                    $progress + 10 < $elapsed => 'en_riesgo',
                    default => 'en_tiempo',
                },
            ];
        });

        $months = [];
        for ($m = $from->copy()->startOfMonth()->addMonth(); $m->lte($to); $m->addMonth()) {
            $months[] = ['label' => $m->translatedFormat('M'), 'left' => $pct($m)];
        }

        return ['rows' => $rows, 'from' => $from, 'to' => $to, 'todayPct' => $pct(today()), 'months' => $months];
    }

    /** Avances cargados por día en las últimas semanas. */
    private function activityByDay(int $days): Collection
    {
        $counts = WorkOrderUpdate::query()->where('created_at', '>=', today()->subDays($days - 1))
            ->get(['created_at'])->countBy(fn ($u) => $u->created_at->toDateString());

        return collect(range($days - 1, 0))->map(fn ($i) => today()->subDays($i))
            ->map(fn (Carbon $d) => ['date' => $d, 'count' => $counts[$d->toDateString()] ?? 0]);
    }
}
