<?php

use App\Console\Commands\ProcessDailyTickCommand;
use App\Console\Commands\ProcessGameMonthCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Schedule the Nagoya Oikos 10-minute online daily tick.
 * Evaluates every minute for sessions where 10-minute interval has elapsed and players are online.
 */
Schedule::command(ProcessDailyTickCommand::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Schedule the Nagoya Oikos once-a-day monthly tick.
 * Runs once every 24 hours (or at scheduled interval) to batch-process all remaining days
 * in the 30-day month along with autonomous disciple and church multiplication.
 */
Schedule::command(ProcessGameMonthCommand::class)
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();
