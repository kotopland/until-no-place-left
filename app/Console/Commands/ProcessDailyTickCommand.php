<?php

namespace App\Console\Commands;

use App\Models\GameSession;
use App\Services\FourFieldsEngine;
use Illuminate\Console\Attributes\Command;
use Illuminate\Console\Command as BaseCommand;

#[Command(
    name: 'game:process-daily-tick',
    description: 'Process 10-minute game day ticks for active sessions with online players'
)]
class ProcessDailyTickCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game:process-daily-tick
                            {--session= : Optional specific GameSession code (e.g. nagoya-2026)}
                            {--force : Force daily tick regardless of 10-minute countdown or online status}';

    /**
     * Execute the console command.
     */
    public function handle(FourFieldsEngine $engine): int
    {
        $sessionCode = $this->option('session');
        $force = (bool) $this->option('force');

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
            if (! $force && ! $session->isDailyTickDue) {
                continue;
            }

            if (! $force && ! $session->hasOnlineActiveUsers(15)) {
                continue;
            }

            $this->components->info("Processing Day {$session->current_game_day}/30 (Month {$session->current_game_month}) for session: {$session->name} [{$session->code}]...");

            $result = $engine->resolveDailyTick($session, force: $force);

            if ($result) {
                $this->components->twoColumnDetail('Status', '<fg=green;options=bold>DAY RESOLVED</>');
                $this->components->twoColumnDetail('Actions Processed', (string) $result->actionsProcessed);
                $this->components->twoColumnDetail('Active Online Players', (string) $result->onlinePlayersCount);
                $processedCount++;
            } else {
                $this->components->twoColumnDetail('Status', '<fg=yellow>SKIPPED</> (No eligible online players or month limit reached)');
            }
        }

        if ($processedCount > 0) {
            $this->components->info("Processed daily tick for {$processedCount} game session(s).");
        }

        return self::SUCCESS;
    }
}
