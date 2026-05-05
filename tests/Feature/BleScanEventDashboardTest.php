<?php

use App\Enums\UserSpaceRole;
use App\Models\BleAnchor;
use App\Models\BleScanBlacklistedMac;
use App\Models\BleScanEvent;
use App\Models\LocationEstimate;
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

it('excludes blacklisted mac addresses from scan dashboard aggregates', function () {
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
        'installed_at' => now(),
    ]);
    $windowStart = CarbonImmutable::parse('2026-05-05T10:00:00+00:00');
    $windowEnd = CarbonImmutable::parse('2026-05-05T11:00:00+00:00');

    BleScanBlacklistedMac::create([
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
        'note' => 'exclude from aggregates',
    ]);

    foreach ([
        ['device_mac' => 'aa:bb:cc:dd:ee:ff', 'rssi_dbm' => -40],
        ['device_mac' => '11:22:33:44:55:66', 'rssi_dbm' => -60],
    ] as $event) {
        BleScanEvent::create([
            'anchor_id' => $anchor->id,
            'room_id' => $room->id,
            'device_mac' => $event['device_mac'],
            'rssi_dbm' => $event['rssi_dbm'],
            'scanned_at' => $windowStart->addMinutes(10),
            'received_at' => $windowStart->addMinutes(10),
            'raw_payload' => ['test' => true],
        ]);
    }

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => $windowStart->toIso8601String(),
        'until' => $windowEnd->toIso8601String(),
        'limit' => 10,
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/dashboard?{$query}");

    $response->assertOk()
        ->assertJsonPath('stats.totalEvents', 1)
        ->assertJsonPath('stats.uniqueDevices', 1)
        ->assertJsonPath('stats.averageRssi', -60)
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.deviceMac', '11:22:33:44:55:66');
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

it('supports 10-minute, 30-minute, and 60-minute buckets for anchor set chart data', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);

    $anchor51 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $anchor52 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);

    $events = [
        ['anchor' => $anchor51, 'device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -50, 'scanned_at' => '2026-04-29T08:05:00+00:00'],
        ['anchor' => $anchor52, 'device_mac' => 'aa:aa:aa:aa:aa:01', 'rssi_dbm' => -54, 'scanned_at' => '2026-04-29T08:08:00+00:00'],
        ['anchor' => $anchor51, 'device_mac' => 'bb:bb:bb:bb:bb:01', 'rssi_dbm' => -60, 'scanned_at' => '2026-04-29T08:22:00+00:00'],
        ['anchor' => $anchor52, 'device_mac' => 'bb:bb:bb:bb:bb:01', 'rssi_dbm' => -64, 'scanned_at' => '2026-04-29T08:28:00+00:00'],
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

    $baseQuery = [
        'since' => '2026-04-29T08:00:00+00:00',
        'until' => '2026-04-29T08:30:00+00:00',
        'anchor_ids' => [$anchor51->id, $anchor52->id],
    ];

    $query10 = http_build_query($baseQuery + ['bucket_minutes' => 10]);

    $response10 = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/anchor-set-chart?{$query10}");

    $response10->assertOk();
    $response10->assertJsonPath('timespan.bucketMinutes', 10);
    $response10->assertJsonPath('stats.bucketMinutes', 10);
    $response10->assertJsonCount(2, 'stats.timeSeries');
    $response10->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-29T08:00:00+09:00');
    $response10->assertJsonPath('stats.timeSeries.0.eventCount', 1);
    $response10->assertJsonPath('stats.timeSeries.1.bucket', '2026-04-29T08:20:00+09:00');
    $response10->assertJsonPath('stats.timeSeries.1.eventCount', 1);

    $query30 = http_build_query($baseQuery + ['bucket_minutes' => 30]);

    $response30 = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/anchor-set-chart?{$query30}");

    $response30->assertOk();
    $response30->assertJsonPath('timespan.bucketMinutes', 30);
    $response30->assertJsonPath('stats.bucketMinutes', 30);
    $response30->assertJsonCount(1, 'stats.timeSeries');
    $response30->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-29T08:00:00+09:00');
    $response30->assertJsonPath('stats.timeSeries.0.eventCount', 2);

    $query60 = http_build_query($baseQuery + ['bucket_minutes' => 60]);

    $response60 = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/anchor-set-chart?{$query60}");

    $response60->assertOk();
    $response60->assertJsonPath('timespan.bucketMinutes', 60);
    $response60->assertJsonPath('stats.bucketMinutes', 60);
    $response60->assertJsonCount(1, 'stats.timeSeries');
    $response60->assertJsonPath('stats.timeSeries.0.bucket', '2026-04-29T08:00:00+09:00');
    $response60->assertJsonPath('stats.timeSeries.0.eventCount', 2);
});

it('returns overview dashboard with anchor health, occupancy, and busiest time slots', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);
    $room->generatedRadiomap()->create([
        'grid_step' => 1,
        'x_range_min' => 0.0,
        'x_range_max' => 10.0,
        'y_range_min' => 0.0,
        'y_range_max' => 10.0,
        'data' => ['test' => true],
    ]);

    BleAnchor::factory()->create([
        'room_id' => $room->id,
        'label' => 'Online Anchor',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now(),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => true,
    ]);
    BleAnchor::factory()->create([
        'room_id' => $room->id,
        'label' => 'Degraded Anchor',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now(),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => false,
    ]);
    BleAnchor::factory()->create([
        'room_id' => $room->id,
        'label' => 'Offline Anchor',
        'installed_at' => now(),
        'health_status' => 'offline',
        'health_last_payload_at' => now(),
        'health_wifi_connected' => false,
        'health_mqtt_connected' => false,
        'health_scan_enabled' => false,
    ]);

    $baseTime = CarbonImmutable::parse('2026-04-30T12:00:00+09:00');
    $estimates = [
        ['device_mac' => 'aa:aa:aa:aa:aa:01', 'x' => 1.1, 'y' => 2.1, 'estimated_at' => $baseTime->subMinutes(20)],
        ['device_mac' => 'bb:bb:bb:bb:bb:01', 'x' => 1.4, 'y' => 2.4, 'estimated_at' => $baseTime->subMinutes(20)],
        ['device_mac' => 'cc:cc:cc:cc:cc:01', 'x' => 4.1, 'y' => 6.1, 'estimated_at' => $baseTime->subMinutes(10)],
        ['device_mac' => 'dd:dd:dd:dd:dd:01', 'x' => 9.5, 'y' => 9.5, 'estimated_at' => $baseTime, 'is_outside' => true],
    ];

    foreach ($estimates as $estimate) {
        LocationEstimate::create([
            'space_id' => $space->id,
            'room_id' => $room->id,
            'device_mac' => $estimate['device_mac'],
            'device_name' => null,
            'matched_anchor_count' => 2,
            'signals' => ['51' => -80, '52' => -90],
            'estimate' => [
                'x' => $estimate['x'],
                'y' => $estimate['y'],
                'confidence' => 0.8,
                'is_outside' => $estimate['is_outside'] ?? false,
                'min_distance' => 1.2,
                'matched_anchors' => 2,
            ],
            'x' => $estimate['x'],
            'y' => $estimate['y'],
            'confidence' => 0.8,
            'is_outside' => $estimate['is_outside'] ?? false,
            'min_distance' => 1.2,
            'window_since' => $estimate['estimated_at']->subMinutes(10),
            'window_until' => $estimate['estimated_at'],
            'estimated_at' => $estimate['estimated_at'],
        ]);
    }

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => $baseTime->subMinutes(30)->toIso8601String(),
        'until' => $baseTime->toIso8601String(),
        'bucket_minutes' => 10,
        'cell_size' => 1,
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/overview-dashboard?{$query}");

    $response->assertOk();
    $response->assertJsonPath('anchorHealth.summary.totalAnchors', 3);
    $response->assertJsonPath('anchorHealth.summary.onlineAnchors', 1);
    $response->assertJsonPath('anchorHealth.summary.degradedAnchors', 1);
    $response->assertJsonPath('anchorHealth.summary.offlineAnchors', 1);
    $response->assertJsonCount(3, 'anchorHealth.anchors');
    $response->assertJsonPath('occupancy.estimateCount', 3);
    $response->assertJsonPath('occupancy.uniqueDeviceCount', 3);
    $response->assertJsonPath('occupancy.occupiedCellCount', 2);
    $response->assertJsonPath('occupancy.totalCellCount', 100);
    $response->assertJsonPath('occupancy.occupiedCellRatePercent', 2);
    $response->assertJsonPath('busiestTimeSlots.0.bucket', '2026-04-30T11:40:00+09:00');
    $response->assertJsonPath('busiestTimeSlots.0.uniqueDeviceCount', 2);
    $response->assertJsonPath('busiestTimeSlots.1.bucket', '2026-04-30T11:50:00+09:00');
    $response->assertJsonPath('busiestTimeSlots.1.uniqueDeviceCount', 1);
});

it('returns stored location estimates from the database without calling the estimator', function () {
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
        'anchor_id' => $anchor51->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -95,
        'scanned_at' => $baseTime->subMinutes(3)->subSeconds(30),
        'received_at' => $baseTime->subMinutes(3)->subSeconds(29),
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
        'anchor_id' => $anchor52->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -90,
        'scanned_at' => $baseTime->subMinutes(2)->subSeconds(30),
        'received_at' => $baseTime->subMinutes(2)->subSeconds(29),
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
        'anchor_id' => $anchor53->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -91,
        'scanned_at' => $baseTime->subMinute(),
        'received_at' => $baseTime->subMinute()->addSecond(),
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

    LocationEstimate::create([
        'space_id' => $space->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'matched_anchor_count' => 3,
        'signals' => [
            (string) $anchor51->id => -99,
            (string) $anchor52->id => -99,
            (string) $anchor53->id => -99,
        ],
        'estimate' => [
            'x' => 9.9,
            'y' => 9.9,
            'confidence' => 0.1,
            'is_outside' => false,
            'min_distance' => 9.9,
            'matched_anchors' => 3,
        ],
        'x' => 9.9,
        'y' => 9.9,
        'confidence' => 0.1,
        'is_outside' => false,
        'min_distance' => 9.9,
        'window_since' => $baseTime->subMinutes(5),
        'window_until' => $baseTime->subMinutes(4),
        'estimated_at' => $baseTime->subMinutes(4),
    ]);
    LocationEstimate::create([
        'space_id' => $space->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'matched_anchor_count' => 3,
        'signals' => [
            (string) $anchor51->id => -96,
            (string) $anchor52->id => -91.5,
            (string) $anchor53->id => -90.5,
        ],
        'estimate' => [
            'x' => 0.097,
            'y' => 0.1,
            'confidence' => 0.4259,
            'is_outside' => false,
            'min_distance' => 8.6113,
            'matched_anchors' => 3,
        ],
        'x' => 0.097,
        'y' => 0.1,
        'confidence' => 0.4259,
        'is_outside' => false,
        'min_distance' => 8.6113,
        'window_since' => $baseTime->subMinutes(5),
        'window_until' => $baseTime,
        'estimated_at' => $baseTime,
    ]);
    LocationEstimate::create([
        'space_id' => $space->id,
        'room_id' => $room->id,
        'device_mac' => 'bb:bb:bb:bb:bb:01',
        'device_name' => 'Not Enough Anchors',
        'matched_anchor_count' => 2,
        'signals' => [
            (string) $anchor51->id => -80,
            (string) $anchor52->id => -82,
        ],
        'estimate' => [
            'x' => 1.5,
            'y' => 2.5,
            'confidence' => 0.5,
            'is_outside' => false,
            'min_distance' => 4.2,
            'matched_anchors' => 2,
        ],
        'x' => 1.5,
        'y' => 2.5,
        'confidence' => 0.5,
        'is_outside' => false,
        'min_distance' => 4.2,
        'window_since' => $baseTime->subMinutes(5),
        'window_until' => $baseTime,
        'estimated_at' => $baseTime,
    ]);

    Http::fake([
        'http://localhost:8000/location/estimate' => fn ($request) => Http::response([
            'x' => 0.097,
            'y' => 0.1,
            'confidence' => 0.4259,
            'is_outside' => false,
            'min_distance' => 8.6113,
            'matched_anchors' => count($request['signals']),
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
    $response->assertJsonPath('stats.estimatedDeviceCount', 2);
    $response->assertJsonPath('generatedRadiomap.xRangeMax', 9.5);
    $response->assertJsonPath('generatedRadiomap.yRangeMax', 7.2);
    $response->assertJsonPath('generatedRadiomap.xRangeMin', 0);
    $response->assertJsonPath('generatedRadiomap.yRangeMin', 0);
    $response->assertJsonCount(2, 'devices');
    $response->assertJsonPath('devices.0.deviceMac', 'aa:aa:aa:aa:aa:01');
    $response->assertJsonPath('devices.0.matchedAnchors', 3);
    $response->assertJsonPath('devices.0.signals.'.$anchor51->id, -96);
    $response->assertJsonPath('devices.0.signals.'.$anchor52->id, -91.5);
    $response->assertJsonPath('devices.0.signals.'.$anchor53->id, -90.5);
    $response->assertJsonPath('devices.0.estimate.x', 0.097);
    $response->assertJsonPath('devices.0.estimate.matchedAnchors', 3);
    $response->assertJsonPath('devices.1.deviceMac', 'bb:bb:bb:bb:bb:01');
    $response->assertJsonPath('devices.1.matchedAnchors', 2);
    $response->assertJsonPath('devices.1.signals.'.$anchor51->id, -80);
    $response->assertJsonPath('devices.1.signals.'.$anchor52->id, -82);
    $response->assertJsonPath('devices.1.signals.'.$anchor53->id, null);
    $response->assertJsonPath('devices.1.estimate.matchedAnchors', 2);

    Http::assertSentCount(0);
});

it('stores generated location estimates from the scheduled command', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $room = $space->rooms()->create(['name' => 'Room 1']);

    $anchor51 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    $anchor52 = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);

    $baseTime = CarbonImmutable::parse('2026-04-30T12:00:00+09:00');

    BleScanEvent::create([
        'anchor_id' => $anchor51->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -80,
        'scanned_at' => $baseTime->subMinutes(4),
        'received_at' => $baseTime->subMinutes(4)->addSecond(),
        'raw_payload' => ['test' => true],
    ]);
    BleScanEvent::create([
        'anchor_id' => $anchor51->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -82,
        'scanned_at' => $baseTime->subMinutes(3),
        'received_at' => $baseTime->subMinutes(3)->addSecond(),
        'raw_payload' => ['test' => true],
    ]);
    BleScanEvent::create([
        'anchor_id' => $anchor52->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:aa:aa:aa:aa:01',
        'device_name' => 'Tracked Device',
        'rssi_dbm' => -90,
        'scanned_at' => $baseTime->subMinutes(2),
        'received_at' => $baseTime->subMinutes(2)->addSecond(),
        'raw_payload' => ['test' => true],
    ]);

    Http::fake([
        'http://localhost:8000/location/estimate' => Http::response([
            'x' => 1.2,
            'y' => 3.4,
            'confidence' => 0.75,
            'is_outside' => false,
            'min_distance' => 2.5,
            'matched_anchors' => 2,
        ], 200),
    ]);

    $this->artisan('location-estimates:generate', [
        '--room-id' => $room->id,
        '--window-minutes' => 5,
        '--until' => $baseTime->toIso8601String(),
    ])->assertExitCode(0);

    Http::assertSentCount(1);
    $this->assertDatabaseCount('location_estimates', 1);

    $estimate = LocationEstimate::firstOrFail();

    expect($estimate->space_id)->toBe($space->id)
        ->and($estimate->room_id)->toBe($room->id)
        ->and($estimate->device_mac)->toBe('aa:aa:aa:aa:aa:01')
        ->and($estimate->matched_anchor_count)->toBe(2)
        ->and($estimate->signals[(string) $anchor51->id])->toBe(-81)
        ->and($estimate->signals[(string) $anchor52->id])->toBe(-90)
        ->and($estimate->x)->toBe(1.2)
        ->and($estimate->y)->toBe(3.4)
        ->and($estimate->confidence)->toBe(0.75)
        ->and($estimate->is_outside)->toBeFalse()
        ->and($estimate->min_distance)->toBe(2.5);
});

it('returns heatmap cells from stored location estimates', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $user = User::factory()->create();

    $space->users()->attach($user->id, [
        'role' => UserSpaceRole::OWNER->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create(['name' => 'Room 1']);
    $room->generatedRadiomap()->create([
        'grid_step' => 0.5,
        'x_range_min' => 0.0,
        'x_range_max' => 10.0,
        'y_range_min' => 0.0,
        'y_range_max' => 10.0,
        'data' => ['test' => true],
    ]);

    $baseTime = CarbonImmutable::parse('2026-04-30T12:00:00+09:00');

    $estimates = [
        ['device_mac' => 'aa:aa:aa:aa:aa:01', 'x' => 1.1, 'y' => 2.1, 'confidence' => 0.8, 'is_outside' => false],
        ['device_mac' => 'bb:bb:bb:bb:bb:01', 'x' => 1.4, 'y' => 2.4, 'confidence' => 0.6, 'is_outside' => false],
        ['device_mac' => 'cc:cc:cc:cc:cc:01', 'x' => 4.1, 'y' => 6.1, 'confidence' => 0.9, 'is_outside' => false],
        ['device_mac' => 'dd:dd:dd:dd:dd:01', 'x' => 9.5, 'y' => 9.5, 'confidence' => 0.9, 'is_outside' => true],
    ];

    foreach ($estimates as $estimate) {
        LocationEstimate::create([
            'space_id' => $space->id,
            'room_id' => $room->id,
            'device_mac' => $estimate['device_mac'],
            'device_name' => null,
            'matched_anchor_count' => 2,
            'signals' => ['51' => -80, '52' => -90],
            'estimate' => [
                'x' => $estimate['x'],
                'y' => $estimate['y'],
                'confidence' => $estimate['confidence'],
                'is_outside' => $estimate['is_outside'],
                'min_distance' => 1.2,
                'matched_anchors' => 2,
            ],
            'x' => $estimate['x'],
            'y' => $estimate['y'],
            'confidence' => $estimate['confidence'],
            'is_outside' => $estimate['is_outside'],
            'min_distance' => 1.2,
            'window_since' => $baseTime->subMinutes(10),
            'window_until' => $baseTime,
            'estimated_at' => $baseTime,
        ]);
    }

    Sanctum::actingAs($user);

    $query = http_build_query([
        'since' => $baseTime->subMinutes(30)->toIso8601String(),
        'until' => $baseTime->toIso8601String(),
        'cell_size' => 1,
    ]);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_scan_events/location-estimates/heatmap?{$query}");

    $response->assertOk();
    $response->assertJsonPath('filters.cellSize', 1);
    $response->assertJsonPath('stats.estimateCount', 3);
    $response->assertJsonPath('stats.uniqueDeviceCount', 3);
    $response->assertJsonPath('stats.cellCount', 2);
    $response->assertJsonPath('stats.maxCellCount', 2);
    $response->assertJsonPath('cells.0.x', 1.5);
    $response->assertJsonPath('cells.0.y', 2.5);
    $response->assertJsonPath('cells.0.count', 2);
    $response->assertJsonPath('cells.0.uniqueDeviceCount', 2);
    $response->assertJsonPath('cells.0.averageConfidence', 0.7);
    $response->assertJsonPath('cells.0.intensity', 1);
    $response->assertJsonPath('cells.1.x', 4.5);
    $response->assertJsonPath('cells.1.y', 6.5);
    $response->assertJsonPath('cells.1.count', 1);
    $response->assertJsonPath('cells.1.intensity', 0.5);
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
