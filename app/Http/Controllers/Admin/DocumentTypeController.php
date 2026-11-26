<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\GatedCommunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lista de documentos que pueden pedir los barrios. */
class DocumentTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.documentacion.index', [
            'types' => DocumentType::query()->with('gatedCommunity')->withCount(['documents', 'requiredBy'])
                ->orderByRaw('gated_community_id is not null')->orderBy('name')->get(),
            'barrios' => GatedCommunity::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DocumentType::create($this->validated($request));

        return back()->with('status', 'Documento agregado.');
    }

    public function update(Request $request, DocumentType $tipo): RedirectResponse
    {
        $tipo->update($this->validated($request));
        if ($tipo->gated_community_id) {
            $tipo->requiredBy()->detach(); // pasó a ser propio de un barrio
        }

        return back()->with('status', 'Documento actualizado.');
    }

    public function destroy(DocumentType $tipo): RedirectResponse
    {
        $paths = $tipo->documents()->pluck('path');
        $tipo->delete(); // borra también los archivos cargados de este tipo
        Storage::disk('local')->delete($paths->all());

        return back()->with('status', 'Documento eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'gated_community_id' => ['nullable', Rule::exists('gated_communities', 'id')],
        ]);

        return $data + ['requires_expiry' => $request->boolean('requires_expiry')];
    }
}
