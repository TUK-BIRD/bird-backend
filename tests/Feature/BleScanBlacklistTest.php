<?php

use App\Models\BleScanBlacklistedMac;
use App\Models\BleScanEvent;
use App\Models\LocationEstimate;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists blacklisted mac addresses', function () {
    $user = User::factory()->create();

    BleScanBlacklistedMac::create([
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
        'note' => 'test device',
        'created_by_user_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/ble_scan_blacklisted_macs')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.deviceMac', 'aa:bb:cc:dd:ee:ff')
        ->assertJsonPath('0.note', 'test device')
        ->assertJsonPath('0.createdByUser.id', $user->id);
});

it('creates a blacklisted mac address', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/ble_scan_blacklisted_macs', [
        'device_mac' => 'AA:BB:CC:DD:EE:FF',
        'note' => 'ignore duplicated sender',
    ])
        ->assertCreated()
        ->assertJsonPath('deviceMac', 'aa:bb:cc:dd:ee:ff')
        ->assertJsonPath('note', 'ignore duplicated sender')
        ->assertJsonPath('createdByUser.id', $user->id);

    $this->assertDatabaseHas('ble_scan_blacklisted_macs', [
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
        'created_by_user_id' => $user->id,
    ]);
});

it('removes existing scan data when creating a blacklisted mac address', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Bird Space']);
    $room = $space->rooms()->create(['name' => 'Room 1']);
    $scannedAt = CarbonImmutable::parse('2026-05-05T12:00:00+09:00');

    BleScanEvent::create([
        'room_id' => $room->id,
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
        'device_name' => 'noise',
        'rssi_dbm' => -50,
        'scanned_at' => $scannedAt,
        'received_at' => $scannedAt,
        'raw_payload' => ['test' => true],
    ]);

    LocationEstimate::create([
        'space_id' => $space->id,
        'room_id' => $room->id,
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
        'matched_anchor_count' => 2,
        'signals' => [],
        'estimate' => [],
        'x' => 1.0,
        'y' => 2.0,
        'confidence' => 0.9,
        'is_outside' => false,
        'window_since' => $scannedAt->subMinutes(5),
        'window_until' => $scannedAt,
        'estimated_at' => $scannedAt,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/ble_scan_blacklisted_macs', [
        'device_mac' => 'AA:BB:CC:DD:EE:FF',
    ])
        ->assertCreated()
        ->assertJsonPath('deviceMac', 'aa:bb:cc:dd:ee:ff')
        ->assertJsonPath('deletedScanEventCount', 1)
        ->assertJsonPath('deletedLocationEstimateCount', 1);

    $this->assertDatabaseMissing('ble_scan_events', [
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
    ]);
    $this->assertDatabaseMissing('location_estimates', [
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
    ]);
});

it('deletes a blacklisted mac address', function () {
    $entry = BleScanBlacklistedMac::create([
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/ble_scan_blacklisted_macs/{$entry->id}")
        ->assertOk()
        ->assertJsonPath('deleted', true)
        ->assertJsonPath('deviceMac', 'aa:bb:cc:dd:ee:ff');

    $this->assertDatabaseMissing('ble_scan_blacklisted_macs', [
        'id' => $entry->id,
    ]);
});
