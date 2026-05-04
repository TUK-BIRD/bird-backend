<?php

namespace App\Services;

use App\Models\LocationEstimate;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

class LocationEstimateService
{
    public function estimateForRoom(
        Room $room,
        CarbonImmutable $since,
        CarbonImmutable $until,
        int $minimumAnchorMatches = 2,
        bool $persist = false,
        ?CarbonImmutable $estimatedAt = null
    ): array {
        $installedAnchorIds = $room->bleAnchors()
            ->whereNotNull('installed_at')
            ->pluck('id')
            ->map(fn (mixed $value) => (int) $value)
            ->values()
            ->all();

        $events = $room->bleScanEvents()
            ->whereIn('anchor_id', $installedAnchorIds)
            ->where('scanned_at', '>=', $since)
            ->where('scanned_at', '<=', $until)
            ->whereNotNull('anchor_id')
            ->whereNotNull('device_mac')
            ->orderBy('device_mac')
            ->orderBy('anchor_id')
            ->orderBy('scanned_at', 'desc')
            ->orderBy('id', 'desc')
            ->get(['id', 'anchor_id', 'device_mac', 'device_name', 'rssi_dbm', 'scanned_at']);

        $estimatedAt ??= CarbonImmutable::now();

        $devices = $events
            ->groupBy(fn ($event) => strtolower(trim((string) $event->device_mac)))
            ->map(function ($deviceEvents, $deviceMac) use ($room, $minimumAnchorMatches, $persist, $since, $until, $estimatedAt) {
                $signals = $deviceEvents
                    ->groupBy('anchor_id')
                    ->map(fn ($anchorEvents) => round((float) $anchorEvents->avg('rssi_dbm'), 1))
                    ->sortKeys();

                if ($signals->count() < $minimumAnchorMatches) {
                    return null;
                }

                $payload = [
                    'room_id' => (string) $room->id,
                    'signals' => $signals
                        ->mapWithKeys(fn ($rssi, $anchorId) => [(string) $anchorId => $rssi])
                        ->all(),
                ];

                $response = Http::timeout((float) config('services.location_estimator.timeout_seconds', 5))
                    ->post((string) config('services.location_estimator.url'), $payload);

                $response->throw();

                $estimate = $response->json();
                $device = [
                    'device_mac' => $deviceMac,
                    'device_name' => $deviceEvents->first()?->device_name,
                    'matched_anchors' => $signals->count(),
                    'signals' => $payload['signals'],
                    'latest_scanned_at' => optional($deviceEvents->first()?->scanned_at)->toIso8601String(),
                    'estimate' => $estimate,
                ];

                if ($persist) {
                    $device['location_estimate_id'] = $this->storeEstimate(
                        $room,
                        $device,
                        $since,
                        $until,
                        $estimatedAt
                    )->id;
                }

                return $device;
            })
            ->filter()
            ->values();

        return [
            'installed_anchor_ids' => $installedAnchorIds,
            'devices' => $devices,
        ];
    }

    private function storeEstimate(
        Room $room,
        array $device,
        CarbonImmutable $since,
        CarbonImmutable $until,
        CarbonImmutable $estimatedAt
    ): LocationEstimate {
        $estimate = $device['estimate'] ?? [];

        return LocationEstimate::create([
            'space_id' => $room->space_id,
            'room_id' => $room->id,
            'device_mac' => $device['device_mac'],
            'device_name' => $device['device_name'],
            'matched_anchor_count' => $device['matched_anchors'],
            'signals' => $device['signals'],
            'estimate' => $estimate,
            'x' => $this->nullableFloat($estimate, 'x'),
            'y' => $this->nullableFloat($estimate, 'y'),
            'confidence' => $this->nullableFloat($estimate, 'confidence'),
            'is_outside' => array_key_exists('is_outside', $estimate) ? (bool) $estimate['is_outside'] : null,
            'min_distance' => $this->nullableFloat($estimate, 'min_distance'),
            'window_since' => $since,
            'window_until' => $until,
            'estimated_at' => $estimatedAt,
        ]);
    }

    private function nullableFloat(array $values, string $key): ?float
    {
        return array_key_exists($key, $values) && $values[$key] !== null
            ? (float) $values[$key]
            : null;
    }
}
