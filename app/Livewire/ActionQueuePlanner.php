<?php

namespace App\Livewire;

use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\AiColaborerService;
use App\Services\FourFieldsEngine;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class ActionQueuePlanner extends Component
{
    #[Url(as: 'view')]
    public string $hqSubView = 'planner'; // 'planner' or 'rhythms'

    public ?int $selectedWardId = null;

    public ?int $selectedNpcId = null;

    public ?string $selectedActionType = null;

    public ?string $selectedAction {
        get => $this->selectedActionType;
        set => $this->selectedActionType = $value;
    }

    public ?string $infoActionType = null;

    public bool $showActionInfoModal = false;

    public string $notes = '';

    public bool $filterEligibleOnly = false;

    public int $mobileStep = 2;

    public ?User $player = null;

    public ?GameSession $session = null;

    /** @var array<string, string> */
    protected $listeners = [
        'day-advanced' => 'refreshPlayerState',
        'action-queued' => '$refresh',
        'home-ward-selected' => 'onHomeWardSelected',
        'set-hq-subview' => 'setHqSubView',
    ];

    #[On('set-hq-subview')]
    public function setHqSubView(string $view): void
    {
        $this->hqSubView = in_array($view, ['planner', 'rhythms'], true) ? $view : 'planner';
    }

    public function boot(): void
    {
        $this->player = auth()->user() ?? $this->player ?? User::first();
        $this->player?->refresh();
        $this->session = $this->player?->currentGameSession ?? $this->session ?? GameSession::first();
        $this->session?->refresh();
    }

    public function mount(): void
    {
        $this->boot();

        // Mark player active (online)
        $this->player?->touchActive();

        // Default viewing selection to player's home ward, or Naka-ku (without auto-assigning residence)
        $defaultWard = $this->player?->homeWard ?? Ward::where('slug', 'naka-ku')->first() ?? Ward::first();
        $this->selectedWardId = $defaultWard?->id;
    }

    public function refreshPlayerState(): void
    {
        $this->boot();
        $this->selectedActionType = null;
    }

    public function onHomeWardSelected(int $wardId): void
    {
        $this->refreshPlayerState();
        $this->selectedWardId = $wardId;
    }

    public function moveToWard(int $wardId): void
    {
        if (! $this->player) {
            return;
        }

        $ward = Ward::find($wardId);
        if (! $ward) {
            return;
        }

        $previousWard = $this->player->homeWard;
        $this->player->update(['home_ward_id' => $ward->id]);
        $this->player->refresh();

        $fromText = $previousWard ? "from {$previousWard->name} " : '';
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "🏡 Relocated your residence {$fromText}to {$ward->name}! You are now an embedded neighbor here (+25% relational trust bonus and zero transit friction).",
        ]);
        $this->selectedWardId = $ward->id;
    }

    public function conductJointPrayerWalk(int $coLaborerId): void
    {
        if (! $this->player || ! $this->selectedWardId) {
            return;
        }

        $ward = Ward::find($this->selectedWardId);
        $coLaborer = User::find($coLaborerId);

        if (! $ward || ! $coLaborer) {
            return;
        }

        $commuteApCost = $this->player->commuteApCostForWard($ward);
        $requiredAp = 2 + $commuteApCost;

        if ($this->player->action_points < $requiredAp) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Not enough Action Points. Joint Prayer Walk in {$ward->name} requires {$requiredAp} AP (2 base + {$commuteApCost} commute). You have {$this->player->action_points} AP.",
            ]);

            return;
        }

        $aiService = app(AiColaborerService::class);
        $walkStatus = $aiService->getJointPrayerWalkStatus($this->player);

        if (! $walkStatus['allowed']) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "👣 {$walkStatus['reason']}",
            ]);

            return;
        }

        $this->player->decrement('action_points', $requiredAp);

        $aiService = app(AiColaborerService::class);
        $outcome = $aiService->conductJointPrayerWalk($this->player, $coLaborer, $ward);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "🤝 Joint MAWL Prayer Walk with {$coLaborer->name} completed! Earned +15 Co-op Synergy Points. {$outcome->narrativeOutcome}",
        ]);

        $this->player->refresh();
        $this->dispatch('action-queued');
    }

    public function conductIronOnIron(int $coLaborerId): void
    {
        if (! $this->player) {
            return;
        }

        $coLaborer = User::find($coLaborerId);
        if (! $coLaborer) {
            return;
        }

        $aiService = app(AiColaborerService::class);
        $status = $aiService->getIronOnIronStatus($this->player, $coLaborer);

        if (! $status['allowed']) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "⚔️ {$status['reason']}",
            ]);

            return;
        }

        $result = $aiService->conductIronOnIron($this->player, $coLaborer);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "⚔️ Iron-on-Iron with {$coLaborer->name}: +{$result['bonus_ap']} AP, +{$result['coop_points']} Co-op Points! {$result['coaching_tip']}",
        ]);

        $this->player->refresh();
        $this->dispatch('action-queued');
    }

    public function setMobileStep(int $step): void
    {
        $this->mobileStep = max(1, min(3, $step));
    }

    public function openActionInfoModal(?string $type = null): void
    {
        $this->infoActionType = $type ?? $this->selectedActionType;
        $this->showActionInfoModal = true;
    }

    public function closeActionInfoModal(): void
    {
        $this->showActionInfoModal = false;
        $this->infoActionType = null;
    }

    public function selectActionFromModal(string $type): void
    {
        $this->selectedActionType = $type;
        $this->showActionInfoModal = false;
        $this->infoActionType = null;
        $this->dispatch('scroll-to-queue-box');
    }

    public function touchHeartbeat(): void
    {
        try {
            $this->player?->touchActive();
        } catch (\Throwable $e) {
            // Heartbeat touch is non-essential; avoid failing request if another process holds user lock
            report($e);
        }
    }

    public function selectWard(int $wardId): void
    {
        $this->selectedWardId = $wardId;
        $this->selectedNpcId = null;
        $this->selectedActionType = null;
        $this->mobileStep = 2;
    }

    public function toggleFilterEligibleOnly(): void
    {
        $this->filterEligibleOnly = ! $this->filterEligibleOnly;
    }

    public function selectNpc(int $npcId): void
    {
        $this->selectedNpcId = $npcId;
        $this->selectedActionType = null;
        $this->mobileStep = 3;
    }

    public function selectWardPlatformAction(?string $actionType = null): void
    {
        $this->selectedNpcId = null;
        if ($actionType) {
            $this->selectedActionType = $actionType;
        } else {
            $this->selectedActionType = MinistryActionType::PrayerWalk->value;
        }
        $this->mobileStep = 3;
    }

    public function getActiveWorkerRhythmProperty(): ?QueuedMonthlyAction
    {
        if (! $this->player) {
            return null;
        }

        return QueuedMonthlyAction::where('user_id', $this->player->id)
            ->where('game_month', $this->player->current_game_month)
            ->where('game_day', $this->player->current_game_day)
            ->where('status', QueuedActionStatus::Pending)
            ->whereIn('action_type', [
                MinistryActionType::ReceiveMentoring,
                MinistryActionType::SabbathRest,
                MinistryActionType::FastingRetreat,
                MinistryActionType::MidLevelConference,
            ])
            ->first();
    }

    public function getIsCurrentDayBlockedProperty(): bool
    {
        return $this->activeWorkerRhythm !== null;
    }

    public function queueAction(): void
    {
        if (! $this->player || ! $this->session) {
            $this->refreshPlayerState();
        }

        if (! $this->player || ! $this->session) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to queue action: Player profile or game session not initialized.',
            ]);

            return;
        }

        if ($this->player->is_burned_out) {
            $monthsLeft = $this->player->burnoutMonthsRemaining($this->player->current_game_month);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "🚨 BURNOUT LEAVE: You collapsed from severe ministry overwork without rest. You are on mandatory medical leave for {$monthsLeft} more month(s) (until Month {$this->player->burned_out_until_game_month}). Advance months to complete your sabbatical recovery.",
            ]);

            return;
        }

        if ($this->isCurrentDayBlocked) {
            $rhythm = $this->activeWorkerRhythm;
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Time Blocked: You are participating in {$rhythm?->actionTitle} on Day {$this->player->current_game_day}. All other field actions are blocked during this activity.",
            ]);

            return;
        }

        if (! $this->selectedActionType) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select an action to schedule.',
            ]);

            return;
        }

        $actionEnum = MinistryActionType::tryFrom($this->selectedActionType);
        if (! $actionEnum) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Invalid action type selected.',
            ]);

            return;
        }

        $baseApCost = $actionEnum->apCost();
        $targetWard = Ward::find($this->selectedWardId);
        $commuteApCost = $this->player->commuteApCostForWard($targetWard);
        $totalApCost = $baseApCost + $commuteApCost;

        // Refresh player state
        $this->player->refresh();

        if ($this->player->action_points < $totalApCost) {
            $commuteDetail = $commuteApCost > 0
                ? " ({$baseApCost} AP base + {$commuteApCost} AP transit commute from {$this->player->homeWard?->name})"
                : '';
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Insufficient Action Points! You have {$this->player->action_points} AP, but {$actionEnum->label()} requires {$totalApCost} AP{$commuteDetail}.",
            ]);

            return;
        }

        $targetNpc = $this->selectedNpcId ? Npc::find($this->selectedNpcId) : null;

        if ($actionEnum->requiresTargetNpc() && ! $targetNpc) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select a resident NPC in the ward before scheduling this action.',
            ]);

            return;
        }

        // Validate 5 Levels of Leadership readiness
        if ($targetNpc && ! $actionEnum->isEligibleFor($targetNpc)) {
            $reason = $actionEnum->ineligibilityReason($targetNpc);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Action Blocked: {$reason}",
            ]);

            return;
        }

        // Enforce daily rate limits for Iron on Iron (max 1 per person per day, max 3 per day total)
        if ($actionEnum === MinistryActionType::IronOnIron) {
            $aiService = app(AiColaborerService::class);
            $ioiStatus = $aiService->getIronOnIronStatus($this->player, null, $targetNpc);
            if (! $ioiStatus['allowed']) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "⚔️ {$ioiStatus['reason']}",
                ]);

                return;
            }
        }

        // Enforce once-per-week rate limit for Church Circles coaching (minimum 7 days between sessions)
        if ($actionEnum === MinistryActionType::ChurchCircles) {
            $church = $targetNpc?->church
                ?? Church::where('leader_npc_id', $targetNpc?->id)->first()
                ?? Church::where('ward_id', $this->selectedWardId)->first();

            if ($church) {
                $eligibility = $church->coachingEligibility($this->player->current_game_month ?: 1, $this->player->current_game_day ?: 1);
                if (! $eligibility['allowed']) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => "⛪ {$eligibility['reason']}",
                    ]);

                    return;
                }
            }
        }

        // Deduct player AP (including transit commute surcharge)
        $this->player->decrement('action_points', $totalApCost);
        $this->player->refresh();

        // Queue action for the daily/monthly resolution tick (supports joint prayer walking with a resident)
        $targetNpcId = match ($actionEnum) {
            MinistryActionType::PrayerWalk => $this->selectedNpcId,
            MinistryActionType::InternationalEvent => null,
            default => $actionEnum->requiresTargetNpc() ? $this->selectedNpcId : null,
        };

        QueuedMonthlyAction::create([
            'game_session_id' => $this->session->id,
            'user_id' => $this->player->id,
            'ward_id' => $this->selectedWardId,
            'target_npc_id' => $targetNpcId,
            'action_type' => $actionEnum,
            'status' => QueuedActionStatus::Pending,
            'game_month' => $this->player->current_game_month ?: 1,
            'game_day' => $this->player->current_game_day ?: 1,
            'ap_cost' => $totalApCost,
            'notes' => $this->notes,
        ]);

        $this->notes = '';
        $this->selectedActionType = null;
        $this->mobileStep = 2;
        $this->dispatch('action-queued');
        $this->dispatch('scroll-to-step-2');
        $commuteMsg = $commuteApCost > 0 ? " (includes +{$commuteApCost} AP transit)" : '';
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Successfully queued {$actionEnum->label()} for Day {$this->player->current_game_day} / Month {$this->player->current_game_month}! ({$totalApCost} AP consumed{$commuteMsg})",
        ]);
    }

    /**
     * Schedules a worker spiritual rhythm or retreat that blocks field time for its duration.
     */
    public function scheduleWorkerRhythm(string $rhythmType): void
    {
        if (! $this->player || ! $this->session) {
            $this->refreshPlayerState();
        }

        if (! $this->player || ! $this->session) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to schedule activity: Player profile or game session not initialized.',
            ]);

            return;
        }

        if ($this->player->is_burned_out) {
            $monthsLeft = $this->player->burnoutMonthsRemaining($this->player->current_game_month);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "🚨 BURNOUT LEAVE: You are on mandatory medical leave for {$monthsLeft} more month(s). Advance months to complete your sabbatical recovery.",
            ]);

            return;
        }

        if ($rhythmType === 'mid_level_conference') {
            $rhythmType = 'midlevel_conference';
        }

        $rhythmEnum = MinistryActionType::tryFrom($rhythmType);
        if (! $rhythmEnum || ! $rhythmEnum->isWorkerRhythm()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Invalid spiritual rhythm or retreat selected.',
            ]);

            return;
        }

        $currentDay = max($this->player->current_game_day ?? 1, $this->session->current_game_day ?? 1);
        $currentMonth = $this->player->current_game_month ?? $this->session->current_game_month ?? 1;
        $duration = $rhythmEnum->durationDays();
        $endDay = $currentDay + $duration - 1;

        if ($endDay > 30) {
            $remaining = max(0, 30 - $currentDay + 1);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Cannot schedule {$rhythmEnum->label()} ({$duration} days): Only {$remaining} day(s) remain in Month {$currentMonth}. Please wait for the next month cycle.",
            ]);

            return;
        }

        // Check if there are already pending actions on any day in this range
        $existingActions = QueuedMonthlyAction::where('user_id', $this->player->id)
            ->where('game_month', $currentMonth)
            ->whereBetween('game_day', [$currentDay, $endDay])
            ->where('status', QueuedActionStatus::Pending)
            ->get();

        if ($existingActions->isNotEmpty()) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Time Conflict: You already have {$existingActions->count()} action(s) scheduled between Day {$currentDay} and Day {$endDay}. Please cancel them first before blocking this time.",
            ]);

            return;
        }

        // Enforce frequency constraints
        if ($rhythmEnum === MinistryActionType::SabbathRest) {
            $recentSabbath = QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::SabbathRest)
                ->where('game_day', '>=', max(1, $currentDay - 6))
                ->exists();

            if ($recentSabbath) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Weekly Sabbath can only be taken once per 7-day period (minimum 6 days between Sabbaths).',
                ]);

                return;
            }
        } elseif ($rhythmEnum === MinistryActionType::ReceiveMentoring) {
            $recentMentoring = QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::ReceiveMentoring)
                ->where('game_day', '>=', max(1, $currentDay - 6))
                ->exists();

            if ($recentMentoring) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Movement mentoring can be scheduled at most once per week (minimum 6 days between sessions).',
                ]);

                return;
            }
        } elseif ($rhythmEnum === MinistryActionType::FastingRetreat) {
            $hadFasting = QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::FastingRetreat)
                ->exists();

            if ($hadFasting) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "You have already completed or scheduled a 4-Day Fasting Retreat in Month {$currentMonth} (limit 1 per month).",
                ]);

                return;
            }
        } elseif ($rhythmEnum === MinistryActionType::MidLevelConference) {
            $hadConference = QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::MidLevelConference)
                ->exists();

            if ($hadConference) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "You have already attended or scheduled an NPL Mid-Level Conference in Month {$currentMonth} (limit 1 per month).",
                ]);

                return;
            }
        }

        // Schedule records for all days in the duration
        $wardId = $this->player->home_ward_id ?? $this->selectedWardId ?? Ward::first()->id;

        for ($day = $currentDay; $day <= $endDay; $day++) {
            $dayIndex = $day - $currentDay + 1;
            $note = $duration > 1
                ? "{$rhythmEnum->label()} (Day {$dayIndex} of {$duration})"
                : $rhythmEnum->label();

            QueuedMonthlyAction::create([
                'game_session_id' => $this->session->id,
                'user_id' => $this->player->id,
                'ward_id' => $wardId,
                'target_npc_id' => null,
                'action_type' => $rhythmEnum,
                'status' => QueuedActionStatus::Pending,
                'game_month' => $currentMonth,
                'game_day' => $day,
                'ap_cost' => 0,
                'notes' => $note,
            ]);
        }

        $this->dispatch('action-queued');
        $rangeText = $duration > 1 ? "Days {$currentDay}–{$endDay}" : "Day {$currentDay}";
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Successfully committed to {$rhythmEnum->label()} for {$rangeText}! Time is now blocked for other field actions.",
        ]);
    }

    public function cancelAction(int $actionId): void
    {
        $action = QueuedMonthlyAction::where('id', $actionId)
            ->where('user_id', $this->player->id)
            ->where('status', QueuedActionStatus::Pending)
            ->first();

        if ($action) {
            if ($action->action_type->isWorkerRhythm()) {
                // Cancel all related pending days of this rhythm in this month
                $count = QueuedMonthlyAction::where('user_id', $this->player->id)
                    ->where('game_month', $action->game_month)
                    ->where('action_type', $action->action_type)
                    ->where('status', QueuedActionStatus::Pending)
                    ->delete();

                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Cancelled {$action->action_type->label()} ({$count} day(s) unblocked).",
                ]);
            } else {
                // Refund AP
                $this->player->increment('action_points', $action->ap_cost);
                $action->delete();

                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Action cancelled. {$action->ap_cost} AP refunded.",
                ]);
            }
        }
    }

    /**
     * Advance to the next game day tick for this player.
     * Enforces the 30-day limit per real-life day without advancing other players.
     */
    public function advanceDay(bool $isManual = true): void
    {
        if (! $this->player || ! $this->session) {
            return;
        }

        $this->player->refresh();
        $this->session->refresh();

        // If triggered automatically by the countdown timer, ensure a tick is actually due
        if (! $isManual && ! $this->player->isDailyTickDue) {
            return;
        }

        // Check if player has reached the 30-day limit for this real-life day
        if ($this->player->days_played_today >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Daily play limit reached: You have completed all 30 game days for today. The new month will begin tonight at midnight!',
                ]);
            }

            return;
        }

        // Check if the current 30-day month cycle is completed
        if ($this->player->days_processed_this_month >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Month {$this->player->current_game_month} has completed all 30 days! You can now advance to Month ".($this->player->current_game_month + 1).'.',
                ]);
            }

            return;
        }

        $engine = app(FourFieldsEngine::class);
        $result = $engine->resolveDailyTick($this->session, specificUser: $this->player, force: true);

        $this->player->refresh();
        $this->session->refresh();
        $this->selectedActionType = null;
        $this->notes = '';

        $this->dispatch('day-advanced');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Advanced to Day {$this->player->current_game_day} / 30! Processed {$result?->actionsProcessed} queued action(s). Bandwidth replenished to {$this->player->action_points} AP.",
        ]);
    }

    /**
     * Advance to the next game month when all 30 days have been completed.
     */
    public function advanceMonth(): void
    {
        if (! $this->player || ! $this->session) {
            return;
        }

        $this->player->refresh();

        if ($this->player->days_processed_this_month < 30) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'You must complete all 30 days before advancing to Month '.($this->player->current_game_month + 1)." ({$this->player->days_processed_this_month}/30 days completed).",
            ]);

            return;
        }

        $engine = app(FourFieldsEngine::class);
        $result = $engine->resolveGameMonthForUser($this->player);

        $this->player->refresh();
        $this->selectedActionType = null;
        $this->notes = '';

        $this->dispatch('day-advanced');
        $this->dispatch('open-month-review');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Welcome to Month {$this->player->current_game_month}! AP replenished to {$this->player->action_points} and monthly multiplication report generated.",
        ]);
    }

    public function render(): View
    {
        $this->player?->refresh();
        $this->session?->refresh();

        $wards = Ward::orderBy('name')->get();
        $selectedWard = Ward::find($this->selectedWardId);

        $npcs = $this->selectedWardId
            ? Npc::where('game_session_id', $this->session?->id)
                ->where('ward_id', $this->selectedWardId)
                ->with(['oikosConnections.relatedNpc'])
                ->orderByDesc('is_person_of_peace')
                ->orderByDesc('trust_level')
                ->get()
            : collect();

        $selectedNpc = $this->selectedNpcId ? Npc::with(['oikosConnections.relatedNpc', 'ward'])->find($this->selectedNpcId) : null;

        $targetMonth = $this->player ? ($this->player->current_game_month ?: 1) : 1;

        $queuedActions = QueuedMonthlyAction::where('user_id', $this->player?->id)
            ->where('game_month', $targetMonth)
            ->where('status', QueuedActionStatus::Pending)
            ->when($this->session?->id, fn ($q) => $q->where('game_session_id', $this->session->id))
            ->with(['targetNpc', 'ward'])
            ->latest()
            ->get();

        $recentOutcomes = QueuedMonthlyAction::where('user_id', $this->player?->id)
            ->where('status', QueuedActionStatus::Processed)
            ->whereNotNull('outcome_notes')
            ->when($this->session?->id, fn ($q) => $q->where('game_session_id', $this->session->id))
            ->with(['targetNpc', 'ward'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $allFieldActionTypes = array_values(array_filter(
            MinistryActionType::cases(),
            fn (MinistryActionType $action) => ! $action->isWorkerRhythm()
        ));

        $actionTypes = ($this->filterEligibleOnly && $selectedNpc)
            ? array_values(array_filter($allFieldActionTypes, fn (MinistryActionType $action) => $action->isEligibleFor($selectedNpc)))
            : $allFieldActionTypes;

        $recommendedActionType = null;
        if ($selectedNpc) {
            if ($selectedNpc->need_state !== NpcNeedState::None) {
                $recommendedActionType = $selectedNpc->need_state->recommendedAction()->value;
            } elseif ($selectedNpc->status === ReceptivityStatus::Green || $selectedNpc->hasPrayedSalvation) {
                if (! $selectedNpc->trained_411) {
                    $recommendedActionType = MinistryActionType::Train411->value;
                } elseif (! $selectedNpc->is_baptized) {
                    $recommendedActionType = MinistryActionType::CommandsOfChrist->value;
                } elseif ($selectedNpc->leadership_tier->level() === 1) {
                    $recommendedActionType = MinistryActionType::MentorTimothy->value;
                } elseif ($selectedNpc->leadership_tier->level() === 2) {
                    $recommendedActionType = MinistryActionType::IronOnIron->value;
                } else {
                    $recommendedActionType = MinistryActionType::SwarmTraining->value;
                }
            } elseif ($selectedNpc->status === ReceptivityStatus::Yellow) {
                $recommendedActionType = MinistryActionType::Share3Circles->value;
            } else {
                $recommendedActionType = MinistryActionType::CasualTalk->value;
            }
        }

        $infoActionEnum = $this->infoActionType ? MinistryActionType::tryFrom($this->infoActionType) : null;

        $commuteApCost = $this->player ? $this->player->commuteApCostForWard($selectedWard) : 0;
        $commuteMinutes = $this->player ? $this->player->commuteTransitMinutesForWard($selectedWard) : 0;
        $commuteDistanceKm = $this->player ? $this->player->distanceToWard($selectedWard) : 0.0;
        $isHomeWard = $this->player && $selectedWard && $this->player->home_ward_id === $selectedWard->id;

        $residentMissionariesByWard = User::where('current_game_session_id', $this->session?->id)
            ->whereNotNull('home_ward_id')
            ->get()
            ->groupBy('home_ward_id');

        $aiCoLaborersInWard = ($this->session?->isCoopMode && $selectedWard)
            ? User::where('current_game_session_id', $this->session?->id)
                ->where('is_ai_colaborer', true)
                ->where('home_ward_id', $selectedWard->id)
                ->get()
            : collect();

        $jointWalkStatus = $this->player
            ? app(AiColaborerService::class)->getJointPrayerWalkStatus($this->player)
            : ['allowed' => true, 'reason' => null, 'walks_today' => 0, 'walks_this_week' => 0, 'remaining_this_week' => 3];

        $todayIoIActions = $this->player
            ? QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('action_type', MinistryActionType::IronOnIron)
                ->where('game_month', $targetMonth)
                ->where('game_day', $this->player->current_game_day ?: 1)
                ->whereIn('status', [QueuedActionStatus::Pending, QueuedActionStatus::Processed])
                ->get()
            : collect();

        $totalIoIToday = $todayIoIActions->count();
        $remainingIoIToday = max(0, 3 - $totalIoIToday);

        $doneColaborerIdsToday = $todayIoIActions
            ->filter(fn ($a) => $a->notes && str_contains($a->notes, 'colaborer:'))
            ->map(function ($a) {
                if (preg_match('/colaborer:(\d+)/', (string) $a->notes, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        $doneNpcIdsToday = $todayIoIActions
            ->pluck('target_npc_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $currentDay = $this->player?->current_game_day ?? 1;
        $currentMonth = $this->player?->current_game_month ?? 1;

        // Active rhythm on current day
        $activeWorkerRhythm = $this->activeWorkerRhythm;
        $isDayBlocked = $this->isCurrentDayBlocked;

        // Recent Sabbath & Mentoring constraints
        $recentSabbath = $this->player
            ? QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::SabbathRest)
                ->where('game_day', '>=', max(1, $currentDay - 6))
                ->first()
            : null;

        $recentMentoring = $this->player
            ? QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::ReceiveMentoring)
                ->where('game_day', '>=', max(1, $currentDay - 6))
                ->first()
            : null;

        $hasFastingThisMonth = $this->player
            ? QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::FastingRetreat)
                ->exists()
            : false;

        $hasMidLevelThisMonth = $this->player
            ? QueuedMonthlyAction::where('user_id', $this->player->id)
                ->where('game_month', $currentMonth)
                ->where('action_type', MinistryActionType::MidLevelConference)
                ->exists()
            : false;

        $workerRhythmStatuses = [
            MinistryActionType::ReceiveMentoring->value => [
                'actionType' => MinistryActionType::ReceiveMentoring,
                'type' => MinistryActionType::ReceiveMentoring,
                'days' => 1,
                'duration' => 1,
                'points' => 30,
                'active' => $activeWorkerRhythm?->action_type === MinistryActionType::ReceiveMentoring,
                'eligible' => ! $isDayBlocked && ! $recentMentoring,
                'allowed' => ! $isDayBlocked && ! $recentMentoring,
                'reason' => $isDayBlocked ? 'Worker is already on a retreat/activity today.' : ($recentMentoring ? 'Mentoring can only be scheduled once per 7-day period.' : null),
            ],
            MinistryActionType::SabbathRest->value => [
                'actionType' => MinistryActionType::SabbathRest,
                'type' => MinistryActionType::SabbathRest,
                'days' => 1,
                'duration' => 1,
                'points' => 25,
                'active' => $activeWorkerRhythm?->action_type === MinistryActionType::SabbathRest,
                'eligible' => ! $isDayBlocked && ! $recentSabbath,
                'allowed' => ! $isDayBlocked && ! $recentSabbath,
                'reason' => $isDayBlocked ? 'Worker is already on a retreat/activity today.' : ($recentSabbath ? 'Weekly Sabbath can only be taken once per 7-day period.' : null),
            ],
            MinistryActionType::FastingRetreat->value => [
                'actionType' => MinistryActionType::FastingRetreat,
                'type' => MinistryActionType::FastingRetreat,
                'days' => 4,
                'duration' => 4,
                'points' => 100,
                'active' => $activeWorkerRhythm?->action_type === MinistryActionType::FastingRetreat,
                'eligible' => ! $isDayBlocked && ! $hasFastingThisMonth && ($currentDay + 3 <= 30),
                'allowed' => ! $isDayBlocked && ! $hasFastingThisMonth && ($currentDay + 3 <= 30),
                'reason' => $isDayBlocked ? 'Worker is already on a retreat/activity today.' : ($hasFastingThisMonth ? '4-Day Fasting Retreat can only be joined once per month.' : ($currentDay + 3 > 30 ? 'Not enough days remaining in Month '.$currentMonth.' (requires 4 days).' : null)),
            ],
            MinistryActionType::MidLevelConference->value => [
                'actionType' => MinistryActionType::MidLevelConference,
                'type' => MinistryActionType::MidLevelConference,
                'days' => 3,
                'duration' => 3,
                'points' => 75,
                'active' => $activeWorkerRhythm?->action_type === MinistryActionType::MidLevelConference,
                'eligible' => ! $isDayBlocked && ! $hasMidLevelThisMonth && ($currentDay + 2 <= 30),
                'allowed' => ! $isDayBlocked && ! $hasMidLevelThisMonth && ($currentDay + 2 <= 30),
                'reason' => $isDayBlocked ? 'Worker is already on a retreat/activity today.' : ($hasMidLevelThisMonth ? 'NPL Mid-Level Conference can only be joined once per month.' : ($currentDay + 2 > 30 ? 'Not enough days remaining in Month '.$currentMonth.' (requires 3 days).' : null)),
            ],
        ];

        return view('livewire.action-queue-planner', [
            'wards' => $wards,
            'selectedWard' => $selectedWard,
            'npcs' => $npcs,
            'selectedNpc' => $selectedNpc,
            'queuedActions' => $queuedActions,
            'recentOutcomes' => $recentOutcomes,
            'actionTypes' => $actionTypes,
            'allActionTypes' => $allFieldActionTypes,
            'recommendedActionType' => $recommendedActionType,
            'infoActionEnum' => $infoActionEnum,
            'commuteApCost' => $commuteApCost,
            'commuteMinutes' => $commuteMinutes,
            'commuteDistanceKm' => $commuteDistanceKm,
            'isHomeWard' => $isHomeWard,
            'residentMissionariesByWard' => $residentMissionariesByWard,
            'aiCoLaborersInWard' => $aiCoLaborersInWard,
            'jointWalkStatus' => $jointWalkStatus,
            'totalIoIToday' => $totalIoIToday,
            'remainingIoIToday' => $remainingIoIToday,
            'doneColaborerIdsToday' => $doneColaborerIdsToday,
            'doneNpcIdsToday' => $doneNpcIdsToday,
            'workerRhythmStatuses' => $workerRhythmStatuses,
            'activeWorkerRhythm' => $activeWorkerRhythm,
            'isDayBlocked' => $isDayBlocked,
            'hqSubView' => $this->hqSubView,
        ]);
    }
}
