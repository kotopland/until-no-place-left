<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\FourFieldsEngine;
use Illuminate\Contracts\View\View;
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
        $this->editingName = auth()->user() ? auth()->user()->name : '';
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

    /** @var array<string, string> */
    protected $listeners = [
        'day-advanced' => '$refresh',
        'action-queued' => '$refresh',
    ];

    /**
     * Advance to the next game day tick for the current player.
     * Does NOT change the day for other players in the session.
     */
    public function advanceDay(bool $isManual = true): void
    {
        $user = auth()->user() ?? User::first();
        $session = $user ? ($user->currentGameSession ?? GameSession::first()) : GameSession::first();

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

        if ($user->days_processed_this_month >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Month {$user->current_game_month} has completed all 30 days! You can now advance to Month ".($user->current_game_month + 1).'.',
                ]);
            }

            return;
        }

        $engine = app(FourFieldsEngine::class);
        $result = $engine->resolveDailyTick($session, specificUser: $user, force: true);

        $user->refresh();
        $session->refresh();

        $this->dispatch('day-advanced');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Advanced to Day {$user->current_game_day} / 30! Processed {$result?->actionsProcessed} queued action(s). Bandwidth replenished to {$user->action_points} AP.",
        ]);
    }

    /**
     * Advance to the next game month when all 30 days have been completed.
     */
    public function advanceMonth(): void
    {
        $user = auth()->user() ?? User::first();
        $session = $user ? ($user->currentGameSession ?? GameSession::first()) : GameSession::first();

        if (! $user || ! $session) {
            return;
        }

        $user->refresh();

        if ($user->days_processed_this_month < 30) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'You must complete all 30 days before advancing to Month '.($user->current_game_month + 1)." ({$user->days_processed_this_month}/30 days completed).",
            ]);

            return;
        }

        $engine = app(FourFieldsEngine::class);
        $result = $engine->resolveGameMonthForUser($user);

        $user->refresh();

        $this->dispatch('day-advanced');
        $this->dispatch('open-month-review');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Welcome to Month {$user->current_game_month}! AP replenished to {$user->action_points} and monthly multiplication report generated.",
        ]);
    }

    public function render(): View
    {
        $user = auth()->user() ?? User::first();
        $user?->touchActive();
        $session = $user ? ($user->currentGameSession ?? GameSession::first()) : GameSession::first();

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
