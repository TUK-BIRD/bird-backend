<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Anchor scan topics
    |--------------------------------------------------------------------------
    |
    | subscribe: backend listener topic (wildcard)
    | publish_pattern: anchor publish topic pattern
    |
    */
    'anchor_scan_subscribe' => env(
        'MQTT_TOPIC_ANCHOR_SCAN_SUBSCRIBE',
        'bird/anchor/+/scan'
    ),

    'anchor_scan_publish_pattern' => env(
        'MQTT_TOPIC_ANCHOR_SCAN_PUBLISH_PATTERN',
        'bird/anchor/{anchor_id}/scan'
    ),
];
