<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use App\Services\MissionaryScreeningService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class NagoyaMovementDashboard extends Component
{
    #[Url(as: 'tab')]
    public string $currentTab = 'planner'; // 'planner', 'churches', 'leaderboard', 'map'

    public string $circlesSubView = 'genmap'; // 'genmap' or 'inspector'

    public ?int $activeChurchId = null;

    public bool $showEditNameModal = false;

    public string $editingName = '';

    public string $editingNationality = 'Japanese' {
        set(string|array $value) => is_array($value) ? ($value['nationality'] ?? reset($value) ?? 'Japanese') : (string) $value;
    }

    public int $editingAge = 30;

    public bool $showOnboardingModal = false;

    public string $onboardingStage = 'screening'; // 'screening', 'gated_coaching', 'qualified_clearance', 'ward_selection'

    /** @var array<string, string> */
    public array $screeningAnswers = [];

    /** @var array<string, string> */
    public array $screeningCoaching = [];

    /** @var array<string, int> */
    public array $screeningDimensionScores = [];

    public int $screeningTotalScore = 0;

    public string $screeningProfileTitle = '';

    /** @var array<string> */
    public array $screeningCriticalGaps = [];

    public bool $showScreeningScorecardModal = false;

    public ?int $selectedHomeWardId = null;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user) {
            $this->editingName = $user->name;
            $this->editingNationality = $user->nationality ?? 'Japanese';
            $this->editingAge = (int) ($user->age ?? 30);
            if ($user->screening_answers) {
                $this->screeningAnswers = $user->screening_answers;
            }
            if ($user->screening_dimension_scores) {
                $this->screeningDimensionScores = $user->screening_dimension_scores;
            }
            if ($user->screening_score !== null) {
                $this->screeningTotalScore = (int) $user->screening_score;
            }
            if ($user->screening_profile_title) {
                $this->screeningProfileTitle = $user->screening_profile_title;
            }

            if (! $user->screening_passed) {
                $this->showOnboardingModal = true;
                $this->onboardingStage = 'screening';
            } elseif (! $user->home_ward_id) {
                $this->showOnboardingModal = true;
                $this->onboardingStage = 'ward_selection';
            }
        }
    }

    public function setTab(string $tab): void
    {
        $this->currentTab = $tab;
    }

    public function setCirclesSubView(string $subView): void
    {
        $this->circlesSubView = in_array($subView, ['genmap', 'inspector'], true) ? $subView : 'genmap';
    }

    public function openSpiritualRhythms(): void
    {
        $this->currentTab = 'planner';
        $this->dispatch('set-hq-subview', view: 'rhythms');
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
        $user = auth()->user();
        $this->editingName = $user ? $user->name : '';
        $this->editingNationality = $user?->nationality ?? 'Japanese';
        $this->editingAge = (int) ($user?->age ?? 30);
        $this->showEditNameModal = true;
    }

    public function closeEditNameModal(): void
    {
        $this->showEditNameModal = false;
    }

    public function openOnboardingModal(): void
    {
        $user = auth()->user();
        if ($user && ! $user->screening_passed) {
            $this->onboardingStage = 'screening';
        } else {
            $this->onboardingStage = 'ward_selection';
        }
        $this->showOnboardingModal = true;
    }

    public function closeOnboardingModal(): void
    {
        $user = auth()->user();
        if ($user && $user->screening_passed && $user->home_ward_id) {
            $this->showOnboardingModal = false;
        }
    }

    public function openScreeningScorecard(): void
    {
        $user = auth()->user();
        if ($user) {
            if ($user->screening_dimension_scores) {
                $this->screeningDimensionScores = $user->screening_dimension_scores;
            }
            if ($user->screening_score !== null) {
                $this->screeningTotalScore = (int) $user->screening_score;
            }
            if ($user->screening_profile_title) {
                $this->screeningProfileTitle = $user->screening_profile_title;
            }
            if ($user->screening_answers) {
                $this->screeningAnswers = $user->screening_answers;
            }
        }
        $this->showScreeningScorecardModal = true;
    }

    public function closeScreeningScorecard(): void
    {
        $this->showScreeningScorecardModal = false;
    }

    public function recordScreeningAnswer(string $questionId, string $choiceKey): void
    {
        $this->screeningAnswers[$questionId] = $choiceKey;
    }

    public function submitScreening(): void
    {
        $user = auth()->user() ?? User::first();
        $screeningService = app(MissionaryScreeningService::class);
        $questions = $screeningService->getQuestions();

        if (count($this->screeningAnswers) < count($questions)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please answer all '.count($questions).' scenario questions across the 7 dimensions before submitting.',
            ]);

            return;
        }

        $result = $screeningService->evaluate($this->screeningAnswers);

        $this->screeningTotalScore = $result['total_score'];
        $this->screeningDimensionScores = $result['dimension_scores'];
        $this->screeningProfileTitle = $result['profile_title'];
        $this->screeningCoaching = $result['coaching_feedback'];
        $this->screeningCriticalGaps = $result['critical_gaps'];

        if ($user) {
            $user->update([
                'screening_passed' => $result['passed'],
                'screening_completed_at' => $result['passed'] ? now() : null,
                'screening_score' => $result['total_score'],
                'screening_dimension_scores' => $result['dimension_scores'],
                'screening_profile_title' => $result['profile_title'],
                'screening_answers' => $this->screeningAnswers,
            ]);
            $user->refresh();
        }

        if ($result['passed']) {
            $this->onboardingStage = 'qualified_clearance';
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "🎉 Deployment Clearance Granted! Score: {$result['total_score']}%. You are cleared for field service in Nagoya.",
            ]);
        } else {
            $this->onboardingStage = 'gated_coaching';
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Deployment Clearance On Hold: Score {$result['total_score']}%. Review mentoring coaching notes to build readiness.",
            ]);
        }
    }

    public function retakeScreening(): void
    {
        $this->screeningAnswers = [];
        $this->onboardingStage = 'screening';
    }

    public function proceedToWardSelection(): void
    {
        $user = auth()->user() ?? User::first();
        if (! $user?->screening_passed) {
            $this->onboardingStage = 'screening';
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gated Qualification: You must pass the Missionary Candidate Capability Screening before proceeding!',
            ]);

            return;
        }

        $this->onboardingStage = 'ward_selection';
    }

    public function selectStartingWard(int $wardId): void
    {
        $this->selectedHomeWardId = $wardId;
    }

    public function confirmStartingWard(): void
    {
        $user = auth()->user() ?? User::first();
        if (! $user) {
            return;
        }

        if (! $user->screening_passed) {
            $this->onboardingStage = 'screening';
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gated Qualification: You must pass the Missionary Candidate Capability Screening before establishing your home base ward!',
            ]);

            return;
        }

        if (! $this->selectedHomeWardId) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select a district to establish your starting residence.',
            ]);

            return;
        }

        $ward = Ward::find($this->selectedHomeWardId);
        if (! $ward) {
            return;
        }

        $user->update(['home_ward_id' => $ward->id]);
        $user->refresh();

        $this->showOnboardingModal = false;
        $this->dispatch('home-ward-selected', wardId: $ward->id);
        $this->dispatch('day-advanced');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "🏡 Welcome to {$ward->name}! Your residence has been established as an embedded neighbor (+25% trust bonus and zero transit friction).",
        ]);
    }

    public function savePlayerName(): void
    {
        $this->validate([
            'editingName' => ['required', 'string', 'min:2', 'max:50'],
            'editingNationality' => ['required', 'string', 'max:50'],
            'editingAge' => ['required', 'integer', 'gt:18', 'max:120'],
        ], [
            'editingName.required' => 'Please enter a missionary player name.',
            'editingName.min' => 'Player name must be at least 2 characters long.',
            'editingNationality.required' => 'Please select your country of origin.',
            'editingAge.required' => 'Please enter your age.',
            'editingAge.gt' => 'You must be older than 18 years old.',
        ]);

        $user = auth()->user();
        if ($user) {
            $user->update([
                'name' => trim($this->editingName),
                'nationality' => trim($this->editingNationality),
                'age' => (int) $this->editingAge,
            ]);
            $this->showEditNameModal = false;
            $this->dispatch('notify', message: "Player profile updated: {$user->name} ({$user->nationalityFlag} {$user->nationality}, {$user->age}y)!", type: 'success');
        }
    }

    /** @var array<string, string> */
    protected $listeners = [
        'day-advanced' => '$refresh',
        'action-queued' => '$refresh',
    ];

    /**
     * Advance to the next game week tick for the current player.
     * Does NOT change the week for other players in the session.
     */
    public function advanceWeek(bool $isManual = true): void
    {
        $user = auth()->user() ?? User::first();
        $session = $user ? ($user->currentGameSession ?? GameSession::first()) : GameSession::first();

        if (! $user || ! $session) {
            return;
        }

        $user->refresh();
        $session->refresh();

        // If triggered automatically by the countdown timer, ensure a tick is actually due
        if (! $isManual && ! $user->isWeeklyTickDue && ! $user->isDailyTickDue) {
            return;
        }

        if ($user->weeks_played_today >= 4 || $user->days_played_today >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Daily play limit reached: You have completed all 4 game weeks for today. The new month will begin tonight at midnight!',
                ]);
            }

            return;
        }

        if ($user->weeks_processed_this_month >= 4 || $user->days_processed_this_month >= 30) {
            if ($isManual) {
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Month {$user->current_game_month} has completed all 4 weeks! The server will automatically resolve and advance to Month ".($user->current_game_month + 1).' tonight at midnight.',
                ]);
            }

            return;
        }

        $engine = app(FourFieldsEngine::class);
        $result = $engine->resolveWeeklyTick($session, specificUser: $user, force: true);

        $user->refresh();
        $session->refresh();

        $this->dispatch('week-advanced');
        $this->dispatch('day-advanced');
        if ($isManual) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Advanced to Week {$user->current_game_week} / 4! Processed {$result?->actionsProcessed} queued action(s). Bandwidth replenished to {$user->action_points} AP.",
            ]);
        }
    }

    public function advanceDay(bool $isManual = true): void
    {
        $this->advanceWeek($isManual);
    }

    /**
     * Manual month advance is disabled for players; only the server advances month at midnight.
     */
    public function advanceMonth(): void
    {
        $user = auth()->user() ?? User::first();

        if (! $user) {
            return;
        }

        $user->refresh();

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "Month {$user->current_game_month} will be resolved tonight at midnight by the server tick. Players cannot manually advance to the next month.",
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

        // Calculate other missionaries stationed in wards to spotlight pioneering frontiers (0 co-laborers)
        $otherMissionariesQuery = User::whereNotNull('home_ward_id')
            ->where('id', '!=', $user?->id)
            ->with('homeWard');

        if (! ($session?->isCoopMode ?? true)) {
            $otherMissionariesQuery->where('is_ai_colaborer', false);
        }

        $otherMissionaries = $otherMissionariesQuery->get();

        $occupiedWardIds = $otherMissionaries->pluck('home_ward_id')->unique()->toArray();
        $missionariesByWard = $otherMissionaries->groupBy('home_ward_id');

        $allWards = Ward::orderBy('name')->get();
        $pioneeringWards = $allWards->filter(fn (Ward $w) => ! in_array($w->id, $occupiedWardIds))->values();
        $recommendedWard = $pioneeringWards->sortByDesc('spiritual_openness')->first() ?? $allWards->first();

        if (! $this->selectedHomeWardId && $recommendedWard) {
            $this->selectedHomeWardId = $recommendedWard->id;
        }

        $screeningService = app(MissionaryScreeningService::class);
        $screeningQuestions = $screeningService->getQuestions();
        $screeningDimensions = $screeningService->getDimensions();

        return view('livewire.nagoya-movement-dashboard', [
            'user' => $user,
            'session' => $session,
            'unreadReportsCount' => $unreadReportsCount,
            'churches' => $churches,
            'activeChurch' => $activeChurch,
            'allWards' => $allWards,
            'pioneeringWards' => $pioneeringWards,
            'recommendedWard' => $recommendedWard,
            'missionariesByWard' => $missionariesByWard,
            'screeningQuestions' => $screeningQuestions,
            'screeningDimensions' => $screeningDimensions,
        ]);
    }
}
