<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Usuarios internos: administradores y carpinteros. Los clientes se gestionan desde su ficha. */
class UserController extends Controller
{
    private const INTERNAL = [Role::Admin, Role::Carpintero];

    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::query()->whereIn('role', self::INTERNAL)->withCount('assignedWorkOrders')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User(['role' => Role::Carpintero, 'is_active' => true]), 'roles' => self::INTERNAL]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        User::create($data);

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        abort_unless($usuario->hasRole(...self::INTERNAL), 404);

        return view('admin.usuarios.form', ['usuario' => $usuario, 'roles' => self::INTERNAL]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        abort_unless($usuario->hasRole(...self::INTERNAL), 404);
        $data = $this->validated($request, $usuario);

        if ($usuario->is($request->user()) && (! $data['is_active'] || $data['role'] !== Role::Admin->value)) {
            return back()->withErrors(['role' => 'No podés quitarte el rol de administrador ni desactivarte.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $usuario->update($data);

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario actualizado.');
    }

    private function validated(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($usuario)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, self::INTERNAL))],
            'password' => [$usuario ? 'nullable' : 'required', Password::min(8)],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
