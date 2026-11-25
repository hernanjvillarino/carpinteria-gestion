<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Al cliente: el trabajo se entregó, ¿cómo nos fue? */
class RatingRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkOrder $order) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        if (config('webpush.vapid.public_key') && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("¿Cómo te fue con tu {$this->order->title}?")
            ->greeting("Hola {$notifiable->name}")
            ->line('Terminamos y entregamos tu pedido '.$this->order->code.'. ¡Gracias por elegirnos!')
            ->line('Nos ayuda mucho que califiques el trabajo, los materiales, la instalación, al personal y la atención. Si querés, podés sumar un comentario y fotos.')
            ->action('Calificar el trabajo', route('portal.trabajos.show', $this->order).'#calificar');
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('¿Cómo te fue con tu pedido?')
            ->body("Calificá {$this->order->title} en un minuto.")
            ->icon('/icons/icon-192.png')
            ->data(['url' => route('portal.trabajos.show', $this->order).'#calificar']);
    }
}
