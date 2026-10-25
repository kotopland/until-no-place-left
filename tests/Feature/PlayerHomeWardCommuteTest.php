<?php

use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can set and change their home base ward via livewire action planner', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $wardB = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1650,
        'longitude' => 136.9500,
        'population' => 160000,
        'spiritual_openness' => 25,
    ]);

    $user = User::factory()->create([
        'game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
        'action_points' => 12,
        'current_game_day' => 1,
    ]);

    $this->actingAs($user);

    expect($user->home_ward_id)->toBe($wardA->id);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSet('selectedWardId', $wardA->id)
        ->call('selectWard', $wardB->id)
        ->assertSet('selectedWardId', $wardB->id)
        ->call('moveToWard', $wardB->id)
        ->assertDispatched('notify');

    expect($user->fresh()->home_ward_id)->toBe($wardB->id);
});

test('commute metrics properly calculate transit minutes, ap surcharge, and trust multiplier based on distance', function () {
    $session = GameSession::create([
        'name' => 'Distance Test Session',
        'is_active' => true,
    ]);

    // Home ward (Center)
    $homeWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    // Nearby ward (~3.8 km away)
    $nearbyWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1650,
        'longitude' => 136.9500,
        'population' => 160000,
        'spiritual_openness' => 20,
    ]);

    // Moderate ward (~7.5 km away)
    $moderateWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Meito-ku',
        'slug' => 'meito-ku',
        'latitude' => 35.1800,
        'longitude' => 136.9900,
        'population' => 160000,
        'spiritual_openness' => 20,
    ]);

    // Distant ward (~13.5 km away)
    $distantWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Minato-ku',
        'slug' => 'minato-ku',
        'latitude' => 35.0850,
        'longitude' => 136.8800,
        'population' => 140000,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'game_session_id' => $session->id,
        'home_ward_id' => $homeWard->id,
    ]);

    // 1. Home Ward: 0 km, 0 min transit, +0 AP, 1.25x trust
    expect($user->distanceToWard($homeWard))->toBe(0.0)
        ->and($user->commuteTransitMinutesForWard($homeWard))->toBe(0)
        ->and($user->commuteApCostForWard($homeWard))->toBe(0)
        ->and($user->commuteTrustMultiplierForWard($homeWard))->toBe(1.25);

    // 2. Nearby Ward (<= 5 km): 0 AP surcharge, 1.0x trust
    expect($user->distanceToWard($nearbyWard))->toBeLessThanOrEqual(5.0)
        ->and($user->commuteApCostForWard($nearbyWard))->toBe(0)
        ->and($user->commuteTrustMultiplierForWard($nearbyWard))->toBe(1.0);

    // 3. Moderate Ward (5 - 10 km): +1 AP surcharge, 0.85x trust
    $modDist = $user->distanceToWard($moderateWard);
    expect($modDist)->toBeGreaterThan(5.0)
        ->and($modDist)->toBeLessThanOrEqual(10.0)
        ->and($user->commuteApCostForWard($moderateWard))->toBe(1)
        ->and($user->commuteTrustMultiplierForWard($moderateWard))->toBe(0.85);

    // 4. Distant Ward (> 10 km): +2 AP surcharge, 0.70x trust
    expect($user->distanceToWard($distantWard))->toBeGreaterThan(10.0)
        ->and($user->commuteApCostForWard($distantWard))->toBe(2)
        ->and($user->commuteTrustMultiplierForWard($distantWard))->toBe(0.70);
});

test('action queue planner deducts base action points plus commute ap surcharge', function () {
    $session = GameSession::create([
        'name' => 'Action Planner Commute Test',
        'is_active' => true,
    ]);

    $homeWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $distantWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Minato-ku',
        'slug' => 'minato-ku',
        'latitude' => 35.0850,
        'longitude' => 136.8800,
        'population' => 140000,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'game_session_id' => $session->id,
        'home_ward_id' => $homeWard->id,
        'action_points' => 12,
        'current_game_day' => 1,
    ]);

    $this->actingAs($user);

    // Prayer walk base cost is 1 AP. Commute to Minato-ku adds +2 AP. Total cost = 3 AP.
    Livewire::test(ActionQueuePlanner::class)
        ->call('selectWard', $distantWard->id)
        ->set('selectedActionType', MinistryActionType::PrayerWalk->value)
        ->call('queueAction');

    expect($user->fresh()->action_points)->toBe(9); // 12 - (1 + 2)

    $queued = QueuedMonthlyAction::where('user_id', $user->id)->first();
    expect($queued)->not->toBeNull()
        ->and($queued->ap_cost)->toBe(3);

    // Now set user's AP to only 2 AP. Attempting another distant action (3 AP required) must fail.
    $user->update(['action_points' => 2]);

    Livewire::test(ActionQueuePlanner::class)
        ->call('selectWard', $distantWard->id)
        ->set('selectedActionType', MinistryActionType::PrayerWalk->value)
        ->call('queueAction')
        ->assertDispatched('notify');

    // AP remains untouched at 2, no second action queued
    expect($user->fresh()->action_points)->toBe(2)
        ->and(QueuedMonthlyAction::where('user_id', $user->id)->count())->toBe(1);
});

test('four fields engine integrates proximity bonus for home ward and friction for distant wards', function () {
    $session = GameSession::create([
        'name' => 'Proximity Engine Test',
        'is_active' => true,
    ]);

    $homeWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $distantWard = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Minato-ku',
        'slug' => 'minato-ku',
        'latitude' => 35.0850,
        'longitude' => 136.8800,
        'population' => 140000,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'game_session_id' => $session->id,
        'home_ward_id' => $homeWard->id,
        'action_points' => 12,
        'current_game_day' => 1,
    ]);

    $localNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $homeWard->id,
        'name' => 'Kenji Local',
        'gender' => 'male',
        'age' => 30,
        'receptivity_status' => ReceptivityStatus::Seeking,
        'need_state' => NpcNeedState::SpiritualEmptiness,
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'trust_level' => 10,
    ]);

    $distantNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $distantWard->id,
        'name' => 'Hiroshi Far',
        'gender' => 'male',
        'age' => 35,
        'receptivity_status' => ReceptivityStatus::Seeking,
        'need_state' => NpcNeedState::SpiritualEmptiness,
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'trust_level' => 10,
    ]);

    $engine = app(FourFieldsEngine::class);

    // Execute Casual Talk in Home Ward (base trust = 15, multiplied by 1.25 = round(18.75) = 19)
    $localOutcome = $engine->executeAction(
        $user,
        $homeWard,
        MinistryActionType::CasualTalk,
        $localNpc,
        1
    );

    expect($localNpc->fresh()->trust_level)->toBeGreaterThan(10)
        ->and($localOutcome->outcome_notes)->toContain('🏡 Local neighbor presence bonus (+25% trust gain)');

    // Execute Casual Talk in Distant Ward (base trust = 15, multiplied by 0.70 = round(10.5) = 11)
    $distantOutcome = $engine->executeAction(
        $user,
        $distantWard,
        MinistryActionType::CasualTalk,
        $distantNpc,
        1
    );

    expect($distantNpc->fresh()->trust_level)->toBeGreaterThan(10)
        ->and($distantOutcome->outcome_notes)->toContain('🚇 Commute distance from home base created friction');

    // Local trust gain (19) should exceed distant trust gain (11)
    expect($localNpc->fresh()->trust_level)->toBeGreaterThan($distantNpc->fresh()->trust_level);
});
