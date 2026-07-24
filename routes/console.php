<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::addCommands([
    \App\Console\Commands\ExpireLoyaltyPoints::class,
]);

Schedule::command('loyalty:expire')->dailyAt('00:00')->withoutOverlapping();
