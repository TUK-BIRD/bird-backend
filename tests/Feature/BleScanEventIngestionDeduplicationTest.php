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
