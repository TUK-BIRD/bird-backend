<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'location_estimator' => [
        'url' => env('LOCATION_ESTIMATOR_URL', 'http://localhost:8000/location/estimate'),
        'timeout_seconds' => (float) env('LOCATION_ESTIMATOR_TIMEOUT_SECONDS', 5),
        'schedule_cron' => env('LOCATION_ESTIMATOR_SCHEDULE_CRON', '*/5 * * * *'),
        'window_minutes' => (int) env('LOCATION_ESTIMATOR_WINDOW_MINUTES', 5),
        'minimum_anchor_matches' => (int) env('LOCATION_ESTIMATOR_MINIMUM_ANCHOR_MATCHES', 2),
    ],

];
