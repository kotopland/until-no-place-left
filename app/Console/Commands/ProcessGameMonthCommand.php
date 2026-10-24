<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGameMonthJob;
use App\Models\GameSession;
use App\Services\FourFieldsEngine;
use Illuminate\Console\Attributes\Command;
use Illuminate\Console\Command as BaseCommand;

#[Command(
    name: 'game:process-month',
    description: 'Process the scheduled Game Month resolution tick across Nagoya multiplayer sessions'
)]
class ProcessGameMonthCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game:process-month 
                            {--session= : Optional specific GameSession code (e.g. nagoya-2026)}
                            {--force : Force resolution regardless of next_tick_at timestamp}
                            {--queue : Dispatch as background queue job instead of running synchronously}';

    /**
     * Execute the console command.
     */
    public function handle(FourFieldsEngine $engine): int
    {
        $sessionCode = $this->option('session');
        $force = (bool) $this->option('force');
        $useQueue = (bool) $this->option('queue');

        $query = GameSession::where('is_active', true);

        if ($sessionCode) {
            $query->where('code', $sessionCode);
        }

        $sessions = $query->get();

        if ($sessions->isEmpty()) {
            $this->warn('No active game sessions found matching criteria.');
            return self::SUCCESS;
        }

        $processedCount = 0;

        foreach ($sessions as $session) {
            if (! $force && ! $session->isTickOverdue) {
                $this->info("Session [{$session->code}] Month {$session->current_game_month} is not due yet ({$session->secondsUntilNextTick}s remaining). Use --force to override.");
                continue;
            }

            $this->components->info("Processing Month {$session->current_game_month} for session: {$session->name} [{$session->code}]...");

            if ($useQueue) {
                ProcessGameMonthJob::dispatch($session);
                $this->components->twoColumnDetail("Dispatched to queue", "game-engine");
            } else {
                $job = new ProcessGameMonthJob($session);
                $job->handle($engine);
                $this->components->twoColumnDetail("Status", "<fg=green;options=bold>RESOLVED</>");
            }

            $processedCount++;
        }

        $this->components->info("Processed {$processedCount} game session tick(s).");

        return self::SUCCESS;
    }
}
