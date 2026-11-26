<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Archivos del personal (ART, seguros, DNI...). Disco privado: datos personales. */
class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Employee $empleado): RedirectResponse
    {
        $type = DocumentType::find($request->integer('document_type_id'));
        $data = $request->validate([
            'document_type_id' => ['required', Rule::exists('document_types', 'id')],
            'file' => ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,webp,heic'],
            'expires_on' => [$type?->requires_expiry ? 'required' : 'nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'expires_on.required' => 'Este documento vence: cargá la fecha de vencimiento.',
        ], ['file' => 'archivo', 'expires_on' => 'vencimiento', 'document_type_id' => 'tipo de documento']);

        $file = $request->file('file');
        $empleado->documents()->create([
            'document_type_id' => $data['document_type_id'],
            'path' => $file->store("personal/{$empleado->id}", 'local'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'expires_on' => $data['expires_on'] ?? null,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', "{$type->name} cargado.");
    }

    public function show(EmployeeDocument $document): StreamedResponse
    {
        $extension = pathinfo($document->original_name, PATHINFO_EXTENSION) ?: pathinfo($document->path, PATHINFO_EXTENSION);
        $name = str($document->type->name.' '.$document->employee->name)->slug().($extension ? '.'.$extension : '');

        return Storage::disk('local')->response($document->path, $name);
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('status', 'Documento eliminado.');
    }
}
