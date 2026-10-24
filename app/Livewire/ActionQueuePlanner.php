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

    public string $notes = '';

    public ?User $player = null;

    public ?GameSession $session = null;

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

        return view('livewire.action-queue-planner', [
            'wards' => $wards,
            'selectedWard' => $selectedWard,
            'npcs' => $npcs,
            'selectedNpc' => $selectedNpc,
            'queuedActions' => $queuedActions,
            'actionTypes' => $actionTypes,
        ]);
    }
}
