<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['path'])]
class WorkOrderRatingPhoto extends Model
{
    public function rating(): BelongsTo
    {
        return $this->belongsTo(WorkOrderRating::class, 'work_order_rating_id');
    }
}
