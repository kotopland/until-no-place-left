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
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\OikosRelationship;
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
    public function resolveDailyTick(GameSession $session, bool $force = false): ?DailyTickResolutionResult
    {
        // 1. Session must be active and not have completed all 30 days of this month
        if (! $session->is_active || (! $force && $session->days_processed_this_month >= 30)) {
            return null;
        }

        // 2. Identify online players (active within last 15 minutes) who have not reached the 30-day cap today
        $onlineUsers = User::where('current_game_session_id', $session->id)
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subMinutes(15))
            ->get();

        if ($force && $onlineUsers->isEmpty()) {
            $onlineUsers = User::where('current_game_session_id', $session->id)->get();
        }

        $eligibleUsers = $onlineUsers->filter(fn (User $user) => $force || ! $user->hasReachedDailyPlayLimit);

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($session, $eligibleUsers) {
            $gameMonth = $session->current_game_month;
            $currentDay = $session->current_game_day;

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

            foreach ($pendingActions as $action) {
                $outcome = $this->resolveSingleAction($action, $gameMonth);
                $outcomes[] = $outcome;
                $actionsProcessed++;

                $userId = $action->user_id;
                $playerScoreGains[$userId] = ($playerScoreGains[$userId] ?? 0) + $outcome->pointsAwarded;

                $action->update([
                    'status' => QueuedActionStatus::Processed,
                    'outcome_notes' => $outcome->narrativeOutcome,
                ]);
            }

            // Award points and replenish daily AP for eligible active players
            foreach ($eligibleUsers as $user) {
                $scoreGain = $playerScoreGains[$user->id] ?? 0;
                if ($scoreGain > 0) {
                    $user->increment('total_generational_score', $scoreGain);
                }

                // Increment days played today and refresh real-life day tracking
                $user->touchActive();
                $user->increment('days_played_today');

                // Replenish daily Action Points up to maximum bandwidth
                $newAp = min($user->max_action_points, $user->action_points + $user->daily_action_points);
                $user->update(['action_points' => $newAp]);
            }

            // Advance session day counters
            $nextDay = min(30, $session->current_game_day + 1);
            $session->increment('days_processed_this_month');
            $session->update([
                'current_game_day' => $nextDay,
                'last_daily_tick_at' => now(),
                'next_daily_tick_at' => now()->addMinutes(10),
            ]);

            return new DailyTickResolutionResult(
                gameSessionId: $session->id,
                gameMonth: $gameMonth,
                gameDay: $currentDay,
                actionsProcessed: $actionsProcessed,
                onlinePlayersCount: $eligibleUsers->count(),
                actionOutcomes: $outcomes,
                playerScoreGains: $playerScoreGains,
                onlineUserIds: $eligibleUsers->pluck('id')->all(),
            );
        });
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

            // Merge autonomous generational scores
            foreach ($multiplicationData['player_scores'] as $pId => $pts) {
                $playerScoreGains[$pId] = ($playerScoreGains[$pId] ?? 0) + $pts;
            }

            // 4. Update player models and reset monthly bandwidth Action Points
            $participatingUsers = User::where('current_game_session_id', $session->id)->get();
            foreach ($participatingUsers as $user) {
                $scoreGain = $playerScoreGains[$user->id] ?? 0;
                $user->increment('total_generational_score', $scoreGain);
                $user->update([
                    'action_points' => $user->max_action_points, // Refresh monthly AP bandwidth
                    'days_played_today' => 0, // Reset daily play limit for new month/day
                ]);
            }

            // 5. Advance Game Session Calendar and reset daily tick counters
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
        });
    }

    /**
     * Resolves a single player ministry action with holistic Japanese relational dynamics.
     */
    public function resolveSingleAction(QueuedMonthlyAction $action, int $gameMonth): ActionResolutionOutcome
    {
        $user = $action->user;
        $ward = $action->ward;
        $npc = $action->targetNpc;

        // Handle ward-wide platform actions (e.g., International Event)
        if ($action->action_type === MinistryActionType::InternationalEvent) {
            return $this->resolveInternationalEvent($action, $gameMonth);
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
                            ReceptivityStatus::Yellow => ReceptivityStatus::Green,
                            default => $prevStatus,
                        };

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
                if ($prevStatus === ReceptivityStatus::Yellow && ($prevTrust >= 50 || $npc->is_person_of_peace)) {
                    $trustChange = rand(25, 35);
                    $newStatus = ReceptivityStatus::Green; // ACCEPTED CHRIST!
                    $npc->update([
                        'prayed_salvation' => true,
                        'prayed_salvation_at' => now(),
                        'baptism_status' => BaptismStatus::CountingCost,
                    ]);
                    $points = 70;
                    $roleAssigned = ContributionRole::Harvester;

                    $narrative = "Glory to God! {$npc->name} clearly understood God's Design, Brokenness, and Gospel Rescue through the 3 Circles and joyfully prayed to receive Christ! They are now counting the cost of baptism.";
                    $coachTip = 'Field 2 to Field 3: A new believer is born! In Japanese context, family relationships must be honored. Walk with them through baptism commitment before, during, or after 411 Discipleship Training!';
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
                    $narrative = "Shared the 3 Circles. {$npc->name} listened thoughtfully and had good questions about Brokenness and Jesus.";
                    $coachTip = 'Yellow status: Continue meeting to answer questions, or lead them in the Prayer of Salvation!';
                    $roleAssigned = ContributionRole::Waterer;
                }
                break;

            case MinistryActionType::PraySalvation:
                // Field 2: Leading Seeker in Prayer of Salvation / Sinner's Prayer
                if ($prevStatus === ReceptivityStatus::Yellow || $prevTrust >= 40 || $npc->is_person_of_peace) {
                    $trustChange = rand(25, 35);
                    $newStatus = ReceptivityStatus::Green;
                    $npc->update([
                        'prayed_salvation' => true,
                        'prayed_salvation_at' => now(),
                        'baptism_status' => BaptismStatus::CountingCost,
                    ]);
                    $points = 75;
                    $roleAssigned = ContributionRole::Harvester;

                    $narrative = "Hallelujah! {$npc->name} prayed the Sinner's Prayer with genuine sincerity, confessing their sins and receiving Jesus Christ as Lord and Savior! They have entered the Kingdom of God and are now prayerfully counting the cost of baptism.";
                    $coachTip = 'Salvation celebrated! In Japan, ancestral and family heritage makes baptism a deep step. You can help them commit to baptism before, during, or after 411 Discipleship Training!';
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
                    if ($npc->baptism_status === BaptismStatus::Baptized || $npc->is_baptized) {
                        $trustChange = 10;
                        $points = 20;
                        $narrative = "Met with {$npc->name} to celebrate their baptism testimony and encourage them in bold Christian discipleship.";
                        $coachTip = 'Already baptized! Focus on equipping them through 411 Discipleship Training or raising them toward Timothy leadership.';
                    } elseif ($npc->baptism_status === BaptismStatus::Committed || ($prevTrust >= 70 && $npc->baptism_cost_months >= 1)) {
                        // Water Baptism Celebrated!
                        $trustChange = rand(25, 35);
                        $npc->update([
                            'is_baptized' => true,
                            'baptism_status' => BaptismStatus::Baptized,
                            'baptized_at' => now(),
                        ]);
                        $points = 80;
                        $roleAssigned = ContributionRole::Discipler;

                        $narrative = "Glory to God! {$npc->name} publicly confessed Christ and was baptized in water! Local believers gathered to celebrate this courageous milestone in Nagoya!";
                        $coachTip = 'Matthew 28:19 fulfilled! Baptized disciples are ready to be equipped in 411 Discipleship (if not already trained) to ignite 2 Timothy 2:2 multiplication!';
                    } else {
                        // Taking the step of commitment / counting the cost
                        $trustChange = rand(15, 25);
                        $npc->update([
                            'baptism_status' => BaptismStatus::Committed,
                            'baptism_cost_months' => $npc->baptism_cost_months + 1,
                        ]);
                        $points = 45;
                        $roleAssigned = ContributionRole::Discipler;

                        $narrative = "Deep pastoral conversation with {$npc->name} addressing Japanese family traditions, ancestral rites, and counting the cost. By God's peace, they formally committed to be baptized!";
                        $coachTip = 'Major breakthrough! In Japan, family pushback is the primary obstacle to baptism. Their commitment is secured! Baptism can now be administered next, or integrated into 411 training.';
                    }
                } else {
                    $trustChange = 0;
                    $points = 0;
                    $narrative = "Discussed water baptism with {$npc->name}, but they have not yet prayed the prayer of salvation.";
                    $coachTip = 'Baptism is for believers who have repented and put their trust in Jesus. Focus on Gospel sowing and the Prayer of Salvation first.';
                }
                break;

            case MinistryActionType::Train411:
                // Field 3: Discipleship Equipping (Why, Who, What, When)
                if ($npc->status === ReceptivityStatus::Green) {
                    $trustChange = rand(20, 30);
                    $npc->update([
                        'trained_411' => true,
                        'leadership_tier' => LeadershipTier::Apprentice,
                    ]);
                    $points = 50;
                    $roleAssigned = ContributionRole::Discipler;

                    // During 411 Training: The Commands of Jesus (Command 2: Be Baptized) catalyze baptism decisions!
                    if (! $npc->is_baptized) {
                        if ($npc->baptism_status === BaptismStatus::CountingCost) {
                            $npc->update(['baptism_status' => BaptismStatus::Committed]);
                            $narrative = "Trained {$npc->name} in 411 Discipleship (Why, Who, What, When)! During the session on Jesus' commands, they were convicted by Scripture and joyfully committed to water baptism!";
                        } elseif ($npc->baptism_status === BaptismStatus::Committed) {
                            $npc->update([
                                'is_baptized' => true,
                                'baptism_status' => BaptismStatus::Baptized,
                                'baptized_at' => now(),
                            ]);
                            $points += 40;
                            $narrative = "Trained {$npc->name} in 411 Discipleship! Obeying the Great Commission, {$npc->name} was joyfully baptized in water during the 411 training gathering!";
                        } else {
                            $narrative = "Trained {$npc->name} in 411: Why make disciples? Who (Oikos Map)? What (15s & 3 Circles)? When (Goals)? They are now an active sower!";
                        }
                    } else {
                        $narrative = "Trained {$npc->name} in 411: Why make disciples? Who (Oikos Map)? What (15s & 3 Circles)? When (Goals)? They are now an equipped sower!";
                    }

                    $coachTip = 'Field 3 activated: Equipped believers multiply automatically during monthly ticks by reaching their own family and friends. (They can also complete baptism afterward if still counting the cost!)';
                } else {
                    $trustChange = 5;
                    $narrative = "Attempted 411 training, but {$npc->name} is not yet a committed believer.";
                    $coachTip = '411 Discipleship Training is designed for Green believers. Focus on Gospel sowing (Field 2) and Prayer of Salvation first.';
                }
                break;

            case MinistryActionType::MentorTimothy:
                // Field 5: Leadership Multiplication
                if ($npc->trained_411 && $npc->leadership_tier === LeadershipTier::Apprentice) {
                    $trustChange = rand(20, 25);
                    $npc->update([
                        'leadership_tier' => LeadershipTier::Timothy,
                    ]);
                    $points = 100;
                    $roleAssigned = ContributionRole::Discipler;
                    $narrative = "Invested deep pastoral coaching into {$npc->name}. Elevated to a Timothy leader capable of planting autonomous daughter churches!";
                    $coachTip = '2 Timothy 2:2: Entrust to faithful people who will be able to teach others also. Timothys break the missionary bandwidth bottleneck!';
                } else {
                    $trustChange = 8;
                    $narrative = "Provided leadership mentorship to {$npc->name}.";
                    $coachTip = 'To raise a Timothy, ensure the disciple is first faithful in 411 and actively leading others.';
                }
                break;
        }

        // Apply new trust level bounded 1-100
        $newTrust = (int) max(1, min(100, $prevTrust + $trustChange));
        $statusChanged = $newStatus !== $prevStatus;

        $npc->update([
            'trust_level' => $newTrust,
            'status' => $newStatus,
        ]);

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
                $att->update(['status' => ReceptivityStatus::Yellow]); // Instantly becomes an open acquaintance!
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
     * Autonomous Multiplication Phase (Field 3 & Field 5)
     * Equipped believers (trained in 411) autonomously witness to their own Oikos networks.
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
                        'baptism_status' => BaptismStatus::Baptized,
                        'is_baptized' => true,
                        'baptized_at' => now(),
                        'generation' => $newGen,
                        'discipler_npc_id' => $believer->id,
                    ]);

                    $newBelieversCount++;

                    // Calculate generational points for original player
                    $originalPlayer = $believer->disciplerUser ?? User::where('current_game_session_id', $session->id)->first();
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
                ->exists();

            // When an equipped believer has at least 1 disciple or is elevated to Timothy, they birth an autonomous church!
            if (! $alreadyPlantedDaughter && ($believer->leadership_tier === LeadershipTier::Timothy || $disciplesCount >= 1)) {
                $churchGen = max(2, $believer->generation + 1);
                $parentChurch = Church::where('leader_npc_id', $believer->discipler_npc_id)->first()
                    ?? Church::where('game_session_id', $session->id)->orderBy('generation')->first();

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
                    'lat' => $believer->lat,
                    'lng' => $believer->lng,
                    'founded_game_month' => $gameMonth,
                ]);

                $newChurchesCount++;

                $originalPlayer = $believer->disciplerUser ?? User::where('current_game_session_id', $session->id)->first();
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
}
