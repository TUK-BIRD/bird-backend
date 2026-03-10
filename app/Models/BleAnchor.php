<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BleAnchor extends Model
{
    use HasFactory;

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

    public function setAnchorUidAttribute(?string $value): void
    {
        $this->attributes['anchor_uid'] = $value !== null ? strtolower($value) : null;
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function radiomapMeasurements(): HasMany
    {
        return $this->hasMany(RadiomapMeasurement::class, 'anchor_id');
    }

    public function bleScanEvents(): HasMany
    {
        return $this->hasMany(BleScanEvent::class, 'anchor_id');
    }
}
