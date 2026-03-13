<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedRadiomap extends Model
{
    protected $fillable = [
        'room_id',
        'grid_step',
        'x_range_min',
        'x_range_max',
        'y_range_min',
        'y_range_max',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
