<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\WorkOrderPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Las fotos se guardan en disco privado: son casas de clientes. */
class WorkOrderPhotoController extends Controller
{
    public function show(Request $request, WorkOrderPhoto $photo): StreamedResponse
    {
        $order = $photo->workOrderUpdate->workOrder;
        Gate::authorize('view', $order);
        abort_if($request->user()->role === Role::Cliente && ! $photo->visible_to_client, 404);

        return Storage::disk('local')->response($photo->path);
    }

    /** El admin publica u oculta una foto para el cliente. */
    public function toggleVisibility(Request $request, WorkOrderPhoto $photo): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $photo->update(['visible_to_client' => ! $photo->visible_to_client]);

        return back()->with('status', $photo->visible_to_client ? 'La foto ahora la ve el cliente.' : 'Foto oculta para el cliente.');
    }
}
