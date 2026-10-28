<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\MovementCelebration;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use App\Services\MissionaryScreeningService;
use App\Services\RealLifeSimulationService;
use App\Services\ShowcaseSandboxService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class NagoyaMovementDashboard extends Component
{
    #[Url(as: 'tab')]
    public string $currentTab = 'planner'; // 'planner', 'churches', 'leaderboard', 'map', 'chat'

    public string $circlesSubView = 'genmap'; // 'genmap' or 'inspector'

    public ?int $activeChurchId = null;

    public bool $showEditNameModal = false;

    public string $editingName = '';

    public string $editingNationality = 'Japanese' {
        /** @param string|array<string, mixed> $value */
        set(string|array $value) => is_array($value) ? (string) ($value['nationality'] ?? reset($value) ?: 'Japanese') : $value;
    }

    public int $editingAge = 30;

    public bool $showOnboardingModal = false;

    public string $onboardingStage = 'screening'; // 'screening', 'gated_coaching', 'qualified_clearance', 'ward_selection', 'host_welcome'

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

    #[Url(as: 'showcase')]
    public bool $showShowcaseBar = false;

    public string $activeShowcaseStage = 'reset';

    public string $showcaseMode = 'coop';

    /** @var array<string, mixed>|null */
    public ?array $showcaseSnapshot = null;

    /** @var array<string, array<string, mixed>> */
    public array $showcaseMilestones = [];

    public bool $showShowcaseComparisonModal = false;

    public bool $isGuestShowcase = false;

    public ?string $guestDatabaseGeneratedDate = null;

    public int $showcaseRevision = 0;

    public function boot(): void
    {
        if (! auth()->check()) {
            $this->isGuestShowcase = true;
            $this->showShowcaseBar = true;
            app(ShowcaseSandboxService::class)->activateGuest();

            return;
        }

        if ($this->showShowcaseBar || request()->routeIs('simulate') || request()->boolean('showcase')) {
            $userId = auth()->id() ? (int) auth()->id() : null;
            app(ShowcaseSandboxService::class)->activate($userId);
        }
    }

    public function mount(): void
    {
        $user = auth()->user();
        if ($user) {
            $this->isGuestShowcase = false;
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

            if ($this->showShowcaseBar || request()->routeIs('simulate') || request()->boolean('showcase')) {
                $this->enterShowcaseSandbox();
            } else {
                session()->forget(['showcase_mode_active', 'showcase_is_guest', 'showcase_user_id']);
                app(ShowcaseSandboxService::class)->deactivate();

                if (! $user->screening_passed) {
                    $this->showOnboardingModal = true;
                    $this->onboardingStage = 'screening';
                } elseif (! $user->home_ward_id) {
                    $this->showOnboardingModal = true;
                    $this->onboardingStage = 'ward_selection';
                } elseif (! $user->has_seen_host_intro) {
                    $this->showOnboardingModal = true;
                    $this->onboardingStage = 'host_welcome';
                    $this->selectedHomeWardId = $user->home_ward_id;
                }
            }
        } else {
            $this->isGuestShowcase = true;
            $this->enterShowcaseSandbox();
        }
    }

    public function toggleShowcaseBar(): void
    {
        if (! auth()->check()) {
            $this->enterShowcaseSandbox();

            return;
        }

        if ($this->showShowcaseBar) {
            $this->exitShowcaseSandbox();
        } else {
            $this->enterShowcaseSandbox();
        }
    }

    public function enterShowcaseSandbox(): void
    {
        $this->showShowcaseBar = true;
        $this->showOnboardingModal = false;

        if (! auth()->check()) {
            $this->isGuestShowcase = true;
            session([
                'showcase_mode_active' => true,
                'showcase_is_guest' => true,
                'showcase_user_id' => null,
            ]);

            $session = app(ShowcaseSandboxService::class)->activateGuest();
            if (! in_array($this->currentTab, ['map', 'churches', 'leaderboard'], true)) {
                $this->currentTab = 'map';
            }

            $defaultStage = $this->activeShowcaseStage !== 'reset' ? $this->activeShowcaseStage : '15y';
            $this->hydrateGuestShowcaseFromSession($session, $defaultStage);
            $this->showcaseRevision++;

            return;
        }

        $this->isGuestShowcase = false;
        $userId = auth()->id() ? (int) auth()->id() : null;
        session([
            'showcase_mode_active' => true,
            'showcase_is_guest' => false,
            'showcase_user_id' => $userId,
        ]);

        app(ShowcaseSandboxService::class)->activate($userId);

        if (! in_array($this->currentTab, ['map', 'churches', 'leaderboard'], true)) {
            $this->currentTab = 'map';
        }

        $this->syncShowcaseSnapshotFromCurrentState();
        $this->showcaseRevision++;
    }

    public function exitShowcaseSandbox(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('register'));

            return;
        }

        $this->showShowcaseBar = false;
        $this->showShowcaseComparisonModal = false;

        session()->forget(['showcase_mode_active', 'showcase_is_guest', 'showcase_user_id']);
        app(ShowcaseSandboxService::class)->deactivate();

        $this->showcaseRevision++;

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => '🏡 Exited Showcase Sandbox and returned to your live game session (100% untouched).',
        ]);
    }

    public function setShowcaseMode(string $mode): void
    {
        if (! auth()->check()) {
            $session = app(ShowcaseSandboxService::class)->activateGuest();
            $this->guestDatabaseGeneratedDate = (string) ($session->settings['guest_generated_date'] ?? now()->toDateString());
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "⏳ Shared Guest Simulation Limit: The public guest SQLite database is only regenerated once per real-life day ({$this->guestDatabaseGeneratedDate}) to reduce server CPU load. Register a free account to run custom Solo/Co-op simulations!",
            ]);

            return;
        }

        $this->showcaseMode = $mode === 'solo' ? 'solo' : 'coop';
        $this->loadShowcaseStage($this->activeShowcaseStage);
    }

    public function regenerateGuestSimulation(): void
    {
        $result = app(ShowcaseSandboxService::class)->regenerateGuestDatabaseIfAllowed();
        $session = $result['session'];
        $this->guestDatabaseGeneratedDate = $result['generated_date'];

        if (! $result['regenerated']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "⏳ Daily Regeneration Limit: The shared guest SQLite database was already generated today ({$result['generated_date']}) and can only be regenerated once per real-life day to reduce CPU load.",
            ]);

            return;
        }

        $this->hydrateGuestShowcaseFromSession($session, $this->activeShowcaseStage ?: '15y');
        $this->showcaseRevision++;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "✅ Shared Guest Simulation SQLite database regenerated for {$result['generated_date']}!",
        ]);
    }

    public function loadShowcaseStage(string $stage): void
    {
        $validStages = array_merge(['reset'], array_keys(RealLifeSimulationService::MILESTONES));
        if (! in_array($stage, $validStages, true)) {
            return;
        }

        $this->showShowcaseBar = true;
        $this->showOnboardingModal = false;

        if (! auth()->check()) {
            $this->isGuestShowcase = true;
            session([
                'showcase_mode_active' => true,
                'showcase_is_guest' => true,
                'showcase_user_id' => null,
            ]);

            $session = app(ShowcaseSandboxService::class)->activateGuest();
            if (! in_array($this->currentTab, ['map', 'churches', 'leaderboard'], true)) {
                $this->currentTab = 'map';
            }

            $this->hydrateGuestShowcaseFromSession($session, $stage);
            $this->showcaseRevision++;

            $label = $this->showcaseSnapshot['label'] ?? $stage;
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => "⏱️ Shared Guest Simulation (Generated {$this->guestDatabaseGeneratedDate}): Viewing {$label} snapshot. Shared guest SQLite DB is regenerated once per real-life day to reduce CPU load.",
            ]);

            return;
        }

        $userId = auth()->id() ? (int) auth()->id() : null;
        session([
            'showcase_mode_active' => true,
            'showcase_is_guest' => false,
            'showcase_user_id' => $userId,
        ]);

        $session = app(ShowcaseSandboxService::class)->activate($userId);

        if (! in_array($this->currentTab, ['map', 'churches', 'leaderboard'], true)) {
            $this->currentTab = 'map';
        }

        $simulator = app(RealLifeSimulationService::class);
        $leadComputerPioneer = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->first()
            ?? User::where('is_ai_colaborer', true)->first();

        if ($stage === 'reset') {
            $simulator->resetSessionToBaseline($session, $this->showcaseMode);
            $session = GameSession::where('code', 'nagoya-2026')->first() ?? $session->fresh();
            $leadComputerPioneer = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->first()
                ?? User::where('is_ai_colaborer', true)->first();
            $this->activeShowcaseStage = 'reset';
            $this->showcaseSnapshot = $simulator->captureSnapshot($session, $leadComputerPioneer, 1, 'reset');
        } else {
            $targetMonths = RealLifeSimulationService::MILESTONES[$stage]['months'];
            $result = $simulator->simulate($session, $targetMonths, $this->showcaseMode, resetFirst: true);
            $session = GameSession::where('code', 'nagoya-2026')->first() ?? $session->fresh();
            $this->activeShowcaseStage = $stage;
            $this->showcaseSnapshot = $result['final_snapshot'];
            $this->showcaseMilestones = array_merge($this->showcaseMilestones, $result['milestones']);
        }

        $this->activeChurchId = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->orderBy('generation')
            ->value('id');

        $this->showcaseRevision++;
        $this->dispatch('week-advanced');
        $this->dispatch('day-advanced');

        $label = $this->showcaseSnapshot['label'] ?? $stage;
        $wardsReached = $this->showcaseSnapshot['wards_reached'] ?? 1;
        $totalWards = $this->showcaseSnapshot['total_wards'] ?? 16;
        $activeChurches = $this->showcaseSnapshot['active_churches_count'] ?? 1;
        $deepestGen = $this->showcaseSnapshot['deepest_generation'] ?? 1;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "⏱️ Isolated Showcase Loaded: {$label} ({$this->showcaseSnapshot['calendar_date']}) • {$wardsReached}/{$totalWards} Wards Reached • {$activeChurches} Active Churches (G1→G{$deepestGen})!",
        ]);
    }

    protected function hydrateGuestShowcaseFromSession(GameSession $session, string $stage = '15y'): void
    {
        $settings = is_array($session->settings) ? $session->settings : [];
        $this->guestDatabaseGeneratedDate = (string) ($settings['guest_generated_date'] ?? now()->toDateString());
        $this->showcaseMilestones = is_array($settings['guest_milestones'] ?? null) ? $settings['guest_milestones'] : [];

        if ($stage === 'reset' && is_array($settings['guest_reset_snapshot'] ?? null)) {
            $this->activeShowcaseStage = 'reset';
            $this->showcaseSnapshot = $settings['guest_reset_snapshot'];
        } elseif (isset($this->showcaseMilestones[$stage])) {
            $this->activeShowcaseStage = $stage;
            $this->showcaseSnapshot = $this->showcaseMilestones[$stage];
        } else {
            $this->activeShowcaseStage = '15y';
            $this->showcaseSnapshot = $settings['guest_final_snapshot']
                ?? ($this->showcaseMilestones['15y'] ?? null);
        }

        $this->activeChurchId = Church::where('game_session_id', $session->id)
            ->where('is_dissolved', false)
            ->orderBy('generation')
            ->value('id');
    }

    public function openShowcaseComparison(bool $populateAll = false): void
    {
        if ($populateAll || count($this->showcaseMilestones) < count(RealLifeSimulationService::MILESTONES)) {
            $this->loadShowcaseStage('15y');
        }

        $this->showShowcaseComparisonModal = true;
    }

    public function closeShowcaseComparison(): void
    {
        $this->showShowcaseComparisonModal = false;
    }

    public function showcaseJumpToView(string $tab, ?string $subView = null): void
    {
        if (! in_array($tab, ['map', 'churches', 'leaderboard'], true)) {
            $tab = 'map';
        }

        $this->currentTab = $tab;
        if ($tab === 'churches' && $subView !== null) {
            $this->circlesSubView = $subView;
        }
    }

    protected function syncShowcaseSnapshotFromCurrentState(): void
    {
        $session = GameSession::where('is_active', true)->first() ?? GameSession::first();
        if (! $session) {
            return;
        }

        $leadComputerPioneer = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->first()
            ?? User::where('is_ai_colaborer', true)->first();

        $simulator = app(RealLifeSimulationService::class);
        $currentMonth = max(1, (int) $session->current_game_month);
        $elapsed = $currentMonth > 1 ? ($currentMonth - 1) : 1;
        $milestoneKey = $currentMonth <= 1 ? 'reset' : ($simulator->milestoneKeyForMonths($elapsed) ?? 'reset');

        $this->activeShowcaseStage = $milestoneKey;
        $this->showcaseMode = $session->isCoopMode ? 'coop' : 'solo';
        $this->showcaseSnapshot = $simulator->captureSnapshot($session, $leadComputerPioneer, $elapsed, $milestoneKey);
    }

    public function setTab(string $tab): void
    {
        if (! auth()->check() && in_array($tab, ['planner', 'chat'], true)) {
            $this->redirect(route('register'));

            return;
        }

        if ($this->showShowcaseBar && in_array($tab, ['planner', 'chat'], true)) {
            $this->exitShowcaseSandbox();
        }

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
        $this->editingNationality = $user->nationality ?? 'Japanese';
        $this->editingAge = (int) ($user->age ?? 30);
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
            if (! $user->has_seen_host_intro) {
                $user->update(['has_seen_host_intro' => true]);
            }
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

        $this->dispatch('home-ward-selected', wardId: $ward->id);
        $this->dispatch('day-advanced');

        if (! $user->has_seen_host_intro) {
            // Transition to Game Host Orientation for new missionaries
            $this->onboardingStage = 'host_welcome';
            $this->showOnboardingModal = true;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "🏡 Base Established in {$ward->name}! Now receiving your Nagoya Field Orientation from the Game Host.",
            ]);
        } else {
            $this->showOnboardingModal = false;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "🏡 Base Established in {$ward->name} ({$ward->name_ja})! Ready for ministry operations.",
            ]);
        }
    }

    #[On('open-host-welcome-modal')]
    public function openHostWelcomeModal(): void
    {
        $user = auth()->user();
        if ($user?->home_ward_id) {
            $this->selectedHomeWardId = $user->home_ward_id;
        }
        $this->onboardingStage = 'host_welcome';
        $this->showOnboardingModal = true;
    }

    public function chooseRecommendedLanguageStrategy(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $ward = $user->homeWard ?? ($this->selectedHomeWardId ? Ward::find($this->selectedHomeWardId) : null);

        $user->committed_to_language_training = true;
        $user->language_training_completed = false;
        $user->language_training_enrolled_month = max(1, $user->current_game_month);
        $user->has_seen_host_intro = true;
        $user->action_points = $user->availableFieldWeeklyAp;
        $user->save();

        $this->showOnboardingModal = false;
        $wardName = $ward->name ?? 'Nagoya';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "🗾 Strategy Confirmed: Enrolled in Japanese Language School (60% AP) & NPL First-Level Training (10% AP) in {$wardName}! Remaining 30% AP ({$user->action_points} AP) available for field actions.",
        ]);
    }

    #[On('celebration-dismissed')]
    public function onCelebrationDismissed(?int $celebrationId = null): void
    {
        if ($celebrationId) {
            MovementCelebration::where('id', $celebrationId)->update(['is_dismissed' => true]);
        }
    }

    public function dismissCelebration(?int $celebrationId = null): void
    {
        if ($celebrationId) {
            MovementCelebration::where('id', $celebrationId)->update(['is_dismissed' => true]);
            $this->dispatch('celebration-dismissed', celebrationId: $celebrationId);
        }
    }

    public function chooseIndependentStrategy(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $ward = $user->homeWard ?? ($this->selectedHomeWardId ? Ward::find($this->selectedHomeWardId) : null);

        $user->committed_to_language_training = false;
        $user->has_seen_host_intro = true;
        $user->action_points = $user->availableFieldWeeklyAp;
        $user->save();

        $this->showOnboardingModal = false;
        $wardName = $ward->name ?? 'Nagoya';

        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => "🗾 Independent Strategy Confirmed: Operating in {$wardName} with Language & Cultural Barrier Handicap ({$user->action_points} AP/week received due to -6 AP effort friction). You may commit to training at any time via Host Field Orientation.",
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
        $isShowcase = $this->showShowcaseBar || app(ShowcaseSandboxService::class)->isActive();
        $user = auth()->user() ?? User::first();

        if (! $isShowcase) {
            $user?->touchActive();
        }

        $session = $isShowcase
            ? (GameSession::where('is_active', true)->first() ?? GameSession::first())
            : ($user ? ($user->currentGameSession ?? GameSession::first()) : GameSession::first());

        $unreadReportsCount = (! $isShowcase && $user)
            ? MonthlyReport::where('user_id', $user->id)->whereNull('read_at')->count()
            : 0;

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

        if (! ($session->isCoopMode ?? true)) {
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

        $activeCelebrations = MovementCelebration::where('game_session_id', $session?->id)
            ->where('is_dismissed', false)
            ->latest()
            ->get();

        $unreadChatCount = (! $isShowcase && $user) ? $user->unreadChatCount($session?->id) : 0;

        return view('livewire.nagoya-movement-dashboard', [
            'user' => $user,
            'session' => $session,
            'unreadReportsCount' => $unreadReportsCount,
            'unreadChatCount' => $unreadChatCount,
            'churches' => $churches,
            'activeChurch' => $activeChurch,
            'allWards' => $allWards,
            'pioneeringWards' => $pioneeringWards,
            'recommendedWard' => $recommendedWard,
            'missionariesByWard' => $missionariesByWard,
            'screeningQuestions' => $screeningQuestions,
            'screeningDimensions' => $screeningDimensions,
            'activeCelebration' => $activeCelebrations->first(),
            'totalCelebrations' => $activeCelebrations->count(),
            'showcaseStageMeta' => RealLifeSimulationService::MILESTONES,
        ]);
    }
}
