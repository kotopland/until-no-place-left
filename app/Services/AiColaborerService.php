<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ActionResolutionOutcome;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\PrayerWalk;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class AiColaborerService
{
    public function __construct(
        protected FourFieldsEngine $engine
    ) {}

    /**
     * Ensures the 3 automated missionary co-laborers exist for this session.
     *
     * @return Collection<int, User>
     */
    public function ensureAiColaborers(GameSession $session): Collection
    {
        $wards = Ward::all()->keyBy('slug');

        $chikusa = $wards->get('chikusa-ku') ?? Ward::where('slug', 'chikusa-ku')->first() ?? Ward::first();
        $minato = $wards->get('minato-ku') ?? Ward::where('slug', 'minato-ku')->first() ?? Ward::first();
        $meito = $wards->get('meito-ku') ?? Ward::where('slug', 'meito-ku')->first() ?? Ward::first();

        // 1. Sarah Jenkins: Campus Sower (Field 1 & 2 Focus)
        $sarah = User::firstOrCreate(
            ['email' => 'sarah.jenkins@nagoyaharvest.jp'],
            [
                'name' => 'Sarah Jenkins',
                'gender' => 'female',
                'age' => 27,
                'nationality' => 'American',
                'password' => Hash::make('password123'),
                'current_game_session_id' => $session->id,
                'home_ward_id' => $chikusa?->id,
                'is_ai_colaborer' => true,
                'colaborer_archetype' => 'campus_sower',
                'ai_strategy_notes' => 'Campus sower near Nagoya University. Focuses on prayer walking, 15-second testimonies, and 3 Circles with university students.',
                'action_points' => 12,
                'max_action_points' => 12,
                'daily_action_points' => 12,
                'total_generational_score' => 90,
                'coop_synergy_score' => 20,
                'badges' => ['Campus Sower', 'Fast Starter'],
                'notify_email' => false,
                'notify_in_app' => true,
            ]
        );

        $sarah->update([
            'is_ai_colaborer' => true,
            'colaborer_archetype' => 'campus_sower',
            'current_game_session_id' => $session->id,
            'home_ward_id' => $chikusa?->id,
            'gender' => 'female',
            'age' => 27,
            'nationality' => 'American',
        ]);

        // 2. Marcus Silva: Mercy Catalyst (Field 1 Entry & Healing)
        $marcus = User::firstOrCreate(
            ['email' => 'marcus.silva@nagoyaharvest.jp'],
            [
                'name' => 'Marcus Silva',
                'gender' => 'male',
                'age' => 35,
                'nationality' => 'Brazilian',
                'password' => Hash::make('password123'),
                'current_game_session_id' => $session->id,
                'home_ward_id' => $minato?->id,
                'is_ai_colaborer' => true,
                'colaborer_archetype' => 'mercy_catalyst',
                'ai_strategy_notes' => 'Mercy catalyst working among homeless, industrial laborers, and international families. Breaks hard ground through prayer for healing and practical aid.',
                'action_points' => 12,
                'max_action_points' => 12,
                'daily_action_points' => 12,
                'total_generational_score' => 75,
                'coop_synergy_score' => 25,
                'badges' => ['Mercy Catalyst', 'Healer'],
                'notify_email' => false,
                'notify_in_app' => true,
            ]
        );

        $marcus->update([
            'is_ai_colaborer' => true,
            'colaborer_archetype' => 'mercy_catalyst',
            'current_game_session_id' => $session->id,
            'home_ward_id' => $minato?->id,
            'gender' => 'male',
            'age' => 35,
            'nationality' => 'Brazilian',
        ]);

        // 3. Kenji Yamada: Discipleship Trainer (Field 3 & 4 Focus)
        $kenji = User::firstOrCreate(
            ['email' => 'kenji.yamada@nagoyaharvest.jp'],
            [
                'name' => 'Kenji Yamada',
                'gender' => 'male',
                'age' => 42,
                'nationality' => 'Japanese',
                'password' => Hash::make('password123'),
                'current_game_session_id' => $session->id,
                'home_ward_id' => $meito?->id,
                'is_ai_colaborer' => true,
                'colaborer_archetype' => 'discipleship_trainer',
                'ai_strategy_notes' => 'Discipleship trainer equipping new believers with 411 and gathering them into Acts 2 church circles.',
                'action_points' => 12,
                'max_action_points' => 12,
                'daily_action_points' => 12,
                'total_generational_score' => 110,
                'coop_synergy_score' => 40,
                'badges' => ['Oikos Pioneer', '411 Master'],
                'notify_email' => false,
                'notify_in_app' => true,
            ]
        );

        $kenji->update([
            'is_ai_colaborer' => true,
            'colaborer_archetype' => 'discipleship_trainer',
            'current_game_session_id' => $session->id,
            'home_ward_id' => $meito?->id,
            'gender' => 'male',
            'age' => 42,
            'nationality' => 'Japanese',
        ]);

        return collect([$sarah, $marcus, $kenji]);
    }

    /**
     * Simulates daily decisions for all active AI co-laborers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function simulateDailyTurn(GameSession $session, int $gameDay): array
    {
        if (! $session->isCoopMode) {
            return [];
        }

        $colaborers = $this->ensureAiColaborers($session);
        $turnOutcomes = [];

        foreach ($colaborers as $aiUser) {
            $aiUser->refresh();
            $aiUser->update([
                'current_game_day' => $gameDay,
                'action_points' => max(4, $aiUser->action_points),
            ]);

            $homeWard = $aiUser->homeWard ?? Ward::first();
            if (! $homeWard) {
                continue;
            }

            // Decide action based on archetype
            [$actionType, $targetNpc, $targetWard] = $this->decideAiAction($aiUser, $homeWard);

            if (! $actionType) {
                continue;
            }

            $outcome = $this->engine->executeAction(
                user: $aiUser,
                ward: $targetWard,
                actionType: $actionType,
                targetNpc: $targetNpc,
                gameMonth: $session->current_game_month ?: 1,
            );

            // Deduct Action Points (default 2 AP per action)
            $apCost = max(1, $aiUser->commuteApCostForWard($targetWard) + 2);
            $aiUser->decrement('action_points', min($aiUser->action_points, $apCost));

            $turnOutcomes[] = [
                'user_id' => $aiUser->id,
                'user_name' => $aiUser->name,
                'archetype' => $aiUser->colaborer_archetype,
                'action_label' => $actionType->label(),
                'ward_name' => $targetWard->name,
                'target_npc_name' => $targetNpc?->name,
                'narrative' => $outcome->narrativeOutcome,
                'points' => $outcome->pointsAwarded,
                'is_breakthrough' => $outcome->isMajorBreakthrough,
            ];
        }

        return $turnOutcomes;
    }

    /**
     * AI Decision Heuristic according to missionary archetype.
     *
     * @return array{0: MinistryActionType|null, 1: Npc|null, 2: Ward}
     */
    protected function decideAiAction(User $aiUser, Ward $homeWard): array
    {
        return match ($aiUser->colaborer_archetype) {
            'campus_sower' => $this->decideCampusSowerAction($aiUser, $homeWard),
            'mercy_catalyst' => $this->decideMercyCatalystAction($aiUser, $homeWard),
            'discipleship_trainer' => $this->decideDiscipleshipTrainerAction($aiUser, $homeWard),
            default => [MinistryActionType::PrayerWalk, null, $homeWard],
        };
    }

    /**
     * Sarah Jenkins (Campus Sower): High Gospel Proclamation & Student Engagement
     *
     * @return array{0: MinistryActionType|null, 1: Npc|null, 2: Ward}
     */
    protected function decideCampusSowerAction(User $aiUser, Ward $homeWard): array
    {
        // 1. Look for receptive seekers in home ward
        $seeker = Npc::where('ward_id', $homeWard->id)
            ->where('status', ReceptivityStatus::Yellow)
            ->orderByDesc('trust_level')
            ->first();

        if ($seeker) {
            if ($seeker->trust_level >= 45) {
                return [MinistryActionType::Share3Circles, $seeker, $homeWard];
            }

            return [MinistryActionType::Share15SecTestimony, $seeker, $homeWard];
        }

        // 2. Look for unopened NPCs to build low-barrier trust
        $unopenedNpc = Npc::where('ward_id', $homeWard->id)
            ->where('status', ReceptivityStatus::Unreached)
            ->inRandomOrder()
            ->first();

        if ($unopenedNpc) {
            return [MinistryActionType::CasualTalk, $unopenedNpc, $homeWard];
        }

        // 3. Fallback: Prayer walk to scout new Persons of Peace
        return [MinistryActionType::PrayerWalk, null, $homeWard];
    }

    /**
     * Marcus Silva (Mercy Catalyst): Focus on brokenness, healing prayer & practical care
     *
     * @return array{0: MinistryActionType|null, 1: Npc|null, 2: Ward}
     */
    protected function decideMercyCatalystAction(User $aiUser, Ward $homeWard): array
    {
        // 1. Prioritize NPCs with physical or material brokenness
        $sickNpc = Npc::where('ward_id', $homeWard->id)
            ->where('need_state', NpcNeedState::SickNeedsHealing)
            ->first();

        if ($sickNpc) {
            return [MinistryActionType::PrayerForHealing, $sickNpc, $homeWard];
        }

        $materialNpc = Npc::where('ward_id', $homeWard->id)
            ->where('need_state', NpcNeedState::HomelessOrMaterialNeed)
            ->first();

        if ($materialNpc) {
            return [MinistryActionType::MercyCareFoodBible, $materialNpc, $homeWard];
        }

        $crisisNpc = Npc::where('ward_id', $homeWard->id)
            ->where('need_state', NpcNeedState::EmotionalCrisis)
            ->first();

        if ($crisisNpc) {
            return [MinistryActionType::DeepTalk, $crisisNpc, $homeWard];
        }

        // 2. Fallback: Prayer walk through rough neighborhoods
        return [MinistryActionType::PrayerWalk, null, $homeWard];
    }

    /**
     * Kenji Yamada (Discipleship Trainer): Discipleship (Field 3) and Church Circles (Field 4)
     *
     * @return array{0: MinistryActionType|null, 1: Npc|null, 2: Ward}
     */
    protected function decideDiscipleshipTrainerAction(User $aiUser, Ward $homeWard): array
    {
        // 1. Look for new believers needing 411 training
        $untrainedBeliever = Npc::where('ward_id', $homeWard->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('trained_411', false)
            ->first();

        if ($untrainedBeliever) {
            return [MinistryActionType::Train411, $untrainedBeliever, $homeWard];
        }

        // 2. Look for believers ready for Commands of Christ (not yet baptized)
        $unbaptizedBeliever = Npc::where('ward_id', $homeWard->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('is_baptized', false)
            ->first();

        if ($unbaptizedBeliever) {
            return [MinistryActionType::CommandsOfChrist, $unbaptizedBeliever, $homeWard];
        }

        // 3. Look for apprentice believers ready for mentorship
        $apprentice = Npc::where('ward_id', $homeWard->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('trained_411', true)
            ->where('leadership_tier', LeadershipTier::Apprentice)
            ->first();

        if ($apprentice) {
            return [MinistryActionType::MentorTimothy, $apprentice, $homeWard];
        }

        // 4. Form Church Circle if 3+ believers exist in ward without church
        $greenBelieversCount = Npc::where('ward_id', $homeWard->id)
            ->where('status', ReceptivityStatus::Green)
            ->count();

        $existingChurch = Church::where('ward_id', $homeWard->id)->exists();

        if ($greenBelieversCount >= 3 && ! $existingChurch) {
            return [MinistryActionType::ChurchCircles, null, $homeWard];
        }

        return [MinistryActionType::PrayerWalk, null, $homeWard];
    }

    /**
     * Check if user is eligible for Joint Prayer Walk (max 1 per day, max 3 per week).
     *
     * @return array{
     *     allowed: bool,
     *     reason: string|null,
     *     walks_today: int,
     *     walks_this_week: int,
     *     remaining_this_week: int
     * }
     */
    public function getJointPrayerWalkStatus(User $user): array
    {
        $currentMonth = $user->current_game_month ?: 1;
        $currentDay = $user->current_game_day ?: 1;
        $gameWeek = intdiv($currentDay - 1, 7) + 1;
        $startDay = (($gameWeek - 1) * 7) + 1;
        $endDay = min(30, $gameWeek * 7);

        // Daily walks count (game day)
        $walksToday = PrayerWalk::where('user_id', $user->id)
            ->where('game_month', $currentMonth)
            ->where('game_day', $currentDay)
            ->where('route_name', 'like', 'Joint%')
            ->count();

        // Weekly walks count (game week)
        $walksThisWeek = PrayerWalk::where('user_id', $user->id)
            ->where('game_month', $currentMonth)
            ->whereBetween('game_day', [$startDay, $endDay])
            ->where('route_name', 'like', 'Joint%')
            ->count();

        $remainingThisWeek = max(0, 3 - $walksThisWeek);

        if ($walksToday >= 1) {
            return [
                'allowed' => false,
                'reason' => 'Joint Prayer Walk limit reached: only 1 walk is allowed per day. You can partner again tomorrow!',
                'walks_today' => $walksToday,
                'walks_this_week' => $walksThisWeek,
                'remaining_this_week' => $remainingThisWeek,
            ];
        }

        if ($walksThisWeek >= 3) {
            return [
                'allowed' => false,
                'reason' => 'Joint Prayer Walk weekly limit reached: only 3 walks are allowed per week (3/3 used this week).',
                'walks_today' => $walksToday,
                'walks_this_week' => $walksThisWeek,
                'remaining_this_week' => 0,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'walks_today' => $walksToday,
            'walks_this_week' => $walksThisWeek,
            'remaining_this_week' => $remainingThisWeek,
        ];
    }

    /**
     * Joint MAWL Prayer Walk with human player and AI co-laborer.
     * Grants +50% spiritual openness bonus and +15 Co-op Synergy Points.
     * Enforces limits: 1 walk per day and at most 3 walks per week.
     */
    public function conductJointPrayerWalk(User $humanUser, User $aiColaborer, Ward $ward): ActionResolutionOutcome
    {
        $status = $this->getJointPrayerWalkStatus($humanUser);
        if (! $status['allowed']) {
            throw new \DomainException($status['reason'] ?? 'Joint Prayer Walk limit exceeded.');
        }

        $outcome = $this->engine->executeAction(
            user: $humanUser,
            ward: $ward,
            actionType: MinistryActionType::PrayerWalk,
            targetNpc: null,
            gameMonth: $humanUser->current_game_month ?: 1,
        );

        // Tag the prayer walk record as a Joint Walk
        $walk = PrayerWalk::where('user_id', $humanUser->id)
            ->where('ward_id', $ward->id)
            ->where('game_month', $humanUser->current_game_month ?: 1)
            ->where('game_day', $humanUser->current_game_day ?: 1)
            ->latest('id')
            ->first();

        if ($walk) {
            $walk->update([
                'route_name' => "Joint Prayer Walk with {$aiColaborer->name} — {$ward->name}",
            ]);
        } else {
            PrayerWalk::create([
                'game_session_id' => $humanUser->current_game_session_id ?? $ward->game_session_id,
                'user_id' => $humanUser->id,
                'ward_id' => $ward->id,
                'game_month' => $humanUser->current_game_month ?: 1,
                'game_day' => $humanUser->current_game_day ?: 1,
                'route_name' => "Joint Prayer Walk with {$aiColaborer->name} — {$ward->name}",
                'path_coords' => [[(float) ($ward->center_lat ?? $ward->latitude), (float) ($ward->center_lng ?? $ward->longitude)]],
                'pop_discovered' => false,
            ]);
        }

        // Additional Co-op Synergy Points
        $humanUser->increment('coop_synergy_score', 15);
        $aiColaborer->increment('coop_synergy_score', 15);

        // Extra spiritual softening for 2-by-2 biblical teamwork
        $ward->increment('spiritual_openness', 2);

        return $outcome;
    }

    /**
     * Get Iron-on-Iron availability and daily quota status for a player,
     * optionally evaluating against a specific co-laborer or resident NPC.
     *
     * Limits:
     * - Max 1 Iron on Iron per person per day.
     * - Max 3 Iron on Iron total per day across all individuals.
     *
     * @return array{
     *     allowed: bool,
     *     reason: ?string,
     *     total_today: int,
     *     remaining_today: int,
     *     done_with_target_today: bool
     * }
     */
    public function getIronOnIronStatus(User $user, ?User $coLaborer = null, ?Npc $npc = null): array
    {
        $currentMonth = $user->current_game_month ?: 1;
        $currentDay = $user->current_game_day ?: 1;

        $todayActions = QueuedMonthlyAction::where('user_id', $user->id)
            ->where('action_type', MinistryActionType::IronOnIron)
            ->where('game_month', $currentMonth)
            ->where('game_day', $currentDay)
            ->whereIn('status', [QueuedActionStatus::Pending, QueuedActionStatus::Processed])
            ->get();

        $totalToday = $todayActions->count();
        $remainingToday = max(0, 3 - $totalToday);

        $doneWithTargetToday = false;
        $targetName = 'this person';

        if ($coLaborer !== null) {
            $targetName = $coLaborer->name;
            $doneWithTargetToday = $todayActions->contains(function (QueuedMonthlyAction $action) use ($coLaborer): bool {
                return $action->notes !== null && str_contains($action->notes, "colaborer:{$coLaborer->id}");
            });
        } elseif ($npc !== null) {
            $targetName = $npc->name;
            $doneWithTargetToday = $todayActions->contains(function (QueuedMonthlyAction $action) use ($npc): bool {
                return (int) $action->target_npc_id === (int) $npc->id;
            });
        }

        if ($totalToday >= 3) {
            return [
                'allowed' => false,
                'reason' => 'Daily Iron on Iron limit reached: max 3 sessions allowed per day (3/3 used today).',
                'total_today' => $totalToday,
                'remaining_today' => 0,
                'done_with_target_today' => $doneWithTargetToday,
            ];
        }

        if ($doneWithTargetToday) {
            return [
                'allowed' => false,
                'reason' => "You have already conducted Iron on Iron with {$targetName} today (max 1 per person per day).",
                'total_today' => $totalToday,
                'remaining_today' => $remainingToday,
                'done_with_target_today' => true,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'total_today' => $totalToday,
            'remaining_today' => $remainingToday,
            'done_with_target_today' => false,
        ];
    }

    /**
     * Iron-on-Iron sharpening meeting between human player and AI co-laborer.
     * Awards +25 Co-op Synergy Points, +2 bonus AP, and tactical coaching tip.
     * Enforces limits: at most 1 session per person per day and at most 3 sessions total per day.
     *
     * @return array{narrative: string, bonus_ap: int, coop_points: int, coaching_tip: string}
     */
    public function conductIronOnIron(User $humanUser, User $aiColaborer): array
    {
        $status = $this->getIronOnIronStatus($humanUser, $aiColaborer);
        if (! $status['allowed']) {
            throw new \DomainException($status['reason'] ?? 'Iron on Iron daily limit exceeded.');
        }

        $humanUser->increment('coop_synergy_score', 25);
        $aiColaborer->increment('coop_synergy_score', 25);
        $humanUser->increment('action_points', 2);

        $tips = [
            'campus_sower' => "Sarah: 'Always lead with your 15-second testimony! Students in Chikusa love authentic stories before formal 3 Circles discussions.'",
            'mercy_catalyst' => "Marcus: 'When meeting someone in crisis, listen before you preach. Physical relief and sincere healing prayer unlock the hardest hearts.'",
            'discipleship_trainer' => "Kenji: 'Don't wait for perfection. As soon as someone prays the prayer of salvation, train them immediately in 411 so they can share with their Oikos.'",
        ];

        $tip = $tips[$aiColaborer->colaborer_archetype] ?? "Co-laborer: 'Until no place is left in Nagoya, let us sharpen each other in prayer and bold sowing!'";

        $narrative = "Met with {$aiColaborer->name} for an Iron-on-Iron coaching and generational mapping session. Sharpened Four Fields focus and exchanged tactical fruit.";

        // Record the completed IoI action for daily rate limiting and outcome history
        $session = $humanUser->currentGameSession ?? $aiColaborer->currentGameSession ?? GameSession::first() ?? GameSession::factory()->create();
        $ward = $aiColaborer->homeWard ?? $humanUser->homeWard ?? Ward::where('game_session_id', $session->id)->first() ?? Ward::first();
        if (! $ward) {
            $ward = Ward::create([
                'game_session_id' => $session->id,
                'name' => 'Naka-ku',
                'name_ja' => '中区',
                'slug' => 'naka-ku',
                'description' => 'Downtown Nagoya',
                'description_ja' => '名古屋の中心地',
                'population' => 90000,
                'spiritual_openness' => 20,
                'center_lat' => 35.1709,
                'center_lng' => 136.9080,
            ]);
        }

        QueuedMonthlyAction::create([
            'game_session_id' => $session->id,
            'user_id' => $humanUser->id,
            'ward_id' => $ward->id,
            'action_type' => MinistryActionType::IronOnIron,
            'target_npc_id' => null,
            'status' => QueuedActionStatus::Processed,
            'game_month' => $humanUser->current_game_month ?: 1,
            'game_day' => $humanUser->current_game_day ?: 1,
            'ap_cost' => 0,
            'notes' => "colaborer:{$aiColaborer->id}",
            'outcome_notes' => $narrative,
        ]);

        return [
            'narrative' => $narrative,
            'bonus_ap' => 2,
            'coop_points' => 25,
            'coaching_tip' => $tip,
        ];
    }
}
