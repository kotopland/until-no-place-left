<?php

use App\Console\Commands\ProcessGameMonthCommand;
use App\Console\Commands\ProcessWeeklyTickCommand;
use App\Console\Commands\SendInactivityRemindersCommand;
use Illuminate\Support\Facades\Schedule;

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
Schedule::command(ProcessGameMonthCommand::class, ['--force'])
    ->dailyAt('00:00')
    ->timezone('Asia/Tokyo')
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Schedule the Nagoya Oikos inactivity reminder check.
 * Evaluates daily at 09:00 Tokyo time for players who forgot to play for 2 real-life days in a row.
 */
Schedule::command(SendInactivityRemindersCommand::class)
    ->dailyAt('09:00')
    ->timezone('Asia/Tokyo')
    ->withoutOverlapping()
    ->runInBackground();
