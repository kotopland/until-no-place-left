<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ReceptivityStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use App\Services\AiColaborerService;
use App\Services\FourFieldsEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class GameCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game
                            {action? : Optional direct action (status, mode, simulate, advance-week, advance-day, advance-month, reset, migrate-fresh, borders)}
                            {param? : Optional secondary parameter (e.g. "solo" or "coop" for mode, or "3m", "6m", "1y", "2y", "5y", "10y", "15y", "all" for simulate)}
                            {--session= : Optional specific GameSession code (e.g. nagoya-2026)}
                            {--force : Bypass confirmation prompts where applicable}';

    /**
     * The console command description.
     */
    protected $description = 'Interactive operator control dashboard and settings manager for Nagoya Oikos';

    /**
     * Execute the console command.
     */
    public function handle(FourFieldsEngine $engine, AiColaborerService $aiService): int
    {
        $session = $this->resolveSession();
        if (! $session) {
            $this->components->error('No active Game Session found. Please run database seeding first.');

            return self::FAILURE;
        }

        $directAction = $this->argument('action');

        if ($directAction) {
            return $this->handleDirectAction((string) $directAction, $session, $engine, $aiService);
        }

        // Interactive Operator Dashboard Menu
        intro('🗾 NAGOYA OIKOS — OPERATOR COMMAND CENTER');

        $running = true;
        while ($running) {
            $session->refresh();
            $this->displaySessionHeader($session);

            $choice = select(
                label: 'What would you like to configure or execute?',
                options: [
                    'status' => '📊 View Detailed Session & Ward Saturation',
                    'mode' => '🎮 Configure Gameplay Mode (Solo Pioneer vs Team Co-op)',
                    'simulate' => '⏱️ Simulate Real-Life Horizons (3m, 6m, 1y, 2y, 5y, 10y, 15y)',
                    'advance-week' => '⏩ Advance 1 Game Week (Weekly 15-Min Tick)',
                    'advance-month' => '📅 Advance Game Month (Batch Resolve Month)',
                    'reset' => '🔄 Reset Simulation Timeline (Month 1 Week 1)',
                    'borders' => '🗾 Sync Authentic Nagoya 16-Ward GIS Boundaries',
                    'migrate-fresh' => '🚨 Production Fresh Migration & Seeding (Dangerous)',
                    'exit' => '🚪 Exit Operator Menu',
                ],
                default: 'status',
            );

            switch ($choice) {
                case 'status':
                    $this->showDetailedStatus($session);
                    break;
                case 'mode':
                    $this->configureMode($session, $aiService);
                    break;
                case 'simulate':
                    $this->runHorizonSimulation($session);
                    break;
                case 'advance-week':
                case 'advance-day':
                    $this->advanceWeek($session, $engine);
                    break;
                case 'advance-month':
                    $this->advanceMonth($session, $engine);
                    break;
                case 'reset':
                    $this->resetSimulation($session);
                    break;
                case 'borders':
                    $this->syncBorders();
                    break;
                case 'migrate-fresh':
                    $this->executeProductionMigrateFresh();
                    $session = $this->resolveSession() ?? $session;
                    break;
                case 'exit':
                    $running = false;
                    break;
            }

            if ($running) {
                info(''); // Visual spacing
            }
        }

        outro('Leaving Nagoya Oikos Operator Command Center. Grace and peace!');

        return self::SUCCESS;
    }

    /**
     * Handle non-interactive direct invocation (e.g. php artisan game status)
     */
    protected function handleDirectAction(string $action, GameSession $session, FourFieldsEngine $engine, AiColaborerService $aiService): int
    {
        return match ($action) {
            'status' => $this->showDetailedStatus($session),
            'mode' => $this->handleDirectMode($session, $aiService),
            'simulate' => $this->runHorizonSimulation($session, (string) ($this->argument('param') ?: 'all')),
            'advance-week', 'advance-day' => $this->advanceWeek($session, $engine),
            'advance-month' => $this->advanceMonth($session, $engine),
            'reset' => $this->resetSimulation($session),
            'borders' => $this->syncBorders(),
            'migrate-fresh' => $this->executeProductionMigrateFresh(),
            default => $this->unknownAction($action),
        };
    }

    protected function resolveSession(): ?GameSession
    {
        $code = $this->option('session');

        if ($code) {
            return GameSession::where('code', $code)->first();
        }

        return GameSession::where('is_active', true)->first()
            ?? GameSession::where('code', 'nagoya-2026')->first()
            ?? GameSession::first();
    }

    protected function displaySessionHeader(GameSession $session): void
    {
        $modeLabel = $session->isCoopMode
            ? '🤝 Team Co-op Mode (3 AI Co-laborers Active)'
            : '🏔️ 100% Solo Pioneer Mode (Human Player Only)';

        $onlinePlayersCount = User::where('current_game_session_id', $session->id)
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subMinutes(15))
            ->count();

        $totalPlayersCount = User::where('current_game_session_id', $session->id)
            ->where('is_ai_colaborer', false)
            ->count();

        table(
            headers: ['Session', 'Timeline', 'Mode', 'Players Online', 'Days Processed'],
            rows: [
                [
                    "{$session->name} [{$session->code}]",
                    "Month {$session->current_game_month} • Day {$session->current_game_day}/30",
                    $modeLabel,
                    "{$onlinePlayersCount} online ({$totalPlayersCount} total registered)",
                    "{$session->days_processed_this_month}/30 days this month",
                ],
            ]
        );
    }

    protected function showDetailedStatus(GameSession $session): int
    {
        $totalWards = Ward::count();
        $reachedWardsCount = Church::where('game_session_id', $session->id)
            ->distinct('ward_id')
            ->count('ward_id');

        $totalBelievers = Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->count();

        $totalChurches = Church::where('game_session_id', $session->id)->count();
        $deepestGen = Church::where('game_session_id', $session->id)->max('generation') ?? 1;

        $saturationPercent = $totalWards > 0
            ? (int) round(($reachedWardsCount / $totalWards) * 100)
            : 0;

        $this->components->twoColumnDetail('Active Session', "{$session->name} ({$session->code})");
        $this->components->twoColumnDetail('Calendar Date', $session->calendar_date->format('F Y'));
        $this->components->twoColumnDetail('Current Game Month', (string) $session->current_game_month);
        $this->components->twoColumnDetail('Current Game Day', "Day {$session->current_game_day} of 30");
        $this->components->twoColumnDetail('Session Gameplay Mode', $session->isCoopMode ? 'Team Co-op Mode' : '100% Solo Pioneer Mode');
        $this->components->twoColumnDetail('Wards Reached with Church', "{$reachedWardsCount} / {$totalWards} wards ({$saturationPercent}%)");
        $this->components->twoColumnDetail('Total Green Believers', (string) $totalBelievers);
        $this->components->twoColumnDetail('Total Reproducing Churches', (string) $totalChurches);
        $this->components->twoColumnDetail('Deepest Church Generation', "G{$deepestGen}");

        // Top 5 Wards by Openness
        $topWards = Ward::orderByDesc('spiritual_openness')->take(5)->get();
        if ($topWards->isNotEmpty()) {
            $rows = $topWards->map(fn (Ward $w) => [
                $w->name,
                $w->name_ja,
                "{$w->spiritual_openness}%",
                number_format($w->population),
                Church::where('game_session_id', $session->id)->where('ward_id', $w->id)->count().' churches',
            ])->all();

            info('Top 5 Open Wards:');
            table(['Ward (EN)', 'Ward (JA)', 'Spiritual Openness', 'Population', 'Churches Planted'], $rows);
        }

        return self::SUCCESS;
    }

    protected function configureMode(GameSession $session, AiColaborerService $aiService): int
    {
        $current = $session->isCoopMode ? 'coop' : 'solo';

        $modeChoice = select(
            label: 'Choose Gameplay Mode for this session:',
            options: [
                'coop' => '🤝 Team Co-op Mode (Deploy Sarah Jenkins, Marcus Silva, Kenji Yamada)',
                'solo' => '🏔️ 100% Solo Pioneer Mode (Pause AI co-laborers; human players only)',
            ],
            default: $current,
        );

        if ($modeChoice === 'coop') {
            $session->enableCoopMode();
            $aiService->ensureAiColaborers($session);
            $this->components->info('Switched to Team Co-op Mode. 3 automated missionary teammates are active.');
        } else {
            $session->enableSoloMode();
            $this->components->info('Switched to 100% Solo Pioneer Mode. AI co-laborers paused; all 16 wards open for solo pioneering.');
        }

        cache()->flush();

        return self::SUCCESS;
    }

    protected function handleDirectMode(GameSession $session, AiColaborerService $aiService): int
    {
        $param = strtolower((string) $this->argument('param'));

        if (! in_array($param, ['solo', 'coop', 'team'], true)) {
            $this->components->error('Please specify mode as "solo" or "coop" (e.g. php artisan game mode solo).');

            return self::FAILURE;
        }

        if (in_array($param, ['coop', 'team'], true)) {
            $session->enableCoopMode();
            $aiService->ensureAiColaborers($session);
            $this->components->info("Session [{$session->code}] set to Team Co-op Mode.");
        } else {
            $session->enableSoloMode();
            $this->components->info("Session [{$session->code}] set to 100% Solo Pioneer Mode.");
        }

        cache()->flush();

        return self::SUCCESS;
    }

    protected function advanceWeek(GameSession $session, FourFieldsEngine $engine): int
    {
        $currentWeek = $session->current_game_week ?: 1;
        $confirmed = $this->option('force') || confirm(
            label: "Advance session timeline by 1 Game Week (Week {$currentWeek}/4 -> ".min(4, $currentWeek + 1).'/4)?',
            default: true,
        );

        if (! $confirmed) {
            $this->components->info('Week advance cancelled.');

            return self::SUCCESS;
        }

        $result = $engine->resolveWeeklyTick($session, force: true);

        if ($result) {
            $label = $this->argument('action') === 'advance-day'
                ? "Day {$result->gameDay} (Week {$result->gameWeek}/4, Month {$result->gameMonth})"
                : "Week {$result->gameWeek}/4 (Month {$result->gameMonth})";
            $this->components->info("✅ Successfully processed {$label}!");
            $this->components->twoColumnDetail('Actions Processed', (string) $result->actionsProcessed);
            $this->components->twoColumnDetail('Online Players Updated', (string) $result->onlinePlayersCount);
        } else {
            $this->components->warn('Weekly tick could not be processed (4-week month limit reached or session inactive).');
        }

        cache()->flush();

        return self::SUCCESS;
    }

    protected function advanceDay(GameSession $session, FourFieldsEngine $engine): int
    {
        return $this->advanceWeek($session, $engine);
    }

    protected function advanceMonth(GameSession $session, FourFieldsEngine $engine): int
    {
        $confirmed = $this->option('force') || confirm(
            label: "Advance Game Month for [{$session->name}] (Month {$session->current_game_month} -> ".($session->current_game_month + 1).')? This will batch-resolve all remaining unplayed days in the 30-day month.',
            default: false,
        );

        if (! $confirmed) {
            $this->components->info('Month advance cancelled.');

            return self::SUCCESS;
        }

        $result = $engine->resolveGameMonth($session);

        $this->components->info("✅ Successfully resolved Month {$result->gameMonth}! Advanced to Month ".($result->gameMonth + 1).'.');
        $this->components->twoColumnDetail('Actions Resolved', (string) $result->actionsProcessed);
        $this->components->twoColumnDetail('New Believers Harvested', (string) $result->newBelieversCount);
        $this->components->twoColumnDetail('Autonomous Disciples Multiplied', (string) $result->autonomousMultiplications);
        $this->components->twoColumnDetail('Churches Planted', (string) $result->churchesPlanted);

        cache()->flush();

        return self::SUCCESS;
    }

    protected function resetSimulation(GameSession $session): int
    {
        $modeChoice = select(
            label: 'Choose Reset Level for game simulation:',
            options: [
                'soft' => '🌱 Reset Timeline Only (Month 1 Day 1, restore 12 AP, keep player accounts)',
                'wipe' => '🧹 Reset Timeline & Wipe Players (Clear non-coach accounts, re-seed from scratch)',
                'cancel' => '❌ Cancel',
            ],
            default: 'cancel',
        );

        if ($modeChoice === 'cancel') {
            $this->components->info('Reset cancelled.');

            return self::SUCCESS;
        }

        $wipePlayers = ($modeChoice === 'wipe');

        $confirmed = $this->option('force') || confirm(
            label: $wipePlayers
                ? "Are you sure you want to WIPE player accounts and reset [{$session->code}] back to Month 1 Day 1?"
                : "Reset [{$session->code}] timeline back to Month 1 Day 1?",
            default: false,
        );

        if (! $confirmed) {
            $this->components->info('Reset cancelled.');

            return self::SUCCESS;
        }

        $exitCode = Artisan::call('game:reset', [
            '--session' => $session->code,
            '--wipe-players' => $wipePlayers,
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        return $exitCode;
    }

    protected function syncBorders(): int
    {
        $this->components->info('Syncing authentic Nagoya 16-ward GIS boundaries...');
        $exitCode = Artisan::call('nagoya:import-borders', ['--force' => true]);
        $this->line(Artisan::output());

        return $exitCode;
    }

    protected function runHorizonSimulation(GameSession $session, ?string $directDuration = null): int
    {
        $duration = $directDuration ?: select(
            label: 'Select real-life horizon to simulate:',
            options: [
                'all' => '🌏 All 7 Horizons (3m, 6m, 1y, 2y, 5y, 10y, 15y)',
                '3m' => '🌱 3 Months (Cultural Entry & Plowing)',
                '6m' => '🌿 6 Months (Foundation Graduation & First Breakthroughs)',
                '1y' => '🌊 1 Year (First Japanese Baptisms & Oikos Ripples)',
                '2y' => '👑 2 Years (Raising Local Timothys & G3 Churches)',
                '5y' => '🔥 5 Years (First Missionary Term & G4 Streams)',
                '10y' => '🏙️ 10 Years (City-Wide Indigenous Movement)',
                '15y' => '🗾 15 Years ("Until No Place Left" in 16 Wards)',
            ],
            default: 'all',
        );

        $exitCode = Artisan::call('game:simulate', [
            'duration' => $duration,
            '--session' => $session->code,
            '--mode' => $session->isCoopMode ? 'coop' : 'solo',
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        return $exitCode;
    }

    /**
     * Production Database Fresh Migration & Seeding with strong safety confirmation
     */
    protected function executeProductionMigrateFresh(): int
    {
        $appEnv = app()->environment();

        warning('🚨 CRITICAL WARNING: PRODUCTION DATABASE WIPE & RE-SEED 🚨');
        $this->components->warn('----------------------------------------------------------------------');
        $this->components->warn("CURRENT ENVIRONMENT: [{$appEnv}]");
        $this->components->warn('Executing `migrate:fresh --seed` will:');
        $this->components->warn(' • DROP ALL EXISTING TABLES AND SCHEMAS in the database.');
        $this->components->warn(' • PERMANENTLY ERASE all player records, login credentials, and history.');
        $this->components->warn(' • Re-create all tables from migration files and run NagoyaOikosSeeder.');
        $this->components->warn('THIS ACTION IS PERMANENT AND CANNOT BE UNDONE!');
        $this->components->warn('----------------------------------------------------------------------');

        if (! $this->option('force')) {
            $verificationPhrase = text(
                label: "To execute this wipe, type the exact confirmation phrase 'WIPE-DATABASE':",
                placeholder: 'WIPE-DATABASE',
                required: true,
                validate: fn (string $val) => $val === 'WIPE-DATABASE' ? null : 'Confirmation phrase does not match. Operation will be cancelled.',
            );

            if ($verificationPhrase !== 'WIPE-DATABASE') {
                error('Confirmation phrase mismatch. Production database wipe cancelled.');

                return self::FAILURE;
            }

            $doubleCheck = confirm(
                label: 'FINAL SAFETY CONFIRMATION: Are you 100% certain you wish to proceed?',
                default: false,
            );

            if (! $doubleCheck) {
                $this->components->info('Production database wipe cancelled by operator.');

                return self::SUCCESS;
            }
        }

        $this->components->info('Running `php artisan migrate:fresh --seed --force`...');

        if (app()->runningUnitTests()) {
            $this->components->info('Test environment detected: simulated migrate:fresh execution.');

            return self::SUCCESS;
        }

        $exitCode = Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        if ($exitCode === 0) {
            $this->components->info('Re-syncing authentic Nagoya GIS boundaries...');
            Artisan::call('nagoya:import-borders', ['--force' => true]);
            $this->line(Artisan::output());

            cache()->flush();

            info('🎉 Database successfully refreshed and seeded with Nagoya Oikos dataset!');
        } else {
            error('Database migration/seed encountered an error. Check logs.');
        }

        return $exitCode;
    }

    protected function unknownAction(string $action): int
    {
        $this->components->error("Unknown action: '{$action}'.");
        $this->components->info('Available direct actions: status, mode, simulate, advance-day, advance-month, reset, borders, migrate-fresh');

        return self::FAILURE;
    }
}
