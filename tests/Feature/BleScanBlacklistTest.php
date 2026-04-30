<?php

use App\Models\BleScanBlacklistedMac;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists blacklisted mac addresses', function () {
    BleScanBlacklistedMac::create([
        'device_mac' => 'aa:bb:cc:dd:ee:ff',
        'note' => 'test device',
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/ble_scan_blacklisted_macs')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.deviceMac', 'aa:bb:cc:dd:ee:ff')
        ->assertJsonPath('0.note', 'test device');
});

it('creates a blacklisted mac address', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/ble_scan_blacklisted_macs', [
        'device_mac' => 'AA:BB:CC:DD:EE:FF',
        'note' => 'ignore duplicated sender',
    ])
        ->assertCreated()
        ->assertJsonPath('deviceMac', 'aa:bb:cc:dd:ee:ff')
        ->assertJsonPath('note', 'ignore duplicated sender');

    $this->assertDatabaseHas('ble_scan_blacklisted_macs', [
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
