<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttPublishScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:scan {scanner_id} {state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scan on/off control to a specific ESP32 scanner.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scannerId = $this->normalizeMac((string) $this->argument('scanner_id'));
        $state = strtolower((string) $this->argument('state'));

        if ($scannerId === null) {
            $this->error('Invalid scanner_id. Expected MAC format like dc:b4:d9:9b:c3:9c');
            return self::FAILURE;
        }

        if (!in_array($state, ['on', 'off'], true)) {
            $this->error('Invalid state. Use "on" or "off".');
            return self::FAILURE;
        }

        $topic = str_replace(
            '{scanner_id}',
            $scannerId,
            config(
                'mqtt_topics.anchor_control_publish_pattern',
                'bird/anchor/{scanner_id}/control'
            )
        );

        $retain = true;

        try {
            $mqtt = MQTT::connection();
            $mqtt->publish($topic, $state, 1, $retain);
            $mqtt->loop(true, true);
            MQTT::disconnect();

            $this->info("Published {$state} to {$topic} (QoS=1, retain=" . ($retain ? 'true' : 'false') . ')');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Publish failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function normalizeMac(string $mac): ?string
    {
        $normalized = strtolower(trim($mac));

        return preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/', $normalized) === 1
            ? $normalized
            : null;
    }
}
