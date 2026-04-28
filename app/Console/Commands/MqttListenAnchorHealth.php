<?php

namespace App\Console\Commands;

use App\Services\AnchorHealthMessageHandler;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttListenAnchorHealth extends Command
{
    protected $signature = 'app:mqtt-listen-anchor-health {--topic=}';

    protected $description = 'Listen for anchor health payloads over MQTT.';

    public function __construct(
        private readonly AnchorHealthMessageHandler $handler
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $topic = $this->option('topic')
            ?: config('mqtt_topics.anchor_health_subscribe', 'bird/anchor/+/health');
        $mqtt = MQTT::connection();

        $this->info("Listening MQTT topic: {$topic}");
        $this->line('Press Ctrl+C to stop.');

        $mqtt->subscribe($topic, function (string $topic, string $message): void {
            $handled = $this->handler->handle($topic, $message);

            if ($handled) {
                $this->info("[{$topic}] Health payload stored.");

                return;
            }

            $this->warn("[{$topic}] Health payload ignored.");
        }, 1);

        $mqtt->loop(true);
        MQTT::disconnect();

        return self::SUCCESS;
    }
}
