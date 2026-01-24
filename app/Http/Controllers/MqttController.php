<?php

namespace App\Http\Controllers;

use PhpMqtt\Client\Facades\MQTT;

class MqttController
{
    public function testPublish()
    {
        MQTT::publish('test/topic', 'hello from laravel'); // QoS0 기본 예시 [web:10]
        return response()->json(['ok' => true]);
    }
}
