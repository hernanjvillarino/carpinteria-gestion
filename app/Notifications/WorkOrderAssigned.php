<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Notifications\Concerns\SendsAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Al carpintero: le asignaron un trabajo. */
class WorkOrderAssigned extends Notification implements ShouldQueue
{
    use Queueable, SendsAppNotification;

    public function __construct(public WorkOrder $order) {}

    public function via(object $notifiable): array
    {
        return [...$this->appChannels($notifiable), 'mail'];
    }

    protected function appMessage(object $notifiable): array
    {
        $tasks = $this->order->tasks()->count();

        return [
            'title' => "Nuevo trabajo asignado: {$this->order->code}",
            'body' => $this->order->title.($tasks ? " · {$tasks} indicación(es) del dueño" : ''),
            'url' => route('taller.trabajos.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Te asignaron el trabajo {$this->order->code}: {$this->order->title}")
            ->greeting("Hola {$notifiable->name}")
            ->line("Te asignaron el trabajo {$this->order->code}: {$this->order->title}.");
        if ($this->order->estimated_delivery) {
            $mail->line('Entrega estimada: '.$this->order->estimated_delivery->format('d/m/Y').'.');
        }
        foreach ($this->order->tasks as $task) {
            $mail->line('• '.$task->text);
        }

        return $mail->action('Ver el trabajo', route('taller.trabajos.show', $this->order));
    }
}
