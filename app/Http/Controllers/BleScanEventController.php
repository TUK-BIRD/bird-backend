<?php

namespace App\Http\Controllers;

use App\Models\BleAnchor;
use App\Models\Room;
use App\Models\Space;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BleScanEventController extends Controller
{
    public function dashboard(Request $request, Space $space, Room $room): JsonResponse
    {
        abort_unless(
            $request->user()->spaces()->where('spaces.id', $space->id)->exists(),
            403
        );
        abort_unless($room->space_id === $space->id, 404);

        $validated = $request->validate([
            'since' => 'nullable|date',
            'until' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $since = isset($validated['since'])
            ? CarbonImmutable::parse($validated['since'])
            : CarbonImmutable::now()->subHour();

        $until = isset($validated['until'])
            ? CarbonImmutable::parse($validated['until'])
            : CarbonImmutable::now();

        if ($since->gt($until)) {
            [$since, $until] = [$until, $since];
        }

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 10;

        $currentWindowQuery = $this->buildWindowQuery($room, $since, $until);

        $latestEvents = (clone $currentWindowQuery)
            ->with('anchor')
            ->orderBy('scanned_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        $currentStats = $this->buildDashboardStats($since, $until, clone $currentWindowQuery);
        $healthKpis = $this->buildHealthKpis($room);

        $previousWeekSince = $since->subWeek();
        $previousWeekUntil = $until->subWeek();
        $previousWeekStats = $this->buildDashboardStats(
            $previousWeekSince,
            $previousWeekUntil,
            $this->buildWindowQuery($room, $previousWeekSince, $previousWeekUntil)
        );

        return response()->json([
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
            ],
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
            ],
            'timespan' => [
                'since' => $since->toIso8601String(),
                'until' => $until->toIso8601String(),
                'limit' => $limit,
            ],
            'stats' => array_merge($currentStats, [
                'latest_event_scanned_at' => optional($latestEvents->first()?->scanned_at)->toIso8601String(),
            ]),
            'comparison' => [
                'previous_week' => [
                    'timespan' => [
                        'since' => $previousWeekSince->toIso8601String(),
                        'until' => $previousWeekUntil->toIso8601String(),
                    ],
                    'stats' => $previousWeekStats,
                    'delta' => [
                        'total_events' => $currentStats['total_events'] - $previousWeekStats['total_events'],
                        'unique_devices' => $currentStats['unique_devices'] - $previousWeekStats['unique_devices'],
                        'average_rssi' => $this->calculateDelta(
                            $currentStats['average_rssi'],
                            $previousWeekStats['average_rssi']
                        ),
                    ],
                ],
            ],
            'health_kpis' => $healthKpis,
            'events' => $latestEvents->map(fn ($event) => [
                'id' => $event->id,
                'device_mac' => $event->device_mac,
                'device_name' => $event->device_name,
                'rssi_dbm' => $event->rssi_dbm,
                'scanned_at' => optional($event->scanned_at)->toIso8601String(),
                'received_at' => optional($event->received_at)->toIso8601String(),
                'anchor' => $event->anchor ? [
                    'id' => $event->anchor->id,
                    'anchor_uid' => $event->anchor->anchor_uid,
                    'label' => $event->anchor->label,
                ] : null,
            ]),
        ]);
    }

    /**
     * Dashboard endpoint which only counts a device when it is scanned by 2+ distinct anchors
     * within a short time window (default: 3 minutes).
     */
    public function multiAnchorDashboard(Request $request, Space $space, Room $room): JsonResponse
    {
        abort_unless(
            $request->user()->spaces()->where('spaces.id', $space->id)->exists(),
            403
        );
        abort_unless($room->space_id === $space->id, 404);

        $validated = $request->validate([
            'since' => 'nullable|date',
            'until' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:200',
            'window_seconds' => 'nullable|integer|min:30|max:1800',
        ]);

        $since = isset($validated['since'])
            ? CarbonImmutable::parse($validated['since'])
            : CarbonImmutable::now()->subHour();

        $until = isset($validated['until'])
            ? CarbonImmutable::parse($validated['until'])
            : CarbonImmutable::now();

        if ($since->gt($until)) {
            [$since, $until] = [$until, $since];
        }

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 50;
        $windowSeconds = isset($validated['window_seconds']) ? (int) $validated['window_seconds'] : 180;

        $events = $room->bleScanEvents()
            ->where('scanned_at', '>=', $since)
            ->where('scanned_at', '<=', $until)
            ->whereNotNull('anchor_id')
            ->orderBy('device_mac')
            ->orderBy('scanned_at')
            ->orderBy('id')
            ->get(['id', 'device_mac', 'anchor_id', 'rssi_dbm', 'scanned_at']);

        $qualifiedByHour = []; // bucket => [device_mac => true]
        $deviceSummaries = []; // device_mac => summary

        foreach ($events->groupBy('device_mac') as $deviceMac => $deviceEvents) {
            if ($deviceMac === null || trim((string) $deviceMac) === '') {
                continue;
            }

            $deviceEvents = $deviceEvents->values();
            $start = 0;
            $anchorCounts = [];
            $qualifiedHours = [];

            for ($i = 0; $i < $deviceEvents->count(); $i++) {
                $current = $deviceEvents[$i];
                $currentAt = CarbonImmutable::parse($current->scanned_at);

                // Add current anchor into the sliding window.
                $anchorId = (int) $current->anchor_id;
                $anchorCounts[$anchorId] = ($anchorCounts[$anchorId] ?? 0) + 1;

                // Move window start so that (current - start) <= windowSeconds.
                while ($start <= $i) {
                    $startEvent = $deviceEvents[$start];
                    $startAt = CarbonImmutable::parse($startEvent->scanned_at);
                    if ($currentAt->diffInSeconds($startAt, false) <= $windowSeconds) {
                        break;
                    }

                    $startAnchor = (int) $startEvent->anchor_id;
                    $anchorCounts[$startAnchor] = ($anchorCounts[$startAnchor] ?? 1) - 1;
                    if (($anchorCounts[$startAnchor] ?? 0) <= 0) {
                        unset($anchorCounts[$startAnchor]);
                    }
                    $start++;
                }

                // Qualify when 2+ distinct anchors are present in the current window.
                if (count($anchorCounts) < 2) {
                    continue;
                }

                $bucket = $currentAt
                    ->setMinute(0)
                    ->setSecond(0)
                    ->setMicrosecond(0)
                    ->toIso8601String();

                // Count each device at most once per hour bucket.
                if (isset($qualifiedHours[$bucket])) {
                    continue;
                }

                $qualifiedHours[$bucket] = true;
                $qualifiedByHour[$bucket] ??= [];
                $qualifiedByHour[$bucket][$deviceMac] = true;

                $summary = $deviceSummaries[$deviceMac] ?? [
                    'device_mac' => $deviceMac,
                    'first_qualified_at' => null,
                    'last_qualified_at' => null,
                    'qualified_hour_count' => 0,
                    'anchor_ids' => [],
                ];

                $summary['qualified_hour_count']++;
                $summary['first_qualified_at'] ??= $currentAt->toIso8601String();
                $summary['last_qualified_at'] = $currentAt->toIso8601String();
                foreach (array_keys($anchorCounts) as $aid) {
                    $summary['anchor_ids'][(string) $aid] = true;
                }

                $deviceSummaries[$deviceMac] = $summary;
            }
        }

        ksort($qualifiedByHour);

        $timeSeries = [];
        foreach ($qualifiedByHour as $bucket => $devicesInBucket) {
            $timeSeries[] = [
                'bucket' => $bucket,
                'event_count' => count($devicesInBucket), // 1 per device_mac per hour when qualified
                'unique_device_count' => count($devicesInBucket),
            ];
        }

        $qualifiedTotalDevices = count($deviceSummaries);

        $devices = array_values($deviceSummaries);
        usort($devices, static function (array $a, array $b) {
            return strcmp((string) ($b['last_qualified_at'] ?? ''), (string) ($a['last_qualified_at'] ?? ''));
        });
        $devices = array_slice($devices, 0, $limit);
        $devices = array_map(static function (array $summary) {
            $summary['anchor_ids'] = array_map('intval', array_keys($summary['anchor_ids'] ?? []));
            sort($summary['anchor_ids']);

            return $summary;
        }, $devices);

        return response()->json([
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
            ],
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
            ],
            'timespan' => [
                'since' => $since->toIso8601String(),
                'until' => $until->toIso8601String(),
                'limit' => $limit,
                'window_seconds' => $windowSeconds,
            ],
            'stats' => [
                'qualified_total_devices' => $qualifiedTotalDevices,
                'time_series' => $timeSeries,
            ],
            'devices' => $devices,
        ]);
    }

    private function buildWindowQuery(Room $room, CarbonImmutable $since, CarbonImmutable $until)
    {
        return $room->bleScanEvents()
            ->where('scanned_at', '>=', $since)
            ->where('scanned_at', '<=', $until);
    }

    private function buildDashboardStats(CarbonImmutable $since, CarbonImmutable $until, $windowQuery): array
    {
        $totalEvents = (clone $windowQuery)->count();
        $uniqueDevices = (clone $windowQuery)->distinct('device_mac')->count('device_mac');
        $averageRssi = (clone $windowQuery)->avg('rssi_dbm');

        $anchorBreakdown = (clone $windowQuery)
            ->whereNotNull('anchor_id')
            ->selectRaw('anchor_id, count(*) as event_count, avg(rssi_dbm) as avg_rssi, max(scanned_at) as last_scanned_at')
            ->groupBy('anchor_id')
            ->orderByDesc('event_count')
            ->limit(10)
            ->get();

        $anchorIds = $anchorBreakdown->pluck('anchor_id')->filter()->values()->all();
        $anchorMap = BleAnchor::whereIn('id', $anchorIds)->get()->keyBy('id');

        $anchorStats = $anchorBreakdown->map(function ($row) use ($anchorMap) {
            $anchor = $anchorMap->get($row->anchor_id);

            return [
                'anchor_id' => $row->anchor_id,
                'anchor_uid' => $anchor?->anchor_uid,
                'label' => $anchor?->label,
                'event_count' => (int) $row->event_count,
                'average_rssi' => $row->avg_rssi !== null ? round((float) $row->avg_rssi, 1) : null,
                'last_scanned_at' => $row->last_scanned_at
                    ? CarbonImmutable::parse($row->last_scanned_at)->toIso8601String()
                    : null,
            ];
        })->values();

        $timeSeries = (clone $windowQuery)
            ->orderBy('scanned_at')
            ->get(['scanned_at', 'rssi_dbm', 'device_mac'])
            ->filter(fn ($event) => $event->scanned_at !== null)
            ->groupBy(function ($event) {
                $slot = CarbonImmutable::parse($event->scanned_at)
                    ->setMinute(0)
                    ->setSecond(0)
                    ->setMicrosecond(0);

                return $slot->toIso8601String();
            })
            ->map(function ($group, $bucket) {
                $uniqueDevices = $group->pluck('device_mac')->filter()->values()->unique();
                $average = $group->avg('rssi_dbm');

                return [
                    'bucket' => $bucket,
                    'event_count' => $uniqueDevices->count(),
                    'unique_device_count' => $uniqueDevices->count(),
                    'average_rssi' => $average !== null ? round((float) $average, 1) : null,
                ];
            })
            ->values();

        return [
            'total_events' => $totalEvents,
            'unique_devices' => $uniqueDevices,
            'average_rssi' => $averageRssi !== null ? round((float) $averageRssi, 1) : null,
            'anchor_breakdown' => $anchorStats,
            'time_series' => $timeSeries,
            'window_minutes' => $until->diffInMinutes($since),
        ];
    }

    private function calculateDelta(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return round($current - $previous, 1);
    }

    private function buildHealthKpis(Room $room): array
    {
        $anchors = $room->bleAnchors()
            ->whereNotNull('installed_at')
            ->get();

        $total = $anchors->count();
        $states = $anchors
            ->map(fn (BleAnchor $anchor) => $anchor->health_state)
            ->countBy();

        $online = (int) ($states->get('online') ?? 0);
        $degraded = (int) ($states->get('degraded') ?? 0);
        $offline = (int) ($states->get('offline') ?? 0);
        $unknown = (int) ($states->get('unknown') ?? 0);
        $reachable = $online + $degraded;

        return [
            'total_anchors' => $total,
            'online_anchors' => $online,
            'degraded_anchors' => $degraded,
            'offline_anchors' => $offline,
            'unknown_anchors' => $unknown,
            'healthy_rate_percent' => $this->percentage($online, $total),
            'reachable_rate_percent' => $this->percentage($reachable, $total),
        ];
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }
}
