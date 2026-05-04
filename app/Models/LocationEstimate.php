<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationEstimate extends Model
{
    protected $fillable = [
        'space_id',
        'room_id',
        'device_mac',
        'device_name',
        'matched_anchor_count',
        'signals',
        'estimate',
        'x',
        'y',
        'confidence',
        'is_outside',
        'min_distance',
        'window_since',
        'window_until',
        'estimated_at',
    ];

    protected $casts = [
        'signals' => 'array',
        'estimate' => 'array',
        'x' => 'float',
        'y' => 'float',
        'confidence' => 'float',
        'is_outside' => 'boolean',
        'min_distance' => 'float',
        'window_since' => 'datetime',
        'window_until' => 'datetime',
        'estimated_at' => 'datetime',
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
