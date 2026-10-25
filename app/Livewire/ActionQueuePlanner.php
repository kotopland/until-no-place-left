<?php

namespace App\Livewire;

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ActionQueuePlanner extends Component
{
    public ?int $selectedWardId = null;

    public ?int $selectedNpcId = null;

    public string $selectedActionType = 'casual_talk';

    public ?string $infoActionType = null;

    public bool $showActionInfoModal = false;

    public string $notes = '';

    public bool $filterEligibleOnly = false;

    public int $mobileStep = 2;

    public ?User $player = null;

    public ?GameSession $session = null;

    /** @var array<string, string> */
    protected $listeners = [
        'day-advanced' => '$refresh',
        'action-queued' => '$refresh',
    ];

    public function mount(): void
    {
        $this->player = auth()->user() ?? User::first();
        $this->session = $this->player ? ($this->player->currentGameSession ?? GameSession::first()) : GameSession::first();

        // Mark player active (online)
        $this->player?->touchActive();

        // Default to player's home ward, or Naka-ku
        $defaultWard = $this->player?->homeWard ?? Ward::where('slug', 'naka-ku')->first() ?? Ward::first();
        if ($this->player && ! $this->player->home_ward_id && $defaultWard) {
            $this->player->update(['home_ward_id' => $defaultWard->id]);
            $this->player->refresh();
        }
        $this->selectedWardId = $defaultWard?->id;
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
    }

    public function touchHeartbeat(): void
    {
        $this->player?->touchActive();
    }

    public function selectWard(int $wardId): void
    {
        $this->selectedWardId = $wardId;
        $this->selectedNpcId = null;
        $this->mobileStep = 2;
    }

    public function toggleFilterEligibleOnly(): void
    {
        $this->filterEligibleOnly = ! $this->filterEligibleOnly;
    }

    public function selectNpc(int $npcId): void
    {
        $this->selectedNpcId = $npcId;
        $npc = Npc::find($npcId);
        if ($npc) {
            // Smartly select recommended action based on 5 Levels of Leadership & spiritual readiness
            if ($npc->need_state !== \App\Enums\NpcNeedState::None) {
                $this->selectedActionType = $npc->need_state->recommendedAction()->value;
            } elseif ($npc->status === \App\Enums\ReceptivityStatus::Green || $npc->hasPrayedSalvation) {
                if (! $npc->trained_411) {
                    $this->selectedActionType = MinistryActionType::Train411->value;
                } elseif (! $npc->is_baptized) {
                    $this->selectedActionType = MinistryActionType::CommandsOfChrist->value;
                } elseif ($npc->leadership_tier->level() === 1) {
                    $this->selectedActionType = MinistryActionType::MentorTimothy->value;
                } elseif ($npc->leadership_tier->level() === 2) {
                    $this->selectedActionType = MinistryActionType::IronOnIron->value;
                } else {
                    $this->selectedActionType = MinistryActionType::SwarmTraining->value;
                }
            } elseif ($npc->status === \App\Enums\ReceptivityStatus::Yellow) {
                $this->selectedActionType = MinistryActionType::Share3Circles->value;
            } else {
                $this->selectedActionType = MinistryActionType::CasualTalk->value;
            }
        }
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

    public function queueAction(): void
    {
        if (! $this->player || ! $this->session) {
            return;
        }

        $actionEnum = MinistryActionType::tryFrom($this->selectedActionType) ?? MinistryActionType::CasualTalk;
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

        // Deduct player AP (including transit commute surcharge)
        $this->player->decrement('action_points', $totalApCost);

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
            'game_month' => $this->player->current_game_month,
            'game_day' => $this->player->current_game_day,
            'ap_cost' => $totalApCost,
            'notes' => $this->notes,
        ]);

        $this->notes = '';
        $this->dispatch('action-queued');
        $commuteMsg = $commuteApCost > 0 ? " (includes +{$commuteApCost} AP transit)" : '';
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Successfully queued {$actionEnum->label()} for Day {$this->player->current_game_day} / Month {$this->player->current_game_month}! ({$totalApCost} AP consumed{$commuteMsg})",
        ]);
    }

    public function cancelAction(int $actionId): void
    {
        $action = QueuedMonthlyAction::where('id', $actionId)
            ->where('user_id', $this->player->id)
            ->where('status', QueuedActionStatus::Pending)
            ->first();

        if ($action) {
            // Refund AP
            $this->player->increment('action_points', $action->ap_cost);
            $action->delete();

            $this->dispatch('notify', [
                'type' => 'info',
                'message' => "Action cancelled. {$action->ap_cost} AP refunded.",
            ]);
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

        $targetMonth = $this->player ? $this->player->current_game_month : 1;

        $queuedActions = QueuedMonthlyAction::where('game_session_id', $this->session?->id)
            ->where('user_id', $this->player?->id)
            ->where('game_month', $targetMonth)
            ->where('status', QueuedActionStatus::Pending)
            ->with(['targetNpc', 'ward'])
            ->latest()
            ->get();

        $recentOutcomes = QueuedMonthlyAction::where('game_session_id', $this->session?->id)
            ->where('user_id', $this->player?->id)
            ->where('status', QueuedActionStatus::Processed)
            ->whereNotNull('outcome_notes')
            ->with(['targetNpc', 'ward'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $allActionTypes = MinistryActionType::cases();
        $actionTypes = ($this->filterEligibleOnly && $selectedNpc)
            ? array_values(array_filter($allActionTypes, fn (MinistryActionType $action) => $action->isEligibleFor($selectedNpc)))
            : $allActionTypes;

        $infoActionEnum = $this->infoActionType ? MinistryActionType::tryFrom($this->infoActionType) : null;

        $commuteApCost = $this->player ? $this->player->commuteApCostForWard($selectedWard) : 0;
        $commuteMinutes = $this->player ? $this->player->commuteTransitMinutesForWard($selectedWard) : 0;
        $commuteDistanceKm = $this->player ? $this->player->distanceToWard($selectedWard) : 0.0;
        $isHomeWard = $this->player && $selectedWard && $this->player->home_ward_id === $selectedWard->id;

        $residentMissionariesByWard = User::where('current_game_session_id', $this->session?->id)
            ->whereNotNull('home_ward_id')
            ->get()
            ->groupBy('home_ward_id');

        return view('livewire.action-queue-planner', [
            'wards' => $wards,
            'selectedWard' => $selectedWard,
            'npcs' => $npcs,
            'selectedNpc' => $selectedNpc,
            'queuedActions' => $queuedActions,
            'recentOutcomes' => $recentOutcomes,
            'actionTypes' => $actionTypes,
            'allActionTypes' => $allActionTypes,
            'infoActionEnum' => $infoActionEnum,
            'commuteApCost' => $commuteApCost,
            'commuteMinutes' => $commuteMinutes,
            'commuteDistanceKm' => $commuteDistanceKm,
            'isHomeWard' => $isHomeWard,
            'residentMissionariesByWard' => $residentMissionariesByWard,
        ]);
    }
}
