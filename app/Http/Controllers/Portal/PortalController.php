<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Portal del cliente. La línea de tiempo completa llega en la etapa 2. */
class PortalController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->client?->workOrders()->with('stage')->latest()->get() ?? collect();

        return view('portal.index', ['orders' => $orders]);
    }
}
