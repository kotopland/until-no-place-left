<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ChurchElement;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class ChurchGenerationMap extends Component
{
    public ?int $selectedChurchId = null;

    public string $viewMode = 'tree'; // 'tree' or 'tiers'

    public string $filterStatus = 'all'; // 'all', 'active', 'dissolved'

    public string $searchQuery = '';

    public float $zoomLevel = 1.0;

    public bool $showInspectorDrawer = true;

    /** @var array<string, string> */
    protected $listeners = [
        'church-selected' => 'selectChurch',
        'day-advanced' => '$refresh',
        'church-revitalized' => '$refresh',
    ];

    public function mount(?int $initialChurchId = null): void
    {
        if ($initialChurchId) {
            $this->selectedChurchId = $initialChurchId;
        } else {
            $firstChurch = Church::where('is_dissolved', false)->orderBy('generation')->first()
                ?? Church::orderBy('generation')->first();
            $this->selectedChurchId = $firstChurch?->id;
        }
    }

    public function selectChurch(int $churchId): void
    {
        $this->selectedChurchId = $churchId;
        $this->showInspectorDrawer = true;
    }

    public function clearSelection(): void
    {
        $this->selectedChurchId = null;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['tree', 'tiers'], true) ? $mode : 'tree';
    }

    public function setFilter(string $filter): void
    {
        $this->filterStatus = $filter;
    }

    public function zoomIn(): void
    {
        $this->zoomLevel = min(1.6, round($this->zoomLevel + 0.15, 2));
    }

    public function zoomOut(): void
    {
        $this->zoomLevel = max(0.65, round($this->zoomLevel - 0.15, 2));
    }

    public function resetZoom(): void
    {
        $this->zoomLevel = 1.0;
    }

    public function toggleDrawer(): void
    {
        $this->showInspectorDrawer = ! $this->showInspectorDrawer;
    }

    /**
     * Revitalizes a dissolved church directly from the Gen Map.
     */
    public function revitalizeChurch(int $churchId): void
    {
        $user = auth()->user() ?? User::first();
        $church = Church::find($churchId);

        if (! $church || ! $church->is_dissolved) {
            return;
        }

        $gameMonth = $user?->current_game_month ?? 1;
        $church->revitalize($gameMonth, $user);

        // If there's an NPC leader, update them back to active church planter
        if ($church->leader_npc_id) {
            Npc::where('id', $church->leader_npc_id)->update([
                'church_id' => $church->id,
                'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Hallelujah! {$church->name} has been revitalized and restored to active 3/3rds fellowship in {$church->ward?->name}!",
        ]);

        $this->dispatch('church-revitalized');
    }

    /**
     * Queues Church Circles pastoral coaching for this church.
     */
    public function queueCoachingForChurch(int $churchId): void
    {
        $user = auth()->user() ?? User::first();
        $church = Church::with(['leaderNpc', 'ward'])->find($churchId);

        if (! $user || ! $church) {
            return;
        }

        if ($user->is_burned_out) {
            $monthsLeft = $user->burnoutMonthsRemaining($user->current_game_month);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "🚨 BURNOUT LEAVE: You collapsed from severe ministry overwork without rest. You are on mandatory medical leave for {$monthsLeft} more month(s). Advance months to complete your sabbatical recovery.",
            ]);

            return;
        }

        $leaderNpc = $church->leaderNpc ?? Npc::where('ward_id', $church->ward_id)->where('status', 'green')->first();

        if (! $leaderNpc) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'No local leader available in this ward for Church Circles coaching.',
            ]);

            return;
        }

        // Enforce once-per-week coaching limit (minimum 7 days between sessions)
        $eligibility = $church->coachingEligibility($user->current_game_month, $user->current_game_day);
        if (! $eligibility['allowed']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => $eligibility['reason'],
            ]);

            return;
        }

        $baseApCost = MinistryActionType::ChurchCircles->apCost();
        $commuteApCost = $user->commuteApCostForWard($church->ward);
        $totalApCost = $baseApCost + $commuteApCost;

        $user->refresh();
        if ($user->action_points < $totalApCost) {
            $commuteMsg = $commuteApCost > 0 ? " ({$baseApCost} AP base + {$commuteApCost} AP transit commute)" : '';
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Insufficient Action Points! You need {$totalApCost} AP{$commuteMsg}, but have only {$user->action_points} AP remaining today.",
            ]);

            return;
        }

        $user->decrement('action_points', $totalApCost);
        $user->refresh();

        QueuedMonthlyAction::create([
            'user_id' => $user->id,
            'ward_id' => $church->ward_id,
            'game_session_id' => $user->current_game_session_id ?? $church->game_session_id,
            'game_month' => $user->current_game_month,
            'game_day' => $user->current_game_day,
            'action_type' => MinistryActionType::ChurchCircles,
            'target_npc_id' => $leaderNpc->id,
            'ap_cost' => $totalApCost,
            'status' => 'pending',
            'notes' => "Coaching {$church->name} (Gen {$church->generation}) in 3/3rds health and Acts 2 functions",
        ]);

        $commuteDetail = $commuteApCost > 0 ? " ({$totalApCost} AP with transit)" : " ({$totalApCost} AP)";
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Queued Church Circles coaching for {$church->name} on Day {$user->current_game_day}!{$commuteDetail}",
        ]);

        $this->dispatch('action-queued');
    }

    /**
     * Compute node positions and connection edges for the Generation Map.
     *
     * @param  Collection<int, Church>  $churches
     * @return array{
     *     nodes: array<int, array<string, mixed>>,
     *     edges: array<int, array<string, mixed>>,
     *     totalWidth: int,
     *     totalHeight: int
     * }
     */
    protected function computeTreeLayout(Collection $churches): array
    {
        if ($churches->isEmpty()) {
            return [
                'nodes' => [],
                'edges' => [],
                'totalWidth' => 800,
                'totalHeight' => 500,
            ];
        }

        // Group by generation tiers
        $byGen = $churches->groupBy(fn (Church $c) => min(4, $c->generation));
        $maxGen = max($churches->pluck('generation')->all() ?: [1]);

        $nodes = [];
        $nodePositions = []; // church_id => [x, y]
        $edges = [];

        $colWidth = 240;
        $rowHeight = 270;
        $topMargin = 70;
        $leftMargin = 120;

        if ($this->viewMode === 'tiers') {
            // Horizontal Generational Columns: Gen 1 | Gen 2 | Gen 3 | Gen 4+
            $maxRowInCol = 1;
            foreach ($byGen as $gen => $list) {
                $maxRowInCol = max($maxRowInCol, $list->count());
            }

            $totalWidth = max(900, ($maxGen * 260) + 160);
            $totalHeight = max(550, ($maxRowInCol * $rowHeight) + 120);

            foreach ($byGen as $gen => $list) {
                $genInt = (int) $gen;
                $colX = $leftMargin + (($genInt - 1) * 260);

                foreach ($list->values() as $index => $church) {
                    $posY = $topMargin + ($index * $rowHeight) + 50;
                    $nodePositions[$church->id] = ['x' => $colX, 'y' => $posY];

                    $nodes[$church->id] = [
                        'church' => $church,
                        'x' => $colX,
                        'y' => $posY,
                    ];
                }
            }
        } else {
            // Hierarchical Vertical Tree: Gen 1 on top, branching downwards
            // Calculate column positions per tier
            $genTiers = [1, 2, 3, 4];
            $tierCounts = [];
            foreach ($genTiers as $g) {
                $tierCounts[$g] = ($byGen->get($g) ?? collect())->count();
            }

            $maxNodesInTier = max(array_values($tierCounts) ?: [1]);
            $totalWidth = max(950, ($maxNodesInTier * 260) + 200);
            $totalHeight = max(650, (count($byGen) * $rowHeight) + 140);

            foreach ($genTiers as $g) {
                $churchesInTier = ($byGen->get($g) ?? collect())->values();
                $count = $churchesInTier->count();
                if ($count === 0) {
                    continue;
                }

                $tierY = $topMargin + (($g - 1) * $rowHeight);
                $spacing = $totalWidth / ($count + 1);

                foreach ($churchesInTier as $index => $church) {
                    $posX = (int) round($spacing * ($index + 1));
                    $nodePositions[$church->id] = ['x' => $posX, 'y' => $tierY];

                    $nodes[$church->id] = [
                        'church' => $church,
                        'x' => $posX,
                        'y' => $tierY,
                    ];
                }
            }
        }

        // Generate connecting edges (parent church -> daughter church)
        foreach ($churches as $church) {
            if ($church->parent_church_id && isset($nodePositions[$church->parent_church_id], $nodePositions[$church->id])) {
                $from = $nodePositions[$church->parent_church_id];
                $to = $nodePositions[$church->id];

                $parent = $churches->firstWhere('id', $church->parent_church_id);
                $isDissolvedEdge = $church->is_dissolved || ($parent && $parent->is_dissolved);

                $edges[] = [
                    'from_id' => $church->parent_church_id,
                    'to_id' => $church->id,
                    'from_x' => $from['x'],
                    'from_y' => $from['y'] + 50, // Bottom of mother circle
                    'to_x' => $to['x'],
                    'to_y' => $to['y'] - 55, // Top of daughter circle
                    'is_dissolved' => $isDissolvedEdge,
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'totalWidth' => $totalWidth,
            'totalHeight' => $totalHeight,
        ];
    }

    public function render(): View
    {
        $user = auth()->user() ?? User::first();
        $session = $user ? ($user->currentGameSession ?? GameSession::first()) : GameSession::first();

        $query = Church::where('game_session_id', $session?->id)
            ->with(['ward', 'leaderNpc', 'leaderUser', 'parentChurch', 'daughterChurches']);

        if ($this->filterStatus === 'active') {
            $query->where('is_dissolved', false);
        } elseif ($this->filterStatus === 'dissolved') {
            $query->where('is_dissolved', true);
        }

        if (! empty($this->searchQuery)) {
            $term = '%'.trim($this->searchQuery).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('started_by_name', 'like', $term)
                    ->orWhereHas('ward', fn ($wq) => $wq->where('name', 'like', $term)->orWhere('name_ja', 'like', $term))
                    ->orWhereHas('leaderNpc', fn ($lq) => $lq->where('name', 'like', $term)->orWhere('name_ja', 'like', $term));
            });
        }

        $allChurches = $query->orderBy('generation')->orderBy('id')->get();

        // Selected church model
        $selectedChurch = $this->selectedChurchId
            ? Church::with(['ward', 'leaderNpc', 'leaderUser', 'parentChurch', 'daughterChurches', 'members'])->find($this->selectedChurchId)
            : $allChurches->first();

        // Generational metrics
        $allSessionChurches = Church::where('game_session_id', $session?->id)->get();
        $deepestGeneration = $allSessionChurches->isNotEmpty() ? $allSessionChurches->max('generation') : 1;
        $hasReachedG4Milestone = $deepestGeneration >= 4;

        $activeChurchesCount = $allSessionChurches->where('is_dissolved', false)->count();
        $dissolvedChurchesCount = $allSessionChurches->where('is_dissolved', true)->count();
        $totalAttenders = $allSessionChurches->where('is_dissolved', false)->sum('attenders_count');
        $totalBelievers = $allSessionChurches->where('is_dissolved', false)->sum('believers_count');
        $totalBaptized = $allSessionChurches->where('is_dissolved', false)->sum('baptized_count');

        $layoutData = $this->computeTreeLayout($allChurches);

        // Core 11 elements catalog
        $allElements = [
            ChurchElement::RepentanceBelief,
            ChurchElement::Baptism,
            ChurchElement::HolySpirit,
            ChurchElement::Word,
            ChurchElement::Communion,
            ChurchElement::Prayer,
            ChurchElement::Fellowship,
            ChurchElement::GivingMercy,
            ChurchElement::Worship,
            ChurchElement::LocalLeaders,
            ChurchElement::MakingDisciples,
        ];

        return view('livewire.church-generation-map', [
            'churches' => $allChurches,
            'selectedChurch' => $selectedChurch,
            'nodes' => $layoutData['nodes'],
            'edges' => $layoutData['edges'],
            'canvasWidth' => $layoutData['totalWidth'],
            'canvasHeight' => $layoutData['totalHeight'],
            'deepestGeneration' => $deepestGeneration,
            'hasReachedG4Milestone' => $hasReachedG4Milestone,
            'activeChurchesCount' => $activeChurchesCount,
            'dissolvedChurchesCount' => $dissolvedChurchesCount,
            'totalAttenders' => $totalAttenders,
            'totalBelievers' => $totalBelievers,
            'totalBaptized' => $totalBaptized,
            'allElements' => $allElements,
            'user' => $user,
        ]);
    }
}
