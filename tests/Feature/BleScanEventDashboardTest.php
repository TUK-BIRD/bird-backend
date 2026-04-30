<?php

use App\Enums\UserSpaceRole;
use App\Models\BleAnchor;
use App\Models\BleScanEvent;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns scan summary for the requested room', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);
    $room->generatedRadiomap()->create([
        'grid_step' => 0.1,
        'x_range_min' => 0.0,
        'x_range_max' => 9.5,
        'y_range_min' => 0.0,
        'y_range_max' => 7.2,
        'data' => ['test' => true],
    ]);

    $anchorA = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'anchor-a',
        'label' => 'Anchor A',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now()->subSeconds(10),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => true,
    ]);

    $anchorB = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'anchor-b',
        'label' => 'Anchor B',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now()->subSeconds(20),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => false,
    ]);

    $anchorC = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'anchor-c',
        'label' => 'Anchor C',
        'installed_at' => now(),
        'health_status' => 'offline',
        'health_last_payload_at' => now()->subSeconds(40),
        'health_wifi_connected' => false,
        'health_mqtt_connected' => false,
        'health_scan_enabled' => false,
    ]);

    $windowStart = CarbonImmutable::parse('2026-04-13T08:00:00+00:00');
    $windowEnd = CarbonImmutable::parse('2026-04-13T09:00:00+00:00');

    $events = [
        [
            'anchor' => $anchorA,
            'device_mac' => 'aa:aa:aa:aa:aa:01',
            'device_name' => 'Device A1',
            'rssi_dbm' => -50,
            'scanned_at' => CarbonImmutable::parse('2026-04-13T08:35:00+00:00'),
        ],
        [
            'anchor' => $anchorA,
            'device_mac' => 'aa:aa:aa:aa:aa:02',
            'device_name' => 'Device A2',
            'rssi_dbm' => -60,
            'scanned_at' => CarbonImmutable::parse('2026-04-13T08:40:00+00:00'),
        ],
        [
            'anchor' => $anchorA,
            'device_mac' => 'aa:aa:aa:aa:aa:03',
            'device_name' => 'Device A3',
            'rssi_dbm' => -70,
            'scanned_at' => CarbonImmutable::parse('2026-04-13T08:55:00+00:00'),
        ],
        [
            'anchor' => $anchorB,
            'device_mac' => 'bb:bb:bb:bb:bb:01',
            'device_name' => 'Device B1',
            'rssi_dbm' => -60,
            'scanned_at' => CarbonImmutable::parse('2026-04-13T08:50:00+00:00'),
        ],
        [
            'anchor' => $anchorA,
            'device_mac' => 'aa:aa:aa:aa:aa:01',
            'device_name' => 'Device A1',
            'rssi_dbm' => -52,
            'scanned_at' => CarbonImmutable::parse('2026-04-13T08:45:00+00:00'),
        ],
    ];

    $previousWeekEvents = [
        [
            'anchor' => $anchorA,
            'device_mac' => 'cc:cc:cc:cc:cc:01',
            'device_name' => 'Prev Device 1',
            'rssi_dbm' => -55,
            'scanned_at' => CarbonImmutable::parse('2026-04-06T08:10:00+00:00'),
        ],
        [
            'anchor' => $anchorB,
            'device_mac' => 'cc:cc:cc:cc:cc:02',
            'device_name' => 'Prev Device 2',
            'rssi_dbm' => -65,
            'scanned_at' => CarbonImmutable::parse('2026-04-06T08:20:00+00:00'),
        ],
        [
            'anchor' => $anchorB,
            'device_mac' => 'cc:cc:cc:cc:cc:02',
            'device_name' => 'Prev Device 2',
            'rssi_dbm' => -75,
            'scanned_at' => CarbonImmutable::parse('2026-04-06T08:25:00+00:00'),
        ],
    ];

    foreach (array_merge($events, $previousWeekEvents) as $event) {
        BleScanEvent::create([
            'anchor_id' => $event['anchor']->id,
            'room_id' => $room->id,
            'device_mac' => $event['device_mac'],
            'device_name' => $event['device_name'],
            'rssi_dbm' => $event['rssi_dbm'],
            'scanned_at' => $event['scanned_at'],
            'received_at' => $event['scanned_at']->addSeconds(2),
            'raw_payload' => ['test' => true],
        ]);
    }

    $otherRoom = $space->rooms()->create(['name' => 'Room 2']);
    BleScanEvent::create([
        'anchor_id' => $anchorA->id,
        'room_id' => $otherRoom->id,
        'device_mac' => 'aa:aa:aa:aa:aa:99',
        'device_name' => 'Other Device',
        'rssi_dbm' => -30,
        'scanned_at' => CarbonImmutable::parse('2026-04-13T08:10:00+00:00'),
        'received_at' => CarbonImmutable::parse('2026-04-13T08:11:00+00:00'),
        'raw_payload' => ['test' => 'other-room'],
    ]);

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => $windowStart->toIso8601String(),
        'until' => $windowEnd->toIso8601String(),
        'limit' => 10,
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard?{$query}");

    $response->assertOk();
    $response->assertJsonPath('room.id', $room->id);
    $response->assertJsonPath('timespan.limit', 10);
    $response->assertJsonPath('timespan.since', $windowStart->toIso8601String());
    $response->assertJsonPath('timespan.until', $windowEnd->toIso8601String());
    $response->assertJsonPath('stats.totalEvents', 5);
    $response->assertJsonPath('stats.uniqueDevices', 4);
    $response->assertJsonPath('stats.averageRssi', -58.4);
    $response->assertJsonCount(2, 'stats.anchorBreakdown');
    $response->assertJsonPath('stats.anchorBreakdown.0.anchorId', $anchorA->id);
    $response->assertJsonPath('stats.anchorBreakdown.0.eventCount', 4);
    $response->assertJsonPath('stats.anchorBreakdown.0.averageRssi', -58);
    $response->assertJsonPath('stats.anchorBreakdown.1.anchorId', $anchorB->id);
    $response->assertJsonCount(1, 'stats.timeSeries');
    $response->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-13T08:00:00+09:00');
    $response->assertJsonPath('stats.timeSeries.0.eventCount', 1);
    $response->assertJsonPath('stats.timeSeries.0.uniqueDeviceCount', 1);
    $response->assertJsonPath('stats.timeSeries.0.averageRssi', -51);
    $response->assertJsonPath('events.0.deviceMac', 'aa:aa:aa:aa:aa:03');
    $response->assertJsonPath('stats.latestEventScannedAt', '2026-04-13T08:55:00+09:00');
    $response->assertJsonPath('comparison.previousWeek.timespan.since', '2026-04-06T08:00:00+00:00');
    $response->assertJsonPath('comparison.previousWeek.timespan.until', '2026-04-06T09:00:00+00:00');
    $response->assertJsonPath('comparison.previousWeek.stats.totalEvents', 3);
    $response->assertJsonPath('comparison.previousWeek.stats.uniqueDevices', 2);
    $response->assertJsonPath('comparison.previousWeek.stats.averageRssi', -65);
    $response->assertJsonCount(1, 'comparison.previousWeek.stats.timeSeries');
    $response->assertJsonPath('comparison.previousWeek.stats.timeSeries.0.bucket', '2026-04-06T08:00:00+09:00');
    $response->assertJsonPath('comparison.previousWeek.stats.timeSeries.0.eventCount', 1);
    $response->assertJsonPath('comparison.previousWeek.stats.timeSeries.0.uniqueDeviceCount', 1);
    $response->assertJsonPath('comparison.previousWeek.stats.timeSeries.0.averageRssi', -70);
    $response->assertJsonPath('comparison.previousWeek.delta.totalEvents', 2);
    $response->assertJsonPath('comparison.previousWeek.delta.uniqueDevices', 2);
    $response->assertJsonPath('comparison.previousWeek.delta.averageRssi', 6.6);
    $response->assertJsonPath('healthKpis.totalAnchors', 3);
    $response->assertJsonPath('healthKpis.onlineAnchors', 1);
    $response->assertJsonPath('healthKpis.degradedAnchors', 1);
    $response->assertJsonPath('healthKpis.offlineAnchors', 1);
    $response->assertJsonPath('healthKpis.unknownAnchors', 0);
    $response->assertJsonPath('healthKpis.healthyRatePercent', 33.3);
    $response->assertJsonPath('healthKpis.reachableRatePercent', 66.7);
});

it('compares the exact same minute window from the previous week', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);

    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'anchor-a',
        'label' => 'Anchor A',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now(),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => true,
    ]);

    BleScanEvent::create([
        'anchor_id' => $anchor->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Current Window Device',
        'rssi_dbm' => -50,
        'scanned_at' => CarbonImmutable::parse('2026-04-28T05:07:00+00:00'),
        'received_at' => CarbonImmutable::parse('2026-04-28T05:07:02+00:00'),
        'raw_payload' => ['test' => true],
    ]);

    BleScanEvent::create([
        'anchor_id' => $anchor->id,
        'room_id' => $room->id,
        'device_mac' => 'bb:bb:bb:bb:bb:01',
        'device_name' => 'Previous Week Window Device',
        'rssi_dbm' => -60,
        'scanned_at' => CarbonImmutable::parse('2026-04-21T05:43:00+00:00'),
        'received_at' => CarbonImmutable::parse('2026-04-21T05:43:02+00:00'),
        'raw_payload' => ['test' => true],
    ]);

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => '2026-04-28T05:07:00+00:00',
        'until' => '2026-04-28T05:43:00+00:00',
        'limit' => 10,
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard?{$query}");

    $response->assertOk();
    $response->assertJsonPath('timespan.since', '2026-04-28T05:07:00+00:00');
    $response->assertJsonPath('timespan.until', '2026-04-28T05:43:00+00:00');
    $response->assertJsonPath('comparison.previousWeek.timespan.since', '2026-04-21T05:07:00+00:00');
    $response->assertJsonPath('comparison.previousWeek.timespan.until', '2026-04-21T05:43:00+00:00');
    $response->assertJsonPath('stats.totalEvents', 1);
    $response->assertJsonPath('comparison.previousWeek.stats.totalEvents', 1);
    $response->assertJsonCount(0, 'stats.timeSeries');
    $response->assertJsonCount(0, 'comparison.previousWeek.stats.timeSeries');
    $response->assertJsonPath('healthKpis.totalAnchors', 1);
    $response->assertJsonPath('healthKpis.healthyRatePercent', 100);
    $response->assertJsonPath('healthKpis.reachableRatePercent', 100);
});

it('supports 10-minute and 30-minute buckets for dashboard chart data', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);

    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now(),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => true,
    ]);

    $events = [
        ['device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -50, 'scanned_at' => '2026-04-29T08:05:00+00:00'],
        ['device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -52, 'scanned_at' => '2026-04-29T08:08:00+00:00'],
        ['device_mac' => 'bb:bb:bb:bb:bb:01', 'rssi_dbm' => -60, 'scanned_at' => '2026-04-29T08:22:00+00:00'],
        ['device_mac' => 'bb:bb:bb:bb:bb:01', 'rssi_dbm' => -62, 'scanned_at' => '2026-04-29T08:28:00+00:00'],
    ];

    foreach ($events as $event) {
        BleScanEvent::create([
            'anchor_id' => $anchor->id,
            'room_id' => $room->id,
            'device_mac' => $event['device_mac'],
            'device_name' => null,
            'rssi_dbm' => $event['rssi_dbm'],
            'scanned_at' => CarbonImmutable::parse($event['scanned_at']),
            'received_at' => CarbonImmutable::parse($event['scanned_at'])->addSeconds(1),
            'raw_payload' => ['test' => true],
        ]);
    }

    Sanctum::actingAs($user);

    $query10 = http_build_query([
        'since' => '2026-04-29T08:00:00+00:00',
        'until' => '2026-04-29T08:30:00+00:00',
        'bucket_minutes' => 10,
    ]);

    $response10 = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard?{$query10}");

    $response10->assertOk();
    $response10->assertJsonPath('timespan.bucketMinutes', 10);
    $response10->assertJsonPath('stats.bucketMinutes', 10);
    $response10->assertJsonCount(2, 'stats.timeSeries');
    $response10->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-29T08:00:00+09:00');
    $response10->assertJsonPath('stats.timeSeries.0.eventCount', 1);
    $response10->assertJsonPath('stats.timeSeries.1.bucket', '2026-04-29T08:20:00+09:00');
    $response10->assertJsonPath('stats.timeSeries.1.eventCount', 1);

    $query30 = http_build_query([
        'since' => '2026-04-29T08:00:00+00:00',
        'until' => '2026-04-29T08:30:00+00:00',
        'bucket_minutes' => 30,
    ]);

    $response30 = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard?{$query30}");

    $response30->assertOk();
    $response30->assertJsonPath('timespan.bucketMinutes', 30);
    $response30->assertJsonPath('stats.bucketMinutes', 30);
    $response30->assertJsonCount(1, 'stats.timeSeries');
    $response30->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-29T08:00:00+09:00');
    $response30->assertJsonPath('stats.timeSeries.0.eventCount', 2);
});

it('returns hourly chart data for devices seen by all requested anchors excluding specified macs', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);
    $room->generatedRadiomap()->create([
        'grid_step' => 0.1,
        'x_range_min' => 0.0,
        'x_range_max' => 9.5,
        'y_range_min' => 0.0,
        'y_range_max' => 7.2,
        'data' => ['test' => true],
    ]);

    $anchor51 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $anchor52 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $anchor53 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $otherAnchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);

    $events = [
        // Included device for 08:00 bucket.
        ['anchor' => $anchor51, 'device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -50, 'scanned_at' => '2026-04-29T08:05:00+00:00'],
        ['anchor' => $anchor52, 'device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -55, 'scanned_at' => '2026-04-29T08:15:00+00:00'],
        ['anchor' => $anchor53, 'device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -60, 'scanned_at' => '2026-04-29T08:20:00+00:00'],
        // Excluded device also matched on all anchors in same bucket.
        ['anchor' => $anchor51, 'device_mac' => 'cd:cf:23:c8:71:c7', 'rssi_dbm' => -40, 'scanned_at' => '2026-04-29T08:08:00+00:00'],
        ['anchor' => $anchor52, 'device_mac' => 'cd:cf:23:c8:71:c7', 'rssi_dbm' => -42, 'scanned_at' => '2026-04-29T08:18:00+00:00'],
        ['anchor' => $anchor53, 'device_mac' => 'cd:cf:23:c8:71:c7', 'rssi_dbm' => -44, 'scanned_at' => '2026-04-29T08:28:00+00:00'],
        // Not enough anchors, should be excluded.
        ['anchor' => $anchor51, 'device_mac' => 'bb:bb:bb:bb:bb:01', 'rssi_dbm' => -65, 'scanned_at' => '2026-04-29T08:12:00+00:00'],
        ['anchor' => $anchor52, 'device_mac' => 'bb:bb:bb:bb:bb:01', 'rssi_dbm' => -67, 'scanned_at' => '2026-04-29T08:19:00+00:00'],
        // Included device for 09:00 bucket.
        ['anchor' => $anchor51, 'device_mac' => 'ee:ee:ee:ee:ee:01', 'rssi_dbm' => -70, 'scanned_at' => '2026-04-29T09:02:00+00:00'],
        ['anchor' => $anchor52, 'device_mac' => 'ee:ee:ee:ee:ee:01', 'rssi_dbm' => -72, 'scanned_at' => '2026-04-29T09:10:00+00:00'],
        ['anchor' => $anchor53, 'device_mac' => 'ee:ee:ee:ee:ee:01', 'rssi_dbm' => -74, 'scanned_at' => '2026-04-29T09:20:00+00:00'],
        // Same MAC but wrong anchor set, should not affect.
        ['anchor' => $otherAnchor, 'device_mac' => 'ee:ee:ee:ee:ee:01', 'rssi_dbm' => -30, 'scanned_at' => '2026-04-29T09:25:00+00:00'],
    ];

    foreach ($events as $event) {
        BleScanEvent::create([
            'anchor_id' => $event['anchor']->id,
            'room_id' => $room->id,
            'device_mac' => $event['device_mac'],
            'device_name' => null,
            'rssi_dbm' => $event['rssi_dbm'],
            'scanned_at' => CarbonImmutable::parse($event['scanned_at']),
            'received_at' => CarbonImmutable::parse($event['scanned_at'])->addSeconds(2),
            'raw_payload' => ['test' => true],
        ]);
    }

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => '2026-04-29T08:00:00+00:00',
        'until' => '2026-04-29T10:00:00+00:00',
        'anchor_ids' => [$anchor51->id, $anchor52->id, $anchor53->id],
        'exclude_device_macs' => ['cd:cf:23:c8:71:c7', 'ec:f3:a7:28:26:41'],
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/anchor-set-chart?{$query}");

    $response->assertOk();
    $response->assertJsonPath('filters.anchorIds.0', $anchor51->id);
    $response->assertJsonPath('filters.anchorIds.1', $anchor52->id);
    $response->assertJsonPath('filters.anchorIds.2', $anchor53->id);
    $response->assertJsonPath('filters.excludeDeviceMacs.0', 'cd:cf:23:c8:71:c7');
    $response->assertJsonPath('stats.matchedBucketCount', 2);
    $response->assertJsonPath('stats.matchedDeviceCount', 2);
    $response->assertJsonCount(2, 'stats.timeSeries');
    $response->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-29T08:00:00+09:00');
    $response->assertJsonPath('stats.timeSeries.0.eventCount', 1);
    $response->assertJsonPath('stats.timeSeries.0.matchedDevices.0.deviceMac', 'aa:aa:aa:aa:aa:01');
    $response->assertJsonPath('stats.timeSeries.0.matchedDevices.0.scanCount', 3);
    $response->assertJsonPath('stats.timeSeries.0.matchedDevices.0.averageRssi', -55);
    $response->assertJsonPath('stats.timeSeries.1.bucket', '2026-04-29T09:00:00+09:00');
    $response->assertJsonPath('stats.timeSeries.1.eventCount', 1);
    $response->assertJsonPath('stats.timeSeries.1.matchedDevices.0.deviceMac', 'ee:ee:ee:ee:ee:01');
});

it('returns location estimates for devices seen by at least 3 installed anchors in the last 5 minutes', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);
    $room->generatedRadiomap()->create([
        'grid_step' => 0.1,
        'x_range_min' => 0.0,
        'x_range_max' => 9.5,
        'y_range_min' => 0.0,
        'y_range_max' => 7.2,
        'data' => ['test' => true],
    ]);

    $anchor51 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $anchor52 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $anchor53 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => null,
    ]);

    $baseTime = CarbonImmutable::parse('2026-04-30T12:00:00+09:00');

    BleScanEvent::create([
        'anchor_id' => $anchor51->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -97,
        'scanned_at' => $baseTime->subMinutes(4),
        'received_at' => $baseTime->subMinutes(4)->addSeconds(1),
        'raw_payload' => ['test' => true],
    ]);
    BleScanEvent::create([
        'anchor_id' => $anchor52->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -93,
        'scanned_at' => $baseTime->subMinutes(3),
        'received_at' => $baseTime->subMinutes(3)->addSeconds(1),
        'raw_payload' => ['test' => true],
    ]);
    BleScanEvent::create([
        'anchor_id' => $anchor53->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -90,
        'scanned_at' => $baseTime->subMinutes(2),
        'received_at' => $baseTime->subMinutes(2)->addSeconds(1),
        'raw_payload' => ['test' => true],
    ]);

    BleScanEvent::create([
        'anchor_id' => $anchor51->id,
        'room_id' => $room->id,
        'device_mac' => 'bb:bb:bb:bb:bb:01',
        'device_name' => 'Not Enough Anchors',
        'rssi_dbm' => -80,
        'scanned_at' => $baseTime->subMinutes(4),
        'received_at' => $baseTime->subMinutes(4)->addSeconds(1),
        'raw_payload' => ['test' => true],
    ]);
    BleScanEvent::create([
        'anchor_id' => $anchor52->id,
        'room_id' => $room->id,
        'device_mac' => 'bb:bb:bb:bb:bb:01',
        'device_name' => 'Not Enough Anchors',
        'rssi_dbm' => -82,
        'scanned_at' => $baseTime->subMinutes(3),
        'received_at' => $baseTime->subMinutes(3)->addSeconds(1),
        'raw_payload' => ['test' => true],
    ]);

    Http::fake([
        'http://localhost:8000/location/estimate' => Http::response([
            'x' => 0.097,
            'y' => 0.1,
            'confidence' => 0.4259,
            'is_outside' => false,
            'min_distance' => 8.6113,
            'matched_anchors' => 3,
        ], 200),
    ]);

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => $baseTime->subMinutes(5)->toIso8601String(),
        'until' => $baseTime->toIso8601String(),
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/location-estimates?{$query}");

    $response->assertOk();
    $response->assertJsonPath('stats.installedAnchorCount', 3);
    $response->assertJsonPath('stats.estimatedDeviceCount', 1);
    $response->assertJsonPath('generatedRadiomap.xRangeMax', 9.5);
    $response->assertJsonPath('generatedRadiomap.yRangeMax', 7.2);
    $response->assertJsonPath('generatedRadiomap.xRangeMin', 0);
    $response->assertJsonPath('generatedRadiomap.yRangeMin', 0);
    $response->assertJsonCount(1, 'devices');
    $response->assertJsonPath('devices.0.deviceMac', 'aa:aa:aa:aa:aa:01');
    $response->assertJsonPath('devices.0.matchedAnchors', 3);
    $response->assertJsonPath('devices.0.signals.'.$anchor51->id, -97);
    $response->assertJsonPath('devices.0.signals.'.$anchor52->id, -93);
    $response->assertJsonPath('devices.0.signals.'.$anchor53->id, -90);
    $response->assertJsonPath('devices.0.estimate.x', 0.097);
    $response->assertJsonPath('devices.0.estimate.matchedAnchors', 3);

    Http::assertSent(function ($request) use ($room, $anchor51, $anchor52, $anchor53) {
        return $request->url() === 'http://localhost:8000/location/estimate'
            && $request['room_id'] === (string) $room->id
            && $request['signals'][(string) $anchor51->id] === -97
            && $request['signals'][(string) $anchor52->id] === -93
            && $request['signals'][(string) $anchor53->id] === -90;
    });
});

it('forbids access when the user is not part of the space', function () {
    $space = Space::create(['name' => 'Home Space']);
    $room = $space->rooms()->create(['name' => 'Lobby']);
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard")
        ->assertForbidden();
});

it('returns 404 when the room does not belong to the space', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Space A']);
    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $otherSpace = Space::create(['name' => 'Space B']);
    $room = $otherSpace->rooms()->create(['name' => 'Secret Room']);

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard")
        ->assertNotFound();
});
