<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tarea o indicación del dueño para un trabajo, que el carpintero marca como hecha. */
#[Fillable(['instruction_template_id', 'text', 'position', 'done_at', 'done_by', 'created_by'])]
class WorkOrderTask extends Model
{
    protected function casts(): array
    {
        return ['done_at' => 'datetime'];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }
}
