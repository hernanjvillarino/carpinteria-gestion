<?php

namespace App\Notifications\Channels;

use App\Services\FcmClient;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/** Canal de avisos push para la app nativa: un mensaje por cada celular del usuario. */
class FcmChannel
{
    public function __construct(protected FcmClient $client) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toFcm($notifiable);

        foreach ($notifiable->deviceTokens as $device) {
            $response = $this->client->send($device->token, $message);

            if ($response->successful()) {
                $device->forceFill(['last_used_at' => now()])->save();
            } elseif (FcmClient::tokenIsGone($response)) {
                $device->delete();
            } else {
                Log::warning('FCM no pudo entregar un aviso', ['status' => $response->status(), 'body' => $response->json()]);
            }
        }
    }
}
