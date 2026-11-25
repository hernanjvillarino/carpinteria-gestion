<?php

namespace App\Notifications;

use App\Models\Stage;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email al cliente cuando su pedido pasa a una etapa visible. */
class WorkOrderStageChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkOrder $order, public Stage $stage) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu pedido {$this->order->code} pasó a «{$this->stage->name}»")
            ->greeting("Hola {$notifiable->name}:")
            ->line("Tu pedido «{$this->order->title}» avanzó a la etapa «{$this->stage->name}».")
            ->action('Ver el avance', route('portal.trabajos.show', $this->order))
            ->salutation('Saludos, '.config('app.name'));
    }
}
