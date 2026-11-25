<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('admin.stock.suppliers', ['suppliers' => Supplier::withCount('materials')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));

        return back()->with('status', 'Proveedor agregado.');
    }

    public function update(Request $request, Supplier $proveedor): RedirectResponse
    {
        $proveedor->update($this->validated($request));

        return back()->with('status', 'Proveedor actualizado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);
    }
}
