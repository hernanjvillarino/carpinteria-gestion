<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['path', 'caption', 'visible_to_client'])]
class WorkOrderPhoto extends Model
{
    protected function casts(): array
    {
        return ['visible_to_client' => 'boolean'];
    }

    public function workOrderUpdate(): BelongsTo
    {
        return $this->belongsTo(WorkOrderUpdate::class, 'work_order_update_id');
    }
}
