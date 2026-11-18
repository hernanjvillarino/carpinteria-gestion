<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatedCommunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatedCommunityController extends Controller
{
    public function index(): View
    {
        return view('admin.barrios.index', [
            'barrios' => GatedCommunity::query()->withCount('clients')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.barrios.form', ['barrio' => new GatedCommunity]);
    }

    public function store(Request $request): RedirectResponse
    {
        GatedCommunity::create($this->validated($request));

        return redirect()->route('admin.barrios.index')->with('status', 'Barrio creado.');
    }

    public function edit(GatedCommunity $barrio): View
    {
        return view('admin.barrios.form', ['barrio' => $barrio]);
    }

    public function update(Request $request, GatedCommunity $barrio): RedirectResponse
    {
        $barrio->update($this->validated($request));

        return redirect()->route('admin.barrios.index')->with('status', 'Barrio actualizado.');
    }

    public function destroy(GatedCommunity $barrio): RedirectResponse
    {
        $barrio->delete();

        return redirect()->route('admin.barrios.index')->with('status', 'Barrio eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:120'],
            'access_notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['show_on_website' => $request->boolean('show_on_website')];
    }
}
