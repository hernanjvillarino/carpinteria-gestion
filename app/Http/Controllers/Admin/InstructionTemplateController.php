<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstructionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lista de indicaciones que el dueño precarga para tildar al asignar trabajos. */
class InstructionTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.indicaciones.index', [
            'templates' => InstructionTemplate::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:255']]);
        InstructionTemplate::create([...$data, 'position' => (int) InstructionTemplate::max('position') + 1]);

        return back()->with('status', 'Indicación agregada.');
    }

    public function update(Request $request, InstructionTemplate $indicacion): RedirectResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
        $indicacion->update([...$data, 'is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Indicación actualizada.');
    }

    public function destroy(InstructionTemplate $indicacion): RedirectResponse
    {
        $indicacion->delete(); // las tareas ya cargadas en trabajos conservan su texto

        return back()->with('status', 'Indicación eliminada.');
    }
}
