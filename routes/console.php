<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('feeds:sync')->everyFifteenMinutes();

// Daily brief — 8am and 5pm, only in production
Schedule::command('brief:send')
    ->twiceDaily(8, 17)
    ->runInBackground()
    ->onOneServer()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/daily-brief.log'));
