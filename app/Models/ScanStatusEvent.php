<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanStatusEvent extends Model
{
    protected $fillable = [
        'room_id',
        'reported_state',
        'request_id',
        'ok',
        'reported_at',
    ];

    protected $casts = [
        'ok' => 'boolean',
        'reported_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
