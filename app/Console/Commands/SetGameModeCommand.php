<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GameSession;
use App\Services\AiColaborerService;
use Illuminate\Console\Command;

class SetGameModeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game:mode
                            {mode : The gameplay mode to configure: "solo" (100% Solo Pioneer) or "coop" (Team Co-op)}
                            {--session= : Optional specific GameSession code (e.g. nagoya-2026)}
                            {--all : Configure mode for all game sessions}';

    /**
     * The console command description.
     */
    protected $description = 'Configure the gameplay mode (solo pioneer or team co-op with AI co-laborers)';

    /**
     * Execute the console command.
     */
    public function handle(AiColaborerService $aiColaborerService): int
    {
        $mode = strtolower((string) $this->argument('mode'));

        if (! in_array($mode, ['solo', 'coop', 'team'], true)) {
            $this->components->error('Invalid mode. Please specify either "solo" or "coop".');

            return self::FAILURE;
        }

        $isCoop = in_array($mode, ['coop', 'team'], true);
        $sessionCode = $this->option('session');
        $all = (bool) $this->option('all');

        if ($all) {
            $sessions = GameSession::all();
        } elseif ($sessionCode) {
            $sessions = GameSession::where('code', $sessionCode)->get();
        } else {
            $activeSession = GameSession::where('is_active', true)->first()
                ?? GameSession::where('code', 'nagoya-2026')->first()
                ?? GameSession::first();

            $sessions = $activeSession ? collect([$activeSession]) : collect();
        }

        if ($sessions->isEmpty()) {
            $this->components->warn('No game sessions found to update.');

            return self::FAILURE;
        }

        foreach ($sessions as $session) {
            if ($isCoop) {
                $session->enableCoopMode();
                $aiColaborerService->ensureAiColaborers($session);

                $this->components->info("Updated {$session->name} [{$session->code}] to Team Co-op Mode.");
                $this->components->twoColumnDetail('Mode', 'Team Co-op (3 AI Co-laborers Active)');
                $this->components->twoColumnDetail('Colaborers', 'Sarah Jenkins, Marcus Silva, Kenji Yamada');
            } else {
                $session->enableSoloMode();

                $this->components->info("Updated {$session->name} [{$session->code}] to 100% Solo Pioneer Mode.");
                $this->components->twoColumnDetail('Mode', '100% Solo Pioneer (AI Co-laborers Paused)');
                $this->components->twoColumnDetail('Coverage Scope', 'All 16 wards pioneered solely by human player(s)');
            }
        }

        cache()->flush();

        return self::SUCCESS;
    }
}
