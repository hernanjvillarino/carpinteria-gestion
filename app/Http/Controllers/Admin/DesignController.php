<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DesignStatus;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\WorkOrder;
use App\Notifications\DesignSentToClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Versiones del diseño SketchUp de un trabajo. */
class DesignController extends Controller
{
    public function store(Request $request, WorkOrder $trabajo): RedirectResponse
    {
        $data = $request->validate([
            'preview' => ['required', 'image', 'max:10240'],
            'skp' => ['nullable', 'file', 'max:102400', function ($attribute, $file, $fail) {
                if (strtolower($file->getClientOriginalExtension()) !== 'skp') {
                    $fail('El archivo de SketchUp tiene que ser .skp.');
                }
            }],
            'notes' => ['nullable', 'string', 'max:5000'],
            'send' => ['boolean'],
        ], [], ['preview' => 'imagen del diseño', 'skp' => 'archivo SketchUp']);

        $design = DB::transaction(function () use ($request, $trabajo, $data) {
            $version = (int) $trabajo->designs()->lockForUpdate()->max('version') + 1;
            $dir = "trabajos/{$trabajo->id}/disenos";

            return $trabajo->designs()->create([
                'version' => $version,
                'preview_path' => $request->file('preview')->store($dir, 'local'),
                'skp_path' => $request->file('skp')?->storeAs($dir, "v{$version}-".now()->format('YmdHis').'.skp', 'local'),
                'notes' => $data['notes'] ?? null,
                'status' => DesignStatus::Borrador,
            ]);
        });

        if ($request->boolean('send')) {
            return $this->send($design);
        }

        return back()->with('status', "Versión {$design->version} del diseño guardada como borrador.");
    }

    /** Envía la versión al cliente para que la apruebe. */
    public function send(Design $design): RedirectResponse
    {
        abort_unless($design->status === DesignStatus::Borrador, 422, 'Esta versión ya se envió.');

        $design->update(['status' => DesignStatus::Enviado, 'sent_at' => now()]);
        $clientUser = $design->workOrder->client->user;
        $clientUser?->notify(new DesignSentToClient($design));

        return back()->with('status', $clientUser
            ? "Versión {$design->version} enviada. Le avisamos al cliente por email."
            : "Versión {$design->version} marcada como enviada. El cliente no tiene acceso al portal: dale acceso desde su ficha para que pueda aprobarla.");
    }

    public function destroy(Design $design): RedirectResponse
    {
        abort_unless($design->status === DesignStatus::Borrador, 422, 'Solo se pueden borrar borradores.');

        Storage::disk('local')->delete(array_filter([$design->preview_path, $design->skp_path]));
        $design->delete();

        return back()->with('status', 'Borrador eliminado.');
    }
}
