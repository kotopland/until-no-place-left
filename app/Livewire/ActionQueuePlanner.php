<?php

namespace App\Livewire;

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use Livewire\Component;

class ActionQueuePlanner extends Component
{
    public ?int $selectedWardId = null;

    public ?int $selectedNpcId = null;

    public string $selectedActionType = 'casual_talk';

    public ?string $infoActionType = null;

    public bool $showActionInfoModal = false;

    public string $notes = '';

    public ?User $player = null;

    public ?GameSession $session = null;

    protected $listeners = [
        'day-advanced' => '$refresh',
        'action-queued' => '$refresh',
    ];

    public function mount(): void
    {
        $this->player = auth()->user() ?? User::first();
        $this->session = $this->player?->currentGameSession ?? GameSession::first();

        // Mark player active (online)
        $this->player?->touchActive();

        // Default to Naka-ku or first ward
        $defaultWard = Ward::where('slug', 'naka-ku')->first() ?? Ward::first();
        $this->selectedWardId = $defaultWard?->id;
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
    }

    public function selectNpc(int $npcId): void
    {
        $this->selectedNpcId = $npcId;
        $npc = Npc::find($npcId);
        if ($npc) {
            // Pre-select recommended action based on NPC need state
            $this->selectedActionType = $npc->need_state->recommendedAction()->value;
        }
    }

    public function queueAction(): void
    {
        if (! $this->player || ! $this->session) {
            return;
        }

        $actionEnum = MinistryActionType::tryFrom($this->selectedActionType) ?? MinistryActionType::CasualTalk;
        $apCost = $actionEnum->apCost();

        // Refresh player state
        $this->player->refresh();

        if ($this->player->action_points < $apCost) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Insufficient Action Points! You have {$this->player->action_points} AP, but {$actionEnum->label()} requires {$apCost} AP.",
            ]);

            return;
        }

        if ($actionEnum->requiresTargetNpc() && ! $this->selectedNpcId) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select a resident NPC in the ward before scheduling this action.',
            ]);

            return;
        }

        // Deduct player AP
        $this->player->decrement('action_points', $apCost);

        // Queue action for the daily/monthly resolution tick
        QueuedMonthlyAction::create([
            'game_session_id' => $this->session->id,
            'user_id' => $this->player->id,
            'ward_id' => $this->selectedWardId,
            'target_npc_id' => $actionEnum->requiresTargetNpc() ? $this->selectedNpcId : null,
            'action_type' => $actionEnum,
            'status' => QueuedActionStatus::Pending,
            'game_month' => $this->session->current_game_month,
            'game_day' => $this->session->current_game_day,
            'ap_cost' => $apCost,
            'notes' => $this->notes,
        ]);

        $this->notes = '';
        $this->dispatch('action-queued');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Successfully queued {$actionEnum->label()} for Day {$this->session->current_game_day} / Month {$this->session->current_game_month}! ({$apCost} AP consumed)",
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
     * Advance to the next game day tick (manual turn or auto-tick every ~10 min).
     * Enforces the 30-day limit per real-life day.
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
        if ($this->session->days_processed_this_month >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Month {$this->session->current_game_month} has completed all 30 days! The full monthly multiplication report will resolve tonight at midnight.",
                ]);
            }

            return;
        }

        $engine = app(\App\Services\FourFieldsEngine::class);
        $result = $engine->resolveDailyTick($this->session, force: true);

        $this->player->refresh();
        $this->session->refresh();

        $this->dispatch('day-advanced');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Advanced to Day {$this->session->current_game_day} / 30! Processed {$result?->actionsProcessed} queued action(s). Bandwidth replenished to {$this->player->action_points} AP.",
        ]);
    }

    public function render()
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

        $queuedActions = QueuedMonthlyAction::where('game_session_id', $this->session?->id)
            ->where('user_id', $this->player?->id)
            ->where('game_month', $this->session?->current_game_month)
            ->where('status', QueuedActionStatus::Pending)
            ->with(['targetNpc', 'ward'])
            ->latest()
            ->get();

        $actionTypes = MinistryActionType::cases();
        $infoActionEnum = $this->infoActionType ? MinistryActionType::tryFrom($this->infoActionType) : null;

        return view('livewire.action-queue-planner', [
            'wards' => $wards,
            'selectedWard' => $selectedWard,
            'npcs' => $npcs,
            'selectedNpc' => $selectedNpc,
            'queuedActions' => $queuedActions,
            'actionTypes' => $actionTypes,
            'infoActionEnum' => $infoActionEnum,
        ]);
    }
}
