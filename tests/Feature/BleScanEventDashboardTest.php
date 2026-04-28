<?php

use App\Enums\UserSpaceRole;
use App\Models\BleAnchor;
use App\Models\BleScanEvent;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    $response->assertJsonPath('stats.timeSeries.0.eventCount', 4);
    $response->assertJsonPath('stats.timeSeries.0.uniqueDeviceCount', 4);
    $response->assertJsonPath('stats.timeSeries.0.averageRssi', -58.4);
    $response->assertJsonPath('events.0.deviceMac', 'aa:aa:aa:aa:aa:03');
    $response->assertJsonPath('stats.latestEventScannedAt', '2026-04-13T08:55:00+09:00');
    $response->assertJsonPath('comparison.previousWeek.timespan.since', '2026-04-06T08:00:00+00:00');
    $response->assertJsonPath('comparison.previousWeek.timespan.until', '2026-04-06T09:00:00+00:00');
    $response->assertJsonPath('comparison.previousWeek.stats.totalEvents', 3);
    $response->assertJsonPath('comparison.previousWeek.stats.uniqueDevices', 2);
    $response->assertJsonPath('comparison.previousWeek.stats.averageRssi', -65);
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
    $response->assertJsonPath('healthKpis.totalAnchors', 1);
    $response->assertJsonPath('healthKpis.healthyRatePercent', 100);
    $response->assertJsonPath('healthKpis.reachableRatePercent', 100);
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
