<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\GatedCommunity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Personal de la carpintería y su documentación para entrar a los barrios. */
class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->with(['user', 'documents.type.gatedCommunity'])
            ->when(! $request->boolean('inactivos'), fn ($q) => $q->active())
            ->orderBy('name')
            ->get();
        $barrios = GatedCommunity::query()->with(['requiredDocumentTypes', 'ownDocumentTypes'])->orderBy('name')->get()
            ->filter(fn (GatedCommunity $b) => $b->requiredTypes()->isNotEmpty());

        // Documentos vigentes que vencen pronto o ya vencieron (solo personal activo).
        $alerts = $employees->where('is_active', true)
            ->flatMap(fn (Employee $e) => $e->currentDocuments())
            ->filter(fn ($doc) => in_array($doc->status(), [DocumentStatus::Vencido, DocumentStatus::PorVencer], true))
            ->sortBy('expires_on')
            ->values();

        return view('admin.personal.index', compact('employees', 'barrios', 'alerts'));
    }

    public function create(): View
    {
        return view('admin.personal.form', $this->formData(new Employee(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = Employee::create($this->validated($request));

        return redirect()->route('admin.personal.show', $employee)->with('status', 'Empleado creado. Ya podés cargarle la documentación.');
    }

    public function show(Employee $empleado): View
    {
        $empleado->load(['user', 'documents' => fn ($q) => $q->with(['type.gatedCommunity', 'uploader'])->latest()]);
        $barrios = GatedCommunity::query()->with(['requiredDocumentTypes', 'ownDocumentTypes'])->orderBy('name')->get()
            ->filter(fn (GatedCommunity $b) => $b->requiredTypes()->isNotEmpty())->values();
        $types = DocumentType::query()->with('gatedCommunity')->orderByRaw('gated_community_id is not null')->orderBy('name')->get();

        return view('admin.personal.show', [
            'employee' => $empleado,
            'types' => $types,
            'barrios' => $barrios,
        ]);
    }

    public function edit(Employee $empleado): View
    {
        return view('admin.personal.form', $this->formData($empleado));
    }

    public function update(Request $request, Employee $empleado): RedirectResponse
    {
        $empleado->update($this->validated($request, $empleado));

        return redirect()->route('admin.personal.show', $empleado)->with('status', 'Datos actualizados.');
    }

    public function destroy(Employee $empleado): RedirectResponse
    {
        $paths = $empleado->documents()->pluck('path');
        $empleado->delete();
        Storage::disk('local')->delete($paths->all());

        return redirect()->route('admin.personal.index')->with('status', 'Empleado y su documentación eliminados.');
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            // Accesos internos que todavía no tienen ficha (o la de este empleado).
            'users' => User::query()
                ->whereIn('role', [Role::Admin, Role::Carpintero])
                ->where(fn ($q) => $q->whereDoesntHave('employee')->when($employee->user_id, fn ($q) => $q->orWhere('id', $employee->user_id)))
                ->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'dni' => ['nullable', 'string', 'max:20'],
            'cuil' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', [Role::Admin->value, Role::Carpintero->value]),
                Rule::unique('employees', 'user_id')->ignore($employee?->id),
            ],
        ], [], ['user_id' => 'acceso al sistema']);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
