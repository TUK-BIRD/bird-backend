<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('location-estimates:generate')
    ->cron((string) config('services.location_estimator.schedule_cron', '*/5 * * * *'))
    ->withoutOverlapping()
    ->runInBackground();
