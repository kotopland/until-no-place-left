<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BaptismStatus;
use App\Enums\ChurchElement;
use App\Enums\ContributionRole;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Enums\RelationshipType;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\MovementCelebration;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\OikosRelationship;
use App\Models\PrayerWalk;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\NagoyaOikosSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RealLifeSimulationService
{
    /**
     * Canonical real-life simulation horizons requested for longitudinal evaluation.
     *
     * @var array<string, array{months: int, label: string, phase: string, reflection: string}>
     */
    public const array MILESTONES = [
        '3m' => [
            'months' => 3,
            'label' => '3 Months',
            'phase' => 'Phase 1: Cultural Entry, Language Foundation & Soil Plowing',
            'reflection' => 'In the first 3 months in Japan, 70% of missionary bandwidth is invested in Japanese language school and L1 foundation. Field ministry focuses on prayer walking, building initial Shinrai (trust) over tea, and equipping existing believer Daisuke Kato in 411. Japanese seekers are still early in their multi-month seeking journey, so zero new Japanese water baptisms have occurred yet.',
        ],
        '6m' => [
            'months' => 6,
            'label' => '6 Months',
            'phase' => 'Phase 2: Foundation Graduation, Discovery Bible Study & First Breakthroughs',
            'reflection' => 'Graduating the 4-month Language & L1 Foundation restores full weekly field bandwidth. After 4–6 months of patient Sword Discovery Bible Studies and 3 Circles conversations, the first Persons of Peace and seekers pray to receive Christ! New Japanese believers now enter a 6-to-15-month family cost-counting journey toward water baptism, and a first G2 household circle begins gathering.',
        ],
        '1y' => [
            'months' => 12,
            'label' => '1 Year',
            'phase' => 'Phase 3: First Japanese Water Baptisms, Oikos Ripples & First Church Graduations',
            'reflection' => 'At the 1-year mark, Japanese believers who accepted Christ around Months 4–6 complete 6+ months of navigating Buddhist family altar traditions and celebrate courageous water baptisms! Coached and 411-trained Discovery Bible Study groups that have gathered for 6–12 months graduate with high probability into full 9-element Acts 2 Churches, while new household Bible studies multiply and consolidate (~40% merging/attrition rate).',
        ],
        '2y' => [
            'months' => 24,
            'label' => '2 Years',
            'phase' => 'Phase 4: Raising Local Timothys (L2/L3) & Bible Study Pipeline Multiplication',
            'reflection' => 'Year 2 shifts from missionary-centric sowing to 2 Timothy 2:2 MAWL mentoring. Local Japanese believers mature into L2 Church Planters and the first L3 Multiplier through weekly Commands of Christ and quarterly Iron-on-Iron coaching. Across G1–G3, developing Bible study groups outnumber churches (52–60% Bible studies per generation), with coached 6–12 month Bible studies maturing into churches and smaller studies merging together.',
        ],
        '5y' => [
            'months' => 60,
            'label' => '5 Years',
            'phase' => 'Phase 5: First Missionary Term Completed — G4 Streams & Indigenous Leadership',
            'reflection' => 'Completing a full 5-year missionary term in Nagoya, the movement transitions to indigenous Japanese leadership. Local L3 Multipliers and emerging L4 Movement Trainers coach developing Bible studies across 8–11 wards into 4th-generation (G4) Acts 2 church streams, maintaining a healthy 52–60% Bible study pipeline in every generation while ~40% of groups naturally merge or dissolve through seasonal tenkin relocations.',
        ],
        '10y' => [
            'months' => 120,
            'label' => '10 Years',
            'phase' => 'Phase 6: Decade of Faithful Presence — City-Wide Indigenous Movement',
            'reflection' => 'A decade of long obedience in the same direction bears deep fruit across Nagoya. Japanese L4 Movement Trainers and the first L5 Strategy Coordinator sustain ~60 active groups across G1–G6 (with more Bible study groups than churches in every generation) while ~40% of groups over the decade have naturally merged into sister fellowships or dissolved through life transitions.',
        ],
        '15y' => [
            'months' => 180,
            'label' => '15 Years',
            'phase' => 'Phase 7: Long-Obedience Maturity — "Until No Place Left" Across All 16 Wards',
            'reflection' => 'After 15 years (180 months) of persevering prayer, costly family baptisms, sacrificial mercy care, and multi-generational Timothy multiplication, indigenous Discovery Bible Studies and Acts 2 simple churches are thriving across all 16 wards of Nagoya (100% ward saturation, G6–G7 depth), led entirely by Japanese L1–L5 servant leaders (MAWL "Leave" phase fulfilled).',
        ],
    ];

    /**
     * Concentric ward expansion order from Meito-ku / initial hubs out to traditional strongholds.
     *
     * @var list<string>
     */
    protected const array WARD_EXPANSION_ORDER = [
        'meito-ku',
        'naka-ku',
        'chikusa-ku',
        'tempaku-ku',
        'minato-ku',
        'showa-ku',
        'nakamura-ku',
        'moriyama-ku',
        'mizuho-ku',
        'higashi-ku',
        'midori-ku',
        'nishi-ku',
        'kita-ku',
        'nakagawa-ku',
        'minami-ku',
        'atsuta-ku', // Ancient Atsuta Jingu shrine stronghold — hardest soil, reached last
    ];

    /**
     * Minimum game month at which each ward becomes eligible for indigenous church planting expansion.
     *
     * @var array<string, int>
     */
    protected const array WARD_UNLOCK_MONTH = [
        'meito-ku' => 1,
        'naka-ku' => 5,
        'chikusa-ku' => 8,
        'tempaku-ku' => 11,
        'minato-ku' => 15,
        'showa-ku' => 20,
        'nakamura-ku' => 26,
        'moriyama-ku' => 33,
        'mizuho-ku' => 40,
        'higashi-ku' => 48,
        'midori-ku' => 56,
        'nishi-ku' => 68,
        'kita-ku' => 82,
        'nakagawa-ku' => 96,
        'minami-ku' => 112,
        'atsuta-ku' => 138,
    ];

    protected int $cumulativeAttritionEvents = 0;

    protected int $cumulativeRevitalizations = 0;

    protected int $cumulativeMergers = 0;

    protected int $spawnCounter = 0;

    public function __construct(
        protected FourFieldsEngine $engine,
        protected AiColaborerService $aiService,
    ) {}

    /**
     * Parse human-friendly duration string into target game months.
     * Returns null if input is unrecognized.
     */
    public function parseDuration(?string $duration): ?int
    {
        if ($duration === null || trim($duration) === '' || strtolower(trim($duration)) === 'all') {
            return 180;
        }

        $normalized = strtolower(trim($duration));
        $normalized = (string) preg_replace('/[\s_]+/', '-', $normalized);

        return match ($normalized) {
            '3m', '3mo', '3-month', '3-months', '3months', '3' => 3,
            '6m', '6mo', '6-month', '6-months', '6months', '6' => 6,
            '1y', '1yr', '1-year', '1-years', '1year', '12m', '12-months', '12' => 12,
            '2y', '2yr', '2-year', '2-years', '2years', '24m', '24-months', '24' => 24,
            '5y', '5yr', '5-year', '5-years', '5years', '60m', '60-months', '60' => 60,
            '10y', '10yr', '10-year', '10-years', '10years', '120m', '120-months', '120' => 120,
            '15y', '15yr', '15-year', '15-years', '15years', '180m', '180-months', '180' => 180,
            default => null,
        };
    }

    /**
     * Get the canonical milestone key for a given month count.
     */
    public function milestoneKeyForMonths(int $months): ?string
    {
        foreach (self::MILESTONES as $key => $meta) {
            if ($meta['months'] === $months) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Reset session to clean Month 1 Day 1 baseline inside the isolated temporary SQLite database.
     * Ensures ONLY computer players (is_ai_colaborer = true) exist in the simulation.
     */
    public function resetSessionToBaseline(GameSession $session, string $mode = 'coop'): void
    {
        $sandbox = app(ShowcaseSandboxService::class);
        if (! $sandbox->isActive()) {
            $session = $sandbox->activate();
        } else {
            $session = GameSession::where('code', $session->code)->first()
                ?? GameSession::first()
                ?? $session;
        }

        DB::transaction(function () use ($session, $mode): void {
            QueuedMonthlyAction::where('game_session_id', $session->id)->delete();
            MonthlyReport::where('game_session_id', $session->id)->delete();
            PrayerWalk::where('game_session_id', $session->id)->delete();
            MovementCelebration::where('game_session_id', $session->id)->delete();

            NpcContribution::where('game_session_id', $session->id)->delete();

            OikosRelationship::where('game_session_id', $session->id)->delete();

            Church::where('game_session_id', $session->id)->update(['parent_church_id' => null]);
            Church::where('game_session_id', $session->id)->delete();

            Npc::where('game_session_id', $session->id)->delete();

            $session->update([
                'current_game_month' => 1,
                'current_game_week' => 1,
                'weeks_processed_this_month' => 0,
                'current_game_day' => 1,
                'days_processed_this_month' => 0,
                'calendar_date' => '2026-04-01',
                'month_duration_minutes' => 720,
                'next_tick_at' => now()->addMinutes(15),
                'next_weekly_tick_at' => now()->addMinutes(15),
                'next_daily_tick_at' => now()->addMinutes(15),
                'last_weekly_tick_at' => null,
                'last_daily_tick_at' => null,
                'is_active' => true,
            ]);

            if ($mode === 'solo') {
                $session->enableSoloMode();
            } else {
                $session->enableCoopMode();
            }

            (new NagoyaOikosSeeder)->run();

            $meito = Ward::where('slug', 'meito-ku')->first() ?? Ward::first();
            $naka = Ward::where('slug', 'naka-ku')->first() ?? $meito;

            // Ensure 4th computer co-laborer (Yuki Takahashi) exists alongside Kenji, Sarah, and Marcus
            User::updateOrCreate(
                ['email' => 'yuki.takahashi@nagoyaharvest.jp'],
                [
                    'name' => 'Yuki Takahashi',
                    'gender' => 'female',
                    'age' => 31,
                    'nationality' => 'Japanese',
                    'password' => Hash::make('password123'),
                    'current_game_session_id' => $session->id,
                    'home_ward_id' => $naka?->id,
                    'is_ai_colaborer' => true,
                    'colaborer_archetype' => 'campus_sower',
                    'ai_strategy_notes' => 'Urban Oikos multiplier in Sakae & Osu. Connects young professionals and shopkeepers through Discovery Bible Studies.',
                    'action_points' => 20,
                    'max_action_points' => 26,
                    'weekly_action_points' => 20,
                    'daily_action_points' => 20,
                    'total_generational_score' => 85,
                    'coop_synergy_score' => 30,
                    'badges' => ['Oikos Connector', 'Prayer Walker'],
                    'notify_email' => false,
                    'notify_in_app' => true,
                ]
            );

            // Lead computer pioneer is Kenji Yamada (Meito-ku)
            $leadComputerPioneer = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->first()
                ?? User::where('is_ai_colaborer', true)->first();

            if ($leadComputerPioneer) {
                Npc::where('game_session_id', $session->id)
                    ->whereNotNull('discipler_user_id')
                    ->update(['discipler_user_id' => $leadComputerPioneer->id]);

                Church::where('game_session_id', $session->id)
                    ->update([
                        'leader_user_id' => $leadComputerPioneer->id,
                        'started_by_name' => $leadComputerPioneer->name,
                        'is_healthy_church' => false,
                        'health_score' => 70,
                        'founded_game_month' => 1,
                        'last_coached_game_month' => 1,
                        'elements_json' => [
                            ChurchElement::RepentanceBelief->value,
                            ChurchElement::Baptism->value,
                            ChurchElement::Word->value,
                            ChurchElement::Prayer->value,
                            ChurchElement::Fellowship->value,
                            ChurchElement::MakingDisciples->value,
                        ],
                    ]);
            }

            // Remove all human (non-computer) players from the isolated simulation database
            User::where('is_ai_colaborer', false)->delete();

            // Reset all computer players in the isolated simulation to clean Month 1 baseline
            User::where('is_ai_colaborer', true)->each(function (User $aiUser) use ($session, $meito): void {
                $isLead = $aiUser->email === 'kenji.yamada@nagoyaharvest.jp';

                $aiUser->update([
                    'current_game_session_id' => $session->id,
                    'current_game_month' => 1,
                    'current_game_week' => 1,
                    'weeks_processed_this_month' => 0,
                    'weeks_played_today' => 0,
                    'current_game_day' => 1,
                    'days_processed_this_month' => 0,
                    'home_ward_id' => $isLead ? $meito?->id : ($aiUser->home_ward_id ?? $meito?->id),
                    'action_points' => 20,
                    'max_action_points' => 26,
                    'weekly_action_points' => 20,
                    'daily_action_points' => 20,
                    'total_generational_score' => $isLead ? 110 : 80,
                    'coop_synergy_score' => $isLead ? 30 : 20,
                    'consecutive_inactive_months' => 0,
                    'last_played_game_month' => 1,
                    'days_played_today' => 0,
                    'is_burned_out' => false,
                    'burned_out_until_game_month' => null,
                    'consecutive_exhaustion_weeks' => 0,
                    'consecutive_exhaustion_days' => 0,
                    'fatigue_penalty_ap' => 0,
                    'committed_to_language_training' => true,
                    'language_training_enrolled_month' => 1,
                    'language_training_completed' => false,
                    'screening_passed' => true,
                    'has_seen_host_intro' => true,
                    'badges' => $isLead ? ['First Step', 'Prayer Walker', 'Oikos Pioneer'] : ($aiUser->badges ?? ['Prayer Walker']),
                ]);
            });

            // Calibrate initial Japanese NPCs to authentic multi-month seeking & baptism cost timelines
            Npc::where('game_session_id', $session->id)->each(function (Npc $npc): void {
                $isJapanese = ($npc->nationality ?? 'Japanese') === 'Japanese';
                $npc->update([
                    'seeking_months' => $npc->status === ReceptivityStatus::Yellow ? 1 : 0,
                    'yellow_since_game_month' => $npc->status === ReceptivityStatus::Yellow ? 1 : null,
                    'target_seeking_months' => $isJapanese ? 5 : 2,
                    'baptism_cost_months' => $npc->is_baptized ? 6 : 0,
                    'target_baptism_months' => $isJapanese ? 7 : 3,
                ]);
            });
        });

        $this->cumulativeAttritionEvents = 0;
        $this->cumulativeRevitalizations = 0;
        $this->cumulativeMergers = 0;
        $this->spawnCounter = Npc::where('game_session_id', $session->id)->count();
    }

    /**
     * Simulate the game up to $targetMonths inside the isolated temporary SQLite database,
     * capturing snapshots at all standard milestones (3m, 6m, 1y, 2y, 5y, 10y, 15y) reached along the way.
     *
     * @param  callable(int, array<string, mixed>|null): void|null  $onMonthCompleted
     * @return array{
     *     target_months: int,
     *     mode: string,
     *     milestones: array<string, array<string, mixed>>,
     *     final_snapshot: array<string, mixed>
     * }
     */
    public function simulate(
        GameSession $session,
        int $targetMonths = 180,
        string $mode = 'coop',
        bool $resetFirst = true,
        ?int $rngSeed = 20260401,
        ?callable $onMonthCompleted = null,
    ): array {
        if ($rngSeed !== null) {
            mt_srand($rngSeed);
        }

        // Always execute inside the isolated temporary SQLite database so live game data is never altered
        $sandbox = app(ShowcaseSandboxService::class);
        if (! $sandbox->isActive()) {
            $session = $sandbox->activate();
        } else {
            $session = GameSession::where('code', $session->code)->first()
                ?? GameSession::first()
                ?? $session;
        }

        if ($resetFirst) {
            $this->resetSessionToBaseline($session, $mode);
            $session = GameSession::where('code', 'nagoya-2026')->first() ?? GameSession::firstOrFail();
        } else {
            if ($mode === 'solo') {
                $session->enableSoloMode();
            } else {
                $session->enableCoopMode();
            }
            $this->spawnCounter = Npc::where('game_session_id', $session->id)->count();
        }

        $session->refresh();

        $pioneer = User::where('current_game_session_id', $session->id)
            ->where('email', 'kenji.yamada@nagoyaharvest.jp')
            ->first()
            ?? User::where('current_game_session_id', $session->id)
                ->where('is_ai_colaborer', true)
                ->orderBy('id')
                ->first()
            ?? User::first();

        $wardsBySlug = Ward::all()->keyBy('slug');
        $milestones = [];

        $startMonth = max(1, (int) $session->current_game_month);
        $endMonth = $resetFirst ? $targetMonths : ($startMonth + $targetMonths - 1);

        for ($month = $startMonth; $month <= $endMonth; $month++) {
            DB::transaction(function () use ($session, $pioneer, $wardsBySlug, $month, $mode): void {
                $this->simulateSingleRealLifeMonth($session, $pioneer, $wardsBySlug, $month, $mode);
            });

            $elapsedMonths = $resetFirst ? $month : ($month - $startMonth + 1);
            $milestoneKey = $this->milestoneKeyForMonths($elapsedMonths);
            $snapshot = null;

            if ($milestoneKey !== null || $month === $endMonth) {
                $session->refresh();
                if ($pioneer) {
                    $pioneer->refresh();
                }
                $key = $milestoneKey ?? $elapsedMonths.'m';
                $snapshot = $this->captureSnapshot($session, $pioneer, $elapsedMonths, $key);
                if ($milestoneKey !== null) {
                    $milestones[$milestoneKey] = $snapshot;
                }
            }

            if ($onMonthCompleted !== null) {
                $onMonthCompleted($elapsedMonths, $snapshot);
            }
        }

        $finalSnapshot = $snapshot ?? $this->captureSnapshot($session, $pioneer, $targetMonths, $this->milestoneKeyForMonths($targetMonths) ?? $targetMonths.'m');

        return [
            'target_months' => $targetMonths,
            'mode' => $mode,
            'milestones' => $milestones,
            'final_snapshot' => $finalSnapshot,
        ];
    }

    /**
     * Simulates 1 full game month (4 weekly cycles + monthly autonomous multiplication,
     * baptism preparation, leadership development, and real-life church attrition/revitalization).
     *
     * @param  Collection<string, Ward>  $wardsBySlug
     */
    protected function simulateSingleRealLifeMonth(
        GameSession $session,
        ?User $pioneer,
        Collection $wardsBySlug,
        int $gameMonth,
        string $mode,
    ): void {
        $isCoop = $mode !== 'solo' && $session->isCoopMode;
        $coopFactor = $isCoop ? 1.0 : 0.78;

        // 1. Unlocked wards for this stage of the movement
        $unlockedWards = $this->getUnlockedWardsForMonth($wardsBySlug, $gameMonth, $isCoop);

        // 2. Missionary Spiritual Rhythms & Language Foundation Progression
        if ($pioneer) {
            $this->processPioneerMonthlyRhythms($session, $pioneer, $unlockedWards, $gameMonth);
        }

        // 3. Field 1: Prayer Walking, Community Presence & Organic Oikos Discovery
        $this->simulateField1EntryAndOikosDiscovery($session, $pioneer, $unlockedWards, $gameMonth, $coopFactor);

        // 4. Advance Monthly Seeking & Baptism Cost-Counting Timelines (Japanese Cultural Pacing)
        Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Yellow)
            ->increment('seeking_months');

        Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('is_baptized', false)
            ->increment('baptism_cost_months');

        // 5. Field 2: Gospel Sowing (15s Testimony, 3 Circles, Sword DBS) & Gradual Conversions
        $this->simulateField2GospelAndSalvation($session, $pioneer, $unlockedWards, $gameMonth, $coopFactor);

        // 6. Field 3: Cost-Counting Pastoral Care, Water Baptisms & 411 Equipping
        $this->simulateField3BaptismAnd411($session, $pioneer, $gameMonth, $coopFactor);

        // 7. Field 4 & 5: Indigenous Leadership Progression (L1 -> L5) & Simple Church Planting
        $this->simulateField4And5ChurchesAndLeadership($session, $pioneer, $unlockedWards, $gameMonth, $coopFactor);

        // 8. Real-Life Trials: Seasonal Job Relocations (Tenkin), Illness, Conflict & Revitalization
        $this->simulateRealisticAttritionAndCare($session, $pioneer, $gameMonth);

        // 9. Advance Session & Player Calendar to Next Month
        $nextMonth = $gameMonth + 1;
        $session->update([
            'current_game_month' => $nextMonth,
            'current_game_week' => 1,
            'weeks_processed_this_month' => 0,
            'current_game_day' => 1,
            'days_processed_this_month' => 0,
            'calendar_date' => Carbon::parse('2026-04-01')->addMonths($gameMonth)->toDateString(),
            'last_monthly_tick_at' => now(),
            'next_tick_at' => now()->addHours(12),
        ]);

        User::where('current_game_session_id', $session->id)->each(function (User $aiUser) use ($nextMonth, $gameMonth): void {
            $aiUser->update([
                'current_game_month' => $nextMonth,
                'current_game_week' => 1,
                'current_game_day' => 1,
                'weeks_processed_this_month' => 0,
                'days_processed_this_month' => 0,
                'consecutive_inactive_months' => 0,
                'last_played_game_month' => $gameMonth,
                'last_active_at' => now(),
            ]);
        });
    }

    /**
     * Determine which of Nagoya's 16 wards have been unlocked by the movement up to $gameMonth.
     *
     * @param  Collection<string, Ward>  $wardsBySlug
     * @return Collection<int, Ward>
     */
    protected function getUnlockedWardsForMonth(Collection $wardsBySlug, int $gameMonth, bool $isCoop): Collection
    {
        $unlocked = collect();

        foreach (self::WARD_EXPANSION_ORDER as $slug) {
            $baseUnlockMonth = self::WARD_UNLOCK_MONTH[$slug];
            // In solo mode, early ward expansion takes slightly longer until indigenous leaders multiply
            $effectiveUnlockMonth = (! $isCoop && $baseUnlockMonth > 1 && $baseUnlockMonth < 120)
                ? (int) round($baseUnlockMonth * 1.15)
                : $baseUnlockMonth;

            if ($gameMonth >= $effectiveUnlockMonth && $wardsBySlug->has($slug)) {
                $unlocked->push($wardsBySlug->get($slug));
            }
        }

        if ($unlocked->isEmpty() && $wardsBySlug->isNotEmpty()) {
            $unlocked->push($wardsBySlug->first());
        }

        return $unlocked;
    }

    /**
     * Process missionary language school graduation, weekly Sabbath rest, fasting retreats, and conferences.
     *
     * @param  Collection<int, Ward>  $unlockedWards
     */
    protected function processPioneerMonthlyRhythms(
        GameSession $session,
        User $pioneer,
        Collection $unlockedWards,
        int $gameMonth,
    ): void {
        $badges = $pioneer->badges ?? [];
        $scoreGain = 25; // Weekly Sabbath rest faithfulness (+25 pts)
        $coopGain = 10;

        // Month 4 Completion: Graduate Japanese Language School & First-Level NPL Training
        if ($gameMonth === 4 && ! $pioneer->language_training_completed) {
            if (! in_array('Language & L1 Certified', $badges, true)) {
                $badges[] = 'Language & L1 Certified';
            }
            $pioneer->update([
                'language_training_completed' => true,
                'action_points' => 26,
                'max_action_points' => 26,
                'badges' => $badges,
            ]);
            $scoreGain += 40;
        }

        // Quarterly Paul-Timothy Mentoring (Months 3, 6, 9, 12...)
        if ($gameMonth % 3 === 0) {
            $scoreGain += 30;
            $coopGain += 15;
        }

        // Semi-Annual 4-Day Corporate Fasting Retreat (Months 6, 12, 18, 24...)
        if ($gameMonth % 6 === 0) {
            $scoreGain += 100;
            $coopGain += 20;
            // Corporate fasting softens spiritual ground across active wards
            Ward::whereIn('id', $unlockedWards->pluck('id'))->each(function (Ward $w): void {
                if ($w->spiritual_openness < 85) {
                    $w->increment('spiritual_openness', 1);
                }
            });
        }

        // Annual NPL Mid-Level Strategy Conference (Months 12, 24, 36...)
        if ($gameMonth % 12 === 0) {
            $scoreGain += 75;
            $coopGain += 25;
            if ($gameMonth >= 24 && ! in_array('Movement Catalyst', $badges, true)) {
                $badges[] = 'Movement Catalyst';
            }
            if ($gameMonth >= 60 && ! in_array('Fourth Generation Stream', $badges, true)) {
                $badges[] = 'Fourth Generation Stream';
            }
            if ($gameMonth >= 180 && ! in_array('No Place Left Nagoya', $badges, true)) {
                $badges[] = 'No Place Left Nagoya';
            }
        }

        $pioneer->update([
            'total_generational_score' => $pioneer->total_generational_score + $scoreGain,
            'coop_synergy_score' => ($pioneer->coop_synergy_score ?? 0) + $coopGain,
            'badges' => $badges,
            'is_burned_out' => false,
            'consecutive_exhaustion_weeks' => 0,
            'consecutive_exhaustion_days' => 0,
            'fatigue_penalty_ap' => 0,
        ]);

        // Also advance fellow computer co-laborers' rhythms & scores in Co-op mode
        if ($session->isCoopMode) {
            User::where('current_game_session_id', $session->id)
                ->where('id', '!=', $pioneer->id)
                ->each(function (User $coLaborer) use ($gameMonth, $scoreGain, $coopGain): void {
                    $coBadges = $coLaborer->badges ?? [];
                    if ($gameMonth === 4 && ! $coLaborer->language_training_completed) {
                        $coBadges[] = 'Language & L1 Certified';
                    }
                    $coLaborer->update([
                        'language_training_completed' => $gameMonth >= 4 ? true : $coLaborer->language_training_completed,
                        'action_points' => $gameMonth >= 4 ? 26 : 20,
                        'total_generational_score' => $coLaborer->total_generational_score + (int) round($scoreGain * 0.8),
                        'coop_synergy_score' => ($coLaborer->coop_synergy_score ?? 0) + $coopGain + 12,
                        'badges' => array_values(array_unique($coBadges)),
                    ]);
                });
        }
    }

    /**
     * Field 1: Prayer walking, building initial acquaintance (Unreached -> Red -> Yellow),
     * and organically discovering Oikos relatives, coworkers, and neighbors across active wards.
     *
     * @param  Collection<int, Ward>  $unlockedWards
     */
    protected function simulateField1EntryAndOikosDiscovery(
        GameSession $session,
        ?User $pioneer,
        Collection $unlockedWards,
        int $gameMonth,
        float $coopFactor,
    ): void {
        // Record 1–2 representative prayer walks per month in target frontier wards
        $frontierWard = $unlockedWards->last() ?? $unlockedWards->first();
        if ($frontierWard && $pioneer) {
            PrayerWalk::create([
                'game_session_id' => $session->id,
                'user_id' => $pioneer->id,
                'ward_id' => $frontierWard->id,
                'game_month' => $gameMonth,
                'game_day' => 1,
                'route_name' => "{$frontierWard->name} Station & Neighborhood Intercession Walk",
                'path_coords' => [[(float) $frontierWard->center_lat, (float) $frontierWard->center_lng]],
                'pop_discovered' => $gameMonth % 2 === 0,
            ]);

            if ($frontierWard->spiritual_openness < 80 && $gameMonth % 3 === 0) {
                $frontierWard->increment('spiritual_openness', 1);
            }
        }

        // Determine how many new Oikos contacts surface this month based on active sowers & era
        $targetNewContacts = $this->calculateMonthlyNewContactsTarget($gameMonth, $coopFactor);

        for ($i = 0; $i < $targetNewContacts; $i++) {
            // Pick a ward: prioritize unlocked wards that currently have fewer NPCs so all unlocked wards get plowed
            /** @var Ward $targetWard */
            $targetWard = $unlockedWards->sortBy(function (Ward $w) use ($session): int {
                return Npc::where('game_session_id', $session->id)->where('ward_id', $w->id)->count();
            })->first() ?? $frontierWard;

            // Find an existing believer or seeker to anchor the Oikos relationship
            $anchorNpc = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->inRandomOrder()
                ->first()
                ?? Npc::where('game_session_id', $session->id)->inRandomOrder()->first();

            $newNpc = $this->spawnRealisticResident($session, $targetWard, $gameMonth, $anchorNpc);

            if ($anchorNpc) {
                $relTypes = [
                    RelationshipType::Family,
                    RelationshipType::Friend,
                    RelationshipType::Coworker,
                    RelationshipType::Neighbor,
                ];
                $relType = $relTypes[$this->spawnCounter % count($relTypes)];

                OikosRelationship::firstOrCreate(
                    [
                        'game_session_id' => $session->id,
                        'npc_id' => $anchorNpc->id,
                        'related_npc_id' => $newNpc->id,
                    ],
                    [
                        'relationship_type' => $relType,
                        'trust_level' => rand(55, 85),
                        'notes' => "Oikos {$relType->label()} connection in {$targetWard->name}.",
                    ]
                );
            }
        }

        // Warm up Unreached & Red residents through Casual Talk, Deep Talk, Mercy Care & Healing Prayer
        $unreachedOrRed = Npc::where('game_session_id', $session->id)
            ->whereIn('status', [ReceptivityStatus::Unreached, ReceptivityStatus::Red])
            ->orderBy('id')
            ->get();

        $TransitionBudget = max(2, (int) ceil($unreachedOrRed->count() * 0.45));
        $transitioned = 0;

        foreach ($unreachedOrRed as $resident) {
            if ($transitioned >= $TransitionBudget) {
                break;
            }

            $trustGain = rand(12, 22);
            $newTrust = min(100, $resident->trust_level + $trustGain);

            if ($resident->status === ReceptivityStatus::Unreached) {
                $newStatus = $newTrust >= 42 ? ReceptivityStatus::Yellow : ReceptivityStatus::Red;
            } else {
                $newStatus = $newTrust >= 45 ? ReceptivityStatus::Yellow : ReceptivityStatus::Red;
            }

            $updates = [
                'trust_level' => $newTrust,
                'status' => $newStatus,
            ];

            // Answer healing or mercy needs along the way
            if ($resident->need_state === NpcNeedState::SickNeedsHealing && rand(1, 100) <= 65) {
                $updates['need_state'] = NpcNeedState::None;
                $updates['status'] = ReceptivityStatus::Yellow;
                $updates['trust_level'] = min(100, $newTrust + 15);
            }

            if ($updates['status'] === ReceptivityStatus::Yellow && $resident->status !== ReceptivityStatus::Yellow) {
                $updates['yellow_since_game_month'] = $gameMonth;
                $updates['seeking_months'] = 1;
            }

            $resident->update($updates);
            $transitioned++;
        }
    }

    /**
     * Calculate realistic monthly new Oikos contacts discovered across the 15-year curve.
     */
    protected function calculateMonthlyNewContactsTarget(int $gameMonth, float $coopFactor): int
    {
        $base = match (true) {
            $gameMonth <= 3 => 1,                   // 3m: Slow cultural entry & language school
            $gameMonth <= 6 => 2,                   // 6m: Early sowing in initial wards
            $gameMonth <= 12 => ($gameMonth % 2 === 0 ? 2 : 1), // 1y: Steady personal & Oikos sowing
            $gameMonth <= 24 => 2,                  // 2y: Emerging L2 leaders opening new Oikos webs
            $gameMonth <= 60 => ($gameMonth % 2 === 0 ? 3 : 2), // 5y: Multi-ward indigenous networks
            $gameMonth <= 120 => 3,                 // 10y: City-wide movement training teams
            default => ($gameMonth % 2 === 0 ? 4 : 3),          // 15y: All 16 wards actively engaged
        };

        return max(1, (int) round($base * $coopFactor));
    }

    /**
     * Field 2: Share 15s Testimony, 3 Circles, and Sword DBS with Yellow seekers.
     * Seekers only pray the Sinner's Prayer (Green) once they complete their gradual Japanese seeking months!
     *
     * @param  Collection<int, Ward>  $unlockedWards
     */
    protected function simulateField2GospelAndSalvation(
        GameSession $session,
        ?User $pioneer,
        Collection $unlockedWards,
        int $gameMonth,
        float $coopFactor,
    ): void {
        // In Japan's first 2 months, missionary is in language school and seekers are just beginning to study
        if ($gameMonth <= 2) {
            Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Yellow)
                ->increment('trust_level', 6);

            return;
        }

        $yellowSeekers = Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Yellow)
            ->orderByDesc('seeking_months')
            ->orderByDesc('trust_level')
            ->get();

        // Realistic monthly harvest cap reflecting active discipler capacity in each era
        $monthlyHarvestCap = match (true) {
            $gameMonth === 3 => 1, // At 3m: at most 1 fast-moving international/PoP seeker converts
            $gameMonth <= 6 => 1,  // At 6m: ~4–5 total Green believers
            $gameMonth <= 12 => ($gameMonth % 2 === 0 ? 2 : 1), // At 1y: ~11–14 total Green believers
            $gameMonth <= 24 => 2, // At 2y: ~28–34 total Green believers
            $gameMonth <= 60 => ($gameMonth % 2 === 0 ? 3 : 2), // At 5y: ~80–105 total Green believers
            $gameMonth <= 120 => 3, // At 10y: ~200–260 total Green believers
            default => ($gameMonth % 2 === 0 ? 4 : 3), // At 15y: ~380–460 total Green believers
        };
        $monthlyHarvestCap = max(1, (int) round($monthlyHarvestCap * $coopFactor));

        $harvestedThisMonth = 0;

        foreach ($yellowSeekers as $seeker) {
            $newTrust = min(100, $seeker->trust_level + rand(8, 15));
            $seeker->update(['trust_level' => $newTrust]);

            if ($harvestedThisMonth >= $monthlyHarvestCap) {
                continue;
            }

            // Check if seeker has completed their cultural seeking duration
            if (! $seeker->isReadyForConversion($gameMonth)) {
                continue;
            }

            // Determine discipler & generational depth realistically by movement era
            $disciplerNpc = $this->findDisciplerForSeeker($session, $seeker, $gameMonth);
            $maxAllowedGen = $this->maxGenerationForMonth($gameMonth);
            $newGen = $disciplerNpc
                ? min($maxAllowedGen, max(2, $disciplerNpc->generation + 1))
                : 1;

            // Distribute discipleship & co-op handoffs across active computer players
            $computerTeam = User::where('current_game_session_id', $session->id)
                ->where('is_ai_colaborer', true)
                ->orderBy('id')
                ->get();

            $assignedComputerCatalyst = $computerTeam->isNotEmpty()
                ? $computerTeam->get(($seeker->id + $gameMonth) % $computerTeam->count())
                : $pioneer;

            $sowerComputerCatalyst = $computerTeam->count() > 1
                ? $computerTeam->get(($seeker->id + $gameMonth + 1) % $computerTeam->count())
                : $assignedComputerCatalyst;

            $seeker->update([
                'status' => ReceptivityStatus::Green,
                'prayed_salvation' => true,
                'prayed_salvation_at' => now(),
                'prayed_salvation_month' => $gameMonth,
                'baptism_status' => BaptismStatus::CountingCost,
                'is_baptized' => false,
                'baptism_cost_months' => 0,
                'generation' => $newGen,
                'discipler_npc_id' => $disciplerNpc?->id,
                'discipler_user_id' => $assignedComputerCatalyst->id ?? $pioneer?->id,
            ]);

            if ($assignedComputerCatalyst) {
                $genPoints = match ($newGen) {
                    1 => 70,
                    2 => 100,
                    3 => 200,
                    default => 500,
                };
                $assignedComputerCatalyst->increment('total_generational_score', $genPoints);
                $assignedComputerCatalyst->increment('coop_synergy_score', 25);

                if ($sowerComputerCatalyst && $sowerComputerCatalyst->id !== $assignedComputerCatalyst->id) {
                    $sowerComputerCatalyst->increment('coop_synergy_score', 30);

                    NpcContribution::create([
                        'game_session_id' => $session->id,
                        'npc_id' => $seeker->id,
                        'user_id' => $sowerComputerCatalyst->id,
                        'action_type' => MinistryActionType::Share3Circles,
                        'contribution_role' => ContributionRole::PioneerSower,
                        'game_month' => max(1, $gameMonth - 1),
                        'trust_gained' => 15,
                        'points_awarded' => 30,
                        'triggered_breakthrough' => false,
                        'notes' => "Planted Gospel seed and Oikos bridge with {$seeker->name} in {$seeker->ward?->name}.",
                    ]);
                }

                NpcContribution::create([
                    'game_session_id' => $session->id,
                    'npc_id' => $seeker->id,
                    'user_id' => $assignedComputerCatalyst->id,
                    'action_type' => MinistryActionType::PraySalvation,
                    'contribution_role' => ContributionRole::Harvester,
                    'game_month' => $gameMonth,
                    'trust_gained' => 20,
                    'points_awarded' => $genPoints,
                    'triggered_breakthrough' => true,
                    'notes' => "Guided {$seeker->name} to salvation (G{$newGen}) after {$seeker->seeking_months} months of Discovery Bible Study.",
                ]);
            }

            $harvestedThisMonth++;
        }
    }

    /**
     * Realistic generational ceiling by movement era (reflecting real-life DMM timelines in Japan).
     */
    protected function maxGenerationForMonth(int $gameMonth): int
    {
        return match (true) {
            $gameMonth <= 3 => 1,   // 3m: G1 foundation
            $gameMonth <= 12 => 2,  // 6m - 1y: G2 household & Oikos reproduction
            $gameMonth <= 24 => 3,  // 2y: First G3 Timothy streams
            $gameMonth <= 60 => 4,  // 5y: 4th-generation (G4) indigenous movement streams
            $gameMonth <= 120 => 6, // 10y: G5–G6 city-wide streams
            default => 7,           // 15y: Deep G6–G7 indigenous streams across all 16 wards
        };
    }

    /**
     * Find an equipped indigenous believer in the seeker's Oikos or ward to serve as discipler.
     */
    protected function findDisciplerForSeeker(GameSession $session, Npc $seeker, int $gameMonth): ?Npc
    {
        if ($gameMonth <= 3) {
            return null;
        }

        // First check direct Oikos connections who are equipped Green believers
        $oikosDisciplerId = OikosRelationship::where('related_npc_id', $seeker->id)
            ->pluck('npc_id');

        if ($oikosDisciplerId->isNotEmpty()) {
            $oikosBeliever = Npc::whereIn('id', $oikosDisciplerId)
                ->where('status', ReceptivityStatus::Green)
                ->where('trained_411', true)
                ->orderByDesc('leadership_tier')
                ->first();

            if ($oikosBeliever) {
                return $oikosBeliever;
            }
        }

        // Otherwise check equipped believers in the same ward or session
        return Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('trained_411', true)
            ->where('id', '!=', $seeker->id)
            ->orderByRaw('CASE WHEN ward_id = ? THEN 0 ELSE 1 END', [$seeker->ward_id])
            ->orderByDesc('generation')
            ->first();
    }

    /**
     * Field 3: Walk new believers through Japanese family/ancestral cost-counting toward water baptism
     * and equip faithful believers with 411 Discipleship Training (L1 Seed Sower).
     */
    protected function simulateField3BaptismAnd411(
        GameSession $session,
        ?User $pioneer,
        int $gameMonth,
        float $coopFactor,
    ): void {
        // 1. Equip Daisuke Kato (or initial believers) in 411 during Month 1–2
        if ($gameMonth <= 2) {
            $daisuke = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->where('trained_411', false)
                ->first();

            if ($daisuke) {
                $daisuke->update([
                    'trained_411' => true,
                    'leadership_tier' => LeadershipTier::L1_SeedSower,
                    'trust_level' => min(100, $daisuke->trust_level + 15),
                ]);
            }
        }

        // 2. Walk unbaptized Green believers through CountingCost -> Committed -> Baptized
        $unbaptizedBelievers = Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('is_baptized', false)
            ->orderByDesc('baptism_cost_months')
            ->get();

        foreach ($unbaptizedBelievers as $believer) {
            $targetMonths = max(2, $believer->target_baptism_months);
            $halfway = (int) max(1, floor($targetMonths / 2));

            if ($believer->baptism_cost_months >= $targetMonths && $believer->trust_level >= 65) {
                // Completed the 6-to-15 month Japanese family preparation journey — Water Baptism!
                $believer->update([
                    'is_baptized' => true,
                    'baptism_status' => BaptismStatus::Baptized,
                    'baptized_at' => now(),
                    'trust_level' => min(100, $believer->trust_level + 15),
                ]);

                if ($pioneer) {
                    $pioneer->increment('total_generational_score', 80);
                }
            } elseif ($believer->baptism_cost_months >= $halfway && $believer->baptism_status === BaptismStatus::CountingCost) {
                // Reached halfway milestone: formally commits to water baptism while preparing family
                $believer->update([
                    'baptism_status' => BaptismStatus::Committed,
                    'trust_level' => min(100, $believer->trust_level + 8),
                ]);
            } else {
                $believer->update([
                    'trust_level' => min(100, $believer->trust_level + 5),
                ]);
            }
        }

        // 3. Equip ready Green believers in 411 Discipleship Training (L1 Seed Sower)
        // In real life, ~70-75% of believers complete 411 training and actively sow
        $untrainedBelievers = Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('trained_411', false)
            ->where(function ($q): void {
                $q->where('is_baptized', true)
                    ->orWhere('baptism_cost_months', '>=', 2);
            })
            ->orderBy('id')
            ->take(max(1, (int) round(2 * $coopFactor)))
            ->get();

        foreach ($untrainedBelievers as $believer) {
            $believer->update([
                'trained_411' => true,
                'leadership_tier' => $believer->leadership_tier->level() === 0
                    ? LeadershipTier::L1_SeedSower
                    : $believer->leadership_tier,
            ]);
        }
    }

    /**
     * Field 4 & 5: Raise indigenous Japanese leaders across the 5 Levels of Leadership
     * (L1 Seed Sower -> L2 Church Planter -> L3 Multiplier -> L4 Movement Trainer -> L5 Strategy Coordinator)
     * and birth Acts 2 Simple Churches across Nagoya's 16 wards as believers gather.
     *
     * @param  Collection<int, Ward>  $unlockedWards
     */
    protected function simulateField4And5ChurchesAndLeadership(
        GameSession $session,
        ?User $pioneer,
        Collection $unlockedWards,
        int $gameMonth,
        float $coopFactor,
    ): void {
        // 1. Progressive Indigenous Leadership Development (2 Tim 2:2)
        // L1 -> L2 (Church Planter / Timothy): Begins Month 5+
        if ($gameMonth >= 5 && $gameMonth % 2 === 1) {
            $l1Candidate = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->where('trained_411', true)
                ->whereIn('leadership_tier', [LeadershipTier::L1_SeedSower, LeadershipTier::Apprentice])
                ->orderByDesc('is_baptized')
                ->orderBy('id')
                ->first();

            if ($l1Candidate) {
                $l1Candidate->update([
                    'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
                    'trust_level' => min(100, $l1Candidate->trust_level + 10),
                ]);
            }
        }

        // L2 -> L3 (Church Plant Multiplier via Iron-on-Iron): Begins Month 16+ (Year 2)
        if ($gameMonth >= 16 && $gameMonth % 6 === 0) {
            $l2Candidate = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->whereIn('leadership_tier', [LeadershipTier::L2_ChurchPlanter, LeadershipTier::Timothy])
                ->where('is_baptized', true)
                ->orderBy('id')
                ->first();

            if ($l2Candidate) {
                $l2Candidate->update([
                    'leadership_tier' => LeadershipTier::L3_Multiplier,
                ]);
            }
        }

        // L3 -> L4 (Movement Trainer via Swarm Training): Begins Month 42+ (Year 3.5–5)
        if ($gameMonth >= 42 && $gameMonth % 18 === 0) {
            $l3Candidate = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->where('leadership_tier', LeadershipTier::L3_Multiplier)
                ->where('is_baptized', true)
                ->orderBy('id')
                ->first();

            if ($l3Candidate) {
                $l3Candidate->update([
                    'leadership_tier' => LeadershipTier::L4_MovementTrainer,
                ]);
            }
        }

        // L4 -> L5 (Strategy Coordinator): Begins Month 96+ (Year 8–15)
        if ($gameMonth >= 96 && ($gameMonth - 96) % 36 === 0) {
            $l4Candidate = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->whereIn('leadership_tier', [LeadershipTier::L4_MovementTrainer, LeadershipTier::Elder])
                ->where('is_baptized', true)
                ->orderBy('id')
                ->first();

            if ($l4Candidate) {
                $l4Candidate->update([
                    'leadership_tier' => LeadershipTier::L5_StrategyCoordinator,
                ]);
            }
        }

        // 2. Plant Acts 2 Simple Churches (Field 4) when unchurched believers gather (min 3–5 believers per church)
        if ($gameMonth <= 4) {
            // In the first 3–4 months, only the initial G1 church in Meito-ku exists
            $this->syncChurchMemberCounts($session, $gameMonth);

            return;
        }

        $activeChurchesCount = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->count();

        $totalBelieversCount = Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->count();

        // Real-life ratio: roughly 1 simple church / Bible study circle per 4–5 believers, expanding across unlocked wards
        $targetActiveChurches = max(1, (int) floor($totalBelieversCount / 4.8));
        if ($gameMonth === 6) {
            $targetActiveChurches = max(3, $targetActiveChurches);
        } elseif ($gameMonth >= 10 && $gameMonth < 20) {
            $targetActiveChurches = max(10, $targetActiveChurches);
        } elseif ($gameMonth >= 20 && $gameMonth < 50) {
            $targetActiveChurches = max(15, $targetActiveChurches);
        } elseif ($gameMonth >= 50 && $gameMonth < 110) {
            $targetActiveChurches = max(30, $targetActiveChurches);
        } elseif ($gameMonth >= 110 && $gameMonth < 170) {
            $targetActiveChurches = max(60, $targetActiveChurches);
        } elseif ($gameMonth >= 170) {
            $targetActiveChurches = max(75, $targetActiveChurches);
        }

        // Plant up to 3 new Discovery Bible Study groups per month so new groups continually replenish merged/dissolved groups
        $plantsThisMonth = 0;
        while ($activeChurchesCount < $targetActiveChurches && $plantsThisMonth < 3) {
            // Prioritize unlocked wards that do NOT have an active church yet!
            $wardToPlant = $unlockedWards->first(function (Ward $w) use ($session): bool {
                return ! Church::where('game_session_id', $session->id)
                    ->where('ward_id', $w->id)
                    ->where('is_dissolved', false)
                    ->exists();
            });

            // Next, prioritize unlocked wards that only have 1 active group so groups in the same ward can naturally merge/fellowship
            if (! $wardToPlant) {
                $wardToPlant = $unlockedWards->sortBy(function (Ward $w) use ($session): int {
                    return Church::where('game_session_id', $session->id)
                        ->where('ward_id', $w->id)
                        ->where('is_dissolved', false)
                        ->count();
                })->first() ?? $unlockedWards->first();
            }

            if (! $wardToPlant) {
                break;
            }

            $this->plantIndigenousSimpleChurch($session, $pioneer, $wardToPlant, $gameMonth);
            $activeChurchesCount++;
            $plantsThisMonth++;
        }

        // At Month 170+ (15 Years), ensure any remaining unlocked ward among Nagoya's 16 wards has its simple church planted
        if ($gameMonth >= 170) {
            foreach ($unlockedWards as $ward) {
                $hasActiveChurch = Church::where('game_session_id', $session->id)
                    ->where('ward_id', $ward->id)
                    ->where('is_dissolved', false)
                    ->exists();

                if (! $hasActiveChurch) {
                    $this->plantIndigenousSimpleChurch($session, $pioneer, $ward, $gameMonth);
                }
            }
        }

        $this->syncChurchMemberCounts($session, $gameMonth);
    }

    /**
     * Plant an authentic Discovery Bible Study group in the target ward led by an equipped indigenous leader.
     * Groups begin as Developing Bible Studies (is_healthy_church = false) and have a high probability (75-95%)
     * to transition into Healthy Acts 2 Simple Churches after 6-12 months when coached and trained.
     */
    protected function plantIndigenousSimpleChurch(
        GameSession $session,
        ?User $pioneer,
        Ward $ward,
        int $gameMonth,
        ?int $forcedGeneration = null,
    ): Church {
        // Find or assign an indigenous leader in this ward
        $leaderNpc = Npc::where('game_session_id', $session->id)
            ->where('ward_id', $ward->id)
            ->where('status', ReceptivityStatus::Green)
            ->whereDoesntHave('ledChurches', fn ($q) => $q->where('is_dissolved', false))
            ->orderByDesc('is_baptized')
            ->orderByDesc('trained_411')
            ->first();

        if (! $leaderNpc) {
            $leaderNpc = Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->whereDoesntHave('ledChurches', fn ($q) => $q->where('is_dissolved', false))
                ->orderByDesc('is_baptized')
                ->first();
        }

        if ($leaderNpc && $leaderNpc->leadership_tier->level() < 2) {
            $leaderNpc->update([
                'trained_411' => true,
                'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
            ]);
        }

        // Determine parent church & generational tier across a natural pyramid (G1 -> G2 -> G3 -> G4+)
        $maxAllowedGen = $this->maxGenerationForMonth($gameMonth);
        $activeChurches = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->get();

        $totalActive = $activeChurches->count();
        $g1Count = $activeChurches->where('generation', 1)->count();
        $g2Count = $activeChurches->where('generation', 2)->count();
        $g3Count = $activeChurches->where('generation', 3)->count();

        if ($forcedGeneration !== null) {
            $churchGen = max(1, min($maxAllowedGen, $forcedGeneration));
            $parentChurch = $churchGen === 1
                ? null
                : ($activeChurches->where('generation', $churchGen - 1)->first() ?? $activeChurches->first());
        } elseif ($g1Count < max(1, (int) floor($totalActive * 0.16)) && $gameMonth >= 8) {
            $parentChurch = null;
            $churchGen = 1;
        } elseif ($maxAllowedGen >= 2 && $g2Count < max(2, (int) floor($totalActive * 0.25))) {
            $parentChurch = $activeChurches->where('generation', 1)->first() ?? $activeChurches->first();
            $churchGen = 2;
        } elseif ($maxAllowedGen >= 3 && $g3Count < max(3, (int) floor($totalActive * 0.28))) {
            $parentChurch = $activeChurches->where('generation', 2)->first() ?? $activeChurches->first();
            $churchGen = 3;
        } else {
            $parentChurch = $activeChurches
                ->where('generation', '<', $maxAllowedGen)
                ->sortByDesc('generation')
                ->first()
                ?? $activeChurches->first();

            $churchGen = $parentChurch
                ? min($maxAllowedGen, $parentChurch->generation + 1)
                : 1;
        }

        $leaderName = $leaderNpc ? $leaderNpc->name : ($pioneer->name ?? 'Nagoya Timothy');

        // All new groups begin as Developing Bible Studies practicing the initial 5-6 core elements
        $elements = [
            ChurchElement::RepentanceBelief->value,
            ChurchElement::Baptism->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
            ChurchElement::Fellowship->value,
            ChurchElement::MakingDisciples->value,
        ];

        $isHealthy = false;
        $healthScore = rand(60, 74);

        $computerTeam = User::where('current_game_session_id', $session->id)
            ->where('is_ai_colaborer', true)
            ->orderBy('id')
            ->get();

        $catalystUser = ($leaderNpc && $leaderNpc->discipler_user_id)
            ? User::find($leaderNpc->discipler_user_id)
            : ($computerTeam->isNotEmpty() ? $computerTeam->get($gameMonth % $computerTeam->count()) : $pioneer);

        $church = Church::create([
            'game_session_id' => $session->id,
            'ward_id' => $ward->id,
            'name' => "{$leaderName}'s {$ward->name} Discovery Bible Study (G{$churchGen})",
            'parent_church_id' => $parentChurch?->id,
            'leader_npc_id' => $leaderNpc?->id,
            'leader_user_id' => $churchGen === 1 ? $catalystUser?->id : null,
            'generation' => $churchGen,
            'elements_json' => $elements,
            'is_healthy_church' => $isHealthy,
            'health_score' => $healthScore,
            'attenders_count' => rand(5, 9),
            'believers_count' => rand(3, 6),
            'baptized_count' => rand(1, 4),
            'is_dissolved' => false,
            'started_by_name' => $leaderName,
            'last_coached_game_month' => $gameMonth,
            'last_coached_game_week' => 1,
            'last_coached_game_day' => 1,
            'lat' => $leaderNpc ? $leaderNpc->lat : (float) $ward->center_lat,
            'lng' => $leaderNpc ? $leaderNpc->lng : (float) $ward->center_lng,
            'founded_game_month' => $gameMonth,
        ]);

        // Associate unchurched believers in this ward with the new Bible study group
        Npc::where('game_session_id', $session->id)
            ->where('ward_id', $ward->id)
            ->where('status', ReceptivityStatus::Green)
            ->whereNull('church_id')
            ->limit(5)
            ->update(['church_id' => $church->id]);

        if ($catalystUser) {
            $churchPts = match ($churchGen) {
                1 => 100,
                2 => 250,
                3 => 600,
                default => 1200,
            };
            $catalystUser->increment('total_generational_score', $churchPts);
            $catalystUser->increment('coop_synergy_score', 40);
        }

        return $church;
    }

    /**
     * Coach active Bible study groups, equip their leaders, and transition 6-12 month old
     * coached & trained Bible study groups into Healthy Acts 2 Churches with high probability
     * while maintaining more Bible study groups than churches (around 52-60%) in each generation.
     */
    protected function syncChurchMemberCounts(GameSession $session, int $gameMonth): void
    {
        $activeChurches = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->with('leaderNpc')
            ->orderBy('founded_game_month')
            ->orderBy('id')
            ->get();

        // 1. Monthly coaching & leader training for developing Bible study groups
        foreach ($activeChurches as $church) {
            if (! $church->is_healthy_church) {
                $church->update([
                    'last_coached_game_month' => $gameMonth,
                    'health_score' => min(90, $church->health_score + 4),
                ]);

                if ($church->leaderNpc && (! $church->leaderNpc->trained_411 || $church->leaderNpc->leadership_tier->level() < 2)) {
                    $church->leaderNpc->update([
                        'trained_411' => true,
                        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
                    ]);
                }
            }
        }

        if ($gameMonth < 6) {
            return;
        }

        // 2. Evaluate each generation bucket (G1, G2, G3, G4+) so that:
        //    - Coached & trained Bible study groups of 6-12+ months have a high probability (75-95%) to become a church
        //    - Every generation maintains more Bible study groups than churches (~52-60% Bible study groups, inside 40-60%)
        $generationBuckets = [
            1 => $activeChurches->where('generation', 1),
            2 => $activeChurches->where('generation', 2),
            3 => $activeChurches->where('generation', 3),
            4 => $activeChurches->where('generation', '>=', 4),
        ];

        foreach ($generationBuckets as $bucketKey => $bucketGroups) {
            $totalInGen = $bucketGroups->count();
            if ($totalInGen < 2) {
                continue;
            }

            // Target ~42-46% churches and ~54-58% Bible study groups in each generation (strictly more Bible studies than churches)
            $maxChurchesInGen = max(1, (int) floor(($totalInGen - 1) / 2));
            if ($totalInGen >= 5) {
                $maxChurchesInGen = min($maxChurchesInGen, (int) floor($totalInGen * 0.46));
            }

            $currentChurchesInGen = $bucketGroups->where('is_healthy_church', true)->count();

            // Eligible Bible study groups that have been a Bible study for 6-12+ months (or 5+ months at Month 6)
            $eligibleStudies = $bucketGroups
                ->where('is_healthy_church', false)
                ->filter(fn (Church $c): bool => $c->monthsAsBibleStudy($gameMonth) >= ($gameMonth === 6 ? 5 : 6))
                ->sortBy('founded_game_month');

            foreach ($eligibleStudies as $study) {
                if ($currentChurchesInGen >= $maxChurchesInGen) {
                    break;
                }

                $probability = $study->churchTransitionProbability($gameMonth);
                // When a group has been coached & trained for 6-12 months, probability is 75-95%
                if (rand(1, 100) <= $probability || ($currentChurchesInGen === 0 && $totalInGen >= 3)) {
                    $study->transitionToHealthyChurch($gameMonth);
                    $currentChurchesInGen++;
                }
            }
        }
    }

    /**
     * Simulates real-life Japanese Bible study consolidation/merging and church attrition
     * (April/October corporate "tenkin" transfers, hospitalization/illness, interpersonal friction)
     * resulting in a natural ~40% dissolvement rate (dissolved / total ≈ 40%) while keeping
     * more Bible study groups than churches (52-60%) in each generation.
     */
    protected function simulateRealisticAttritionAndCare(
        GameSession $session,
        ?User $pioneer,
        int $gameMonth,
    ): void {
        if ($gameMonth <= 6) {
            return;
        }

        $allChurches = Church::where('game_session_id', $session->id)
            ->orderBy('id')
            ->get();

        $activeChurches = $allChurches->where('is_dissolved', false);
        $dissolvedCount = $allChurches->where('is_dissolved', true)->count();
        $activeCount = $activeChurches->count();

        // Target ~40% dissolvement rate: dissolved / (active + dissolved) ≈ 0.40 => dissolved ≈ active * (0.40 / 0.60) = active * 0.667
        $targetDissolvedCount = (int) round($activeCount * (0.40 / 0.60));

        // Group active churches by ward so we only merge/dissolve in wards with >= 2 active groups (preserving ward saturation)
        $wardCounts = $activeChurches->groupBy('ward_id')->map->count();

        $dissolutionsThisMonth = 0;
        while ($dissolvedCount < $targetDissolvedCount && $activeCount >= 4 && $dissolutionsThisMonth < 2) {
            $candidates = $activeChurches->filter(function (Church $c) use ($wardCounts): bool {
                return ($wardCounts[$c->ward_id] ?? 0) >= 2;
            });

            if ($candidates->isEmpty()) {
                break;
            }

            // Prefer merging developing Bible study groups from generations that have abundant Bible studies
            $bibleStudyCandidates = $candidates->where('is_healthy_church', false);
            /** @var Church $vulnerableChurch */
            $vulnerableChurch = ($bibleStudyCandidates->isNotEmpty() ? $bibleStudyCandidates : $candidates)
                ->sortBy('health_score')
                ->first();

            // Find another active group/church in the same ward to merge into
            /** @var Church|null $mergeTarget */
            $mergeTarget = $activeChurches
                ->where('ward_id', $vulnerableChurch->ward_id)
                ->where('id', '!=', $vulnerableChurch->id)
                ->where('is_dissolved', false)
                ->sortByDesc('is_healthy_church')
                ->first();

            // ~67% of dissolutions are Bible study groups merging into a neighboring group/church; ~33% are relocation/illness/friction
            if ($mergeTarget && ! $vulnerableChurch->is_healthy_church && ($dissolvedCount % 3 !== 0)) {
                $vulnerableChurch->mergeInto($mergeTarget, $gameMonth);
                $this->cumulativeMergers++;
                $this->cumulativeAttritionEvents++;
            } else {
                $reasons = [
                    'Job relocation ("tenkin") of key host family to Tokyo',
                    'Key gathering host hospitalized with prolonged illness',
                    'Interpersonal friction and misunderstandings between members',
                    'University student believers graduated and relocated out of Aichi',
                ];
                $reason = $reasons[($gameMonth + $dissolvedCount) % count($reasons)];
                $vulnerableChurch->dissolve($reason, $gameMonth);
                $this->cumulativeAttritionEvents++;
            }

            $activeChurches = $activeChurches->reject(fn (Church $c): bool => $c->id === $vulnerableChurch->id);
            $wardCounts[$vulnerableChurch->ward_id] = max(0, ($wardCounts[$vulnerableChurch->ward_id] ?? 1) - 1);
            $activeCount--;
            $dissolvedCount++;
            $dissolutionsThisMonth++;
        }

        // Pastoral Revitalization: Periodically restore a previously dissolved group through apostolic coaching
        if ($gameMonth % 36 === 18 && $dissolvedCount > $targetDissolvedCount) {
            $dissolvedChurch = Church::where('game_session_id', $session->id)
                ->where('is_dissolved', true)
                ->first();

            if ($dissolvedChurch) {
                $dissolvedChurch->revitalize($gameMonth, $pioneer);
                $this->cumulativeRevitalizations++;
            }
        }

        // Ongoing pastoral care by local L2–L5 leaders keeps active groups healthy and balanced per generation
        Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->where('health_score', '<', 75)
            ->increment('health_score', 4);

        $this->rebalanceGenerationBibleStudyRatios($session, $pioneer, $gameMonth);
    }

    /**
     * Ensure every active generation (G1, G2, G3, G4+) has more Bible study groups than churches
     * (around 40-60% of the generation being Bible study groups, specifically 52-60%).
     */
    protected function rebalanceGenerationBibleStudyRatios(
        GameSession $session,
        ?User $pioneer,
        int $gameMonth,
    ): void {
        if ($gameMonth < 6) {
            return;
        }

        $activeChurches = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->orderBy('founded_game_month')
            ->get();

        $buckets = [
            1 => $activeChurches->where('generation', 1),
            2 => $activeChurches->where('generation', 2),
            3 => $activeChurches->where('generation', 3),
            4 => $activeChurches->where('generation', '>=', 4),
        ];

        foreach ($buckets as $genKey => $groups) {
            $total = $groups->count();
            if ($total === 0) {
                continue;
            }

            // Ensure active generations from Year 1 onward have at least 5 active groups so a 3 BS / 2 Church (60% BS) split is possible
            if ($gameMonth >= 12 && $total < 5) {
                $toPlant = 5 - $total;
                for ($i = 0; $i < $toPlant; $i++) {
                    /** @var Church $reference */
                    $reference = $groups->first();
                    $ward = Ward::find($reference->ward_id) ?? Ward::first();
                    if ($ward) {
                        $this->plantIndigenousSimpleChurch($session, $pioneer, $ward, $gameMonth, $genKey);
                    }
                }

                $groups = Church::where('game_session_id', $session->id)
                    ->where('is_dissolved', false)
                    ->when($genKey < 4, fn ($q) => $q->where('generation', $genKey), fn ($q) => $q->where('generation', '>=', 4))
                    ->orderBy('founded_game_month')
                    ->get();
                $total = $groups->count();
            }

            // If Bible studies <= churches in this generation, plant new Bible studies so Bible studies strictly outnumber churches
            $bibleStudies = $groups->where('is_healthy_church', false);
            $churches = $groups->where('is_healthy_church', true);

            if ($bibleStudies->count() <= $churches->count()) {
                $neededStudies = ($churches->count() - $bibleStudies->count()) + 1;
                for ($i = 0; $i < $neededStudies; $i++) {
                    /** @var Church $reference */
                    $reference = $groups->first();
                    $ward = Ward::find($reference->ward_id) ?? Ward::first();
                    if ($ward) {
                        $this->plantIndigenousSimpleChurch($session, $pioneer, $ward, $gameMonth, $genKey);
                    }
                }

                // Refresh bucket after planting
                $groups = Church::where('game_session_id', $session->id)
                    ->where('is_dissolved', false)
                    ->when($genKey < 4, fn ($q) => $q->where('generation', $genKey), fn ($q) => $q->where('generation', '>=', 4))
                    ->orderBy('founded_game_month')
                    ->get();
                $total = $groups->count();
                $bibleStudies = $groups->where('is_healthy_church', false);
                $churches = $groups->where('is_healthy_church', true);
            }

            // Conversely, if Bible studies exceed 60% in a generation with >= 5 groups, graduate the most mature coached Bible study
            // so the generation stays in the 52-60% Bible study window (more Bible studies than churches, within 40-60%)
            $bsRatio = $total > 0 ? ($bibleStudies->count() / $total) : 1.0;
            while ($total >= 5 && $bsRatio > 0.60 && ($bibleStudies->count() - 1) > ($churches->count() + 1)) {
                /** @var Church|null $oldestStudy */
                $oldestStudy = $bibleStudies->sortBy('founded_game_month')->first();
                if (! $oldestStudy) {
                    break;
                }
                $oldestStudy->transitionToHealthyChurch($gameMonth);
                $bibleStudies = $bibleStudies->reject(fn (Church $c): bool => $c->id === $oldestStudy->id);
                $churches->push($oldestStudy);
                $bsRatio = $bibleStudies->count() / $total;
            }

            // If a small even generation total (6 or 8) leaves the split at 4/2 (67%) or 5/3 (63%),
            // graduate the oldest Bible study and plant 1 fresh Bible study so it becomes 4/3 (57%) or 5/4 (56%)
            if ($total >= 6 && (int) round(($bibleStudies->count() / $total) * 100) > 60) {
                /** @var Church|null $oldestStudy */
                $oldestStudy = $bibleStudies->sortBy('founded_game_month')->first();
                $ward = $oldestStudy ? (Ward::find($oldestStudy->ward_id) ?? Ward::first()) : Ward::first();
                if ($oldestStudy && $ward) {
                    $oldestStudy->transitionToHealthyChurch($gameMonth);
                    $this->plantIndigenousSimpleChurch($session, $pioneer, $ward, $gameMonth, $genKey);
                }
            }
        }
    }

    /**
     * Spawn an authentic Japanese or international resident in a Nagoya ward.
     */
    protected function spawnRealisticResident(
        GameSession $session,
        Ward $ward,
        int $gameMonth,
        ?Npc $anchorNpc = null,
    ): Npc {
        $this->spawnCounter++;
        $idx = $this->spawnCounter;

        $profiles = [
            ['name' => 'Takashi Watanabe', 'name_ja' => '渡辺 隆', 'gender' => 'male', 'age' => 38, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::Salaryman, 'need' => NpcNeedState::EmotionalCrisis],
            ['name' => 'Megumi Sato', 'name_ja' => '佐藤 恵', 'gender' => 'female', 'age' => 34, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::SingleParent, 'need' => NpcNeedState::Lonely],
            ['name' => 'Haruto Takahashi', 'name_ja' => '高橋 陽翔', 'gender' => 'male', 'age' => 21, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::UniversityStudent, 'need' => NpcNeedState::SeekingCommunity],
            ['name' => 'Yui Nakamura', 'name_ja' => '中村 結衣', 'gender' => 'female', 'age' => 27, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::Salaryman, 'need' => NpcNeedState::Lonely],
            ['name' => 'Osamu Yoshida', 'name_ja' => '吉田 修', 'gender' => 'male', 'age' => 68, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::ElderlyResident, 'need' => NpcNeedState::SickNeedsHealing],
            ['name' => 'Sakura Yamada', 'name_ja' => '山田 さくら', 'gender' => 'female', 'age' => 20, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::UniversityStudent, 'need' => NpcNeedState::SeekingCommunity],
            ['name' => 'Ryoichi Mori', 'name_ja' => '森 良一', 'gender' => 'male', 'age' => 46, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::Shopkeeper, 'need' => NpcNeedState::None],
            ['name' => 'Kana Inoue', 'name_ja' => '井上 香奈', 'gender' => 'female', 'age' => 31, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::SingleParent, 'need' => NpcNeedState::EmotionalCrisis],
            ['name' => 'Maria Oliveira', 'name_ja' => 'マリア・オリベイラ', 'gender' => 'female', 'age' => 29, 'nat' => 'Brazilian', 'role' => NpcRoleArchetype::InternationalExpat, 'need' => NpcNeedState::SeekingCommunity],
            ['name' => 'Tatsuya Kimura', 'name_ja' => '木村 達也', 'gender' => 'male', 'age' => 41, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::Salaryman, 'need' => NpcNeedState::EmotionalCrisis],
            ['name' => 'Sachiko Hayashi', 'name_ja' => '林 幸子', 'gender' => 'female', 'age' => 71, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::ElderlyResident, 'need' => NpcNeedState::SickNeedsHealing],
            ['name' => 'Kaito Shimizu', 'name_ja' => '清水 海斗', 'gender' => 'male', 'age' => 23, 'nat' => 'Japanese', 'role' => NpcRoleArchetype::UniversityStudent, 'need' => NpcNeedState::SeekingCommunity],
        ];

        $profile = $profiles[$idx % count($profiles)];
        $isJapanese = $profile['nat'] === 'Japanese';

        // Realistic Japanese seeking (4–8 months) and baptism cost-counting (6–12 months) durations
        $targetSeekingMonths = $isJapanese ? (4 + ($idx % 4)) : 2;
        $targetBaptismMonths = $isJapanese ? (6 + ($idx % 5)) : 3;

        $latOffset = (($idx % 9) - 4) * 0.0022;
        $lngOffset = (($idx % 7) - 3) * 0.0022;

        return Npc::create([
            'game_session_id' => $session->id,
            'ward_id' => $ward->id,
            'name' => "{$profile['name']} #{$idx}",
            'name_ja' => $profile['name_ja'],
            'gender' => $profile['gender'],
            'age' => $profile['age'],
            'nationality' => $profile['nat'],
            'role_archetype' => $profile['role'],
            'need_state' => $profile['need'],
            'status' => ($idx % 3 === 0) ? ReceptivityStatus::Red : ReceptivityStatus::Unreached,
            'prayed_salvation' => false,
            'trust_level' => $anchorNpc ? rand(25, 38) : rand(15, 28),
            'seeking_months' => 0,
            'yellow_since_game_month' => null,
            'target_seeking_months' => $targetSeekingMonths,
            'is_person_of_peace' => ($idx % 6 === 0),
            'is_baptized' => false,
            'baptism_status' => BaptismStatus::Uncommitted,
            'baptism_cost_months' => 0,
            'target_baptism_months' => $targetBaptismMonths,
            'trained_411' => false,
            'leadership_tier' => LeadershipTier::None,
            'generation' => 0,
            'discipler_npc_id' => $anchorNpc?->id,
            'lat' => round((float) $ward->center_lat + $latOffset, 6),
            'lng' => round((float) $ward->center_lng + $lngOffset, 6),
            'backstory' => "Resident in {$ward->name} ({$ward->name_ja}) connected through relational Oikos outreach in Month {$gameMonth}.",
        ]);
    }

    /**
     * Capture a comprehensive real-life metrics snapshot at the given game month.
     *
     * @return array<string, mixed>
     */
    public function captureSnapshot(
        GameSession $session,
        ?User $pioneer,
        int $elapsedMonths,
        string $milestoneKey,
    ): array {
        $meta = $milestoneKey === 'reset' ? [
            'months' => 1,
            'label' => 'Day 1 (Start)',
            'phase' => 'Phase 0: Arrival in Nagoya — Unplowed Soil & First Pioneer Seed Circle',
            'reflection' => 'April 2026: You have just arrived in Greater Nagoya (2.3M residents, <0.2% evangelical). Only 1 pioneer G1 seed circle gathers in Meito-ku around bilingual returnee Daisuke Kato. The other 15 wards are unreached, and your first 4 months require investing 70% of your weekly bandwidth into Japanese Language School (60% AP) and NPL First-Level Training (10% AP).',
        ] : (self::MILESTONES[$milestoneKey] ?? [
            'months' => $elapsedMonths,
            'label' => "{$elapsedMonths} Months",
            'phase' => "Month {$elapsedMonths} Movement Snapshot",
            'reflection' => "Longitudinal movement state after {$elapsedMonths} months in Nagoya.",
        ]);

        $totalWards = max(16, Ward::count());
        $reachedWardsCount = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->distinct('ward_id')
            ->count('ward_id');

        $saturationPercent = (int) round(($reachedWardsCount / max(1, $totalWards)) * 100);

        $npcs = Npc::where('game_session_id', $session->id)->get();
        $unreachedCount = $npcs->where('status', ReceptivityStatus::Unreached)->count();
        $redCount = $npcs->where('status', ReceptivityStatus::Red)->count();
        $yellowCount = $npcs->where('status', ReceptivityStatus::Yellow)->count();
        $greenBelievers = $npcs->where('status', ReceptivityStatus::Green);
        $greenCount = $greenBelievers->count();
        $baptizedCount = $greenBelievers->where('is_baptized', true)->count();
        $countingCostCount = $greenBelievers->where('is_baptized', false)->count();
        $trained411Count = $greenBelievers->where('trained_411', true)->count();

        $churches = Church::where('game_session_id', $session->id)->get();
        $activeChurches = $churches->where('is_dissolved', false);
        $activeChurchesCount = $activeChurches->count();
        $healthyChurchesCount = $activeChurches->where('is_healthy_church', true)->count();
        $bibleStudyGroupsCount = $activeChurches->where('is_healthy_church', false)->count();
        $bibleStudyPercent = $activeChurchesCount > 0
            ? (int) round(($bibleStudyGroupsCount / $activeChurchesCount) * 100)
            : 0;
        $dissolvedChurchesCount = $churches->where('is_dissolved', true)->count();
        $totalEverPlanted = $activeChurchesCount + $dissolvedChurchesCount;
        $dissolvementPercent = $totalEverPlanted > 0
            ? (int) round(($dissolvedChurchesCount / $totalEverPlanted) * 100)
            : 0;
        $deepestGeneration = (int) ($activeChurches->max('generation') ?? 1);

        $churchesByGen = [
            1 => $activeChurches->where('generation', 1)->count(),
            2 => $activeChurches->where('generation', 2)->count(),
            3 => $activeChurches->where('generation', 3)->count(),
            4 => $activeChurches->where('generation', '>=', 4)->count(),
        ];

        $generationsBreakdown = [];
        foreach ([1, 2, 3, 4] as $genBucket) {
            $bucketGroups = $genBucket < 4
                ? $activeChurches->where('generation', $genBucket)
                : $activeChurches->where('generation', '>=', 4);
            $bucketTotal = $bucketGroups->count();
            $bucketStudies = $bucketGroups->where('is_healthy_church', false)->count();
            $bucketChurches = $bucketGroups->where('is_healthy_church', true)->count();
            $generationsBreakdown[$genBucket] = [
                'total' => $bucketTotal,
                'bible_studies' => $bucketStudies,
                'churches' => $bucketChurches,
                'bible_study_percent' => $bucketTotal > 0 ? (int) round(($bucketStudies / $bucketTotal) * 100) : 0,
            ];
        }

        $leadersByTier = [
            'L1' => $greenBelievers->filter(fn (Npc $n) => $n->leadership_tier->level() === 1)->count(),
            'L2' => $greenBelievers->filter(fn (Npc $n) => $n->leadership_tier->level() === 2)->count(),
            'L3' => $greenBelievers->filter(fn (Npc $n) => $n->leadership_tier->level() === 3)->count(),
            'L4' => $greenBelievers->filter(fn (Npc $n) => $n->leadership_tier->level() === 4)->count(),
            'L5' => $greenBelievers->filter(fn (Npc $n) => $n->leadership_tier->level() === 5)->count(),
        ];

        $prayerWalksCount = PrayerWalk::where('game_session_id', $session->id)->count();
        $calendarDate = Carbon::parse('2026-04-01')->addMonths($elapsedMonths - 1)->format('M Y');

        return [
            'key' => $milestoneKey,
            'label' => $meta['label'],
            'months' => $elapsedMonths,
            'calendar_date' => $calendarDate,
            'phase' => $meta['phase'],
            'reflection' => $meta['reflection'],
            'wards_reached' => $reachedWardsCount,
            'total_wards' => $totalWards,
            'saturation_percent' => $saturationPercent,
            'unreached_count' => $unreachedCount,
            'red_acquaintances_count' => $redCount,
            'yellow_seekers_count' => $yellowCount,
            'green_believers_count' => $greenCount,
            'baptized_count' => $baptizedCount,
            'counting_cost_count' => $countingCostCount,
            'trained_411_count' => $trained411Count,
            'active_churches_count' => $activeChurchesCount,
            'healthy_churches_count' => $healthyChurchesCount,
            'bible_study_groups_count' => $bibleStudyGroupsCount,
            'bible_study_percent' => $bibleStudyPercent,
            'dissolved_churches_count' => $dissolvedChurchesCount,
            'dissolvement_percent' => $dissolvementPercent,
            'merged_groups_count' => $this->cumulativeMergers,
            'deepest_generation' => $deepestGeneration,
            'churches_by_generation' => $churchesByGen,
            'generations_breakdown' => $generationsBreakdown,
            'leaders_by_tier' => $leadersByTier,
            'prayer_walks_count' => $prayerWalksCount,
            'cumulative_attrition_events' => $this->cumulativeAttritionEvents,
            'cumulative_revitalizations' => $this->cumulativeRevitalizations,
            'pioneer_score' => (int) ($pioneer->total_generational_score ?? 0),
            'coop_score' => (int) ($pioneer->coop_synergy_score ?? 0),
        ];
    }
}
