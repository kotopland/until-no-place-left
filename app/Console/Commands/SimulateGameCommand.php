<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\Ward;
use App\Services\RealLifeSimulationService;
use App\Services\ShowcaseSandboxService;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

class SimulateGameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:simulate
                            {duration? : Target horizon: 3m, 6m, 1y, 2y, 5y, 10y, 15y, or "all" (default: all milestones)}
                            {--session= : Optional specific GameSession code (e.g. nagoya-2026)}
                            {--mode=coop : Gameplay mode to simulate: coop (Team Co-op) or solo (Solo Pioneer)}
                            {--seed=20260401 : Deterministic RNG seed for reproducible real-life runs}
                            {--no-reset : Continue from current session state instead of resetting to Month 1 Day 1}
                            {--force : Run without interactive prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate the Nagoya Oikos movement across 3 months, 6 months, 1 year, 2 years, 5 years, 10 years, and 15 years reflecting real-life Japanese ministry dynamics';

    /**
     * Execute the console command.
     */
    public function handle(RealLifeSimulationService $simulator): int
    {
        $session = $this->resolveOrSeedSession();

        $durationInput = $this->argument('duration');

        if ($durationInput === null && $this->input->isInteractive() && ! $this->option('no-interaction') && ! $this->option('force')) {
            $durationInput = select(
                label: 'Select the real-life simulation horizon to execute:',
                options: [
                    'all' => '🌏 Full Longitudinal Journey (3m → 6m → 1y → 2y → 5y → 10y → 15y Checkpoints)',
                    '3m' => '🌱 3 Months (Month 3 — Cultural Entry, Language Foundation & Soil Plowing)',
                    '6m' => '🌿 6 Months (Month 6 — Foundation Graduation, Sword DBS & First Breakthroughs)',
                    '1y' => '🌊 1 Year (Month 12 — First Japanese Water Baptisms & Oikos Ripples)',
                    '2y' => '👑 2 Years (Month 24 — Raising Local Timothys L2/L3 & First G3 Churches)',
                    '5y' => '🔥 5 Years (Month 60 — First Missionary Term, G4 Streams & Indigenous Roots)',
                    '10y' => '🏙️ 10 Years (Month 120 — City-Wide Indigenous Movement & Breaking Strongholds)',
                    '15y' => '🗾 15 Years (Month 180 — Long-Obedience Maturity: "Until No Place Left" in 16 Wards)',
                ],
                default: 'all',
            );
        }

        $targetMonths = $simulator->parseDuration($durationInput !== null ? (string) $durationInput : 'all');

        if ($targetMonths === null) {
            $this->components->error("Invalid simulation duration '{$durationInput}'. Supported values: 3m, 6m, 1y, 2y, 5y, 10y, 15y, or all.");

            return self::FAILURE;
        }

        $modeOption = strtolower((string) $this->option('mode'));
        $mode = in_array($modeOption, ['solo', 'pioneer'], true) ? 'solo' : 'coop';
        $resetFirst = ! (bool) $this->option('no-reset');
        $seedOption = $this->option('seed');
        $rngSeed = ($seedOption !== null && $seedOption !== '') ? (int) $seedOption : null;

        intro('🗾 NAGOYA OIKOS — REAL-LIFE LONGITUDINAL MOVEMENT SIMULATOR');

        $modeLabel = $mode === 'coop'
            ? '🤝 Team Co-op Mode (4 Computer Co-Laborers + Indigenous Japanese Leaders)'
            : '🏔️ Solo Pioneer Mode (Single Computer Pioneer + Indigenous Japanese Leaders)';

        $horizonLabel = ($durationInput === null || strtolower((string) $durationInput) === 'all')
            ? 'All 7 Horizons (3m, 6m, 1y, 2y, 5y, 10y, 15y — 180 Game Months)'
            : (RealLifeSimulationService::MILESTONES[$simulator->milestoneKeyForMonths($targetMonths) ?? '']['label'] ?? "{$targetMonths} Months")." ({$targetMonths} Game Months)";

        $this->components->twoColumnDetail('Active Session', "{$session->name} [{$session->code}]");
        $this->components->twoColumnDetail('Isolated Sandbox DB', basename(app(ShowcaseSandboxService::class)->getSandboxDatabasePath()).' (Live game DB untouched)');
        $this->components->twoColumnDetail('Target Horizon', $horizonLabel);
        $this->components->twoColumnDetail('Ministry Mode', $modeLabel);
        $this->components->twoColumnDetail('Cultural Pacing', 'Japanese Real-Life (4–9m Seeking • 6–15m Baptism Cost-Counting • Seasonal Tenkin)');

        $result = $simulator->simulate(
            session: $session,
            targetMonths: $targetMonths,
            mode: $mode,
            resetFirst: $resetFirst,
            rngSeed: $rngSeed,
        );

        $milestones = $result['milestones'];
        $finalSnapshot = $result['final_snapshot'];

        // 1. Render Milestone Checkpoints
        foreach ($milestones as $snapshot) {
            $this->renderMilestoneCard($snapshot);
        }

        // 2. Render Comparative Longitudinal Summary Table
        if (count($milestones) > 1) {
            $this->newLine();
            info('📊 REAL-LIFE LONGITUDINAL COMPARISON (3 MONTHS → 15 YEARS)');

            $rows = [];
            foreach ($milestones as $m) {
                $leaders = $m['leaders_by_tier'];
                $gens = $m['churches_by_generation'];
                $bsCount = $m['bible_study_groups_count'] ?? 0;
                $hcCount = $m['healthy_churches_count'] ?? 0;
                $disPct = $m['dissolvement_percent'] ?? 0;
                $rows[] = [
                    "{$m['label']} (M{$m['months']})",
                    $m['calendar_date'],
                    "{$m['wards_reached']}/{$m['total_wards']} ({$m['saturation_percent']}%)",
                    "{$m['yellow_seekers_count']}Y / {$m['red_acquaintances_count']}R",
                    (string) $m['green_believers_count'],
                    "{$m['baptized_count']} bap ({$m['counting_cost_count']} cost)",
                    "{$m['active_churches_count']} act ({$bsCount} BS / {$hcCount} Ch) • {$m['dissolved_churches_count']} dis ({$disPct}%)",
                    "G{$m['deepest_generation']} ({$gens[1]}/{$gens[2]}/{$gens[3]}/{$gens[4]})",
                    "L1:{$leaders['L1']} L2:{$leaders['L2']} L3:{$leaders['L3']} L4:{$leaders['L4']} L5:{$leaders['L5']}",
                ];
            }

            table(
                headers: [
                    'Horizon',
                    'Date',
                    'Wards Reached',
                    'Seekers (Y/R)',
                    'Believers',
                    'Baptisms',
                    'Churches',
                    'Gen (G1/G2/G3/G4+)',
                    'Indigenous Leaders (L1–L5)',
                ],
                rows: $rows,
            );
        }

        // 3. Render Final Ward Saturation Summary
        $this->renderFinalWardSummary($session, $finalSnapshot);

        cache()->flush();

        outro("✅ Real-life simulation completed through {$finalSnapshot['label']} (Month {$finalSnapshot['months']} — {$finalSnapshot['calendar_date']}).");

        return self::SUCCESS;
    }

    protected function resolveOrSeedSession(): GameSession
    {
        return app(ShowcaseSandboxService::class)->activate();
    }

    /**
     * Render a detailed real-life milestone checkpoint card.
     *
     * @param  array<string, mixed>  $snapshot
     */
    protected function renderMilestoneCard(array $snapshot): void
    {
        $this->newLine();
        $this->components->info("⏱️  MILESTONE: {$snapshot['label']} (Month {$snapshot['months']} • {$snapshot['calendar_date']}) — {$snapshot['phase']}");
        $this->line("   <fg=gray>{$snapshot['reflection']}</>");

        $leaders = $snapshot['leaders_by_tier'];
        $gens = $snapshot['churches_by_generation'];

        $this->components->twoColumnDetail(
            'Wards Reached with Simple Church',
            "{$snapshot['wards_reached']} / {$snapshot['total_wards']} wards ({$snapshot['saturation_percent']}%)"
        );
        $this->components->twoColumnDetail(
            'Spiritual Soil & Seekers',
            "{$snapshot['yellow_seekers_count']} Yellow Seekers • {$snapshot['red_acquaintances_count']} Red Acquaintances • {$snapshot['prayer_walks_count']} Prayer Walks"
        );
        $this->components->twoColumnDetail(
            'Saved Believers & Water Baptisms',
            "{$snapshot['green_believers_count']} Believers ({$snapshot['baptized_count']} Baptized in Water • {$snapshot['counting_cost_count']} Counting the Cost with Family)"
        );
        $bsCount = $snapshot['bible_study_groups_count'] ?? 0;
        $bsPct = $snapshot['bible_study_percent'] ?? 0;
        $disPct = $snapshot['dissolvement_percent'] ?? 0;
        $mergedCount = $snapshot['merged_groups_count'] ?? 0;

        $this->components->twoColumnDetail(
            'Bible Studies & Acts 2 Churches',
            "{$snapshot['active_churches_count']} Active ({$bsCount} Bible Studies [{$bsPct}%] • {$snapshot['healthy_churches_count']} Healthy Churches) • {$snapshot['dissolved_churches_count']} Dissolved ({$disPct}% — {$mergedCount} Merged)"
        );
        $this->components->twoColumnDetail(
            'Generational Depth',
            "Deepest G{$snapshot['deepest_generation']} [G1: {$gens[1]} | G2: {$gens[2]} | G3: {$gens[3]} | G4+: {$gens[4]}]"
        );
        $this->components->twoColumnDetail(
            '5 Levels of Indigenous Leadership',
            "L1 Sowers: {$leaders['L1']} | L2 Planters: {$leaders['L2']} | L3 Multipliers: {$leaders['L3']} | L4 Trainers: {$leaders['L4']} | L5 Coordinators: {$leaders['L5']}"
        );
    }

    /**
     * Render final ward-by-ward saturation overview.
     *
     * @param  array<string, mixed>  $finalSnapshot
     */
    protected function renderFinalWardSummary(GameSession $session, array $finalSnapshot): void
    {
        $wards = Ward::orderBy('id')->get();
        if ($wards->isEmpty()) {
            return;
        }

        $this->newLine();
        info("🗾 NAGOYA 16-WARD SATURATION AT {$finalSnapshot['label']} ({$finalSnapshot['wards_reached']}/{$finalSnapshot['total_wards']} WARDS REACHED)");

        $rows = [];
        foreach ($wards as $ward) {
            $activeChurches = Church::where('game_session_id', $session->id)
                ->where('ward_id', $ward->id)
                ->where('is_dissolved', false)
                ->get();

            $count = $activeChurches->count();
            $maxGen = $activeChurches->max('generation');

            $rows[] = [
                "{$ward->name} ({$ward->name_ja})",
                "{$ward->spiritual_openness}%",
                $count > 0 ? "✅ {$count} church(es)" : '⏳ Plowing / Unreached',
                $maxGen ? "G{$maxGen}" : '—',
            ];
        }

        table(['Ward', 'Openness', 'Church Presence', 'Deepest Gen'], $rows);
    }
}
