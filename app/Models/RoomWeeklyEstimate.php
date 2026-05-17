<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomWeeklyEstimate extends Model
{
    protected $table = 'room_weekly_estimates';

    protected $fillable = [
        'space_id',
        'room_id',
        'day_of_week',
        'time',
        'estimated_device_count',
        'avg_device_count',
        'max_device_count',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'estimated_device_count' => 'integer',
        'avg_device_count' => 'float',
        'max_device_count' => 'integer',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}