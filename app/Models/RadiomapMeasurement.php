<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiomapMeasurement extends Model
{
    protected $table = 'radiomap_measurements';

    protected $fillable = [
        'radiomap_session_id',
        'reference_point_id',
        'anchor_id',
        'rssi_dbm',
        'measured_at',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RadiomapSession::class, 'radiomap_session_id');
    }

    public function referencePoint(): BelongsTo
    {
        return $this->belongsTo(ReferencePoint::class, 'reference_point_id');
    }

    public function anchor(): BelongsTo
    {
        return $this->belongsTo(BleAnchor::class, 'anchor_id');
    }
}
