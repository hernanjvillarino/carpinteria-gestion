<?php

namespace App\Notifications;

use App\Models\WorkOrderUpdate;
use App\Notifications\Concerns\SendsAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Al dueño: un carpintero cargó un avance (cambio de etapa, fotos o comentario). */
class WorkOrderProgressed extends Notification implements ShouldQueue
{
    use Queueable, SendsAppNotification;

    public function __construct(public WorkOrderUpdate $update) {}

    public function via(object $notifiable): array
    {
        return $this->appChannels($notifiable);
    }

    protected function appMessage(object $notifiable): array
    {
        $update = $this->update->loadMissing(['workOrder', 'user', 'toStage'])->loadCount('photos');
        $order = $update->workOrder;

        $what = collect([
            $update->toStage ? "pasó a {$update->toStage->name}" : null,
            $update->photos_count ? "subió {$update->photos_count} foto(s)" : null,
            ! $update->toStage && ! $update->photos_count ? 'dejó un comentario' : null,
        ])->filter()->join(' y ');

        return [
            'title' => "{$order->code}: {$update->user->name} {$what}",
            'body' => $update->comment ? str($update->comment)->limit(120)->toString() : $order->title,
            'url' => route('admin.trabajos.show', $order),
        ];
    }
}
