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

    'anchor_health_subscribe' => env(
        'MQTT_TOPIC_ANCHOR_HEALTH_SUBSCRIBE',
        'bird/anchor/+/health'
    ),

    'anchor_scan_publish_pattern' => env(
        'MQTT_TOPIC_ANCHOR_SCAN_PUBLISH_PATTERN',
        'bird/anchor/{anchor_id}/scan'
    ),

    'anchor_control_publish_pattern' => env(
        'MQTT_TOPIC_ANCHOR_CONTROL_PUBLISH_PATTERN',
        'bird/anchor/{scanner_id}/control'
    ),

    'anchor_health_online_timeout_seconds' => (int) env(
        'MQTT_TOPIC_ANCHOR_HEALTH_ONLINE_TIMEOUT_SECONDS',
        150
    ),

    'anchor_health_min_free_heap_threshold' => env(
        'MQTT_TOPIC_ANCHOR_HEALTH_MIN_FREE_HEAP_THRESHOLD'
    ),
];
