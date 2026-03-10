<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttPublishTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-publish-test {anchor_id=anchor-test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a sample anchor scan payload over MQTT.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $anchorId = (string) $this->argument('anchor_id');
        $topic = str_replace(
            '{anchor_id}',
            $anchorId,
            config(
                'mqtt_topics.anchor_scan_publish_pattern',
                'bird/anchor/{anchor_id}/scan'
            )
        );

        $payload = [
            'anchor_id' => $anchorId,
            'scanned_at' => now()->toIso8601String(),
            'devices' => [
                ['mac' => 'AA:BB:CC:11:22:33', 'rssi' => -62, 'name' => 'iPhone'],
                ['mac' => '11:22:33:44:55:66', 'rssi' => -75, 'name' => 'Galaxy'],
            ],
        ];

        MQTT::publish($topic, json_encode($payload, JSON_UNESCAPED_SLASHES));
        $this->info("Published to {$topic}");

        return self::SUCCESS;
    }
}
