<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignFileController extends Controller
{
    public function preview(Request $request, Design $design): StreamedResponse
    {
        Gate::authorize('view', $design->workOrder);
        abort_if($request->user()->role === Role::Cliente && ! $design->isVisibleToClient(), 404);

        return Storage::disk('local')->response($design->preview_path);
    }

    /** El .skp queda para el taller: el cliente ve la imagen. */
    public function skp(Request $request, Design $design): StreamedResponse
    {
        Gate::authorize('view', $design->workOrder);
        abort_if($request->user()->role === Role::Cliente || ! $design->skp_path, 404);

        return Storage::disk('local')->download($design->skp_path, "{$design->workOrder->code}-diseno-v{$design->version}.skp");
    }
}
