<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSentToClient extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quote $quote) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->quote->workOrder;

        return (new MailMessage)
            ->subject("Presupuesto para «{$order->title}»")
            ->greeting("Hola {$notifiable->name}:")
            ->line("Te enviamos el presupuesto de «{$order->title}» por ".money($this->quote->total).' (IVA incluido).')
            ->when($this->quote->valid_until, fn ($m) => $m->line('Es válido hasta el '.$this->quote->valid_until->format('d/m/Y').'.'))
            ->action('Ver y responder el presupuesto', route('portal.trabajos.show', $order))
            ->salutation('Saludos, '.config('app.name'));
    }
}
