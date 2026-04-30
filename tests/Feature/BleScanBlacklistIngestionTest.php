<?php

use App\Models\BleAnchor;
use App\Models\BleScanBlacklistedMac;
use App\Models\BleScanEvent;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('skips storing blacklisted mac scan events', function () {
    $space = Space::create(['name' => 'Bird Space']);
    $room = $space->rooms()->create(['name' => 'Room 1']);
    $anchor = BleAnchor::factory()->create([
        'room_id' => $room->id,
        'anchor_uid' => 'aa:bb:cc:dd:ee:ff',
        'installed_at' => now(),
    ]);

    BleScanBlacklistedMac::create([
        'device_mac' => '11:22:33:44:55:66',
        'note' => 'do not store',
    ]);

    $command = app(\App\Console\Commands\MqttListenScannedDevices::class);
    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('isBlacklistedMac');
    $method->setAccessible(true);

    expect($method->invoke($command, '11:22:33:44:55:66'))->toBeTrue()
        ->and($method->invoke($command, '22:33:44:55:66:77'))->toBeFalse();

    BleScanEvent::query()->create([
        'anchor_id' => $anchor->id,
        'room_id' => $room->id,
        'device_mac' => '22:33:44:55:66:77',
        'device_name' => 'allowed',
        'rssi_dbm' => -50,
        'scanned_at' => now(),
        'received_at' => now(),
        'raw_payload' => ['test' => true],
    ]);

    expect(BleScanEvent::query()->count())->toBe(1);
});
