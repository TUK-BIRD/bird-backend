<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiomapSession extends Model
{
    protected $table = 'radiomap_sessions';

    protected $fillable = [
        'room_id',
        'started_at',
        'ended_at',
        'note'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(RadiomapMeasurement::class, 'radiomap_session_id');
    }
}
