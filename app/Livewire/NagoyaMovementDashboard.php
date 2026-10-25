<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class NagoyaMovementDashboard extends Component
{
    #[Url(as: 'tab')]
    public string $currentTab = 'planner'; // 'planner', 'churches', 'leaderboard', 'map'

    public ?int $activeChurchId = null;

    public bool $showEditNameModal = false;

    public string $editingName = '';

    public function setTab(string $tab): void
    {
        $this->currentTab = $tab;
    }

    public function selectChurch(int $churchId): void
    {
        $this->activeChurchId = $churchId;
    }

    public function openReportModal(): void
    {
        $this->dispatch('open-month-review');
    }

    public function openEditNameModal(): void
    {
        $this->editingName = auth()->user()?->name ?? '';
        $this->showEditNameModal = true;
    }

    public function closeEditNameModal(): void
    {
        $this->showEditNameModal = false;
    }

    public function savePlayerName(): void
    {
        $this->validate([
            'editingName' => ['required', 'string', 'min:2', 'max:50'],
        ], [
            'editingName.required' => 'Please enter a missionary player name.',
            'editingName.min' => 'Player name must be at least 2 characters long.',
        ]);

        $user = auth()->user();
        if ($user) {
            $user->update(['name' => trim($this->editingName)]);
            $this->showEditNameModal = false;
            $this->dispatch('notify', message: "Player name updated to '{$user->name}'!", type: 'success');
        }
    }

    protected $listeners = [
        'day-advanced' => '$refresh',
        'action-queued' => '$refresh',
    ];

    /**
     * Advance to the next game day tick.
     */
    public function advanceDay(bool $isManual = true): void
    {
        $user = auth()->user() ?? User::first();
        $session = $user?->currentGameSession ?? GameSession::first();

        if (! $user || ! $session) {
            return;
        }

        $user->refresh();
        $session->refresh();

        if ($user->days_played_today >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Daily play limit reached: You have completed all 30 game days for today. The new month will begin tonight at midnight!',
                ]);
            }

            return;
        }

        if ($session->days_processed_this_month >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Month {$session->current_game_month} has completed all 30 days! The full monthly multiplication report will resolve tonight at midnight.",
                ]);
            }

            return;
        }

        $engine = app(\App\Services\FourFieldsEngine::class);
        $result = $engine->resolveDailyTick($session, force: true);

        $user->refresh();
        $session->refresh();

        $this->dispatch('day-advanced');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Advanced to Day {$session->current_game_day} / 30! Processed {$result?->actionsProcessed} queued action(s). Bandwidth replenished to {$user->action_points} AP.",
        ]);
    }

    public function render()
    {
        $user = auth()->user() ?? User::first();
        $user?->touchActive();
        $session = $user?->currentGameSession ?? GameSession::first();

        $unreadReportsCount = MonthlyReport::where('user_id', $user?->id)
            ->whereNull('read_at')
            ->count();

        $churches = Church::where('game_session_id', $session?->id)
            ->with(['ward', 'leaderNpc', 'leaderUser'])
            ->orderBy('generation')
            ->get();

        if (! $this->activeChurchId && $churches->isNotEmpty()) {
            $this->activeChurchId = $churches->first()->id;
        }

        $activeChurch = $this->activeChurchId ? Church::with(['ward', 'leaderNpc', 'leaderUser'])->find($this->activeChurchId) : $churches->first();

        return view('livewire.nagoya-movement-dashboard', [
            'user' => $user,
            'session' => $session,
            'unreadReportsCount' => $unreadReportsCount,
            'churches' => $churches,
            'activeChurch' => $activeChurch,
        ]);
    }
}
