<?php

use App\Models\BleAnchor;
use App\Models\Space;
use App\Models\User;
use App\Services\AnchorHealthMessageHandler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('stores anchor health payload on a registered anchor', function () {
    $space = Space::create([
        'name' => 'Test Space',
    ]);
    $room = $space->rooms()->create([
        'name' => 'Room A',
    ]);

    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:ff',
        'installed_at' => now(),
    ]);

    $receivedAt = CarbonImmutable::parse('2026-04-28 14:00:00');
    Carbon::setTestNow($receivedAt);
    $payload = json_encode([
        'scanner_id' => 'aa:bb:cc:dd:ee:ff',
        'status' => 'online',
        'uptime_sec' => 1234,
        'free_heap' => 210000,
        'min_free_heap' => 180000,
        'wifi_connected' => true,
        'mqtt_connected' => true,
        'scan_enabled' => true,
    ], JSON_UNESCAPED_SLASHES);

    $handled = app(AnchorHealthMessageHandler::class)->handle(
        'bird/anchor/aa:bb:cc:dd:ee:ff/health',
        $payload,
        $receivedAt,
    );

    expect($handled)->toBeTrue();

    $anchor->refresh();

    expect($anchor->health_status)->toBe('online')
        ->and($anchor->health_last_topic)->toBe('bird/anchor/aa:bb:cc:dd:ee:ff/health')
        ->and($anchor->health_last_payload_at?->toDateTimeString())->toBe('2026-04-28 14:00:00')
        ->and($anchor->health_uptime_sec)->toBe(1234)
        ->and($anchor->health_free_heap)->toBe(210000)
        ->and($anchor->health_min_free_heap)->toBe(180000)
        ->and($anchor->health_wifi_connected)->toBeTrue()
        ->and($anchor->health_mqtt_connected)->toBeTrue()
        ->and($anchor->health_scan_enabled)->toBeTrue()
        ->and($anchor->health_state)->toBe('online');

    Carbon::setTestNow();
});

it('marks anchor health as degraded in room ble anchor response when scan is disabled', function () {
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

    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:ff',
        'installed_at' => now(),
        'health_status' => 'online',
        'health_last_payload_at' => now(),
        'health_scan_enabled' => false,
        'health_wifi_connected' => true,
        'health_mqtt_connected' => true,
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/spaces/{$space->id}/rooms/{$room->id}/ble_anchors")
        ->assertOk()
        ->assertJsonPath('0.id', $anchor->id)
        ->assertJsonPath('0.healthState', 'degraded')
        ->assertJsonPath('0.healthIsStale', false);
});

it('treats stale online health as offline', function () {
    config()->set('mqtt_topics.anchor_health_online_timeout_seconds', 150);

    $space = Space::create([
        'name' => 'Test Space',
    ]);
    $room = $space->rooms()->create([
        'name' => 'Room A',
    ]);

    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'health_status' => 'online',
        'health_last_payload_at' => now()->subSeconds(151),
    ]);

    expect($anchor->health_state)->toBe('offline')
        ->and($anchor->health_is_stale)->toBeTrue();
});
