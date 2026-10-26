<?php

declare(strict_types=1);

use App\Livewire\NagoyaMovementDashboard;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('new unscreened player is prompted with candidate capability screening on mount', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
        'is_active' => true,
    ]);

    $user = User::factory()->unscreened()->create([
        'name' => 'Barnabas Candidate',
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', true)
        ->assertSet('onboardingStage', 'screening')
        ->assertSee('Missionary Candidate Capability Screening')
        ->assertSee('Candidate Qualification Gate')
        ->assertSee('Cultural Connections & Food Adaptability')
        ->assertSee('Calling & Ministry Background')
        ->assertSee('Language Acquisition & Cultural Learning')
        ->assertSee('Interpersonal Dynamics, Conflict & Authority')
        ->assertSee('Emotional Resilience, Mental Health & Loneliness')
        ->assertSee('Dietary Needs, Allergies & Daily Health')
        ->assertSee('Practical, Financial & Exit Considerations');
});

test('unscreened player is blocked from establishing home ward until screening is passed', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
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

    $user = User::factory()->unscreened()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->set('selectedHomeWardId', $ward->id)
        ->call('confirmStartingWard')
        ->assertSet('onboardingStage', 'screening')
        ->assertDispatched('notify', function (string $name, mixed $params): bool {
            $data = is_array($params) && isset($params[0]) ? $params[0] : (array) $params;

            return ($data['type'] ?? '') === 'error'
                && str_contains($data['message'] ?? '', 'Gated Qualification');
        });

    $user->refresh();
    expect($user->home_ward_id)->toBeNull();
    expect($user->screening_passed)->toBeFalse();
});

test('submitting incomplete answers triggers notification', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
        'is_active' => true,
    ]);

    $user = User::factory()->unscreened()->create([
        'current_game_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->call('recordScreeningAnswer', 'q1', 'A')
        ->call('recordScreeningAnswer', 'q2', 'A')
        // Only 2 of 14 answered
        ->call('submitScreening')
        ->assertSet('onboardingStage', 'screening')
        ->assertDispatched('notify', function (string $name, mixed $params): bool {
            $data = is_array($params) && isset($params[0]) ? $params[0] : (array) $params;

            return ($data['type'] ?? '') === 'warning'
                && str_contains($data['message'] ?? '', 'Please answer all 14 scenario questions');
        });
});

test('candidate passing screening receives clearance and unlocks ward selection', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1650,
        'longitude' => 136.9500,
        'population' => 160000,
        'spiritual_openness' => 45,
    ]);

    $user = User::factory()->unscreened()->create([
        'name' => 'Priscilla Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NagoyaMovementDashboard::class);

    // Answer all 14 questions with the mature 25-point choices across randomized options (B, C, A, etc.)
    $matureChoices = [
        'q1' => 'B',
        'q2' => 'C',
        'q3' => 'A',
        'q4' => 'C',
        'q5' => 'B',
        'q6' => 'A',
        'q7' => 'B',
        'q8' => 'C',
        'q9' => 'C',
        'q10' => 'A',
        'q11' => 'B',
        'q12' => 'B',
        'q13' => 'C',
        'q14' => 'A',
    ];

    foreach ($matureChoices as $qId => $choice) {
        $component->call('recordScreeningAnswer', $qId, $choice);
    }

    $component->call('submitScreening')
        ->assertSet('onboardingStage', 'qualified_clearance')
        ->assertSet('screeningTotalScore', 100)
        ->assertSee('Deployment Clearance Granted')
        ->assertSee('READY FOR FIELD');

    $user->refresh();
    expect($user->screening_passed)->toBeTrue();
    expect($user->screening_score)->toBe(100);
    expect($user->screening_completed_at)->not->toBeNull();
    expect($user->screening_profile_title)->not->toBeEmpty();

    // Proceed to ward selection
    $component->call('proceedToWardSelection')
        ->assertSet('onboardingStage', 'ward_selection')
        ->call('selectStartingWard', $ward->id)
        ->call('confirmStartingWard')
        ->assertSet('showOnboardingModal', false);

    $user->refresh();
    expect($user->home_ward_id)->toBe($ward->id);
});

test('candidate with critical gap in dietary health or financial exit fails screening and enters gated coaching debrief', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
        'is_active' => true,
    ]);

    $user = User::factory()->unscreened()->create([
        'current_game_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NagoyaMovementDashboard::class);

    $matureChoices = [
        'q1' => 'B',
        'q2' => 'C',
        'q3' => 'A',
        'q4' => 'C',
        'q5' => 'B',
        'q6' => 'A',
        'q7' => 'B',
        'q8' => 'C',
        'q9' => 'C',
        'q10' => 'A',
        'q11' => 'B',
        'q12' => 'B',
        'q13' => 'C',
        'q14' => 'A',
    ];

    foreach ($matureChoices as $qId => $choice) {
        $component->call('recordScreeningAnswer', $qId, $choice);
    }

    // Inject critical gap (0 points) in dietary allergy safety (q11: A, q12: C) and financial debt/reserves (q13: A, q14: C)
    $component->call('recordScreeningAnswer', 'q11', 'A') // 0 pts in dietary allergy safety
        ->call('recordScreeningAnswer', 'q12', 'C') // 0 pts in physical health
        ->call('recordScreeningAnswer', 'q13', 'A') // 0 pts in financial reserves
        ->call('recordScreeningAnswer', 'q14', 'C') // 0 pts in exit strategy
        ->call('submitScreening')
        ->assertSet('onboardingStage', 'gated_coaching')
        ->assertSee('Clearance On Hold • Mentorship Required')
        ->assertSee('Missionary Candidate Readiness Debrief')
        ->assertSee('Dietary Needs, Allergies & Daily Health')
        ->assertSee('Practical, Financial & Exit Considerations');

    $user->refresh();
    expect($user->screening_passed)->toBeFalse();

    // Retake screening resets stage to questionnaire
    $component->call('retakeScreening')
        ->assertSet('onboardingStage', 'screening')
        ->assertSet('screeningAnswers', []);
});

test('blindly selecting option A across all questions fails screening due to randomized answer distribution', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
        'is_active' => true,
    ]);

    $user = User::factory()->unscreened()->create([
        'current_game_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NagoyaMovementDashboard::class);

    // Blindly pick 'A' for all 14 questions (now contains several 0-point critical gaps like q2, q5, q8, q11, q13)
    for ($i = 1; $i <= 14; $i++) {
        $component->call('recordScreeningAnswer', "q{$i}", 'A');
    }

    $component->call('submitScreening')
        ->assertSet('onboardingStage', 'gated_coaching')
        ->assertSee('Clearance On Hold • Mentorship Required');

    $user->refresh();
    expect($user->screening_passed)->toBeFalse();
    expect($user->screening_score)->toBeLessThan(70);
});

test('screened missionary can view capability scorecard modal from header anytime', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Midori-ku',
        'name_ja' => '緑区',
        'slug' => 'midori-ku',
        'latitude' => 35.0800,
        'longitude' => 136.9700,
        'population' => 240000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'name' => 'Apollos Veteran',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'screening_score' => 95,
        'screening_profile_title' => 'Wholesome Steward & Host',
        'screening_dimension_scores' => [
            'cultural_connections' => 90,
            'calling_background' => 95,
            'language_learning' => 95,
            'interpersonal_dynamics' => 95,
            'emotional_resilience' => 95,
            'dietary_health' => 100,
            'practical_financial_exit' => 95,
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showScreeningScorecardModal', false)
        ->call('openScreeningScorecard')
        ->assertSet('showScreeningScorecardModal', true)
        ->assertSee('Capability Scorecard')
        ->assertSee('Wholesome Steward & Host')
        ->assertSee('Dietary Needs, Allergies & Daily Health')
        ->assertSee('Practical, Financial & Exit Considerations')
        ->assertSee('95%')
        ->call('closeScreeningScorecard')
        ->assertSet('showScreeningScorecardModal', false);
});
