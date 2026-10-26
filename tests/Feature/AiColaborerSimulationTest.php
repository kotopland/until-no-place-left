<?php

declare(strict_types=1);

use App\Livewire\ActionQueuePlanner;
use App\Livewire\MultiplayerLeaderboard;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use App\Services\AiColaborerService;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('ensureAiColaborers creates 3 distinct missionary archetypes in key wards', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'current_game_month' => 1,
        'is_active' => true,
    ]);

    $chikusa = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1667,
        'longitude' => 136.9667,
        'population' => 165000,
        'spiritual_openness' => 35,
    ]);

    $minato = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Minato-ku',
        'name_ja' => '港区',
        'slug' => 'minato-ku',
        'latitude' => 35.1000,
        'longitude' => 136.8833,
        'population' => 145000,
        'spiritual_openness' => 20,
    ]);

    $meito = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'latitude' => 35.1833,
        'longitude' => 137.0000,
        'population' => 160000,
        'spiritual_openness' => 25,
    ]);

    $service = app(AiColaborerService::class);
    $colaborers = $service->ensureAiColaborers($session);

    expect($colaborers)->toHaveCount(3);

    $sarah = User::where('email', 'sarah.jenkins@nagoyaharvest.jp')->first();
    expect($sarah)->not->toBeNull()
        ->and($sarah->is_ai_colaborer)->toBeTrue()
        ->and($sarah->colaborer_archetype)->toBe('campus_sower')
        ->and($sarah->home_ward_id)->toBe($chikusa->id)
        ->and($sarah->archetypeBadgeLabel)->toBe('🎓 Campus Sower');

    $marcus = User::where('email', 'marcus.silva@nagoyaharvest.jp')->first();
    expect($marcus)->not->toBeNull()
        ->and($marcus->is_ai_colaborer)->toBeTrue()
        ->and($marcus->colaborer_archetype)->toBe('mercy_catalyst')
        ->and($marcus->home_ward_id)->toBe($minato->id)
        ->and($marcus->archetypeBadgeLabel)->toBe('❤️ Mercy Catalyst');

    $kenji = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->first();
    expect($kenji)->not->toBeNull()
        ->and($kenji->is_ai_colaborer)->toBeTrue()
        ->and($kenji->colaborer_archetype)->toBe('discipleship_trainer')
        ->and($kenji->home_ward_id)->toBe($meito->id)
        ->and($kenji->archetypeBadgeLabel)->toBe('📖 Discipleship Trainer');
});

test('simulateDailyTurn executes autonomous actions for AI co-laborers', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'current_game_month' => 1,
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1667,
        'longitude' => 136.9667,
        'population' => 165000,
        'spiritual_openness' => 30,
    ]);

    // Create an unreached resident in Chikusa
    Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Daiki Ito',
        'gender' => 'male',
        'age' => 21,
        'occupation' => 'University Student',
        'status' => \App\Enums\ReceptivityStatus::Unreached,
        'trust_level' => 10,
    ]);

    $service = app(AiColaborerService::class);
    $service->ensureAiColaborers($session);

    $sarah = User::where('email', 'sarah.jenkins@nagoyaharvest.jp')->first();
    expect($sarah->action_points)->toBe(12);

    $outcomes = $service->simulateDailyTurn($session, 1);

    expect($outcomes)->not->toBeEmpty();
    $sarah->refresh();
    expect($sarah->action_points)->toBeLessThan(12);
});

test('joint prayer walk awards co-op synergy points and extra spiritual softening', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Minato-ku',
        'name_ja' => '港区',
        'slug' => 'minato-ku',
        'latitude' => 35.1000,
        'longitude' => 136.8833,
        'population' => 145000,
        'spiritual_openness' => 20,
    ]);

    $human = User::factory()->create([
        'name' => 'Human Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'coop_synergy_score' => 0,
        'action_points' => 12,
    ]);

    $ai = User::factory()->create([
        'name' => 'Marcus Silva',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'mercy_catalyst',
        'coop_synergy_score' => 10,
    ]);

    $service = app(AiColaborerService::class);
    $outcome = $service->conductJointPrayerWalk($human, $ai, $ward);

    $human->refresh();
    $ai->refresh();
    $ward->refresh();

    expect($human->coop_synergy_score)->toBe(15)
        ->and($ai->coop_synergy_score)->toBe(25)
        ->and($ward->spiritual_openness)->toBeGreaterThan(20);
});

test('iron-on-iron provides bonus AP and tactical coaching tip', function () {
    $human = User::factory()->create([
        'action_points' => 5,
        'coop_synergy_score' => 10,
    ]);

    $ai = User::factory()->create([
        'name' => 'Kenji Yamada',
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'discipleship_trainer',
        'coop_synergy_score' => 10,
    ]);

    $service = app(AiColaborerService::class);
    $result = $service->conductIronOnIron($human, $ai);

    $human->refresh();
    $ai->refresh();

    expect($human->action_points)->toBe(7)
        ->and($human->coop_synergy_score)->toBe(35)
        ->and($ai->coop_synergy_score)->toBe(35)
        ->and($result['bonus_ap'])->toBe(2)
        ->and($result['coaching_tip'])->toContain('Kenji:');
});

test('leaderboard displays mode as read-only and reflects game:mode command configuration', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'is_active' => true,
        'settings' => ['coop_team_mode' => true],
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 20,
    ]);

    $human = User::factory()->create([
        'name' => 'Human Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => false,
    ]);

    $service = app(AiColaborerService::class);
    $service->ensureAiColaborers($session);

    $this->actingAs($human);

    // Initial state: Co-op mode is on, AI co-laborers visible in leaderboard
    Livewire::test(MultiplayerLeaderboard::class)
        ->assertSee('Team Co-op Mode')
        ->assertSee('Sarah Jenkins')
        ->assertSee('Marcus Silva')
        ->assertSee('Kenji Yamada')
        ->assertDontSeeHtml('wire:click="toggleCoopMode"');

    // Players cannot toggle mode from UI; switch must be performed via Artisan command
    $this->artisan('game:mode', ['mode' => 'solo'])
        ->expectsOutputToContain('100% Solo Pioneer Mode')
        ->assertSuccessful();

    $session->refresh();
    expect($session->isCoopMode)->toBeFalse();

    Livewire::test(MultiplayerLeaderboard::class)
        ->assertSee('Solo Pioneer Mode')
        ->assertDontSee('Sarah Jenkins');

    // Switch back to coop mode via Artisan command
    $this->artisan('game:mode', ['mode' => 'coop'])
        ->expectsOutputToContain('Team Co-op Mode')
        ->assertSuccessful();

    $session->refresh();
    expect($session->isCoopMode)->toBeTrue();
});

test('action queue planner allows joint prayer walk and iron-on-iron with AI co-laborer', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
        'settings' => ['coop_team_mode' => true],
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1667,
        'longitude' => 136.9667,
        'population' => 165000,
        'spiritual_openness' => 30,
    ]);

    $human = User::factory()->create([
        'name' => 'Human Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'action_points' => 10,
        'coop_synergy_score' => 0,
    ]);

    $sarah = User::create([
        'name' => 'Sarah Jenkins',
        'email' => 'sarah.jenkins@nagoyaharvest.jp',
        'password' => bcrypt('password'),
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'campus_sower',
        'ai_strategy_notes' => 'Campus sower near Nagoya University.',
        'action_points' => 12,
        'max_action_points' => 12,
        'coop_synergy_score' => 10,
    ]);

    $this->actingAs($human);

    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->assertSee('Sarah Jenkins')
        ->assertSee('Campus Sower')
        ->call('conductJointPrayerWalk', $sarah->id)
        ->assertDispatched('notify')
        ->call('conductIronOnIron', $sarah->id)
        ->assertDispatched('notify');

    $human->refresh();
    expect($human->coop_synergy_score)->toBeGreaterThan(0);
});
