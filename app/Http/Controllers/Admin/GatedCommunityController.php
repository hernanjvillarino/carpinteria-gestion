<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\GatedCommunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GatedCommunityController extends Controller
{
    public function index(): View
    {
        $barrios = GatedCommunity::query()->withCount('clients')->with(['requiredDocumentTypes', 'ownDocumentTypes'])->orderBy('name')->paginate(25);
        $employees = Employee::query()->active()->with('documents.type')->get();

        return view('admin.barrios.index', [
            'barrios' => $barrios,
            'employees' => $employees,
            'enabledCount' => $barrios->getCollection()->mapWithKeys(fn (GatedCommunity $b) => [
                $b->id => $employees->filter(fn (Employee $e) => $e->isEnabledFor($b))->count(),
            ]),
        ]);
    }

    /** Requisitos del barrio y quién del personal está habilitado para entrar. */
    public function show(GatedCommunity $barrio): View
    {
        $barrio->load(['requiredDocumentTypes', 'ownDocumentTypes', 'clients' => fn ($q) => $q->orderBy('name')]);

        return view('admin.barrios.show', [
            'barrio' => $barrio,
            'types' => $barrio->requiredTypes(),
            'employees' => Employee::query()->active()->with('documents.type')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.barrios.form', $this->formData(new GatedCommunity));
    }

    public function store(Request $request): RedirectResponse
    {
        $barrio = DB::transaction(function () use ($request) {
            [$data, $requirements] = $this->validated($request);
            $barrio = GatedCommunity::create($data);
            $barrio->requiredDocumentTypes()->sync($requirements);

            return $barrio;
        });

        return redirect()->route('admin.barrios.show', $barrio)->with('status', 'Barrio creado.');
    }

    public function edit(GatedCommunity $barrio): View
    {
        return view('admin.barrios.form', $this->formData($barrio->load('requiredDocumentTypes')));
    }

    public function update(Request $request, GatedCommunity $barrio): RedirectResponse
    {
        DB::transaction(function () use ($request, $barrio) {
            [$data, $requirements] = $this->validated($request);
            $barrio->update($data);
            $barrio->requiredDocumentTypes()->sync($requirements);
        });

        return redirect()->route('admin.barrios.show', $barrio)->with('status', 'Barrio actualizado.');
    }

    public function destroy(GatedCommunity $barrio): RedirectResponse
    {
        // Los documentos propios del barrio se borran con él: también sus archivos.
        $paths = EmployeeDocument::query()->whereIn('document_type_id', $barrio->ownDocumentTypes()->select('id'))->pluck('path');
        $barrio->delete();
        Storage::disk('local')->delete($paths->all());

        return redirect()->route('admin.barrios.index')->with('status', 'Barrio eliminado.');
    }

    private function formData(GatedCommunity $barrio): array
    {
        return [
            'barrio' => $barrio,
            'documentTypes' => DocumentType::query()->general()->orderBy('name')->get(),
        ];
    }

    /** @return array{0: array, 1: array<int>} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:120'],
            'access_notes' => ['nullable', 'string', 'max:2000'],
            'requisitos' => ['array'],
            'requisitos.*' => ['integer', Rule::exists('document_types', 'id')->whereNull('gated_community_id')],
        ]);

        return [
            collect($data)->only(['name', 'city', 'access_notes'])->all() + ['show_on_website' => $request->boolean('show_on_website')],
            array_map('intval', $data['requisitos'] ?? []),
        ];
    }
}
