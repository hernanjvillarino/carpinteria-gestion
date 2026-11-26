<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Alta y baja del celular (app nativa) para recibir avisos por Firebase Cloud Messaging. */
class DeviceTokenController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'in:android,ios'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        // Si en el mismo celular entra otra persona, el token pasa a la cuenta nueva.
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'], 'device_name' => $data['device_name'] ?? null],
        );

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $request->validate(['token' => ['required', 'string']]);
        $request->user()->deviceTokens()->where('token', $request->input('token'))->delete();

        return response()->noContent();
    }
}
