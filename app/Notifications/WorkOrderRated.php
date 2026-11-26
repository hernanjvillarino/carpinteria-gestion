<?php

namespace App\Notifications;

use App\Models\WorkOrderRating;
use App\Notifications\Concerns\SendsAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Al dueño: un cliente calificó un trabajo. */
class WorkOrderRated extends Notification implements ShouldQueue
{
    use Queueable, SendsAppNotification;

    public function __construct(public WorkOrderRating $rating) {}

    public function via(object $notifiable): array
    {
        return $this->appChannels($notifiable);
    }

    protected function appMessage(object $notifiable): array
    {
        $order = $this->rating->workOrder->loadMissing('client');

        return [
            'title' => "{$order->code}: {$order->client->name} calificó con {$this->rating->average()} de 5",
            'body' => $this->rating->comment ? str($this->rating->comment)->limit(120)->toString() : $order->title,
            'url' => route('admin.trabajos.show', $order).'#calificacion',
        ];
    }
}
