<?php

namespace App\Services;

use App\DTOs\ActionResolutionOutcome;
use App\DTOs\DailyTickResolutionResult;
use App\DTOs\MonthResolutionResult;
use App\Enums\BaptismStatus;
use App\Enums\ChurchElement;
use App\Enums\ContributionRole;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\OikosRelationship;
use App\Models\PrayerWalk;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FourFieldsEngine
{
    /**
     * Resolves a single 10-minute game day tick when participating players are online.
     * Cap: Each player can play at most 30 game days per real-life day.
     */
    public function resolveDailyTick(GameSession $session, ?User $specificUser = null, bool $force = false): ?DailyTickResolutionResult
    {
        // 1. If a specific user turned the day, advance ONLY this user independently
        if ($specificUser !== null) {
            if ($specificUser->hasReachedDailyPlayLimit || $specificUser->days_processed_this_month >= 30) {
                return null;
            }

            return DB::transaction(function () use ($session, $specificUser) {
                // Pessimistically lock user row to prevent snapshot isolation / write conflict errors
                /** @var User|null $lockedUser */
                $lockedUser = User::where('id', $specificUser->id)->lockForUpdate()->first();
                if (! $lockedUser) {
                    return null;
                }

                if ($lockedUser->hasReachedDailyPlayLimit || $lockedUser->days_processed_this_month >= 30) {
                    return null;
                }

                $gameMonth = $lockedUser->current_game_month;
                $currentDay = $lockedUser->current_game_day;

                // Fetch pending actions queued for this user on or before this day in their current month
                $pendingActions = QueuedMonthlyAction::where('user_id', $lockedUser->id)
                    ->where('game_month', $gameMonth)
                    ->where('game_day', '<=', $currentDay)
                    ->where('status', QueuedActionStatus::Pending)
                    ->with(['user', 'targetNpc', 'ward'])
                    ->get();

                $actionsProcessed = 0;
                $outcomes = [];
                $scoreGain = 0;
                $coopScoreGain = 0;

                foreach ($pendingActions as $action) {
                    $outcome = $this->resolveSingleAction($action, $gameMonth);
                    $outcomes[] = $outcome;
                    $actionsProcessed++;
                    if ($action->action_type->isWorkerRhythm()) {
                        $coopScoreGain += $outcome->pointsAwarded;
                    } else {
                        $scoreGain += $outcome->pointsAwarded;
                    }

                    $action->update([
                        'status' => QueuedActionStatus::Processed,
                        'outcome_notes' => $outcome->narrativeOutcome,
                    ]);
                }

                $today = now()->startOfDay();
                $isNewRealDay = ! $lockedUser->isPlayedToday();
                $daysPlayedToday = $isNewRealDay ? 1 : ($lockedUser->days_played_today + 1);

                $hasSabbath = $pendingActions->contains(fn ($a) => $a->action_type === MinistryActionType::SabbathRest);
                $nextDay = min(30, $lockedUser->current_game_day + 1);

                $isBurnedOut = $lockedUser->is_burned_out;
                $burnedOutUntilMonth = $lockedUser->burned_out_until_game_month;
                $consecutiveExhaustion = $lockedUser->consecutive_exhaustion_days;
                $fatiguePenalty = $lockedUser->fatigue_penalty_ap;
                $burnoutCount = $lockedUser->burnout_count;

                if ($isBurnedOut) {
                    if ($burnedOutUntilMonth !== null && $lockedUser->current_game_month >= $burnedOutUntilMonth) {
                        $isBurnedOut = false;
                        $burnedOutUntilMonth = null;
                        $consecutiveExhaustion = 0;
                        $fatiguePenalty = 0;
                        $newAp = $lockedUser->max_action_points;
                    } else {
                        $newAp = 0;
                    }
                } elseif ($hasSabbath) {
                    // Observing Sabbath rest restores soul and body, resetting fatigue!
                    $isBurnedOut = false;
                    $burnedOutUntilMonth = null;
                    $consecutiveExhaustion = 0;
                    $fatiguePenalty = 0;
                    $newAp = $lockedUser->max_action_points;
                } else {
                    // Check if player used up their AP today (remaining AP <= 1)
                    if ($lockedUser->action_points <= 1) {
                        $consecutiveExhaustion++;
                        if ($consecutiveExhaustion >= 5) {
                            // Sustained overwork leads to collapse into 3-month burnout
                            $isBurnedOut = true;
                            $burnedOutUntilMonth = $lockedUser->current_game_month + 3;
                            $burnoutCount++;
                            $fatiguePenalty = $lockedUser->daily_action_points;
                            $newAp = 0;
                        } else {
                            // Progressive fatigue penalty: reduces available AP next days (-2, -4, -6 AP)
                            $fatiguePenalty = ($consecutiveExhaustion - 1) * 2;
                            $replenished = max(0, $lockedUser->daily_action_points - $fatiguePenalty);
                            $effectiveMaxAp = max(2, $lockedUser->max_action_points - $fatiguePenalty);
                            $newAp = min($effectiveMaxAp, $lockedUser->action_points + $replenished);
                        }
                    } else {
                        // Moderated labor or left unspent AP: streak broken, fatigue clears!
                        $consecutiveExhaustion = 0;
                        $fatiguePenalty = 0;
                        $newAp = min($lockedUser->max_action_points, $lockedUser->action_points + $lockedUser->daily_action_points);
                    }
                }

                $userUpdates = [
                    'current_game_day' => $nextDay,
                    'last_daily_tick_at' => now(),
                    'next_daily_tick_at' => now()->addMinutes(10),
                    'action_points' => $newAp,
                    'consecutive_exhaustion_days' => $consecutiveExhaustion,
                    'fatigue_penalty_ap' => $fatiguePenalty,
                    'is_burned_out' => $isBurnedOut,
                    'burned_out_until_game_month' => $burnedOutUntilMonth,
                    'burnout_count' => $burnoutCount,
                    'days_played_today' => $daysPlayedToday,
                    'days_processed_this_month' => $lockedUser->days_processed_this_month + 1,
                    'last_active_at' => now(),
                    'last_played_real_date' => $today,
                ];

                if ($scoreGain > 0) {
                    $userUpdates['total_generational_score'] = $lockedUser->total_generational_score + $scoreGain;
                }
                if ($coopScoreGain > 0) {
                    $userUpdates['coop_synergy_score'] = ($lockedUser->coop_synergy_score ?? 0) + $coopScoreGain;
                }

                $lockedUser->update($userUpdates);

                // Sync attributes back to caller's in-memory model instance
                $specificUser->fill($lockedUser->getAttributes());
                $specificUser->syncOriginal();

                // Update session state without modifying other players
                $session->update([
                    'current_game_day' => max($session->current_game_day, $nextDay),
                    'days_processed_this_month' => max($session->days_processed_this_month, $lockedUser->days_processed_this_month),
                    'last_daily_tick_at' => now(),
                    'next_daily_tick_at' => now()->addMinutes(10),
                ]);

                // Simulate AI co-laborer turns if co-op mode is active
                if ($session->isCoopMode) {
                    app(AiColaborerService::class)->simulateDailyTurn($session, $nextDay);
                }

                return new DailyTickResolutionResult(
                    gameSessionId: $session->id,
                    gameMonth: $gameMonth,
                    gameDay: $currentDay,
                    actionsProcessed: $actionsProcessed,
                    onlinePlayersCount: 1,
                    actionOutcomes: $outcomes,
                    playerScoreGains: [$lockedUser->id => $scoreGain],
                    onlineUserIds: [$lockedUser->id],
                );
            }, attempts: 3);
        }

        // 2. Global / automated tick across online players who are due
        if (! $session->is_active || $session->days_processed_this_month >= 30) {
            return null;
        }

        // Identify online players (active within last 15 minutes) who have not reached the 30-day cap today
        $onlineUsers = User::where('current_game_session_id', $session->id)
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subMinutes(15))
            ->get();

        if ($force) {
            $eligibleUsers = $onlineUsers->isEmpty()
                ? User::where('current_game_session_id', $session->id)->get()
                : $onlineUsers;
        } else {
            $eligibleUsers = $onlineUsers->filter(fn (User $user) => ! $user->hasReachedDailyPlayLimit && $user->days_processed_this_month < 30);

            if ($eligibleUsers->isEmpty()) {
                return null;
            }
        }

        return DB::transaction(function () use ($session, $eligibleUsers) {
            $gameMonth = $session->current_game_month;
            $currentDay = $session->current_game_day;

            $lockedUsers = User::whereIn('id', $eligibleUsers->pluck('id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Fetch pending actions queued for this day or earlier days in this month
            $pendingActions = QueuedMonthlyAction::where('game_session_id', $session->id)
                ->where('game_month', $gameMonth)
                ->where('game_day', '<=', $currentDay)
                ->where('status', QueuedActionStatus::Pending)
                ->with(['user', 'targetNpc', 'ward'])
                ->get();

            $actionsProcessed = 0;
            $outcomes = [];
            $playerScoreGains = [];
            $playerCoopGains = [];

            foreach ($pendingActions as $action) {
                $outcome = $this->resolveSingleAction($action, $gameMonth);
                $outcomes[] = $outcome;
                $actionsProcessed++;

                $userId = $action->user_id;

                if ($action->action_type->isWorkerRhythm()) {
                    $playerCoopGains[$userId] = ($playerCoopGains[$userId] ?? 0) + $outcome->pointsAwarded;
                } else {
                    $playerScoreGains[$userId] = ($playerScoreGains[$userId] ?? 0) + $outcome->pointsAwarded;
                }

                $action->update([
                    'status' => QueuedActionStatus::Processed,
                    'outcome_notes' => $outcome->narrativeOutcome,
                ]);
            }

            // Award points and replenish daily AP for eligible active players
            $today = now()->startOfDay();
            foreach ($lockedUsers as $user) {
                $scoreGain = $playerScoreGains[$user->id] ?? 0;
                $coopGain = $playerCoopGains[$user->id] ?? 0;
                $isNewRealDay = ! $user->isPlayedToday();
                $daysPlayedToday = $isNewRealDay ? 1 : ($user->days_played_today + 1);

                $userHasSabbath = $pendingActions->where('user_id', $user->id)
                    ->contains(fn ($a) => $a->action_type === MinistryActionType::SabbathRest);
                $nextDay = min(30, $user->current_game_day + 1);

                $isBurnedOut = $user->is_burned_out;
                $burnedOutUntilMonth = $user->burned_out_until_game_month;
                $consecutiveExhaustion = $user->consecutive_exhaustion_days;
                $fatiguePenalty = $user->fatigue_penalty_ap;
                $burnoutCount = $user->burnout_count;

                if ($isBurnedOut) {
                    if ($burnedOutUntilMonth !== null && $user->current_game_month >= $burnedOutUntilMonth) {
                        $isBurnedOut = false;
                        $burnedOutUntilMonth = null;
                        $consecutiveExhaustion = 0;
                        $fatiguePenalty = 0;
                        $newAp = $user->max_action_points;
                    } else {
                        $newAp = 0;
                    }
                } elseif ($userHasSabbath) {
                    $isBurnedOut = false;
                    $burnedOutUntilMonth = null;
                    $consecutiveExhaustion = 0;
                    $fatiguePenalty = 0;
                    $newAp = $user->max_action_points;
                } else {
                    if ($user->action_points <= 1) {
                        $consecutiveExhaustion++;
                        if ($consecutiveExhaustion >= 5) {
                            $isBurnedOut = true;
                            $burnedOutUntilMonth = $user->current_game_month + 3;
                            $burnoutCount++;
                            $fatiguePenalty = $user->daily_action_points;
                            $newAp = 0;
                        } else {
                            $fatiguePenalty = ($consecutiveExhaustion - 1) * 2;
                            $replenished = max(0, $user->daily_action_points - $fatiguePenalty);
                            $effectiveMaxAp = max(2, $user->max_action_points - $fatiguePenalty);
                            $newAp = min($effectiveMaxAp, $user->action_points + $replenished);
                        }
                    } else {
                        $consecutiveExhaustion = 0;
                        $fatiguePenalty = 0;
                        $newAp = min($user->max_action_points, $user->action_points + $user->daily_action_points);
                    }
                }

                $userUpdates = [
                    'current_game_day' => $nextDay,
                    'last_daily_tick_at' => now(),
                    'next_daily_tick_at' => now()->addMinutes(10),
                    'action_points' => $newAp,
                    'consecutive_exhaustion_days' => $consecutiveExhaustion,
                    'fatigue_penalty_ap' => $fatiguePenalty,
                    'is_burned_out' => $isBurnedOut,
                    'burned_out_until_game_month' => $burnedOutUntilMonth,
                    'burnout_count' => $burnoutCount,
                    'days_played_today' => $daysPlayedToday,
                    'days_processed_this_month' => $user->days_processed_this_month + 1,
                    'last_active_at' => now(),
                    'last_played_real_date' => $today,
                ];

                if ($scoreGain > 0) {
                    $userUpdates['total_generational_score'] = $user->total_generational_score + $scoreGain;
                }
                if ($coopGain > 0) {
                    $userUpdates['coop_synergy_score'] = ($user->coop_synergy_score ?? 0) + $coopGain;
                }

                $user->update($userUpdates);
            }

            // Advance session day counters
            $nextDay = min(30, $session->current_game_day + 1);
            $session->increment('days_processed_this_month');
            $session->update([
                'current_game_day' => $nextDay,
                'last_daily_tick_at' => now(),
                'next_daily_tick_at' => now()->addMinutes(10),
            ]);

            // Simulate AI co-laborer turns if co-op mode is active
            if ($session->isCoopMode) {
                app(AiColaborerService::class)->simulateDailyTurn($session, $nextDay);
            }

            return new DailyTickResolutionResult(
                gameSessionId: $session->id,
                gameMonth: $gameMonth,
                gameDay: $currentDay,
                actionsProcessed: $actionsProcessed,
                onlinePlayersCount: $lockedUsers->count(),
                actionOutcomes: $outcomes,
                playerScoreGains: $playerScoreGains,
                onlineUserIds: $lockedUsers->keys()->all(),
            );
        }, attempts: 3);
    }

    /**
     * Resolves the entire monthly cycle for a Game Session.
     * Any remaining days in the 30-day month (30 - days_processed_this_month)
     * are batch-processed here along with autonomous multiplication.
     */
    public function resolveGameMonth(GameSession $session): MonthResolutionResult
    {
        return DB::transaction(function () use ($session) {
            $gameMonth = $session->current_game_month;

            // 1. Fetch remaining pending queued player actions (batch-processed for remainder of month)
            $pendingActions = QueuedMonthlyAction::where('game_session_id', $session->id)
                ->where('game_month', $gameMonth)
                ->where('status', QueuedActionStatus::Pending)
                ->with(['user', 'targetNpc', 'ward'])
                ->get();

            $actionsProcessed = 0;
            $breakthroughsCount = 0;
            $newBelieversCount = 0;
            $baptismsCount = 0;
            $trained411Count = 0;
            $prayersAnswered = 0;
            $coopCollaborationsCount = 0;
            $outcomes = [];
            $playerScoreGains = [];
            $coachTips = [];

            // 2. Resolve Player Actions
            foreach ($pendingActions as $action) {
                $outcome = $this->resolveSingleAction($action, $gameMonth);
                $outcomes[] = $outcome;
                $actionsProcessed++;

                if ($outcome->isMajorBreakthrough) {
                    $breakthroughsCount++;
                }

                if ($outcome->newStatus === ReceptivityStatus::Green && $outcome->previousStatus !== ReceptivityStatus::Green) {
                    $newBelieversCount++;
                }

                if ($outcome->isBaptismCompleted) {
                    $baptismsCount++;
                }

                if ($action->action_type === MinistryActionType::Train411 && $outcome->statusChanged) {
                    $trained411Count++;
                }

                if ($outcome->prayerAnswered) {
                    $prayersAnswered++;
                }

                if ($outcome->coopPointsAwarded > 0) {
                    $coopCollaborationsCount++;
                }

                // Tally player points
                $userId = $action->user_id;
                $playerScoreGains[$userId] = ($playerScoreGains[$userId] ?? 0) + $outcome->pointsAwarded;

                if (! empty($outcome->pedagogicalTip)) {
                    $coachTips[] = [
                        'user_id' => $userId,
                        'action' => $action->action_type->label(),
                        'tip' => $outcome->pedagogicalTip,
                    ];
                }

                // Mark action processed
                $action->update([
                    'status' => QueuedActionStatus::Processed,
                    'outcome_notes' => $outcome->narrativeOutcome,
                ]);
            }

            // 3. Autonomous Disciple Multiplication Phase (Field 3 & Field 5)
            $multiplicationData = $this->runAutonomousMultiplication($session, $gameMonth);
            $autonomousMultiplications = $multiplicationData['new_believers_count'];
            $churchesPlanted = $multiplicationData['new_churches_count'];
            $autonomousHighlights = $multiplicationData['highlights'];

            // 3b. Church Member Attrition & Dissolution Simulation
            $attritionData = $this->simulateChurchAttrition($session, $gameMonth);
            $autonomousHighlights = array_merge($autonomousHighlights, $attritionData['highlights']);

            // Merge autonomous generational scores
            foreach ($multiplicationData['player_scores'] as $pId => $pts) {
                $playerScoreGains[$pId] = ($playerScoreGains[$pId] ?? 0) + $pts;
            }

            // 4. Update player models and reset monthly bandwidth Action Points
            $participatingUsers = User::where('current_game_session_id', $session->id)->lockForUpdate()->get();
            foreach ($participatingUsers as $user) {
                // If a player has already changed their month today, do NOT change it to the next one!
                if ($user->hasChangedMonthToday) {
                    $user->update(['days_played_today' => 0]);

                    continue;
                }

                $scoreGain = $playerScoreGains[$user->id] ?? 0;
                $nextMonth = $user->current_game_month + 1;

                $isBurnedOut = $user->is_burned_out;
                $burnedOutUntilMonth = $user->burned_out_until_game_month;
                $consecutiveExhaustion = $user->consecutive_exhaustion_days;
                $fatiguePenalty = $user->fatigue_penalty_ap;

                if ($isBurnedOut) {
                    if ($burnedOutUntilMonth !== null && $nextMonth >= $burnedOutUntilMonth) {
                        $isBurnedOut = false;
                        $burnedOutUntilMonth = null;
                        $consecutiveExhaustion = 0;
                        $fatiguePenalty = 0;
                        $actionPoints = $user->max_action_points;
                        $autonomousHighlights[] = "🕊️ [BURNOUT HEALED] {$user->name} has completed 3 months of mandatory medical leave and is fully restored!";
                    } else {
                        $monthsLeft = max(1, ($burnedOutUntilMonth ?? $nextMonth) - $nextMonth);
                        $actionPoints = 0;
                        $autonomousHighlights[] = "⚠️ [BURNOUT SABBATICAL] {$user->name} is on mandatory burnout medical leave ({$monthsLeft} month(s) remaining).";
                    }
                } else {
                    $actionPoints = $user->max_action_points;
                }

                $userUpdates = [
                    'current_game_month' => $nextMonth,
                    'current_game_day' => 1,
                    'days_processed_this_month' => 0,
                    'last_monthly_tick_at' => now(),
                    'action_points' => $actionPoints,
                    'is_burned_out' => $isBurnedOut,
                    'burned_out_until_game_month' => $burnedOutUntilMonth,
                    'consecutive_exhaustion_days' => $consecutiveExhaustion,
                    'fatigue_penalty_ap' => $fatiguePenalty,
                    'days_played_today' => 0,
                ];

                if ($scoreGain > 0) {
                    $userUpdates['total_generational_score'] = $user->total_generational_score + $scoreGain;
                }

                $user->update($userUpdates);
            }

            // 5. Advance seeking and baptism timeline across the month for residents in this session
            Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Yellow)
                ->increment('seeking_months');

            Npc::where('game_session_id', $session->id)
                ->where('status', ReceptivityStatus::Green)
                ->where('is_baptized', false)
                ->increment('baptism_cost_months');

            // 6. Advance Game Session Calendar and reset daily tick counters
            $session->increment('current_game_month');
            $session->update([
                'current_game_day' => 1,
                'days_processed_this_month' => 0,
                'last_monthly_tick_at' => now(),
                'next_daily_tick_at' => now()->addMinutes(10),
                'calendar_date' => Carbon::parse($session->calendar_date)->addMonth(),
                'next_tick_at' => now()->addMinutes($session->month_duration_minutes),
            ]);

            return new MonthResolutionResult(
                gameSessionId: $session->id,
                gameMonth: $gameMonth,
                actionsProcessed: $actionsProcessed,
                breakthroughsCount: $breakthroughsCount,
                newBelieversCount: $newBelieversCount,
                baptismsCount: $baptismsCount,
                trained411Count: $trained411Count,
                autonomousMultiplications: $autonomousMultiplications,
                churchesPlanted: $churchesPlanted,
                prayersAnswered: $prayersAnswered,
                coopCollaborationsCount: $coopCollaborationsCount,
                actionOutcomes: $outcomes,
                playerScoreGains: $playerScoreGains,
                autonomousHighlights: $autonomousHighlights,
                coachTips: $coachTips,
            );
        }, attempts: 3);
    }

    /**
     * Resolves the entire monthly cycle for a single player independently.
     */
    public function resolveGameMonthForUser(User $user): MonthResolutionResult
    {
        return DB::transaction(function () use ($user) {
            $session = $user->currentGameSession ?? GameSession::first();
            $gameMonth = $user->current_game_month;

            // 1. Fetch remaining pending queued player actions for this user in this month
            $pendingActions = QueuedMonthlyAction::where('user_id', $user->id)
                ->where('game_month', $gameMonth)
                ->where('status', QueuedActionStatus::Pending)
                ->with(['user', 'targetNpc', 'ward'])
                ->get();

            $actionsProcessed = 0;
            $breakthroughsCount = 0;
            $newBelieversCount = 0;
            $baptismsCount = 0;
            $trained411Count = 0;
            $prayersAnswered = 0;
            $coopCollaborationsCount = 0;
            $outcomes = [];
            $playerScoreGain = 0;
            $coachTips = [];

            foreach ($pendingActions as $action) {
                $outcome = $this->resolveSingleAction($action, $gameMonth);
                $outcomes[] = $outcome;
                $actionsProcessed++;

                if ($outcome->isMajorBreakthrough) {
                    $breakthroughsCount++;
                }

                if ($outcome->newStatus === ReceptivityStatus::Green && $outcome->previousStatus !== ReceptivityStatus::Green) {
                    $newBelieversCount++;
                }

                if ($outcome->isBaptismCompleted) {
                    $baptismsCount++;
                }

                if ($action->action_type === MinistryActionType::Train411 && $outcome->statusChanged) {
                    $trained411Count++;
                }

                if ($outcome->prayerAnswered) {
                    $prayersAnswered++;
                }

                if ($outcome->coopPointsAwarded > 0) {
                    $coopCollaborationsCount++;
                }

                $playerScoreGain += $outcome->pointsAwarded;

                if (! empty($outcome->pedagogicalTip)) {
                    $coachTips[] = [
                        'user_id' => $user->id,
                        'action' => $action->action_type->label(),
                        'tip' => $outcome->pedagogicalTip,
                    ];
                }

                $action->update([
                    'status' => QueuedActionStatus::Processed,
                    'outcome_notes' => $outcome->narrativeOutcome,
                ]);
            }

            // 2. Autonomous Disciple Multiplication Phase
            $multiplicationData = $this->runAutonomousMultiplication($session ?? GameSession::first(), $gameMonth);
            $autonomousMultiplications = $multiplicationData['new_believers_count'];
            $churchesPlanted = $multiplicationData['new_churches_count'];
            $autonomousHighlights = $multiplicationData['highlights'];

            // 2b. Church Member Attrition & Dissolution Simulation
            $attritionData = $this->simulateChurchAttrition($session ?? GameSession::first(), $gameMonth);
            $autonomousHighlights = array_merge($autonomousHighlights, $attritionData['highlights']);

            $userAutonomousScore = $multiplicationData['player_scores'][$user->id] ?? 0;
            $totalScoreGain = $playerScoreGain + $userAutonomousScore;

            if ($totalScoreGain > 0) {
                $user->increment('total_generational_score', $totalScoreGain);
            }

            // 3. Generate Monthly Report for this user
            MonthlyReport::create([
                'game_session_id' => $session?->id,
                'user_id' => $user->id,
                'game_month' => $gameMonth,
                'summary_title' => "Nagoya Movement Digest — Month {$gameMonth}",
                'highlights_json' => $autonomousHighlights,
                'answered_prayers_json' => [
                    'count' => $prayersAnswered,
                    'notes' => $prayersAnswered > 0 ? 'God moved in power through intercession this month!' : 'Continue faithful prayer walking and intercession.',
                ],
                'disciples_multiplied_count' => $autonomousMultiplications,
                'churches_planted_count' => $churchesPlanted,
                'generational_score_gained' => $totalScoreGain,
                'total_generational_score' => $user->total_generational_score,
                'coach_tips_json' => $coachTips,
                'read_at' => null,
            ]);

            // 4. Advance seeking and baptism timeline across the month for residents in this session
            if ($session) {
                Npc::where('game_session_id', $session->id)
                    ->where('status', ReceptivityStatus::Yellow)
                    ->increment('seeking_months');

                Npc::where('game_session_id', $session->id)
                    ->where('status', ReceptivityStatus::Green)
                    ->where('is_baptized', false)
                    ->increment('baptism_cost_months');
            }

            // 5. Advance user to next game month and reset day counters
            $user->increment('current_game_month');
            $newMonth = $user->current_game_month;

            $isBurnedOut = $user->is_burned_out;
            $burnedOutUntilMonth = $user->burned_out_until_game_month;
            $consecutiveExhaustion = $user->consecutive_exhaustion_days;
            $fatiguePenalty = $user->fatigue_penalty_ap;

            if ($isBurnedOut) {
                if ($burnedOutUntilMonth !== null && $newMonth >= $burnedOutUntilMonth) {
                    $isBurnedOut = false;
                    $burnedOutUntilMonth = null;
                    $consecutiveExhaustion = 0;
                    $fatiguePenalty = 0;
                    $actionPoints = $user->max_action_points;
                    $autonomousHighlights[] = "🕊️ [BURNOUT HEALED] {$user->name} has completed 3 months of mandatory medical leave and is fully restored!";
                } else {
                    $monthsLeft = max(1, ($burnedOutUntilMonth ?? $newMonth) - $newMonth);
                    $actionPoints = 0;
                    $autonomousHighlights[] = "⚠️ [BURNOUT SABBATICAL] {$user->name} is on mandatory burnout medical leave ({$monthsLeft} month(s) remaining).";
                }
            } else {
                $actionPoints = $user->max_action_points;
            }

            $user->update([
                'current_game_day' => 1,
                'days_processed_this_month' => 0,
                'last_monthly_tick_at' => now(),
                'next_daily_tick_at' => now()->addMinutes(10),
                'action_points' => $actionPoints,
                'is_burned_out' => $isBurnedOut,
                'burned_out_until_game_month' => $burnedOutUntilMonth,
                'consecutive_exhaustion_days' => $consecutiveExhaustion,
                'fatigue_penalty_ap' => $fatiguePenalty,
                'days_played_today' => 0,
            ]);

            return new MonthResolutionResult(
                gameSessionId: $session ? $session->id : 1,
                gameMonth: $gameMonth,
                actionsProcessed: $actionsProcessed,
                breakthroughsCount: $breakthroughsCount,
                newBelieversCount: $newBelieversCount,
                baptismsCount: $baptismsCount,
                trained411Count: $trained411Count,
                autonomousMultiplications: $autonomousMultiplications,
                churchesPlanted: $churchesPlanted,
                prayersAnswered: $prayersAnswered,
                coopCollaborationsCount: $coopCollaborationsCount,
                actionOutcomes: $outcomes,
                playerScoreGains: [$user->id => (int) $totalScoreGain],
                autonomousHighlights: $autonomousHighlights,
                coachTips: $coachTips,
            );
        });
    }

    /**
     * Executes and resolves an immediate action for tests or direct simulation.
     */
    public function executeAction(User $user, Ward $ward, MinistryActionType $actionType, ?Npc $targetNpc = null, int $gameMonth = 1): ActionResolutionOutcome
    {
        $action = QueuedMonthlyAction::create([
            'game_session_id' => $user->current_game_session_id ?? $ward->game_session_id,
            'user_id' => $user->id,
            'ward_id' => $ward->id,
            'action_type' => $actionType,
            'target_npc_id' => $targetNpc?->id,
            'game_month' => $gameMonth,
            'game_day' => $user->current_game_day ?: 1,
            'status' => QueuedActionStatus::Pending,
        ]);

        $outcome = $this->resolveSingleAction($action, $gameMonth);

        $action->update([
            'status' => QueuedActionStatus::Processed,
            'outcome_notes' => $outcome->narrativeOutcome,
        ]);

        return $outcome;
    }

    /**
     * Resolves a single player ministry action with holistic Japanese relational dynamics.
     */
    public function resolveSingleAction(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $ward = $action->ward;
        $npc = $action->targetNpc;

        // Handle ward-wide platform actions (e.g., International Event, Prayer Walk, Swarm Training)
        if ($action->action_type === MinistryActionType::InternationalEvent) {
            return $this->resolveInternationalEvent($action, $gameMonth);
        }

        if ($action->action_type === MinistryActionType::PrayerWalk) {
            return $this->resolvePrayerWalk($action, $gameMonth);
        }

        if ($action->action_type === MinistryActionType::SwarmTraining && ! $npc) {
            return $this->resolveSwarmTrainingPlatform($action, $gameMonth);
        }

        if ($action->action_type === MinistryActionType::ReceiveMentoring) {
            return $this->resolveReceiveMentoring($action, $gameMonth);
        }

        if ($action->action_type === MinistryActionType::SabbathRest) {
            return $this->resolveSabbathRest($action, $gameMonth);
        }

        if ($action->action_type === MinistryActionType::FastingRetreat) {
            return $this->resolveFastingRetreat($action, $gameMonth);
        }

        if ($action->action_type === MinistryActionType::MidLevelConference) {
            return $this->resolveMidLevelConference($action, $gameMonth);
        }

        if (! $npc) {
            return new ActionResolutionOutcome(
                actionId: $action->id,
                actionType: $action->action_type,
                userId: $action->user_id,
                targetNpcId: null,
                wardId: $action->ward_id,
                trustChange: 0,
                newTrustLevel: 0,
                previousStatus: ReceptivityStatus::Unreached,
                newStatus: ReceptivityStatus::Unreached,
                statusChanged: false,
                pointsAwarded: 0,
                coopPointsAwarded: 0,
                roleAssigned: null,
                collaboratorsImpacted: [],
                narrativeOutcome: 'Action skipped: target resident no longer available.',
                pedagogicalTip: 'Always confirm that the target person of peace is reachable in the ward.',
            );
        }

        $prevStatus = $npc->status;
        $prevTrust = $npc->trust_level;
        $trustChange = 0;
        $newStatus = $prevStatus;
        $narrative = '';
        $coachTip = '';
        $prayerAnswered = false;
        $isBaptismCompleted = false;
        $unlockedOikos = false;
        $points = 0;
        $roleAssigned = ContributionRole::Waterer;

        // Check if other players previously engaged this NPC
        $priorOtherContributions = NpcContribution::where('npc_id', $npc->id)
            ->where('user_id', '!=', $user->id)
            ->get();
        $isCollaborative = $priorOtherContributions->isNotEmpty();

        switch ($action->action_type) {
            case MinistryActionType::CasualTalk:
                // Field 1: Low-barrier friendship building
                $trustChange = match ($npc->need_state) {
                    NpcNeedState::Lonely => rand(18, 25),
                    default => rand(10, 18),
                };

                $roleAssigned = $npc->contributions()->count() === 0
                    ? ContributionRole::PioneerSower
                    : ContributionRole::Waterer;

                if ($prevStatus === ReceptivityStatus::Unreached) {
                    $newStatus = ReceptivityStatus::Red; // Moved from stranger to known acquaintance
                }

                // If trust reaches 50+, NPC opens up to personal discussions
                if (($prevTrust + $trustChange) >= 50 && $newStatus === ReceptivityStatus::Red) {
                    $newStatus = ReceptivityStatus::Yellow;
                }

                $points = 10;
                $narrative = "Shared casual conversation over tea with {$npc->name}. Trust grew steadily.";
                $coachTip = 'In Japan, trust (Shinrai) precedes truth. Consistent casual presence lays the foundation for deeper questions.';
                break;

            case MinistryActionType::DeepTalk:
                // Field 1: Active listening & emotional heart care
                if ($npc->need_state === NpcNeedState::EmotionalCrisis || $npc->need_state === NpcNeedState::Lonely) {
                    $trustChange = rand(22, 32);
                    $narrative = "Listened attentively to {$npc->name}'s emotional struggles and workplace burnout. They felt truly heard and respected.";
                    $coachTip = 'Active listening without rushing to preach disarms Japanese societal defense mechanisms and softens hardened hearts.';

                    // Breakthrough: Red -> Yellow transition
                    if ($prevStatus === ReceptivityStatus::Red || $prevStatus === ReceptivityStatus::Unreached) {
                        $newStatus = ReceptivityStatus::Yellow;
                    }
                } else {
                    $trustChange = rand(14, 20);
                    $narrative = "Engaged {$npc->name} in a meaningful conversation regarding life values and family.";
                    $coachTip = 'Deep conversations unlock hidden worldviews and prepare hearts for the Gospel story.';
                }

                $points = 20;
                $roleAssigned = ContributionRole::Waterer;
                break;

            case MinistryActionType::PrayerForHealing:
                // Field 1 & 2: Power encounter and intercession
                if ($npc->need_state === NpcNeedState::SickNeedsHealing) {
                    // 70% probability of divine miracle breakthrough
                    $miracleSuccess = rand(1, 100) <= 70;

                    if ($miracleSuccess) {
                        $prayerAnswered = true;
                        $trustChange = rand(30, 40);
                        $npc->update(['need_state' => NpcNeedState::None]); // Healed!

                        $newStatus = match ($prevStatus) {
                            ReceptivityStatus::Unreached, ReceptivityStatus::Red => ReceptivityStatus::Yellow,
                            ReceptivityStatus::Yellow => $npc->isReadyForConversion($gameMonth) ? ReceptivityStatus::Green : ReceptivityStatus::Yellow,
                            default => $prevStatus,
                        };

                        if ($newStatus === ReceptivityStatus::Green && $prevStatus !== ReceptivityStatus::Green) {
                            $npc->update([
                                'prayed_salvation' => true,
                                'prayed_salvation_at' => now(),
                                'prayed_salvation_month' => $gameMonth,
                                'baptism_status' => BaptismStatus::CountingCost,
                                'baptism_cost_months' => 0,
                            ]);
                        }

                        // Luke 10:9 Household Ripple: All immediate Oikos relations receive +20 trust!
                        OikosRelationship::where('npc_id', $npc->id)->increment('trust_level', 20);

                        $points = 50;
                        $narrative = "Miraculous answer to prayer! {$npc->name} experienced immediate physical relief and peace. Their whole household was amazed!";
                        $coachTip = "Luke 10:9: 'Heal the sick who are there and tell them, The kingdom of God has come near to you.' Physical and emotional healing opens entire families.";
                    } else {
                        $trustChange = rand(12, 18);
                        $narrative = "Prayed sincerely for {$npc->name}'s health. They felt touched by the spiritual care and warmth.";
                        $coachTip = "Even when physical healing is gradual, genuine prayer demonstrates Christ's compassion.";
                        $points = 15;
                    }
                } else {
                    $trustChange = rand(10, 16);
                    $narrative = "Prayed a blessing over {$npc->name}'s life and family peace.";
                    $coachTip = 'Offering prayer for peace is universally welcomed in Japanese culture when asked politely.';
                    $points = 10;
                }
                $roleAssigned = ContributionRole::Waterer;
                break;

            case MinistryActionType::MercyCareFoodBible:
                // Field 1 & Field 4: Compassion / Word + Deed
                if ($npc->need_state === NpcNeedState::HomelessOrMaterialNeed) {
                    $trustChange = rand(28, 38);
                    $points = 40;
                    $narrative = "Served {$npc->name} a warm, nutritious meal with dignity, clean socks, and a Gospel portion. Years of cynicism melted.";
                    $coachTip = 'Mercy ministry demonstrates the Gospel before speaking it. Acts 2 fellowship and giving unlock the most marginalized hearts.';

                    if ($prevStatus === ReceptivityStatus::Red || $prevStatus === ReceptivityStatus::Unreached) {
                        $newStatus = ReceptivityStatus::Yellow;
                    }
                } else {
                    $trustChange = rand(12, 18);
                    $points = 15;
                    $narrative = "Provided practical support and shared Scripture with {$npc->name}.";
                    $coachTip = 'Practical service and the Word go hand-in-hand in the Acts 2 Church Circle.';
                }
                $roleAssigned = ContributionRole::Waterer;
                break;

            case MinistryActionType::Share15SecTestimony:
                // Field 2: Personal testimony bridging
                if ($prevTrust >= 30) {
                    $trustChange = rand(12, 20);
                    $points = 25;
                    $narrative = "Shared a 15-second testimony with {$npc->name} describing personal transformation from brokenness to rest in Christ.";
                    $coachTip = "The 15-second testimony is clear, concise, and relational. It explains 'There was a time when...' without being preachy.";

                    if ($prevStatus === ReceptivityStatus::Red) {
                        $newStatus = ReceptivityStatus::Yellow;
                    }
                } else {
                    $trustChange = rand(4, 8);
                    $points = 10;
                    $narrative = "Shared personal testimony, but {$npc->name} seemed polite yet reserved.";
                    $coachTip = 'When trust is below 30%, start with Casual Talk or Deep Listening before sharing your personal story.';
                }
                $roleAssigned = ContributionRole::Waterer;
                break;

            case MinistryActionType::Share3Circles:
                // Field 2: Gospel Presentation & Call to Decision
                $seekingMonths = $npc->effectiveSeekingMonths($gameMonth);
                $targetSeekingMonths = max(1, $npc->target_seeking_months);

                if ($prevStatus === ReceptivityStatus::Yellow && ($prevTrust >= 50 || $npc->is_person_of_peace)) {
                    // In Japan, conversion is a gradual process lasting several months to several years
                    if ($npc->isReadyForConversion($gameMonth)) {
                        $trustChange = rand(25, 35);
                        $newStatus = ReceptivityStatus::Green; // ACCEPTED CHRIST!
                        $npc->update([
                            'prayed_salvation' => true,
                            'prayed_salvation_at' => now(),
                            'prayed_salvation_month' => $gameMonth,
                            'baptism_status' => BaptismStatus::CountingCost,
                            'baptism_cost_months' => 0,
                        ]);
                        $points = 70;
                        $roleAssigned = ContributionRole::Harvester;

                        $narrative = "Glory to God! After {$seekingMonths} months of patient spiritual journey, {$npc->name} clearly understood God's Design, Brokenness, and Gospel Rescue through the 3 Circles and joyfully prayed to receive Christ! They are now counting the cost of baptism.";
                        $coachTip = 'Field 2 to Field 3: A new believer is born! In Japanese context, water baptism usually takes 6 months to 2 years of family and cost-counting preparation. Walk with them patiently!';
                    } else {
                        // Seeking journey continues: liked the community/explanation, but needs more months to process
                        $trustChange = rand(12, 18);
                        $points = 25;
                        $newStatus = ReceptivityStatus::Yellow;
                        $narrative = "Shared the 3 Circles with {$npc->name}. They listened with open fascination, especially connecting with the concept of Brokenness and forgiveness. In Japanese culture, committing one's life to Christ is a momentous decision affecting entire families and ancestors that takes several months to process (seeking for {$seekingMonths}/{$targetSeekingMonths} months).";
                        $coachTip = 'In Japan, moving from Yellow to Green takes several months or years of patient discovery. Continue gathering in Sword Bible Study and community fellowship!';
                        $roleAssigned = ContributionRole::Waterer;
                    }
                } elseif ($prevStatus === ReceptivityStatus::Red && $prevTrust < 30) {
                    // Premature pushback
                    $trustChange = -10;
                    $points = 0;
                    $narrative = "Attempted full 3 Circles presentation, but {$npc->name} felt overwhelmed and pulled back.";
                    $coachTip = 'Warning: Gauging receptivity is vital. Pushing a decision before trust is established triggers Japanese social withdrawal. Return to Field 1 caring!';
                } else {
                    $trustChange = rand(10, 16);
                    $points = 20;
                    $newStatus = ReceptivityStatus::Yellow;
                    $narrative = "Shared the 3 Circles. {$npc->name} listened thoughtfully and had good questions about Brokenness and Jesus (seeking for {$seekingMonths}/{$targetSeekingMonths} months).";
                    $coachTip = 'Yellow status: In Japan, seekers journey through several months of exploring before praying the prayer of salvation. Continue walking with them in community!';
                    $roleAssigned = ContributionRole::Waterer;
                }
                break;

            case MinistryActionType::PraySalvation:
                // Field 2: Leading Seeker in Prayer of Salvation / Sinner's Prayer
                $seekingMonths = $npc->effectiveSeekingMonths($gameMonth);
                $targetSeekingMonths = max(1, $npc->target_seeking_months);

                if (($prevStatus === ReceptivityStatus::Yellow || $npc->is_person_of_peace) && $npc->isReadyForConversion($gameMonth)) {
                    $trustChange = rand(25, 35);
                    $newStatus = ReceptivityStatus::Green;
                    $npc->update([
                        'prayed_salvation' => true,
                        'prayed_salvation_at' => now(),
                        'prayed_salvation_month' => $gameMonth,
                        'baptism_status' => BaptismStatus::CountingCost,
                        'baptism_cost_months' => 0,
                    ]);
                    $points = 75;
                    $roleAssigned = ContributionRole::Harvester;

                    $narrative = "Hallelujah! After {$seekingMonths} months of sincere seeking, {$npc->name} prayed the Sinner's Prayer with genuine sincerity and tears, confessing their sins and receiving Jesus Christ as Lord and Savior! They have entered the Kingdom of God and are now prayerfully counting the cost of baptism.";
                    $coachTip = 'Salvation celebrated! In Japan, ancestral and family heritage makes baptism a deep step requiring 6 months to 2 years of cost-counting. Walk alongside them through this journey!';
                } elseif ($prevStatus === ReceptivityStatus::Yellow || $prevTrust >= 40 || $npc->is_person_of_peace) {
                    // Friendly / interested, but conversion in Japan takes several months
                    $trustChange = rand(6, 12);
                    $points = 15;
                    $narrative = "Invited {$npc->name} to pray the prayer of salvation. They replied with gentle honesty: 'I truly value this community and respect Jesus, but in Japan, converting to Christianity is a momentous step affecting my family and ancestors. I need more time to study and count the cost.' (Seeking for {$seekingMonths}/{$targetSeekingMonths} months).";
                    $coachTip = 'Avoid rushing the Sinner\'s Prayer: for Japanese seekers, moving from Yellow to Green is a gradual process lasting several months to several years. Continue cultivating trust through Field 1 care and Sword DBS.';
                } else {
                    $trustChange = -5;
                    $points = 5;
                    $narrative = "Invited {$npc->name} to pray the prayer of salvation, but they felt hesitant: 'I respect your faith, but I am not ready yet.'";
                    $coachTip = 'Avoid rushing the Sinner\'s Prayer before relational trust reaches Yellow (40+). Continue loving them through Field 1 care and active listening.';
                }
                break;

            case MinistryActionType::CommitToBaptism:
                // Field 3: Walking through Japanese family concerns and committing to / receiving water baptism
                if ($npc->status === ReceptivityStatus::Green || $npc->hasPrayedSalvation) {
                    $targetBaptismMonths = max(1, $npc->target_baptism_months);

                    if ($npc->baptism_status === BaptismStatus::Baptized || $npc->is_baptized) {
                        $trustChange = 10;
                        $points = 20;
                        $narrative = "Met with {$npc->name} to celebrate their baptism testimony and encourage them in bold Christian discipleship.";
                        $coachTip = 'Already baptized! Focus on equipping them through 411 Discipleship Training or raising them toward Timothy leadership.';
                    } elseif ($npc->isReadyForBaptism()) {
                        // Water Baptism Celebrated after 6 months to 2 years of family preparation!
                        $trustChange = rand(25, 35);
                        $npc->update([
                            'is_baptized' => true,
                            'baptism_status' => BaptismStatus::Baptized,
                            'baptized_at' => now(),
                        ]);
                        $points = 80;
                        $roleAssigned = ContributionRole::Discipler;
                        $isBaptismCompleted = true;

                        $narrative = "Glory to God! After {$npc->baptism_cost_months} months of faithful prayer, overcoming family obstacles, and counting the cost, {$npc->name} publicly confessed Christ and was baptized in water! Local believers gathered to celebrate this courageous milestone in Nagoya!";
                        $coachTip = 'Matthew 28:19 fulfilled! In Japan, baptism usually takes 6 months to 2 years to prepare family relationships. Baptized disciples are ready to multiply!';
                    } else {
                        // Taking the step of commitment / counting the cost
                        $trustChange = rand(15, 25);
                        $points = 45;
                        $roleAssigned = ContributionRole::Discipler;

                        if ($npc->baptism_status === BaptismStatus::Committed) {
                            $narrative = "Pastoral consultation with {$npc->name}. They are committed to water baptism and counting the cost with their family. In Japan, baptism usually takes 6 months to 2 years to prepare family relationships. (Month {$npc->baptism_cost_months} of {$targetBaptismMonths} in the baptism journey).";
                            $coachTip = "Patience in Field 3: Walking alongside {$npc->name} for 6 months to 2 years prevents familial estrangement and wins their entire household (Oikos).";
                        } else {
                            $shouldCommit = $npc->baptism_cost_months >= (int) floor($targetBaptismMonths / 2) || $prevTrust >= 65;
                            $newBaptismStatus = $shouldCommit ? BaptismStatus::Committed : BaptismStatus::CountingCost;

                            $npc->update([
                                'baptism_status' => $newBaptismStatus,
                            ]);

                            if ($newBaptismStatus === BaptismStatus::Committed) {
                                $narrative = "Deep pastoral conversation with {$npc->name} addressing Japanese family traditions, ancestral rites, and counting the cost. By God's peace, they formally committed to be baptized! (Month {$npc->baptism_cost_months} of {$targetBaptismMonths} in the 6-month to 2-year baptism journey).";
                                $coachTip = 'Major breakthrough! In Japan, family pushback is the primary obstacle to baptism. Their commitment is secured! Continue walking with them through the full preparation journey.';
                            } else {
                                $narrative = "Pastoral counseling session with {$npc->name} exploring water baptism, Buddhist altar rituals, and explaining their faith to parents. In Japan, baptism usually takes 6 months to 2 years to navigate family relationships. ({$npc->baptism_cost_months} of {$targetBaptismMonths} months counting the cost).";
                                $coachTip = 'Patience in Field 3: Walking alongside new believers for 6 months to 2 years prevents familial estrangement and wins their entire household (Oikos).';
                            }
                        }
                    }
                } else {
                    $trustChange = 0;
                    $points = 0;
                    $narrative = "Discussed water baptism with {$npc->name}, but they have not yet prayed the prayer of salvation.";
                    $coachTip = 'Baptism is for believers who have repented and put their trust in Jesus. Focus on Gospel sowing and the Prayer of Salvation first.';
                }
                break;

            case MinistryActionType::CommandsOfChrist:
                // Field 3: Discipleship Growth - 7-9 commands of Jesus with hand motions
                $trustChange = rand(22, 32);
                $points = 45;
                $roleAssigned = ContributionRole::Discipler;
                $targetBaptismMonths = max(1, $npc->target_baptism_months);

                if (! $npc->is_baptized) {
                    if ($npc->baptism_status === BaptismStatus::CountingCost) {
                        $npc->update(['baptism_status' => BaptismStatus::Committed]);
                        $narrative = "Discipled {$npc->name} in the Commands of Christ (Hand Motions)! Convicted by Command 2 (Be Baptized), they resolved their ancestral fears and committed to baptism (Month {$npc->baptism_cost_months} of {$targetBaptismMonths})!";
                    } elseif ($npc->baptism_status === BaptismStatus::Committed) {
                        if ($npc->isReadyForBaptism()) {
                            $npc->update([
                                'is_baptized' => true,
                                'baptism_status' => BaptismStatus::Baptized,
                                'baptized_at' => now(),
                            ]);
                            $points += 35;
                            $isBaptismCompleted = true;
                            $narrative = "Discipled {$npc->name} in the Commands of Christ! Having faithfully prepared their family over {$npc->baptism_cost_months} months, {$npc->name} was publicly baptized in water!";
                        } else {
                            $narrative = "Discipled {$npc->name} in the Commands of Christ! Convicted by Command 2 (Be Baptized), they reaffirmed their baptism commitment as they continue preparing their parents and family (Month {$npc->baptism_cost_months} of {$targetBaptismMonths}).";
                        }
                    } else {
                        $narrative = "Discipled {$npc->name} in the Commands of Christ (Repent, Believe, Be Baptized, Pray, Love, Lord's Supper, Give, Make Disciples). They memorized the hand motions to teach their family!";
                    }
                } else {
                    $narrative = "Discipled {$npc->name} in the Commands of Christ with hand motions. Loving obedience to Jesus took deep root in their daily walk!";
                }

                $coachTip = 'L2 Discipleship: The Commands of Christ establish loving obedience rather than mere head knowledge (John 14:15), reproducible in any home.';
                break;

            case MinistryActionType::SwordBibleStudy:
                // Field 3: Discovery Bible Study (God, People, Command, Example)
                $trustChange = rand(18, 28);
                $points = 35;
                $roleAssigned = ContributionRole::Discipler;
                $seekingMonths = $npc->effectiveSeekingMonths($gameMonth);
                $targetSeekingMonths = max(1, $npc->target_seeking_months);

                if ($prevStatus === ReceptivityStatus::Yellow) {
                    // Breakthrough to Green only if they have journeyed through the gradual seeking process
                    if ($npc->isReadyForConversion($gameMonth) && rand(1, 100) <= 60) {
                        $newStatus = ReceptivityStatus::Green;
                        $npc->update([
                            'prayed_salvation' => true,
                            'prayed_salvation_at' => now(),
                            'prayed_salvation_month' => $gameMonth,
                            'baptism_status' => BaptismStatus::CountingCost,
                            'baptism_cost_months' => 0,
                        ]);
                        $points += 40;
                        $narrative = "Facilitated a SWORD discovery Bible study with {$npc->name}. After {$seekingMonths} months of seeking, the Holy Spirit convicted them as they answered the 4 questions, and they joyfully gave their life to Christ!";
                        $coachTip = 'Field 2 to Field 3: A new believer born through direct encounter with Scripture! Begin walking with them through the 6-month to 2-year baptism journey.';
                    } else {
                        $narrative = "Facilitated a SWORD discovery Bible study with {$npc->name}. They loved exploring Scripture directly and are eager to read more with their family (seeking for {$seekingMonths}/{$targetSeekingMonths} months).";
                        $coachTip = 'L2 Discipleship: Discovery Bible Study builds the foundational worldview over several months, letting God speak directly into Japanese hearts.';
                    }
                } else {
                    $narrative = "Conducted a SWORD Bible study with {$npc->name}. They practiced finding what God says and applying obedience into their workplace and relational circle.";
                    $coachTip = 'L2 Discipleship: SWORD Bible study empowers self-feeding disciples who hear God directly without dependency on missionary sermons.';
                }
                break;

            case MinistryActionType::Train411:
                // Field 3: Discipleship Equipping (Why, Who, What, When)
                if ($npc->status === ReceptivityStatus::Green) {
                    $trustChange = rand(20, 30);
                    $newTier = $npc->leadership_tier->level() === 0 ? LeadershipTier::L1_SeedSower : $npc->leadership_tier;
                    $npc->update([
                        'trained_411' => true,
                        'leadership_tier' => $newTier,
                    ]);
                    $points = 50;
                    $roleAssigned = ContributionRole::Discipler;
                    $targetBaptismMonths = max(1, $npc->target_baptism_months);

                    // During 411 Training: The Commands of Jesus (Command 2: Be Baptized) catalyze baptism decisions!
                    if (! $npc->is_baptized) {
                        if ($npc->baptism_status === BaptismStatus::CountingCost) {
                            $npc->update(['baptism_status' => BaptismStatus::Committed]);
                            $narrative = "Trained {$npc->name} in 411 Discipleship (Why, Who, What, When)! During the session on Jesus' commands, they were convicted by Scripture and joyfully committed to water baptism!";
                        } elseif ($npc->baptism_status === BaptismStatus::Committed) {
                            if ($npc->isReadyForBaptism()) {
                                $npc->update([
                                    'is_baptized' => true,
                                    'baptism_status' => BaptismStatus::Baptized,
                                    'baptized_at' => now(),
                                ]);
                                $points += 40;
                                $isBaptismCompleted = true;
                                $narrative = "Trained {$npc->name} in 411 Discipleship! Having completed {$npc->baptism_cost_months} months of family preparation, {$npc->name} was joyfully baptized in water during the 411 training gathering!";
                            } else {
                                $narrative = "Trained {$npc->name} in 411 Discipleship! Convicted by Jesus' command to be baptized, they reaffirmed their commitment and continue preparing their family (Month {$npc->baptism_cost_months} of {$targetBaptismMonths}). They are now an active L1 Seed Sower!";
                            }
                        } else {
                            $narrative = "Trained {$npc->name} in 411: Why make disciples? Who (Oikos Map)? What (15s & 3 Circles)? When (Goals)? They are now an active L1 Seed Sower!";
                        }
                    } else {
                        $narrative = "Trained {$npc->name} in 411: Why make disciples? Who (Oikos Map)? What (15s & 3 Circles)? When (Goals)? Equipped as an L1 Seed Sower!";
                    }

                    $coachTip = 'Field 3 activated: Equipped believers multiply automatically during monthly ticks by reaching their own family and friends. (They can also complete baptism afterward if still counting the cost!)';
                } else {
                    $trustChange = 5;
                    $narrative = "Attempted 411 training, but {$npc->name} is not yet a committed believer.";
                    $coachTip = '411 Discipleship Training is designed for Green believers. Focus on Gospel sowing (Field 2) and Prayer of Salvation first.';
                }
                break;

            case MinistryActionType::ChurchCircles:
                // Field 4: Acts 2 Church Formation (3/3rds pattern)
                $trustChange = rand(25, 35);
                $points = 85;
                $roleAssigned = ContributionRole::Discipler;

                if ($npc->leadership_tier->level() < 2) {
                    $npc->update(['leadership_tier' => LeadershipTier::L2_ChurchPlanter]);
                }

                $existingChurch = Church::where('leader_npc_id', $npc->id)->first();
                if (! $existingChurch) {
                    // Determine parent church:
                    // 1. Check if the active player has an established church in this session
                    $parentChurch = Church::where('game_session_id', $action->game_session_id)
                        ->where('leader_user_id', $user->id)
                        ->where('is_dissolved', false)
                        ->first()
                        ?? Church::where('game_session_id', $action->game_session_id)
                            ->where('started_by_name', $user->name)
                            ->where('generation', 1)
                            ->where('is_dissolved', false)
                            ->first();

                    // 2. If the active player does not lead a church, check if the NPC has a discipler NPC with a church
                    if (! $parentChurch && $npc->discipler_npc_id) {
                        $parentChurch = Church::where('leader_npc_id', $npc->discipler_npc_id)
                            ->where('is_dissolved', false)
                            ->first();
                    }

                    // 3. Determine generation:
                    // If a parent church is found, this is a daughter church (gen + 1).
                    // If no parent church exists, this is the player's own pioneer Generation 1 (G1) church!
                    $churchGen = $parentChurch ? ($parentChurch->generation + 1) : max(1, $npc->generation);

                    $newChurch = Church::create([
                        'game_session_id' => $action->game_session_id,
                        'ward_id' => $npc->ward_id,
                        'name' => "{$npc->name}'s Acts 2 Church Circle (G{$churchGen})",
                        'parent_church_id' => $parentChurch?->id,
                        'leader_npc_id' => $npc->id,
                        'leader_user_id' => null,
                        'generation' => $churchGen,
                        'elements_json' => [
                            ChurchElement::RepentanceBelief->value,
                            ChurchElement::Baptism->value,
                            ChurchElement::Word->value,
                            ChurchElement::Prayer->value,
                            ChurchElement::Fellowship->value,
                            ChurchElement::MakingDisciples->value,
                        ],
                        'is_healthy_church' => false,
                        'health_score' => 65,
                        'attenders_count' => 6,
                        'believers_count' => 4,
                        'baptized_count' => 3,
                        'is_dissolved' => false,
                        'started_by_name' => $user->name,
                        'last_coached_game_month' => $gameMonth,
                        'last_coached_game_day' => $action->game_day ?: 1,
                        'lat' => $npc->lat,
                        'lng' => $npc->lng,
                        'founded_game_month' => $gameMonth,
                    ]);
                    $npc->update([
                        'church_id' => $newChurch->id,
                        'discipler_user_id' => $user->id,
                    ]);
                    $narrative = "Gathered believers with {$npc->name} to form an Acts 2 Church Circle using the 3/3rds pattern (Look Back, Look Up, Look Ahead). An autonomous simple church is now birthed in {$ward->name}!";
                } elseif ($existingChurch->is_dissolved) {
                    $existingChurch->revitalize($gameMonth, $user);
                    $existingChurch->update([
                        'last_coached_game_month' => $gameMonth,
                        'last_coached_game_day' => $action->game_day ?: 1,
                    ]);
                    $npc->update(['church_id' => $existingChurch->id]);
                    $points += 20;
                    $narrative = "Revitalized {$existingChurch->name}! Gathered scattered believers back together in {$ward->name}, resolved past relational hurt/sickness obstacles, and restored the Acts 2 Church Circle!";
                } else {
                    $existingChurch->increment('health_score', min(25, 100 - $existingChurch->health_score));
                    $existingChurch->increment('attenders_count', 2);
                    $existingChurch->increment('believers_count', 1);
                    $existingChurch->update([
                        'is_healthy_church' => $existingChurch->health_score >= 50 && $existingChurch->hasAllCoreElements(),
                        'last_coached_game_month' => $gameMonth,
                        'last_coached_game_day' => $action->game_day ?: 1,
                    ]);
                    $narrative = "Coached {$existingChurch->name} in the 3/3rds pattern. Church health strengthened to {$existingChurch->health_score}% with {$existingChurch->attenders_count} attenders and deeper mutual fellowship!";
                }

                $coachTip = 'L2 Church Planting: Acts 2:38-47 simple church circles give believers biblical ecclesial identity without needing dedicated church buildings.';
                break;

            case MinistryActionType::MentorTimothy:
                // Field 5: Leadership Multiplication (L1 -> L2 or L2 -> L3)
                if ($npc->trained_411 && $npc->leadership_tier->level() >= 1) {
                    $trustChange = rand(22, 30);
                    $newTier = match ($npc->leadership_tier->level()) {
                        1 => LeadershipTier::L2_ChurchPlanter,
                        2 => LeadershipTier::L3_Multiplier,
                        default => $npc->leadership_tier,
                    };
                    $npc->update(['leadership_tier' => $newTier]);
                    $points = 100;
                    $roleAssigned = ContributionRole::Discipler;
                    $narrative = "Invested deep pastoral coaching into {$npc->name}. Elevated from L1 Seed Sower to {$newTier->roleTitle()} capable of multiplying autonomous daughter churches!";
                    $coachTip = '2 Timothy 2:2: Entrust to faithful people who will be able to teach others also. Timothys break the missionary bandwidth bottleneck!';
                } else {
                    $trustChange = 8;
                    $narrative = "Provided leadership mentorship to {$npc->name}.";
                    $coachTip = 'To raise a Timothy, ensure the disciple is first faithful in 411 and actively leading others.';
                }
                break;

            case MinistryActionType::IronOnIron:
                // Field 5: Peer Coaching with Generational Mapping (L2 -> L3 / L4)
                $trustChange = rand(25, 35);
                $newTier = match ($npc->leadership_tier->level()) {
                    2 => LeadershipTier::L3_Multiplier,
                    3 => LeadershipTier::L4_MovementTrainer,
                    default => $npc->leadership_tier,
                };
                $npc->update(['leadership_tier' => $newTier]);
                $points = 110;
                $roleAssigned = ContributionRole::Discipler;

                // Generational ripple: all disciples under this leader receive +15 trust!
                Npc::where('discipler_npc_id', $npc->id)->increment('trust_level', 15);

                $narrative = "Conducted quarterly Iron on Iron (IoI) coaching with {$npc->name}. Diagnosed the 4 Fields, mapped 2nd & 3rd generational fruit, and resolved growth bottlenecks. Elevated to {$newTier->roleTitle()}!";
                $coachTip = 'L3 Multiplication: Iron on Iron (Proverbs 27:17) uses generational mapping to shift leaders from addition to exponential multiplication.';
                break;

            case MinistryActionType::SwarmTraining:
                // Field 5: Regional Movement Catalyzing (L4 / L5)
                $trustChange = rand(30, 40);
                $newTier = match ($npc->leadership_tier->level()) {
                    3 => LeadershipTier::L4_MovementTrainer,
                    4 => LeadershipTier::L5_StrategyCoordinator,
                    default => $npc->leadership_tier,
                };
                $npc->update(['leadership_tier' => $newTier]);
                $points = 130;
                $roleAssigned = ContributionRole::Discipler;

                // Soften soil across the ward
                Npc::where('ward_id', $npc->ward_id)->increment('trust_level', 10);

                $narrative = "Mobilized a regional Swarm Training with {$npc->name}! Modeled 4 Fields entry, assisted local teams, and aligned unreached strategy across {$ward->name}. Elevated to {$newTier->roleTitle()}!";
                $coachTip = 'L4/L5 Movement Catalyzing: Swarm training brings multi-generational teams to saturate an unreached zone, seeding new 3/3rds groups and coaching regional trainers.';
                break;
        }

        // Proximity & Commuting dynamics:
        // Living close (home ward) yields +25% trust bonus due to spontaneous neighborly follow-up.
        // Long distance commuting across town introduces follow-up friction (0.85x or 0.70x).
        if ($user->home_ward_id && $trustChange > 0) {
            $proximityMultiplier = $user->commuteTrustMultiplierForWard($ward);
            $trustChange = (int) max(1, round($trustChange * $proximityMultiplier));
            $distanceKm = $user->distanceToWard($ward);
            if ($user->home_ward_id === $ward->id) {
                $narrative .= " [🏡 Local Neighbor: Based in {$ward->name}, regular follow-up accelerated trust +25%!]";
            } elseif ($distanceKm > 10.0) {
                $narrative .= " [⚠️ Distant Commute: Traveling {$distanceKm}km from {$user->homeWard?->name} limits frequent follow-up.]";
            }
        }

        // Apply new trust level bounded 1-100
        $newTrust = (int) max(1, min(100, $prevTrust + $trustChange));
        $statusChanged = $newStatus !== $prevStatus;

        $npcUpdates = [
            'trust_level' => $newTrust,
            'status' => $newStatus,
        ];

        if ($npc->discipler_user_id === null && ($newStatus === ReceptivityStatus::Green || $npc->prayed_salvation || $roleAssigned === ContributionRole::Discipler)) {
            $npcUpdates['discipler_user_id'] = $user->id;
        }

        if ($newStatus === ReceptivityStatus::Yellow && $prevStatus !== ReceptivityStatus::Yellow) {
            $npcUpdates['yellow_since_game_month'] = $gameMonth;
            if ($npc->seeking_months === 0) {
                $npcUpdates['seeking_months'] = 0;
            }
        }

        $npc->update($npcUpdates);

        // Record player contribution
        NpcContribution::create([
            'game_session_id' => $action->game_session_id,
            'npc_id' => $npc->id,
            'user_id' => $user->id,
            'action_type' => $action->action_type,
            'contribution_role' => $roleAssigned,
            'game_month' => $gameMonth,
            'trust_gained' => max(0, $trustChange),
            'points_awarded' => $points,
            'triggered_breakthrough' => $statusChanged || $prayerAnswered,
            'notes' => $narrative,
        ]);

        // =========================================================================
        // CO-OP SYNERGY HARVEST EVALUATION (1 Cor 3:6-8 "Paul planted, Apollos watered")
        // =========================================================================
        $coopPoints = 0;
        $collaborators = [];

        if ($statusChanged && $newStatus === ReceptivityStatus::Green && $isCollaborative) {
            // A collaborative breakthrough occurred!
            // Apply +50% Cooperative Synergy Bonus to the point pool
            $coopBonusPool = (int) round($points * 0.50);
            $coopPoints = $coopBonusPool;

            // Find all prior waterers and pioneer sowers
            $uniqueCollaboratorIds = $priorOtherContributions->pluck('user_id')->unique()->values();
            $collaboratorShare = (int) round($coopBonusPool / max(1, $uniqueCollaboratorIds->count()));

            foreach ($uniqueCollaboratorIds as $collabUserId) {
                User::where('id', $collabUserId)->increment('coop_synergy_score', $collaboratorShare);
                $collaborators[] = [
                    'user_id' => $collabUserId,
                    'assist_points' => $collaboratorShare,
                ];
            }

            // Also reward the closer with synergy assist bonus
            $user->increment('coop_synergy_score', (int) round($coopBonusPool * 0.5));

            $narrative .= " 🤝 [CO-OP HARVEST] 1 Cor 3:6 Synergy Bonus! You and {$uniqueCollaboratorIds->count()} other missionary co-laborers watered this seed together! Bonus points distributed!";
        }

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: $npc->id,
            wardId: $action->ward_id,
            trustChange: $trustChange,
            newTrustLevel: $newTrust,
            previousStatus: $prevStatus,
            newStatus: $newStatus,
            statusChanged: $statusChanged,
            pointsAwarded: $points,
            coopPointsAwarded: $coopPoints,
            roleAssigned: $roleAssigned,
            collaboratorsImpacted: $collaborators,
            narrativeOutcome: $narrative,
            pedagogicalTip: $coachTip,
            prayerAnswered: $prayerAnswered,
            newOikosUnlocked: $unlockedOikos,
            isBaptismCompleted: $isBaptismCompleted,
        );
    }

    /**
     * Resolves ward-wide International Exchange Event (Platform Ministry).
     */
    protected function resolveInternationalEvent(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $ward = $action->ward;

        // Attract 2-4 NPCs in this ward (preferring students, expats, and persons of peace)
        $attendees = Npc::where('game_session_id', $action->game_session_id)
            ->where('ward_id', $ward->id)
            ->whereIn('status', [ReceptivityStatus::Unreached, ReceptivityStatus::Red, ReceptivityStatus::Yellow])
            ->inRandomOrder()
            ->take(rand(2, 4))
            ->get();

        $attendeeNames = [];
        $points = 30;

        foreach ($attendees as $att) {
            $att->increment('trust_level', rand(15, 22));
            if ($att->status === ReceptivityStatus::Unreached) {
                $att->update([
                    'status' => ReceptivityStatus::Yellow,
                    'yellow_since_game_month' => $gameMonth,
                    'seeking_months' => 0,
                ]); // Instantly becomes an open acquaintance!
            }
            $attendeeNames[] = $att->name;

            NpcContribution::create([
                'game_session_id' => $action->game_session_id,
                'npc_id' => $att->id,
                'user_id' => $user->id,
                'action_type' => MinistryActionType::InternationalEvent,
                'contribution_role' => ContributionRole::PioneerSower,
                'game_month' => $gameMonth,
                'trust_gained' => 18,
                'points_awarded' => 15,
                'triggered_breakthrough' => true,
                'notes' => "Met player at International Cultural Cafe in {$ward->name}.",
            ]);
        }

        $namesList = implode(', ', $attendeeNames);
        $narrative = ! empty($namesList)
            ? "Hosted a vibrant International Cafe in {$ward->name}. Attracted {$attendees->count()} seekers: {$namesList}. Great friendships established!"
            : "Hosted an International Cafe in {$ward->name}. Sowed seeds of hospitality across the neighborhood.";

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: null,
            wardId: $ward->id,
            trustChange: 20,
            newTrustLevel: 50,
            previousStatus: ReceptivityStatus::Unreached,
            newStatus: ReceptivityStatus::Yellow,
            statusChanged: true,
            pointsAwarded: $points,
            coopPointsAwarded: 0,
            roleAssigned: ContributionRole::PioneerSower,
            collaboratorsImpacted: [],
            narrativeOutcome: $narrative,
            pedagogicalTip: 'Platform ministries like International Exchange Cafes cast a wide net across Japanese wards, rapidly surfacing Persons of Peace (Luke 10).',
        );
    }

    /**
     * Resolves ward-wide Prayer Walking & Spiritual Mapping (Field 1: Entry Strategy).
     * Traces street paths across the ward and increases the chance to discover a Luke 10 Person of Peace.
     */
    protected function resolvePrayerWalk(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $ward = $action->ward ?? Ward::find($action->ward_id) ?? Ward::first();

        // Count previous prayer walks in this ward for this game session
        $previousWardWalks = PrayerWalk::where('game_session_id', $action->game_session_id)
            ->where('ward_id', $ward->id)
            ->count();

        // Generate street route path coordinates and route name
        $routeData = $this->generateStreetRoute($ward, $previousWardWalks + 1, (int) $action->game_day);
        $routeName = $routeData['name'];
        $pathCoords = $routeData['coords'];

        // Option B: Joint Prayer Walk when a target resident is selected (MAWL Luke 10 harvest intercession)
        $targetNpc = $action->targetNpc;
        if ($targetNpc) {
            $trustChange = rand(22, 32);
            if ($user->home_ward_id) {
                $proximityMultiplier = $user->commuteTrustMultiplierForWard($ward);
                $trustChange = (int) max(1, round($trustChange * $proximityMultiplier));
            }
            $prevStatus = $targetNpc->status;
            $targetNpc->increment('trust_level', $trustChange);

            if ($targetNpc->status === ReceptivityStatus::Unreached) {
                $targetNpc->update([
                    'status' => ReceptivityStatus::Yellow,
                    'yellow_since_game_month' => $gameMonth,
                    'seeking_months' => 0,
                ]);
            }

            // Oikos connection trust boost (+15%)
            OikosRelationship::where('npc_id', $targetNpc->id)->increment('trust_level', 15);
            OikosRelationship::where('related_npc_id', $targetNpc->id)->increment('trust_level', 15);

            // Record pioneer sowing / MAWL contribution
            NpcContribution::create([
                'game_session_id' => $action->game_session_id,
                'npc_id' => $targetNpc->id,
                'user_id' => $user->id,
                'action_type' => MinistryActionType::PrayerWalk,
                'contribution_role' => ContributionRole::PioneerSower,
                'game_month' => $gameMonth,
                'trust_gained' => $trustChange,
                'points_awarded' => 40,
                'triggered_breakthrough' => true,
                'notes' => "Joint Prayer Walk with {$targetNpc->displayName} along {$routeName} in {$ward->name}. Modeled Luke 10 harvest intercession and placed their Oikos under prayer.",
            ]);

            // Save prayer walk route record linked to the resident
            PrayerWalk::create([
                'game_session_id' => $action->game_session_id,
                'user_id' => $user->id,
                'ward_id' => $ward->id,
                'game_month' => $gameMonth,
                'game_day' => (int) $action->game_day,
                'route_name' => $routeName,
                'path_coords' => $pathCoords,
                'pop_discovered' => false,
                'discovered_npc_id' => $targetNpc->id,
            ]);

            // Soften ward atmosphere
            $ward->increment('spiritual_openness', 3);

            return new ActionResolutionOutcome(
                actionId: $action->id,
                actionType: $action->action_type,
                userId: $user->id,
                targetNpcId: $targetNpc->id,
                wardId: $ward->id,
                trustChange: $trustChange,
                newTrustLevel: $targetNpc->trust_level,
                previousStatus: $prevStatus,
                newStatus: $targetNpc->status,
                statusChanged: $prevStatus !== $targetNpc->status,
                pointsAwarded: 40,
                coopPointsAwarded: 10,
                roleAssigned: ContributionRole::PioneerSower,
                collaboratorsImpacted: [],
                narrativeOutcome: "Joint Luke 10 prayer walk with {$targetNpc->displayName} across {$ward->name} along {$routeName}! Modeled intercession on the streets, softening spiritual ground. Their personal trust surged by +{$trustChange}% and their relational Oikos network is opened in prayer.",
                pedagogicalTip: 'MAWL (Model, Assist, Watch, Leave): Walking the streets with a resident models prayer evangelism directly in their everyday environment (Luke 10:1-9).',
                newOikosUnlocked: true,
            );
        }

        // Solo Ward-wide Prayer Walk: progressive Luke 10 Person of Peace discovery
        $popChance = min(90, 35 + ($previousWardWalks * 15));
        $popDiscovered = rand(1, 100) <= $popChance;
        $discoveredNpc = null;
        $points = 15;

        if ($popDiscovered) {
            // Find existing non-PoP resident in this ward, or spawn a new archetypal Japanese seeker
            $discoveredNpc = Npc::where('game_session_id', $action->game_session_id)
                ->where('ward_id', $ward->id)
                ->where('is_person_of_peace', false)
                ->inRandomOrder()
                ->first();

            if ($discoveredNpc) {
                $updates = ['is_person_of_peace' => true];
                if ($discoveredNpc->status === ReceptivityStatus::Unreached) {
                    $updates['status'] = ReceptivityStatus::Yellow;
                    $updates['yellow_since_game_month'] = $gameMonth;
                    $updates['seeking_months'] = 0;
                }
                $discoveredNpc->update($updates);
                $discoveredNpc->increment('trust_level', rand(18, 26));
            } else {
                $midCoord = $pathCoords[intdiv(count($pathCoords), 2)] ?? [(float) $ward->center_lat, (float) $ward->center_lng];
                $discoveredNpc = $this->spawnPersonOfPeaceResident((int) $action->game_session_id, $ward, $midCoord);
            }

            // Record pioneer sowing contribution
            NpcContribution::create([
                'game_session_id' => $action->game_session_id,
                'npc_id' => $discoveredNpc->id,
                'user_id' => $user->id,
                'action_type' => MinistryActionType::PrayerWalk,
                'contribution_role' => ContributionRole::PioneerSower,
                'game_month' => $gameMonth,
                'trust_gained' => 20,
                'points_awarded' => 35,
                'triggered_breakthrough' => true,
                'notes' => "Discovered Person of Peace ({$discoveredNpc->displayName}) during prayer walk on {$routeName} in {$ward->name}.",
            ]);

            $points = 35;
            $narrative = "Divine appointment during prayer walk along {$routeName} in {$ward->name}! Discovered {$discoveredNpc->displayName}, a Luke 10 Person of Peace receptive to spiritual conversation. Spiritual openness increased to ".min(100, $ward->spiritual_openness + 4).'%.';
        } else {
            $nextChance = min(90, 35 + (($previousWardWalks + 1) * 15));
            $narrative = "Interceded on foot along {$routeName} in {$ward->name}. Spiritual ground softened (Openness +2%). Prayer walk count in {$ward->name} is now ".($previousWardWalks + 1).", raising Person of Peace discovery chance to {$nextChance}%!";
        }

        // Soften ward atmosphere and increment openness (+1 extra boost when interceding in your home base!)
        $opennessGain = ($popDiscovered ? 4 : 2) + ($user->home_ward_id === $ward->id ? 1 : 0);
        $ward->increment('spiritual_openness', $opennessGain);
        Npc::where('game_session_id', $action->game_session_id)
            ->where('ward_id', $ward->id)
            ->increment('trust_level', rand(2, 5));

        // Save prayer walk route record
        PrayerWalk::create([
            'game_session_id' => $action->game_session_id,
            'user_id' => $user->id,
            'ward_id' => $ward->id,
            'game_month' => $gameMonth,
            'game_day' => (int) $action->game_day,
            'route_name' => $routeName,
            'path_coords' => $pathCoords,
            'pop_discovered' => $popDiscovered,
            'discovered_npc_id' => $discoveredNpc?->id,
        ]);

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: $discoveredNpc?->id,
            wardId: $ward->id,
            trustChange: $popDiscovered ? 20 : 5,
            newTrustLevel: $discoveredNpc ? $discoveredNpc->trust_level : 40,
            previousStatus: ReceptivityStatus::Unreached,
            newStatus: $discoveredNpc ? $discoveredNpc->status : ReceptivityStatus::Yellow,
            statusChanged: $popDiscovered,
            pointsAwarded: $points,
            coopPointsAwarded: 0,
            roleAssigned: ContributionRole::PioneerSower,
            collaboratorsImpacted: [],
            narrativeOutcome: $narrative,
            pedagogicalTip: 'Luke 10:2-6 teaches that prayer walking precedes finding the Person of Peace. Intercession softens spiritual soil before sowing the Gospel.',
            newOikosUnlocked: $popDiscovered,
        );
    }

    /**
     * Resolves ward-wide Swarm Training platform action (Field 5: Movement Strategy).
     */
    protected function resolveSwarmTrainingPlatform(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $ward = $action->ward;

        // Soften soil across all NPCs in the ward
        Npc::where('game_session_id', $action->game_session_id)
            ->where('ward_id', $ward->id)
            ->increment('trust_level', rand(12, 18));

        // Surface Person of Peace if unreached
        $pop = Npc::where('game_session_id', $action->game_session_id)
            ->where('ward_id', $ward->id)
            ->where('is_person_of_peace', true)
            ->first();

        if ($pop && $pop->status === ReceptivityStatus::Unreached) {
            $pop->update(['status' => ReceptivityStatus::Yellow]);
        }

        $narrative = "Mobilized a catalytic Swarm Training team across {$ward->name}! Sowing teams blanketed transit hubs, shopping arcades, and parks, leaving behind fresh contacts and lifting spiritual receptivity across the ward.";

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: null,
            wardId: $ward->id,
            trustChange: 18,
            newTrustLevel: 55,
            previousStatus: ReceptivityStatus::Unreached,
            newStatus: ReceptivityStatus::Yellow,
            statusChanged: true,
            pointsAwarded: 50,
            coopPointsAwarded: 0,
            roleAssigned: ContributionRole::PioneerSower,
            collaboratorsImpacted: [],
            narrativeOutcome: $narrative,
            pedagogicalTip: 'Swarm trainings take multi-generational disciple makers into new territory to seed 3/3rds groups and model reproducible entry.',
        );
    }

    /**
     * Resolves worker receiving mentoring from an experienced movement leader.
     */
    protected function resolveReceiveMentoring(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $points = 30;

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: null,
            wardId: $action->ward_id,
            trustChange: 0,
            newTrustLevel: 0,
            previousStatus: ReceptivityStatus::Green,
            newStatus: ReceptivityStatus::Green,
            statusChanged: false,
            pointsAwarded: $points,
            coopPointsAwarded: 15,
            roleAssigned: ContributionRole::Discipler,
            collaboratorsImpacted: [],
            narrativeOutcome: 'Received intentional mentoring and debrief from an experienced movement practitioner (Paul). Addressed spiritual bottlenecks, sharpened Four Fields diagnostic vision, and received deep pastoral encouragement.',
            pedagogicalTip: 'Even catalytic church planters need intentional mentoring (2 Tim 2:2). Consistent input from senior coaches prevents blind spots, nurtures soul care, and sustains multi-generational momentum in Japan.',
        );
    }

    /**
     * Resolves worker observing a weekly biblical day of rest (Sabbath).
     */
    protected function resolveSabbathRest(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $points = 25;

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: null,
            wardId: $action->ward_id,
            trustChange: 0,
            newTrustLevel: 0,
            previousStatus: ReceptivityStatus::Green,
            newStatus: ReceptivityStatus::Green,
            statusChanged: false,
            pointsAwarded: $points,
            coopPointsAwarded: 10,
            roleAssigned: null,
            collaboratorsImpacted: [],
            narrativeOutcome: 'Ceased all field ministry labors to observe a holy Sabbath day of rest, worship, and abiding prayer. Soul refreshed, emotional resilience renewed, and Action Points replenished to maximum.',
            pedagogicalTip: "Sabbath rest is not lost time; it is a declaration that Jesus is Lord of the harvest. Practicing regular Sabbath prevents missionary burnout and anchors identity firmly in God's grace rather than ministry output.",
        );
    }

    /**
     * Resolves a day of the 4-day corporate fasting retreat with fellow missionaries.
     */
    protected function resolveFastingRetreat(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $points = 25;
        $dayLabel = $action->notes ?: '4-Day Fasting Retreat with Fellow Missionaries';

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: null,
            wardId: $action->ward_id,
            trustChange: 0,
            newTrustLevel: 0,
            previousStatus: ReceptivityStatus::Green,
            newStatus: ReceptivityStatus::Green,
            statusChanged: false,
            pointsAwarded: $points,
            coopPointsAwarded: 20,
            roleAssigned: null,
            collaboratorsImpacted: [],
            narrativeOutcome: "{$dayLabel}: Joined fellow missionaries in Nagoya for consecrated corporate prayer and fasting. Broke through spiritual heaviness, bound strongholds over unreached wards, and received fresh heavenly clarity.",
            pedagogicalTip: 'Some spiritual strongholds yield only through concerted prayer and fasting (Mark 9:29). Corporate fasting aligns missionary teams, shatters urban spiritual oppression, and releases apostolic boldness.',
        );
    }

    /**
     * Resolves a day of the 3-day NPL Mid-Level Strategy Conference.
     */
    protected function resolveMidLevelConference(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $points = 25;
        $dayLabel = $action->notes ?: '3-Day NPL Mid-Level Strategy Conference';

        return new ActionResolutionOutcome(
            actionId: $action->id,
            actionType: $action->action_type,
            userId: $user->id,
            targetNpcId: null,
            wardId: $action->ward_id,
            trustChange: 0,
            newTrustLevel: 0,
            previousStatus: ReceptivityStatus::Green,
            newStatus: ReceptivityStatus::Green,
            statusChanged: false,
            pointsAwarded: $points,
            coopPointsAwarded: 25,
            roleAssigned: null,
            collaboratorsImpacted: [],
            narrativeOutcome: "{$dayLabel}: Engaged in high-level strategic problem-solving at the No Place Left (NPL) Mid-Level conference. Analyzed generational lines, diagnosed movement bottlenecks, and established joint multiplication targets across Chubu.",
            pedagogicalTip: "A No Place Left (NPL) Mid-Level (or 'Midlevel') conference is a problem-solving and strategy retreat for active movement practitioners rather than an introductory training session. Teams address real field obstacles and establish clear reproduction pathways.",
        );
    }

    /**
     * Generate realistic street waypoints and name for a prayer walk in a ward.
     *
     * @return array{name: string, coords: array<array<float>>}
     */
    protected function generateStreetRoute(Ward $ward, int $walkNumber, int $gameDay): array
    {
        $lat = (float) $ward->center_lat;
        $lng = (float) $ward->center_lng;

        $routesByWard = [
            'naka-ku' => [
                [
                    'name' => 'Sakae & Hisaya Odori Central Avenue',
                    'offsets' => [[0.003, -0.002], [0.003, 0.002], [-0.002, 0.002], [-0.004, 0.0], [-0.004, -0.003]],
                ],
                [
                    'name' => 'Osu Kannon Shopping Arcade & Shrine Approach',
                    'offsets' => [[-0.005, -0.006], [-0.005, -0.002], [-0.001, -0.002], [-0.001, -0.005], [-0.003, -0.007]],
                ],
                [
                    'name' => 'Shirakawa Park & Fushimi Station Loop',
                    'offsets' => [[-0.002, -0.007], [0.002, -0.007], [0.002, -0.003], [-0.002, -0.003], [-0.004, -0.005]],
                ],
                [
                    'name' => 'Marunouchi Financial Ave & Castle South Moat',
                    'offsets' => [[0.006, -0.004], [0.006, 0.001], [0.009, 0.001], [0.009, -0.003], [0.007, -0.005]],
                ],
            ],
            'nakamura-ku' => [
                [
                    'name' => 'Meieki Station Plaza & Taiko-dori Avenue',
                    'offsets' => [[0.001, 0.002], [-0.003, 0.002], [-0.003, -0.004], [-0.001, -0.006], [0.002, -0.004]],
                ],
                [
                    'name' => 'Noritake Garden & Commercial District Circuit',
                    'offsets' => [[0.004, 0.001], [0.007, 0.001], [0.007, -0.003], [0.003, -0.003], [0.002, -0.001]],
                ],
                [
                    'name' => 'Sasashima Live & Canal Promenade',
                    'offsets' => [[-0.004, 0.003], [-0.007, 0.003], [-0.007, -0.001], [-0.004, -0.001], [-0.003, 0.001]],
                ],
            ],
            'meito-ku' => [
                [
                    'name' => 'Fujigaoka Station Plaza & Shopping Arcade',
                    'offsets' => [[-0.001, -0.002], [0.003, -0.002], [0.003, 0.004], [-0.001, 0.004], [-0.003, 0.001]],
                ],
                [
                    'name' => 'Kamiyashiro Station & Hillside Residential Loop',
                    'offsets' => [[-0.005, -0.006], [-0.001, -0.006], [-0.001, -0.002], [-0.004, -0.002], [-0.006, -0.004]],
                ],
                [
                    'name' => 'Issha Station & Inokoshi Park Circuit',
                    'offsets' => [[-0.006, -0.012], [-0.002, -0.012], [-0.002, -0.008], [-0.005, -0.008], [-0.007, -0.010]],
                ],
            ],
            'chikusa-ku' => [
                [
                    'name' => 'Motoyama Station & Nagoya Univ Campus Loop',
                    'offsets' => [[-0.002, -0.003], [0.002, -0.003], [0.002, 0.003], [-0.003, 0.003], [-0.004, 0.0]],
                ],
                [
                    'name' => 'Kakuozan Nittai-ji Approach & Cafe Alley',
                    'offsets' => [[0.001, -0.008], [0.005, -0.008], [0.005, -0.004], [0.001, -0.004], [-0.001, -0.006]],
                ],
                [
                    'name' => 'Imaike Station & Commercial Corridor',
                    'offsets' => [[0.002, -0.015], [-0.002, -0.015], [-0.002, -0.010], [0.003, -0.010], [0.004, -0.013]],
                ],
            ],
        ];

        $slug = $ward->slug ?? '';
        $availableRoutes = $routesByWard[$slug] ?? null;

        if ($availableRoutes) {
            $routeIndex = ($walkNumber - 1) % count($availableRoutes);
            $selected = $availableRoutes[$routeIndex];
            $routeName = $selected['name'];
            $coords = array_map(function ($offset) use ($lat, $lng) {
                return [round($lat + $offset[0], 6), round($lng + $offset[1], 6)];
            }, $selected['offsets']);
        } else {
            // Procedural rectangular street grid route tailored to ward center
            $gridVariation = ($walkNumber + $gameDay) % 4;
            $names = [
                "{$ward->name} Station Plaza & Shopping Arcade",
                "{$ward->name} Central Park & School District Circuit",
                "{$ward->name} Residential Grid & Shrine Approach",
                "{$ward->name} Riverfront Boulevard & Community Route",
            ];
            $routeName = $names[$gridVariation];

            $step = 0.0035;
            $patterns = [
                [[0, 0], [$step, 0], [$step, $step], [0, $step], [-$step, $step * 0.5], [-$step, -$step * 0.5]],
                [[0, 0], [-$step, 0], [-$step, -$step], [0, -$step], [$step, -$step * 0.5], [$step, $step * 0.5]],
                [[0, 0], [0, $step], [$step, $step], [$step, 0], [$step * 1.5, -$step * 0.5], [0, -$step]],
                [[0, 0], [0, -$step], [-$step, -$step], [-$step, 0], [-$step * 1.5, $step * 0.5], [0, $step]],
            ];
            $selectedPattern = $patterns[$gridVariation];
            $coords = array_map(function ($pt) use ($lat, $lng) {
                return [round($lat + $pt[0], 6), round($lng + $pt[1], 6)];
            }, $selectedPattern);
        }

        return [
            'name' => $routeName,
            'coords' => $coords,
        ];
    }

    /**
     * Spawn an archetypal Japanese resident in a ward who is a Luke 10 Person of Peace.
     *
     * @param  array<int, float>  $coord
     */
    protected function spawnPersonOfPeaceResident(int $gameSessionId, Ward $ward, array $coord): Npc
    {
        $namesPool = [
            ['name' => 'Satoshi Yamada', 'name_ja' => '山田 聡', 'role' => NpcRoleArchetype::Shopkeeper, 'need' => NpcNeedState::EmotionalCrisis, 'gender' => 'male', 'age' => 44, 'nationality' => 'Japanese'],
            ['name' => 'Yuka Shibata', 'name_ja' => '柴田 優香', 'role' => NpcRoleArchetype::InternationalExpat, 'need' => NpcNeedState::Lonely, 'gender' => 'female', 'age' => 29, 'nationality' => 'Japanese'],
            ['name' => 'Kazuo Kondo', 'name_ja' => '近藤 一男', 'role' => NpcRoleArchetype::ElderlyResident, 'need' => NpcNeedState::Lonely, 'gender' => 'male', 'age' => 72, 'nationality' => 'Japanese'],
            ['name' => 'Mari Ishikawa', 'name_ja' => '石川 まり', 'role' => NpcRoleArchetype::SingleParent, 'need' => NpcNeedState::EmotionalCrisis, 'gender' => 'female', 'age' => 36, 'nationality' => 'Japanese'],
            ['name' => 'Takumi Ogawa', 'name_ja' => '小川 拓海', 'role' => NpcRoleArchetype::UniversityStudent, 'need' => NpcNeedState::SeekingCommunity, 'gender' => 'male', 'age' => 20, 'nationality' => 'Japanese'],
            ['name' => 'Emi Suzuki', 'name_ja' => '鈴木 恵美', 'role' => NpcRoleArchetype::Salaryman, 'need' => NpcNeedState::EmotionalCrisis, 'gender' => 'female', 'age' => 28, 'nationality' => 'Japanese'],
        ];

        $existingCount = Npc::where('game_session_id', $gameSessionId)->count();
        $profile = $namesPool[$existingCount % count($namesPool)];

        return Npc::create([
            'game_session_id' => $gameSessionId,
            'ward_id' => $ward->id,
            'name' => $profile['name'],
            'name_ja' => $profile['name_ja'],
            'gender' => $profile['gender'],
            'age' => $profile['age'],
            'nationality' => $profile['nationality'],
            'role_archetype' => $profile['role'],
            'need_state' => $profile['need'],
            'status' => ReceptivityStatus::Yellow,
            'yellow_since_game_month' => 1,
            'seeking_months' => 0,
            'trust_level' => rand(35, 50),
            'is_person_of_peace' => true,
            'lat' => $coord[0] ?? (float) $ward->center_lat,
            'lng' => $coord[1] ?? (float) $ward->center_lng,
            'backstory' => "Surfaced as a Person of Peace through prayer walking in {$ward->name}. Receptive and welcoming to community believers.",
        ]);
    }

    /**
     * Autonomous Multiplication Phase (Field 3 & Field 5)
     * Equipped believers (trained in 411) autonomously witness to their own Oikos networks.
     *
     * @return array{new_believers_count: int, new_churches_count: int, highlights: list<string>, player_scores: array<int, int>}
     */
    public function runAutonomousMultiplication(GameSession $session, int $gameMonth): array
    {
        // Find all equipped Green believers in this session
        $equippedBelievers = Npc::where('game_session_id', $session->id)
            ->where('status', ReceptivityStatus::Green)
            ->where('trained_411', true)
            ->with(['oikosConnections.relatedNpc', 'ward', 'disciplerUser'])
            ->get();

        $newBelieversCount = 0;
        $newChurchesCount = 0;
        $highlights = [];
        $playerScores = [];

        foreach ($equippedBelievers as $believer) {
            // Find unsaved Oikos connections using PHP 8.4 array_find()
            $connections = $believer->oikosConnections;

            $unsavedTargetRel = array_find(
                $connections->all(),
                fn (OikosRelationship $rel) => $rel->relatedNpc !== null && $rel->relatedNpc->status !== ReceptivityStatus::Green
            );

            if ($unsavedTargetRel && $unsavedTargetRel->relatedNpc) {
                $targetNpc = $unsavedTargetRel->relatedNpc;
                $trustBoost = (int) round(rand(20, 30) * $unsavedTargetRel->relationship_type->viralInfluenceFactor());
                $targetNpc->increment('trust_level', $trustBoost);

                // If trust crosses threshold, target accepts Christ!
                if ($targetNpc->trust_level >= 60) {
                    $newGen = $believer->generation + 1;
                    $targetNpc->update([
                        'status' => ReceptivityStatus::Green,
                        'prayed_salvation' => true,
                        'prayed_salvation_at' => now(),
                        'prayed_salvation_month' => $gameMonth,
                        'baptism_status' => BaptismStatus::CountingCost,
                        'is_baptized' => false,
                        'baptism_cost_months' => 0,
                        'generation' => $newGen,
                        'discipler_npc_id' => $believer->id,
                        'discipler_user_id' => $believer->discipler_user_id,
                    ]);

                    $newBelieversCount++;

                    // Calculate generational points for original player
                    $originalPlayer = $believer->disciplerUser
                        ?? $believer->disciplerNpc?->disciplerUser
                        ?? User::where('current_game_session_id', $session->id)->first();
                    if ($originalPlayer) {
                        $genPoints = match ($newGen) {
                            1 => 10,
                            2 => 50,
                            3 => 200,
                            default => 1000, // G4+ Movement stream!
                        };

                        $playerScores[$originalPlayer->id] = ($playerScores[$originalPlayer->id] ?? 0) + $genPoints;

                        $highlights[] = "🌱 [AUTONOMOUS FRUIT] {$believer->name} (G{$believer->generation}) shared Christ with {$targetNpc->name} ({$unsavedTargetRel->relationship_type->label()}) in {$believer->ward->name}! New G{$newGen} disciple born (+{$genPoints} pts)!";
                    }
                }
            }

            // Check Field 4 & 5 Autonomous Daughter Church Planting
            $disciplesCount = Npc::where('discipler_npc_id', $believer->id)->count();
            $alreadyPlantedDaughter = Church::where('leader_npc_id', $believer->id)
                ->where('generation', '>', 1)
                ->where('is_dissolved', false)
                ->exists();

            // When an equipped believer has at least 1 disciple or can lead a church (L2+), they birth an autonomous church!
            if (! $alreadyPlantedDaughter && ($believer->leadership_tier->canLeadChurch() || $disciplesCount >= 1)) {
                // Find parent church: first check discipler NPC, then discipler User
                $parentChurch = Church::where('leader_npc_id', $believer->discipler_npc_id)
                    ->where('is_dissolved', false)
                    ->first();

                if (! $parentChurch && $believer->discipler_user_id) {
                    $parentChurch = Church::where('game_session_id', $session->id)
                        ->where('leader_user_id', $believer->discipler_user_id)
                        ->where('is_dissolved', false)
                        ->first()
                        ?? Church::where('game_session_id', $session->id)
                            ->where('started_by_name', $believer->disciplerUser?->name)
                            ->where('generation', 1)
                            ->where('is_dissolved', false)
                            ->first();
                }

                if (! $parentChurch) {
                    $parentChurch = Church::where('game_session_id', $session->id)
                        ->where('is_dissolved', false)
                        ->orderBy('generation')
                        ->first();
                }

                $churchGen = $parentChurch ? ($parentChurch->generation + 1) : max(2, $believer->generation + 1);

                $attenders = rand(5, 8);
                $believers = rand(3, 5);
                $baptized = rand(2, 4);

                $newChurch = Church::create([
                    'game_session_id' => $session->id,
                    'ward_id' => $believer->ward_id,
                    'name' => "{$believer->name}'s {$believer->ward->name} Discovery Church (G{$churchGen})",
                    'parent_church_id' => $parentChurch?->id,
                    'leader_npc_id' => $believer->id,
                    'leader_user_id' => null, // Autonomous!
                    'generation' => $churchGen,
                    'elements_json' => [
                        ChurchElement::RepentanceBelief->value,
                        ChurchElement::Baptism->value,
                        ChurchElement::Word->value,
                        ChurchElement::Prayer->value,
                        ChurchElement::Fellowship->value,
                        ChurchElement::MakingDisciples->value,
                    ],
                    'is_healthy_church' => false,
                    'health_score' => 60,
                    'attenders_count' => $attenders,
                    'believers_count' => $believers,
                    'baptized_count' => $baptized,
                    'is_dissolved' => false,
                    'started_by_name' => $believer->name,
                    'lat' => $believer->lat,
                    'lng' => $believer->lng,
                    'founded_game_month' => $gameMonth,
                ]);

                $believer->update(['church_id' => $newChurch->id]);

                $newChurchesCount++;

                $originalPlayer = $believer->disciplerUser
                    ?? $believer->disciplerNpc?->disciplerUser
                    ?? User::where('current_game_session_id', $session->id)->first();
                if ($originalPlayer) {
                    $churchPts = match ($churchGen) {
                        2 => 250,
                        3 => 1000,
                        default => 5000, // G4+ Movement stream!
                    };
                    $playerScores[$originalPlayer->id] = ($playerScores[$originalPlayer->id] ?? 0) + $churchPts;

                    $highlights[] = "👑 [FIELD 5 CHURCH MULTIPLICATION] Timothy leader {$believer->name} planted an autonomous G{$churchGen} church in {$believer->ward->name} (+{$churchPts} pts)!";
                }
            }
        }

        return [
            'new_believers_count' => $newBelieversCount,
            'new_churches_count' => $newChurchesCount,
            'highlights' => $highlights,
            'player_scores' => $playerScores,
        ];
    }

    /**
     * Simulates periodic real-world church attrition and challenges:
     * - Moving away (relocation / job transfer)
     * - Sickness / chronic health difficulties
     * - Interpersonal conflict / issues between church members
     *
     * These life events reduce church quality/health, lower member attendance,
     * and can sometimes dissolve the church if health score falls too low.
     *
     * @return array{attrition_count: int, dissolved_count: int, highlights: array<string>}
     */
    public function simulateChurchAttrition(GameSession $session, int $gameMonth): array
    {
        $activeChurches = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->with(['ward', 'leaderNpc', 'members'])
            ->get();

        $attritionCount = 0;
        $dissolvedCount = 0;
        $highlights = [];

        $causes = [
            'moved' => [
                'titles' => [
                    'Job relocation ("tenkin") to another city',
                    'Family moved away to another prefecture',
                    'Student believer graduated and relocated',
                ],
                'attendersLoss' => [1, 3],
                'believersLoss' => [1, 2],
                'healthLoss' => [12, 22],
            ],
            'sickness' => [
                'titles' => [
                    'Key gathering hosts hospitalized with prolonged illness',
                    'Severe illness struck key believers, halting weekly gatherings',
                    'Elder fell ill with chronic health challenges',
                ],
                'attendersLoss' => [1, 2],
                'believersLoss' => [1, 1],
                'healthLoss' => [15, 25],
            ],
            'issues' => [
                'titles' => [
                    'Relational friction and misunderstandings between members',
                    'Interpersonal offenses and gossip fractured fellowship',
                    'Disputes over church practices led members to leave',
                ],
                'attendersLoss' => [2, 4],
                'believersLoss' => [1, 3],
                'healthLoss' => [20, 32],
            ],
        ];

        foreach ($activeChurches as $church) {
            // Give active churches a baseline chance of experiencing life trials/attrition (~28-50%)
            $attritionChance = $church->health_score < 40 ? 50 : 28;
            if (rand(1, 100) > $attritionChance) {
                continue;
            }

            // Pick a random cause
            $causeKey = array_rand($causes);
            $cause = $causes[$causeKey];
            $causeTitle = $cause['titles'][array_rand($cause['titles'])];

            $lostAttenders = rand($cause['attendersLoss'][0], $cause['attendersLoss'][1]);
            $lostBelievers = rand($cause['believersLoss'][0], $cause['believersLoss'][1]);
            $healthLoss = rand($cause['healthLoss'][0], $cause['healthLoss'][1]);

            // Spiritual resilience: churches practicing Fellowship & Prayer mitigate 5% health loss
            if ($church->practicesElement(ChurchElement::Fellowship) && $church->practicesElement(ChurchElement::Prayer)) {
                $healthLoss = max(5, $healthLoss - 5);
            }

            // If an NPC member is present, optionally update their personal state
            if ($church->members->isNotEmpty()) {
                /** @var Npc $member */
                $member = $church->members->random();
                if ($causeKey === 'moved') {
                    $member->update([
                        'church_id' => null,
                        'backstory' => ($member->backstory ? $member->backstory.' ' : '')."Relocated out of {$church->ward->name} in Month {$gameMonth}.",
                    ]);
                } elseif ($causeKey === 'sickness') {
                    $member->update([
                        'need_state' => NpcNeedState::SicknessHospitalization,
                    ]);
                } elseif ($causeKey === 'issues') {
                    $member->decrement('trust_level', min($member->trust_level, 15));
                }
            }

            $dissolved = $church->applyAttrition(
                $causeTitle,
                $lostAttenders,
                $lostBelievers,
                $healthLoss,
                $gameMonth
            );

            $attritionCount++;

            if ($dissolved) {
                $dissolvedCount++;
                $highlights[] = "⚠️ [CHURCH DISSOLVED] {$church->name} in {$church->ward->name} has dissolved in Month {$gameMonth}. Reason: {$causeTitle}.";
            } else {
                $highlights[] = "📉 [MEMBER ATTRITION] {$church->name}: {$causeTitle}. Quality decreased to {$church->health_score}% ({$church->attenders_count} attenders remaining).";
            }
        }

        return [
            'attrition_count' => $attritionCount,
            'dissolved_count' => $dissolvedCount,
            'highlights' => $highlights,
        ];
    }
}
