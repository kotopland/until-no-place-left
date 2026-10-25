<?php

use App\Livewire\ActionQueuePlanner;
use App\Livewire\NagoyaMapCanvas;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('ward has resident users relationship', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $user1 = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'name' => 'Player Missionary',
    ]);

    $user2 = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'name' => 'Sarah Jenkins',
    ]);

    expect($ward->residentUsers()->count())->toBe(2);
    expect($ward->residentUsers->pluck('name'))->toContain('Player Missionary', 'Sarah Jenkins');
});

test('nagoya map canvas provides house markers for player and co-laborers', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Session',
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

    $player = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
        'name' => 'Main Player',
        'total_generational_score' => 500,
    ]);

    $fellow = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardB->id,
        'name' => 'Sarah Jenkins',
        'total_generational_score' => 320,
    ]);

    $this->actingAs($player);

    $component = Livewire::test(NagoyaMapCanvas::class)
        ->assertSet('showHouses', true)
        ->assertViewHas('housesData', function ($housesData) use ($player, $fellow) {
            expect($housesData)->toBeArray();
            expect(count($housesData))->toBe(2);

            $playerHouse = collect($housesData)->firstWhere('user_id', $player->id);
            expect($playerHouse)->not->toBeNull();
            expect($playerHouse['is_current_player'])->toBeTrue();
            expect($playerHouse['ward_name'])->toBe('Naka-ku');

            $fellowHouse = collect($housesData)->firstWhere('user_id', $fellow->id);
            expect($fellowHouse)->not->toBeNull();
            expect($fellowHouse['is_current_player'])->toBeFalse();
            expect($fellowHouse['user_name'])->toBe('Sarah Jenkins');
            expect($fellowHouse['ward_name'])->toBe('Chikusa-ku');

            return true;
        });

    // Test toggle layer
    $component->call('toggleLayer', 'houses')
        ->assertSet('showHouses', false)
        ->call('toggleLayer', 'houses')
        ->assertSet('showHouses', true);
});

test('action queue planner displays co-worker missionary badges and banner', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Session',
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

    $player = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
        'name' => 'Main Player',
    ]);

    $fellow = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardB->id,
        'name' => 'Kenji Yamada',
    ]);

    $this->actingAs($player);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSee('🏡 Home')
        ->assertSee('1 co-worker')
        ->call('selectWard', $wardB->id)
        ->assertSee('Fellow Missionaries Living Here:')
        ->assertSee('Kenji Yamada');
});
