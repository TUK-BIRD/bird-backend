<?php

namespace App\Console\Commands;

use App\Models\BleAnchor;
use App\Models\BleScanEvent;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttListenScannedDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-listen-scanned-devices {--topic=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for scanned devices over MQTT.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $topic = $this->option('topic')
            ?: config('mqtt_topics.anchor_scan_subscribe', 'bird/anchor/+/scan');
        $mqtt = MQTT::connection();

        $this->info("Listening MQTT topic: {$topic}");
        $this->line('Press Ctrl+C to stop.');

        $mqtt->subscribe($topic, function (string $topic, string $message): void {
            $decoded = json_decode($message, true);

            if (!is_array($decoded)) {
                $this->warn("[{$topic}] Invalid JSON payload: {$message}");
                Log::warning('MQTT anchor scan payload is not valid JSON.', [
                    'topic' => $topic,
                    'payload' => $message,
                ]);

                return;
            }

            $anchorId = $decoded['anchor_id']
                ?? $decoded['anchor_uid']
                ?? $this->extractAnchorIdFromTopic($topic);
            $anchor = $this->resolveAnchor($anchorId);
            $devices = $this->extractDevices($decoded);
            $deviceCount = count($devices);
            $savedCount = 0;

            foreach ($devices as $device) {
                $mac = $this->normalizeMac((string) ($device['mac'] ?? $device['device_mac'] ?? ''));
                if ($mac === null) {
                    continue;
                }

                $rssi = $device['rssi'] ?? $device['rssi_dbm'] ?? null;
                if (!is_numeric($rssi)) {
                    continue;
                }

                $deviceName = $device['name'] ?? $device['device_name'] ?? null;
                if (is_string($deviceName) && strtolower(trim($deviceName)) === 'unknown') {
                    $deviceName = null;
                }

                $scannedAt = $this->resolveScannedAt(
                    $device['scanned_at'] ?? null,
                    $decoded['scanned_at'] ?? null
                );

                $roomId = $anchor?->room_id ?? $this->resolveRoomIdFromMac($mac);

                BleScanEvent::create([
                    'anchor_id' => $anchor?->id,
                    'room_id' => $roomId,
                    'device_mac' => $mac,
                    'device_name' => $deviceName,
                    'rssi_dbm' => (int) $rssi,
                    'scanned_at' => $scannedAt,
                    'received_at' => now(),
                    'raw_payload' => [
                        'topic' => $topic,
                        'payload' => $decoded,
                        'device' => $device,
                    ],
                ]);

                $savedCount++;
            }

            $resolvedAnchor = $anchor?->id ?? 'null';
            $resolvedRoom = $anchor?->room_id ?? 'null';
            $this->info(
                "[{$topic}] anchor_id={$anchorId}, resolved_anchor_id={$resolvedAnchor}, ".
                "resolved_room_id={$resolvedRoom}, devices={$deviceCount}, saved={$savedCount}"
            );

            Log::info('MQTT anchor scan payload received.', [
                'topic' => $topic,
                'anchor_id' => $anchorId,
                'resolved_anchor_id' => $anchor?->id,
                'resolved_room_id' => $anchor?->room_id,
                'device_count' => $deviceCount,
                'saved_count' => $savedCount,
                'payload' => $decoded,
            ]);
        }, 0);

        $mqtt->loop(true);
        MQTT::disconnect();

        return self::SUCCESS;
    }

    private function extractAnchorIdFromTopic(string $topic): ?string
    {
        $parts = explode('/', trim($topic, '/'));

        return $parts[2] ?? null;
    }

    private function resolveAnchor(mixed $anchorValue): ?BleAnchor
    {
        if ($anchorValue === null || $anchorValue === '') {
            return null;
        }

        if (is_numeric($anchorValue)) {
            $anchor = BleAnchor::query()->find((int) $anchorValue);
            if ($anchor !== null) {
                return $anchor;
            }
        }

        return BleAnchor::query()
            ->where('anchor_uid', (string) $anchorValue)
            ->first();
    }

    private function resolveRoomIdFromMac(string $mac): ?int
    {
        $anchor = BleAnchor::query()
            ->where('anchor_uid', $mac)
            ->first();

        return $anchor?->room_id;
    }

    private function extractDevices(array $payload): array
    {
        $devices = $payload['devices'] ?? null;
        if (is_array($devices)) {
            return array_values(array_filter($devices, static fn ($item) => is_array($item)));
        }

        $singleDevice = [
            'mac' => $payload['mac'] ?? $payload['device_mac'] ?? null,
            'name' => $payload['name'] ?? $payload['device_name'] ?? null,
            'rssi' => $payload['rssi'] ?? $payload['rssi_dbm'] ?? null,
            'scanned_at' => $payload['scanned_at'] ?? null,
        ];

        if ($singleDevice['mac'] === null && $singleDevice['rssi'] === null) {
            return [];
        }

        return [$singleDevice];
    }

    private function normalizeMac(string $mac): ?string
    {
        $normalized = strtolower(trim($mac));
        if ($normalized === '') {
            return null;
        }

        return preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/', $normalized) === 1
            ? $normalized
            : null;
    }

    private function resolveScannedAt(mixed $deviceScannedAt, mixed $payloadScannedAt): CarbonImmutable
    {
        $candidate = $deviceScannedAt ?? $payloadScannedAt;
        if (!is_string($candidate) || trim($candidate) === '') {
            return CarbonImmutable::now();
        }

        try {
            return CarbonImmutable::parse($candidate);
        } catch (\Throwable) {
            return CarbonImmutable::now();
        }
    }
}
