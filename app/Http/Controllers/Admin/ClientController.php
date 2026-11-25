<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GatedCommunity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.clientes.index', [
            'clientes' => Client::query()->with('gatedCommunity')->withCount('workOrders')
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")))
                ->orderBy('name')->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.clientes.form', ['cliente' => new Client, 'barrios' => GatedCommunity::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = Client::create($this->validated($request));
        $password = $this->syncPortalAccess($request, $cliente);

        return redirect()->route('admin.clientes.show', $cliente)
            ->with('status', 'Cliente creado.'.($password ? " Contraseña inicial del portal: {$password}" : ''));
    }

    public function show(Client $cliente): View
    {
        $cliente->load(['gatedCommunity', 'user', 'workOrders' => fn ($q) => $q->with('stage')->latest()]);

        return view('admin.clientes.show', ['cliente' => $cliente]);
    }

    public function edit(Client $cliente): View
    {
        return view('admin.clientes.form', ['cliente' => $cliente, 'barrios' => GatedCommunity::orderBy('name')->get()]);
    }

    public function update(Request $request, Client $cliente): RedirectResponse
    {
        $cliente->update($this->validated($request, $cliente));
        $password = $this->syncPortalAccess($request, $cliente);

        return redirect()->route('admin.clientes.show', $cliente)
            ->with('status', 'Cliente actualizado.'.($password ? " Contraseña inicial del portal: {$password}" : ''));
    }

    public function destroy(Client $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente archivado.');
    }

    private function validated(Request $request, ?Client $cliente = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190', Rule::requiredIf($request->boolean('portal_access'))],
            'phone' => ['nullable', 'string', 'max:40'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'gated_community_id' => ['nullable', 'exists:gated_communities,id'],
            'lot' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * Crea el usuario del portal si se pidió y el cliente todavía no tiene uno.
     * Devuelve la contraseña generada para mostrarla una sola vez.
     */
    private function syncPortalAccess(Request $request, Client $cliente): ?string
    {
        if (! $request->boolean('portal_access') || $cliente->user_id) {
            return null;
        }

        $request->validate(['email' => [Rule::unique('users', 'email')]], [
            'email.unique' => 'Ya existe un usuario con ese email.',
        ]);

        $password = Str::password(10, symbols: false);
        $user = User::create([
            'name' => $cliente->name,
            'email' => $cliente->email,
            'phone' => $cliente->phone,
            'password' => $password,
            'role' => Role::Cliente,
        ]);
        $cliente->user()->associate($user)->save();

        return $password;
    }
}
