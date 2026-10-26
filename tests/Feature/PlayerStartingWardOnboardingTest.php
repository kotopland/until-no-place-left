<?php

declare(strict_types=1);

use App\Livewire\NagoyaMovementDashboard;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('new player without home ward has onboarding modal open on mount', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', true)
        ->assertSee('Choose Your Starting Home Base')
        ->assertSee('Until No Place Left');
});

test('onboarding recommends pioneering ward where 0 other missionaries are stationed', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    // Ward A has an existing missionary
    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 50, // high openness but occupied!
    ]);

    $otherMissionary = User::factory()->create([
        'name' => 'Coach Paul',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
    ]);

    // Ward B and C have 0 missionaries; Ward B has higher openness
    $wardB = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Showa-ku',
        'name_ja' => '昭和区',
        'slug' => 'showa-ku',
        'latitude' => 35.1460,
        'longitude' => 136.9380,
        'population' => 110000,
        'spiritual_openness' => 40,
    ]);

    $wardC = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Minato-ku',
        'name_ja' => '港区',
        'slug' => 'minato-ku',
        'latitude' => 35.1000,
        'longitude' => 136.8800,
        'population' => 140000,
        'spiritual_openness' => 25,
    ]);

    $newUser = User::factory()->create([
        'name' => 'Timothy Scout',
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($newUser);

    $component = Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', true)
        ->assertSet('selectedHomeWardId', $wardB->id) // Auto-selected the best pioneering ward
        ->assertSee('Showa-ku')
        ->assertSee('Recommended Pioneering Frontier')
        ->assertSee('0 Co-laborers Stationed Here');

    // Confirm starting ward
    $component->call('confirmStartingWard')
        ->assertSet('showOnboardingModal', false)
        ->assertDispatched('home-ward-selected', wardId: $wardB->id);

    $newUser->refresh();
    expect($newUser->home_ward_id)->toBe($wardB->id);
});

test('player can manually select a different ward from recommendation and confirm', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Showa-ku',
        'name_ja' => '昭和区',
        'slug' => 'showa-ku',
        'latitude' => 35.1460,
        'longitude' => 136.9380,
        'population' => 110000,
        'spiritual_openness' => 45,
    ]);

    $wardB = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1650,
        'longitude' => 136.9500,
        'population' => 160000,
        'spiritual_openness' => 30,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->call('selectStartingWard', $wardB->id)
        ->assertSet('selectedHomeWardId', $wardB->id)
        ->call('confirmStartingWard')
        ->assertSet('showOnboardingModal', false);

    $user->refresh();
    expect($user->home_ward_id)->toBe($wardB->id);
});

test('returning player with established home ward does not see modal on mount but can open it', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
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
        'spiritual_openness' => 25,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', false)
        ->call('openOnboardingModal')
        ->assertSet('showOnboardingModal', true)
        ->call('closeOnboardingModal')
        ->assertSet('showOnboardingModal', false);
});

test('action queue planner updates selected ward on home-ward-selected event', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $wardB = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Showa-ku',
        'name_ja' => '昭和区',
        'slug' => 'showa-ku',
        'latitude' => 35.1460,
        'longitude' => 136.9380,
        'population' => 110000,
        'spiritual_openness' => 40,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
    ]);

    $this->actingAs($user);

    \Livewire\Livewire::test(\App\Livewire\ActionQueuePlanner::class)
        ->assertSet('selectedWardId', $wardA->id)
        ->dispatch('home-ward-selected', wardId: $wardB->id)
        ->assertSet('selectedWardId', $wardB->id);
});

test('100% solo pioneer experience has zero other missionaries across all wards', function () {
    $session = GameSession::create([
        'name' => 'Solo Mission Session',
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Tempaku-ku',
        'name_ja' => '天白区',
        'slug' => 'tempaku-ku',
        'latitude' => 35.1200,
        'longitude' => 136.9800,
        'population' => 160000,
        'spiritual_openness' => 45,
    ]);

    $wardB = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Atsuta-ku',
        'name_ja' => '熱田区',
        'slug' => 'atsuta-ku',
        'latitude' => 35.1300,
        'longitude' => 136.9100,
        'population' => 67000,
        'spiritual_openness' => 18,
    ]);

    // Only the player exists
    $soloPlayer = User::factory()->create([
        'name' => 'Solo Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => null,
    ]);

    $this->actingAs($soloPlayer);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('showOnboardingModal', true)
        ->assertSet('selectedHomeWardId', $wardA->id) // Recommends highest openness ward
        ->assertViewHas('pioneeringWards', function ($pioneeringWards) {
            return $pioneeringWards->count() === 2; // All available wards are 100% pioneering!
        })
        ->assertViewHas('missionariesByWard', function ($missionariesByWard) {
            return $missionariesByWard->isEmpty(); // Zero other missionaries stationed anywhere
        });
});


