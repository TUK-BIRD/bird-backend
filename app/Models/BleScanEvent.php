<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BleScanEvent extends Model
{
    use HasFactory;

    protected $table = 'ble_scan_events';

    protected $fillable = [
        'anchor_id',
        'room_id',
        'device_mac',
        'device_name',
        'rssi_dbm',
        'scanned_at',
        'received_at',
        'raw_payload',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'received_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function anchor(): BelongsTo
    {
        return $this->belongsTo(BleAnchor::class, 'anchor_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
