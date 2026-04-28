<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BleAnchor extends Model
{
    use HasFactory;

    protected $table = 'ble_anchors';

    protected $fillable = [
        'anchor_uid',
        'room_id',
        'label',
        'tx_power_dbm',
        'installed_at',
        'health_status',
        'health_last_payload_at',
        'health_last_topic',
        'health_uptime_sec',
        'health_free_heap',
        'health_min_free_heap',
        'health_wifi_connected',
        'health_mqtt_connected',
        'health_scan_enabled',
        'health_raw_payload',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'health_last_payload_at' => 'datetime',
        'health_wifi_connected' => 'boolean',
        'health_mqtt_connected' => 'boolean',
        'health_scan_enabled' => 'boolean',
        'health_raw_payload' => 'array',
    ];

    protected $appends = [
        'health_state',
        'health_is_stale',
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

    public function getHealthStateAttribute(): string
    {
        $status = $this->health_status;

        if ($status === null || $this->health_last_payload_at === null) {
            return 'unknown';
        }

        if ($status === 'offline' || $this->health_is_stale) {
            return 'offline';
        }

        if ($status !== 'online') {
            return 'unknown';
        }

        if ($this->health_wifi_connected === false || $this->health_scan_enabled === false) {
            return 'degraded';
        }

        $threshold = config('mqtt_topics.anchor_health_min_free_heap_threshold');
        if (is_numeric($threshold) && $this->health_min_free_heap !== null && $this->health_min_free_heap <= (int) $threshold) {
            return 'degraded';
        }

        return 'online';
    }

    public function getHealthIsStaleAttribute(): bool
    {
        if (! $this->health_last_payload_at instanceof Carbon) {
            return false;
        }

        $timeoutSeconds = max(
            1,
            (int) config('mqtt_topics.anchor_health_online_timeout_seconds', 150)
        );

        return $this->health_last_payload_at->lt(now()->subSeconds($timeoutSeconds));
    }
}
