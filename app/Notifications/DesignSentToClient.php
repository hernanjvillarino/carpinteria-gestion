<?php

namespace App\Notifications;

use App\Models\Design;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email al cliente con una versión nueva del diseño para aprobar. */
class DesignSentToClient extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Design $design) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->design->workOrder;

        return (new MailMessage)
            ->subject("Diseño para revisar: {$order->title}")
            ->greeting("Hola {$notifiable->name}:")
            ->line("Te enviamos la versión {$this->design->version} del diseño de «{$order->title}».")
            ->line('Podés aprobarlo o pedirnos cambios desde tu cuenta.')
            ->action('Ver el diseño', route('portal.trabajos.show', $order))
            ->salutation('Saludos, '.config('app.name'));
    }
}
