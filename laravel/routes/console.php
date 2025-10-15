<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clean up session replay events older than 60 days, daily at 00:30 AM
Schedule::command('session-replay:cleanup')
    ->dailyAt('00:30')
    ->withoutOverlapping();

// Expire trial plans hourly
Schedule::command('plans:expire-trials')
    ->hourly()
    ->withoutOverlapping();

// Send trial expiration warnings daily at 10:00 AM
Schedule::command('plans:send-trial-warnings --days=2')
    ->dailyAt('10:00')
    ->withoutOverlapping();
