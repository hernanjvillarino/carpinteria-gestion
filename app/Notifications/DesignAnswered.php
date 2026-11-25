<?php

namespace App\Notifications;

use App\Enums\DesignStatus;
use App\Models\Design;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email a los administradores cuando el cliente aprueba o pide cambios. */
class DesignAnswered extends Notification implements ShouldQueue
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
        $approved = $this->design->status === DesignStatus::Aprobado;

        $mail = (new MailMessage)
            ->subject(($approved ? 'Diseño aprobado' : 'Piden cambios en el diseño').": {$order->code} {$order->title}")
            ->line("{$order->client->name} ".($approved ? 'aprobó' : 'pidió cambios en')." la versión {$this->design->version} del diseño.");

        if (! $approved && $this->design->client_feedback) {
            $mail->line('Comentario: '.$this->design->client_feedback);
        }

        return $mail->action('Abrir el trabajo', route('admin.trabajos.show', $order));
    }
}
