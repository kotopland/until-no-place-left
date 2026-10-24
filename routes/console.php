<?php

use App\Console\Commands\ProcessGameMonthCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Schedule the Nagoya Oikos monthly resolution engine.
 * Checks every minute and processes game sessions whose next_tick_at has expired.
 */
Schedule::command(ProcessGameMonthCommand::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
