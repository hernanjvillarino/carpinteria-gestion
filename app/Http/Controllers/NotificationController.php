<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Campanita de avisos internos (dueño y carpinteros). */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('avisos.index', [
            'notifications' => $request->user()->notifications()->paginate(30),
        ]);
    }

    /** Marca el aviso como leído y lleva a lo que avisa. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('panel'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Avisos marcados como leídos.');
    }

    /** Cantidad sin leer, para actualizar la campanita sin recargar. */
    public function count(Request $request): JsonResponse
    {
        return response()->json(['unread' => $request->user()->unreadNotifications()->count()]);
    }
}
