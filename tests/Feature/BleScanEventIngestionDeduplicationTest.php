<?php

use App\Models\BleAnchor;
use App\Models\BleScanEvent;
use App\Models\Space;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('enforces a unique scan event per anchor, device mac, and scanned_at', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $room = $space->rooms()->create(['name' => 'Room 1']);
    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'installed_at' => now(),
    ]);

    $scannedAt = CarbonImmutable::parse('2026-04-29T12:00:00+09:00');
    $receivedAt = CarbonImmutable::parse('2026-04-29T12:00:01+09:00');

    $inserted = DB::table('ble_scan_events')->insertOrIgnore([
        [
            'anchor_id' => $anchor->id,
            'room_id' => $room->id,
            'device_mac' => 'aa:aa:aa:aa:aa:01',
            'device_name' => 'Device A',
            'rssi_dbm' => -50,
            'scanned_at' => $scannedAt,
            'received_at' => $receivedAt,
            'raw_payload' => json_encode(['first' => true], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'anchor_id' => $anchor->id,
            'room_id' => $room->id,
            'device_mac' => 'aa:aa:aa:aa:aa:01',
            'device_name' => 'Device A duplicate',
            'rssi_dbm' => -48,
            'scanned_at' => $scannedAt,
            'received_at' => $receivedAt,
            'raw_payload' => json_encode(['duplicate' => true], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    expect($inserted)->toBe(1)
        ->and(BleScanEvent::query()->count())->toBe(1);
});

it('stores scan events from mqtt payload data with json raw payloads', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $room = $space->rooms()->create(['name' => 'Room 1']);
    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'dc:b4:d9:9b:c3:9e',
        'installed_at' => now(),
    ]);

    $command = app(\App\Console\Commands\MqttListenScannedDevices::class);
    $reflection = new \ReflectionClass($command);

    $resolveScannedAt = $reflection->getMethod('resolveScannedAt');
    $resolveScannedAt->setAccessible(true);
    $scannedAt = $resolveScannedAt->invoke(
        $command,
        '1970-01-01 00:06:21.679',
        null,
    );

    $storeScanEvent = $reflection->getMethod('storeScanEvent');
    $storeScanEvent->setAccessible(true);
    $result = $storeScanEvent->invoke(
        $command,
        $anchor->id,
        $room->id,
        '72:bf:3b:8e:89:b6',
        null,
        -62,
        $scannedAt,
        [
            'topic' => 'bird/anchor/dc:b4:d9:9b:c3:9e/scan',
            'payload' => [
                'scanner_id' => 'dc:b4:d9:9b:c3:9e',
                'time' => '1970-01-01 00:06:21.679',
                'mac' => '72:bf:3b:8e:89:b6',
                'rssi' => -62,
            ],
        ],
    );

    expect($result['saved'])->toBeTrue()
        ->and(BleScanEvent::query()->count())->toBe(1);

    $event = BleScanEvent::query()->sole();

    expect($event->device_mac)->toBe('72:bf:3b:8e:89:b6')
        ->and($event->raw_payload)->toBeArray()
        ->and(data_get($event->raw_payload, 'payload.time'))->toBe('1970-01-01 00:06:21.679')
        ->and($event->scanned_at?->format('Y-m-d H:i:s'))->toStartWith('1970-01-01 00:06:21');
});
