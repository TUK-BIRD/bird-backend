<?php

use App\Models\BleAnchor;
use App\Models\Room;
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
