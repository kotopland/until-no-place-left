<?php

declare(strict_types=1);

use App\Livewire\ActionQueuePlanner;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('confirming starting ward transitions candidate directly to game host welcome', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
        'screening_passed' => true,
        'has_seen_host_intro' => false,
        'action_points' => 20,
        'weekly_action_points' => 20,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('onboardingStage', 'ward_selection')
        ->assertSet('showOnboardingModal', true)
        ->call('selectStartingWard', $ward->id)
        ->call('confirmStartingWard')
        ->assertSet('onboardingStage', 'host_welcome')
        ->assertSet('showOnboardingModal', true)
        ->assertSee('Welcome to Nagoya & Naka-ku!')
        ->assertSee('Kenjiro Sato')
        ->assertSee('The Japanese Language Reality')
        ->assertSee('Group Culture & Relational-Based Decisions')
        ->assertSee('First-Level NPL Training + Japanese Language School')
        ->assertSee('70% AP Bandwidth Investment Rule')
        ->assertSee('60% AP')
        ->assertSee('10% AP')
        ->assertSee('30% AP');

    expect($user->fresh()->home_ward_id)->toBe($ward->id);
});

test('player with home ward who has not seen host intro is greeted with host welcome on mount', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1666,
        'longitude' => 136.9666,
        'population' => 165000,
        'spiritual_openness' => 45,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', true)
        ->assertSet('onboardingStage', 'host_welcome')
        ->assertSee('Welcome to Nagoya & Chikusa-ku!');
});

test('choosing recommended language and training strategy dedicates 70 percent AP for first four months', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Atsuta-ku',
        'name_ja' => '熱田区',
        'slug' => 'atsuta-ku',
        'latitude' => 35.1278,
        'longitude' => 136.9083,
        'population' => 66000,
        'spiritual_openness' => 28,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => false,
        'committed_to_language_training' => false,
        'language_training_completed' => false,
        'current_game_month' => 1,
        'action_points' => 20,
        'weekly_action_points' => 20,
        'max_action_points' => 26,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->set('onboardingStage', 'host_welcome')
        ->call('chooseRecommendedLanguageStrategy')
        ->assertSet('showOnboardingModal', false);

    $refreshed = $user->fresh();
    expect($refreshed->committed_to_language_training)->toBeTrue()
        ->and($refreshed->has_seen_host_intro)->toBeTrue()
        ->and($refreshed->language_training_enrolled_month)->toBe(1)
        ->and($refreshed->isLanguageTrainingActive)->toBeTrue()
        ->and($refreshed->languageTrainingMonthsRemaining)->toBe(4)
        ->and($refreshed->languageTrainingCurrentMonthNumber)->toBe(1)
        ->and($refreshed->languageSchoolWeeklyAp)->toBe(12) // 60% of 20 AP
        ->and($refreshed->firstLevelTrainingWeeklyAp)->toBe(2) // 10% of 20 AP
        ->and($refreshed->totalTrainingWeeklyAp)->toBe(14) // 70% of 20 AP
        ->and($refreshed->availableFieldWeeklyAp)->toBe(6) // 30% of 20 AP
        ->and($refreshed->action_points)->toBe(6);
});

test('choosing independent strategy incurs language and cultural barrier handicap of 14 AP', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'latitude' => 35.1760,
        'longitude' => 137.0100,
        'population' => 160000,
        'spiritual_openness' => 35,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => false,
        'committed_to_language_training' => false,
        'language_training_completed' => false,
        'current_game_month' => 1,
        'action_points' => 20,
        'weekly_action_points' => 20,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->set('onboardingStage', 'host_welcome')
        ->call('chooseIndependentStrategy')
        ->assertSet('showOnboardingModal', false);

    $refreshed = $user->fresh();
    expect($refreshed->committed_to_language_training)->toBeFalse()
        ->and($refreshed->has_seen_host_intro)->toBeTrue()
        ->and($refreshed->isLanguageTrainingActive)->toBeFalse()
        ->and($refreshed->hasLanguageBarrierHandicap)->toBeTrue()
        ->and($refreshed->availableFieldWeeklyAp)->toBe(14)
        ->and($refreshed->action_points)->toBe(14);
});

test('action queue planner displays language barrier handicap banner when operating independently without foundation', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
        'current_game_week' => 1,
        'current_game_month' => 1,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
        'committed_to_language_training' => false,
        'language_training_completed' => false,
        'current_game_month' => 1,
        'current_game_week' => 1,
        'action_points' => 14,
        'weekly_action_points' => 20,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSee('Language & Cultural Barrier Handicap Active')
        ->assertSee('-6 AP Effort Friction Penalty')
        ->assertSee('14 AP');
});

test('action queue planner displays dedicated language school and NPL training banner when active', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
        'current_game_week' => 1,
        'current_game_month' => 1,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
        'committed_to_language_training' => true,
        'language_training_completed' => false,
        'language_training_enrolled_month' => 1,
        'current_game_month' => 1,
        'current_game_week' => 1,
        'action_points' => 6,
        'weekly_action_points' => 20,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSee('Intensive Japanese Language School & First-Level NPL Foundation')
        ->assertSee('Month 1 of 4')
        ->assertSee('60% Japanese Language Studies (12 AP)')
        ->assertSee('10% First-Level NPL Training (2 AP)')
        ->assertSee('30% AP (6 AP) available for field actions');
});

test('advancing through four months of language training graduates the missionary and restores full AP', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
        'current_game_month' => 4,
        'current_game_week' => 4,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
        'committed_to_language_training' => true,
        'language_training_enrolled_month' => 1,
        'language_training_completed' => false,
        'current_game_month' => 4,
        'current_game_week' => 4,
        'last_monthly_tick_at' => now()->subDay(),
        'action_points' => 6,
        'weekly_action_points' => 20,
        'max_action_points' => 26,
        'total_generational_score' => 10,
    ]);

    $engine = app(FourFieldsEngine::class);

    // Process month advance from Month 4 to Month 5
    $engine->resolveGameMonth($session);

    $refreshed = $user->fresh();
    expect($refreshed->current_game_month)->toBe(5)
        ->and($refreshed->language_training_completed)->toBeTrue()
        ->and($refreshed->isLanguageTrainingActive)->toBeFalse()
        ->and($refreshed->action_points)->toBe(26)
        ->and($refreshed->badges)->toContain('Language & L1 Certified')
        ->and($refreshed->total_generational_score)->toBeGreaterThan(10);
});

test('language training remains active through months 2 and 3 with 70 percent AP dedicated', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
        'current_game_month' => 2,
        'current_game_week' => 4,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
        'committed_to_language_training' => true,
        'language_training_enrolled_month' => 1,
        'language_training_completed' => false,
        'current_game_month' => 2,
        'current_game_week' => 4,
        'last_monthly_tick_at' => now()->subDay(),
        'action_points' => 6,
        'weekly_action_points' => 20,
        'max_action_points' => 26,
    ]);

    $engine = app(FourFieldsEngine::class);

    // Process month advance from Month 2 to Month 3
    $engine->resolveGameMonth($session);

    $refreshed = $user->fresh();
    expect($refreshed->current_game_month)->toBe(3)
        ->and($refreshed->language_training_completed)->toBeFalse()
        ->and($refreshed->isLanguageTrainingActive)->toBeTrue()
        ->and($refreshed->languageTrainingCurrentMonthNumber)->toBe(3)
        ->and($refreshed->languageTrainingMonthsRemaining)->toBe(2)
        ->and($refreshed->action_points)->toBe(6);
});

test('host welcome modal can be reopened at any time', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', false)
        ->call('openHostWelcomeModal')
        ->assertSet('showOnboardingModal', true)
        ->assertSet('onboardingStage', 'host_welcome')
        ->assertSee('Welcome to Nagoya & Naka-ku!')
        ->call('closeOnboardingModal')
        ->assertSet('showOnboardingModal', false);
});
