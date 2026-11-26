<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente mínimo de Firebase Cloud Messaging (API HTTP v1).
 *
 * FCM entrega tanto a Android como a iPhone (Firebase reenvía a APNs con la
 * clave .p8 que se carga en la consola de Firebase), así que el servidor solo
 * habla con Google.
 */
class FcmClient
{
    public static function configured(): bool
    {
        return filled(config('services.fcm.project_id')) && filled(config('services.fcm.credentials'));
    }

    /**
     * Envía un aviso a un celular.
     *
     * @param  array{title: string, body: string, url: string}  $message
     */
    public function send(string $token, array $message): Response
    {
        $project = config('services.fcm.project_id');

        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $message['title'], 'body' => $message['body']],
                    'data' => ['url' => $message['url']],
                    'android' => ['priority' => 'high', 'notification' => ['channel_id' => 'avisos']],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                ],
            ]);
    }

    /** El token dejó de servir (app desinstalada, sesión vencida en el celular, etc.). */
    public static function tokenIsGone(Response $response): bool
    {
        $codes = collect($response->json('error.details', []))->pluck('errorCode');

        return $response->status() === 404 || $codes->contains('UNREGISTERED');
    }

    /** Token OAuth de la cuenta de servicio, cacheado casi una hora. */
    protected function accessToken(): string
    {
        return Cache::remember('fcm.access_token', now()->addMinutes(50), function () {
            $credentials = $this->credentials();
            $now = time();
            $jwt = $this->jwt([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], $credentials['private_key']);

            $response = Http::asForm()->timeout(10)->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw();

            return $response->json('access_token');
        });
    }

    /** @return array{client_email: string, private_key: string, token_uri?: string} */
    protected function credentials(): array
    {
        $path = config('services.fcm.credentials');
        if (! is_string($path) || ! is_readable($path)) {
            throw new RuntimeException('No se encuentra el archivo de credenciales de Firebase (FCM_CREDENTIALS).');
        }

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    protected function jwt(array $claims, string $privateKey): string
    {
        $encode = fn (string $data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $unsigned = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$encode(json_encode($claims));
        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el token para Firebase.');
        }

        return $unsigned.'.'.$encode($signature);
    }
}
