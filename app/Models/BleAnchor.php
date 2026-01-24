<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BleAnchor extends Model
{
    protected $table = 'ble_anchors';

    protected $fillable = [
        'anchor_uid',
        'room_id',
        'label',
        'tx_power_dbm',
        'installed_at'
    ];

    protected $casts = [
        'installed_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function radiomapMeasurements(): HasMany
    {
        return $this->hasMany(RadiomapMeasurement::class, 'anchor_id');
    }
}
