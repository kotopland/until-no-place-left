<?php

use App\Console\Commands\ProcessGameMonthCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Console\Commands\ProcessWeeklyTickCommand;

/**
 * Schedule the Nagoya Oikos 15-minute online weekly tick.
 * Evaluates every minute for sessions where 15-minute interval has elapsed and players are online.
 */
Schedule::command(ProcessWeeklyTickCommand::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Schedule the Nagoya Oikos once-a-day monthly tick.
 * Runs once every 24 hours (or at scheduled interval) to batch-process all remaining days
 * in the 30-day month along with autonomous disciple and church multiplication.
 */
Schedule::command(ProcessGameMonthCommand::class)
    ->dailyAt('00:00')->timezone('Asia/Tokyo')
    ->withoutOverlapping()
    ->runInBackground();
