<?php

namespace App\Notifications;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAnswered extends Notification implements ShouldQueue
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
        $accepted = $this->quote->status === QuoteStatus::Aceptado;

        $mail = (new MailMessage)
            ->subject(($accepted ? 'Presupuesto aceptado' : 'Presupuesto rechazado').": {$order->code} {$order->title}")
            ->line("{$order->client->name} ".($accepted ? 'aceptó' : 'rechazó')." el presupuesto v{$this->quote->version}.");
        if ($this->quote->client_feedback) {
            $mail->line('Comentario: '.$this->quote->client_feedback);
        }

        return $mail->action('Abrir el trabajo', route('admin.trabajos.show', $order));
    }
}
