<?php

namespace App\Livewire;

use App\Enums\ReceptivityStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\User;
use App\Models\Ward;
use App\Services\ShowcaseSandboxService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MultiplayerLeaderboard extends Component
{
    public string $activeTab = 'generational'; // 'generational' or 'coop'

    public bool $isCoopMode = true;

    public function boot(): void
    {
        app(ShowcaseSandboxService::class)->syncConnectionFromSession();
    }

    public function mount(): void
    {
        $session = GameSession::where('is_active', true)->first();
        $this->isCoopMode = $session?->isCoopMode ?? true;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['generational', 'coop'], true) ? $tab : 'generational';
    }

    public function render(): View
    {
        $session = GameSession::where('is_active', true)->first();
        $isCoop = $session?->isCoopMode ?? true;

        $hasHumanPlayers = User::where('current_game_session_id', $session?->id)
            ->where('is_ai_colaborer', false)
            ->exists();

        // Ladder 1: Generational Depth
        $generationalQuery = User::where('current_game_session_id', $session?->id)
            ->withCount(['ledChurches', 'discipledNpcs']);

        if (! $isCoop) {
            if ($hasHumanPlayers) {
                $generationalQuery->where('is_ai_colaborer', false);
            } else {
                $generationalQuery->where('email', 'kenji.yamada@nagoyaharvest.jp');
            }
        }

        $generationalLeaders = $generationalQuery
            ->orderByDesc('total_generational_score')
            ->take(10)
            ->get();

        // Ladder 2: Co-op Synergy & Sower Assists
        $coopQuery = User::where('current_game_session_id', $session?->id);

        if (! $isCoop) {
            if ($hasHumanPlayers) {
                $coopQuery->where('is_ai_colaborer', false);
            } else {
                $coopQuery->where('email', 'kenji.yamada@nagoyaharvest.jp');
            }
        }

        $coopLeaders = $coopQuery
            ->orderByDesc('coop_synergy_score')
            ->take(10)
            ->get();

        // City-Wide Nagoya Saturation Metrics
        $totalWards = Ward::count();
        $reachedWardsCount = Church::where('game_session_id', $session?->id)
            ->distinct('ward_id')
            ->count('ward_id');

        $totalBelievers = Npc::where('game_session_id', $session?->id)
            ->where('status', ReceptivityStatus::Green)
            ->count();

        $totalChurches = Church::where('game_session_id', $session?->id)->count();
        $deepestGeneration = Church::where('game_session_id', $session?->id)->max('generation') ?? 1;

        $citySaturationPercent = $totalWards > 0
            ? (int) round(($reachedWardsCount / $totalWards) * 100)
            : 0;

        // Recent Multiplayer Activity Feed
        $recentActivity = NpcContribution::where('game_session_id', $session?->id)
            ->with(['user', 'npc'])
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.multiplayer-leaderboard', [
            'session' => $session,
            'generationalLeaders' => $generationalLeaders,
            'coopLeaders' => $coopLeaders,
            'totalWards' => $totalWards,
            'reachedWardsCount' => $reachedWardsCount,
            'totalBelievers' => $totalBelievers,
            'totalChurches' => $totalChurches,
            'deepestGeneration' => $deepestGeneration,
            'citySaturationPercent' => $citySaturationPercent,
            'recentActivity' => $recentActivity,
        ]);
    }
}
