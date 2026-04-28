<?php

use App\Models\BleAnchor;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns installed ble anchors for a specific room', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Test Space',
        'description' => 'Test',
    ]);
    $space->users()->attach($user->id, [
        'role' => 'OWNER',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create([
        'name' => 'Room A',
    ]);
    $otherRoom = $space->rooms()->create([
        'name' => 'Room B',
    ]);

    $oldAnchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now()->subHour(),
    ]);
    $newAnchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);
    BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => null,
    ]);
    BleAnchor::factory()->create([
        'room_id' => $otherRoom->id,
        'installed_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors");

    $response->assertOk();
    $response->assertJsonCount(2);
    $response->assertJsonPath('0.id', $newAnchor->id);
    $response->assertJsonPath('1.id', $oldAnchor->id);
});

it('returns room ble anchor health summary for dashboard', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Test Space',
        'description' => 'Test',
    ]);
    $space->users()->attach($user->id, [
        'role' => 'OWNER',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $space->rooms()->create([
        'name' => 'Room A',
    ]);
    $otherRoom = $space->rooms()->create([
        'name' => 'Room B',
    ]);

    $online = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:01',
        'installed_at' => now()->subMinutes(2),
        'health_status' => 'online',
        'health_last_payload_at' => now()->subSeconds(20),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => true,
    ]);
    $degraded = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:02',
        'installed_at' => now()->subMinute(),
        'health_status' => 'online',
        'health_last_payload_at' => now()->subSeconds(30),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => false,
    ]);
    $offline = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:03',
        'installed_at' => now(),
        'health_status' => 'offline',
        'health_last_payload_at' => now()->subSeconds(40),
        'health_wifi_connected' => false,
        'health_mqtt_connected' => false,
        'health_scan_enabled' => false,
    ]);
    BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:04',
        'installed_at' => null,
    ]);
    BleAnchor::factory()->create([
        'room_id' => $otherRoom->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:05',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now(),
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
        'health_scan_enabled' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors/health");

    $response->assertOk();
    $response->assertJsonPath('roomId', $room->id);
    $response->assertJsonPath('spaceId', $space->id);
    $response->assertJsonPath('summary.total', 3);
    $response->assertJsonPath('summary.online', 1);
    $response->assertJsonPath('summary.degraded', 1);
    $response->assertJsonPath('summary.offline', 1);
    $response->assertJsonPath('summary.unknown', 0);
    $response->assertJsonCount(3, 'anchors');
    $response->assertJsonPath('anchors.0.id', $offline->id);
    $response->assertJsonPath('anchors.0.healthState', 'offline');
    $response->assertJsonPath('anchors.1.id', $degraded->id);
    $response->assertJsonPath('anchors.1.healthState', 'degraded');
    $response->assertJsonPath('anchors.1.scanEnabled', false);
    $response->assertJsonPath('anchors.2.id', $online->id);
    $response->assertJsonPath('anchors.2.healthState', 'online');
    $response->assertJsonPath('anchors.2.wifiConnected', true);
    $response->assertJsonPath('anchors.2.mqttConnected', true);
});

it('returns 403 when user is not a member of the space', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Test Space',
    ]);
    $room = $space->rooms()->create([
        'name' => 'Room A',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors")
        ->assertForbidden();
});

it('returns 403 for room ble anchor health when user is not a member of the space', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Test Space',
    ]);
    $room = $space->rooms()->create([
        'name' => 'Room A',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors/health")
        ->assertForbidden();
});

it('returns 404 when the room does not belong to the space', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Space A',
    ]);
    $otherSpace = Space::create([
        'name' => 'Space B',
    ]);
    $space->users()->attach($user->id, [
        'role' => 'OWNER',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $otherSpace->rooms()->create([
        'name' => 'Room B',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors")
        ->assertNotFound();
});

it('returns 404 for room ble anchor health when the room does not belong to the space', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Space A',
    ]);
    $otherSpace = Space::create([
        'name' => 'Space B',
    ]);
    $space->users()->attach($user->id, [
        'role' => 'OWNER',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $room = $otherSpace->rooms()->create([
        'name' => 'Room B',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors/health")
        ->assertNotFound();
});

it('deletes a ble anchor in the room', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Test Space']);
    $space->users()->attach($user->id, [
        'role' => 'OWNER',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $room = $space->rooms()->create(['name' => 'Room A']);
    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/ble_anchors/{$anchor->id}")
        ->assertOk()
        ->assertJson([
            'deleted' => true,
            'anchorId' => $anchor->id,
        ]);

    $this->assertDatabaseMissing('ble_anchors', [
        'id' => $anchor->id,
    ]);
});

it('returns 403 when deleting without space membership', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Test Space']);
    $room = $space->rooms()->create(['name' => 'Room A']);
    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/ble_anchors/{$anchor->id}")
        ->assertForbidden();
});

it('deletes anchor by id regardless of room in path context', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Space A']);
    $space->users()->attach($user->id, [
        'role' => 'OWNER',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $otherRoom = $space->rooms()->create(['name' => 'Room B']);
    $anchor = BleAnchor::factory()->create([
        'room_id' => $otherRoom->id,
        'installed_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/ble_anchors/{$anchor->id}")
        ->assertOk();
});
