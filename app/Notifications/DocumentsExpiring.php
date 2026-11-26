<?php

namespace App\Notifications;

use App\Models\EmployeeDocument;
use App\Notifications\Concerns\SendsAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** Al dueño: documentos del personal que vencen pronto o ya vencieron. */
class DocumentsExpiring extends Notification implements ShouldQueue
{
    use Queueable, SendsAppNotification;

    /** @param  Collection<int, EmployeeDocument>  $documents */
    public function __construct(public Collection $documents) {}

    public function via(object $notifiable): array
    {
        return [...$this->appChannels($notifiable), 'mail'];
    }

    protected function appMessage(object $notifiable): array
    {
        $count = $this->documents->count();
        $first = $this->documents->first();

        return [
            'title' => $count === 1 ? 'Documento del personal por vencer' : "{$count} documentos del personal por vencer",
            'body' => $count === 1 ? $this->line($first) : $this->documents->take(3)->map(fn ($d) => $d->employee->name.': '.$d->type->name)->join(' · ').($count > 3 ? ' y más' : ''),
            'url' => $count === 1 ? route('admin.personal.show', $first->employee_id) : route('admin.personal.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Documentación del personal: vencimientos')
            ->greeting("Hola {$notifiable->name}")
            ->line('Estos documentos que piden los barrios vencen pronto o ya vencieron:');
        foreach ($this->documents as $document) {
            $mail->line('• '.$this->line($document));
        }

        return $mail->action('Ver el personal', route('admin.personal.index'));
    }

    private function line(EmployeeDocument $document): string
    {
        $date = $document->expires_on->format('d/m/Y');
        $when = $document->expires_on->isPast() && ! $document->expires_on->isToday() ? "venció el {$date}" : "vence el {$date}";

        return "{$document->employee->name}: {$document->type->displayName()} {$when}";
    }
}
