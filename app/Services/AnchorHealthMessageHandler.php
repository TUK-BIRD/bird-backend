<?php

namespace App\Services;

use App\Models\BleAnchor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class AnchorHealthMessageHandler
{
    public function handle(string $topic, string $message, ?CarbonImmutable $receivedAt = null): bool
    {
        $receivedAt ??= CarbonImmutable::now();
        $decoded = json_decode($message, true);

        if (! is_array($decoded)) {
            Log::warning('MQTT anchor health payload is not valid JSON.', [
                'topic' => $topic,
                'payload' => $message,
            ]);

            return false;
        }

        $topicScannerId = $this->extractScannerIdFromTopic($topic);
        $payloadScannerId = $this->normalizeMac((string) ($decoded['scanner_id'] ?? ''));
        $scannerId = $payloadScannerId ?? $topicScannerId;

        if ($scannerId === null) {
            Log::warning('MQTT anchor health payload does not contain a valid scanner id.', [
                'topic' => $topic,
                'payload' => $decoded,
            ]);

            return false;
        }

        if ($payloadScannerId !== null && $topicScannerId !== null && $payloadScannerId !== $topicScannerId) {
            Log::warning('MQTT anchor health scanner id mismatch.', [
                'topic' => $topic,
                'topic_scanner_id' => $topicScannerId,
                'payload_scanner_id' => $payloadScannerId,
                'payload' => $decoded,
            ]);
        }

        $anchor = BleAnchor::query()
            ->where('anchor_uid', $scannerId)
            ->first();

        if ($anchor === null) {
            Log::warning('MQTT anchor health received for unknown anchor.', [
                'topic' => $topic,
                'scanner_id' => $scannerId,
                'payload' => $decoded,
            ]);

            return false;
        }

        $status = is_string($decoded['status'] ?? null)
            ? strtolower(trim((string) $decoded['status']))
            : null;

        $anchor->forceFill([
            'health_status' => $status,
            'health_last_payload_at' => $receivedAt,
            'health_last_topic' => $topic,
            'health_uptime_sec' => $this->toNullableInt($decoded['uptime_sec'] ?? null),
            'health_free_heap' => $this->toNullableInt($decoded['free_heap'] ?? null),
            'health_min_free_heap' => $this->toNullableInt($decoded['min_free_heap'] ?? null),
            'health_wifi_connected' => $this->toNullableBool($decoded['wifi_connected'] ?? null),
            'health_mqtt_connected' => $this->toNullableBool($decoded['mqtt_connected'] ?? null),
            'health_scan_enabled' => $this->toNullableBool($decoded['scan_enabled'] ?? null),
            'health_raw_payload' => [
                'topic' => $topic,
                'payload' => $decoded,
            ],
        ]);

        $anchor->save();

        Log::info('MQTT anchor health payload received.', [
            'topic' => $topic,
            'scanner_id' => $scannerId,
            'anchor_id' => $anchor->id,
            'health_status' => $anchor->health_status,
            'health_state' => $anchor->health_state,
        ]);

        return true;
    }

    private function extractScannerIdFromTopic(string $topic): ?string
    {
        $parts = explode('/', trim($topic, '/'));

        return $this->normalizeMac($parts[2] ?? '');
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

    private function toNullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function toNullableBool(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
