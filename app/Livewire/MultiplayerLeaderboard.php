<?php

namespace App\Livewire;

use App\Enums\ReceptivityStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\User;
use App\Models\Ward;
use Livewire\Component;

class MultiplayerLeaderboard extends Component
{
    public string $activeTab = 'generational'; // 'generational' or 'coop'

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $session = GameSession::where('is_active', true)->first();

        // Ladder 1: Generational Depth
        $generationalLeaders = User::where('current_game_session_id', $session?->id)
            ->withCount(['ledChurches', 'discipledNpcs'])
            ->orderByDesc('total_generational_score')
            ->take(10)
            ->get();

        // Ladder 2: Co-op Synergy & Sower Assists
        $coopLeaders = User::where('current_game_session_id', $session?->id)
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
