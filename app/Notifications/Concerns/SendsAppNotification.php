<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\FcmChannel;
use App\Services\FcmClient;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Avisos internos: quedan en la campanita del sistema y, si el usuario activó
 * las notificaciones en el celular o la compu, también llegan como push
 * (Web Push en el navegador, FCM en la app nativa de Android / iPhone).
 *
 * Cada notificación define appMessage(): ['title' => ..., 'body' => ..., 'url' => ...].
 */
trait SendsAppNotification
{
    /** @return array{title: string, body: string, url: string} */
    abstract protected function appMessage(object $notifiable): array;

    protected function appChannels(object $notifiable): array
    {
        $channels = ['database'];
        if (config('webpush.vapid.public_key') && method_exists($notifiable, 'pushSubscriptions') && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }
        if (FcmClient::configured() && method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->exists()) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return $this->appMessage($notifiable);
    }

    /** @return array{title: string, body: string, url: string} */
    public function toFcm(object $notifiable): array
    {
        return [...$this->appMessage($notifiable), 'url' => route('avisos.open', $this->id)];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $message = $this->appMessage($notifiable);

        return (new WebPushMessage)
            ->title($message['title'])
            ->body($message['body'])
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-72.png')
            ->tag(static::class.':'.$notification->id)
            ->data(['url' => route('avisos.open', $notification->id)]);
    }
}
