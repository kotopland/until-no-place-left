<?php

namespace App\Livewire;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NagoyaMovementDashboard extends Component
{
    public string $currentTab = 'planner'; // 'planner', 'churches', 'leaderboard', 'map'

    public ?int $activeChurchId = null;

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
