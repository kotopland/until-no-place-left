<?php

declare(strict_types=1);

use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\MultiplayerLeaderboard;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use App\Services\AiColaborerService;
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
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
    ]);

    $service = app(AiColaborerService::class);
    $service->ensureAiColaborers($session);

    $sarah = User::where('email', 'sarah.jenkins@nagoyaharvest.jp')->first();
    expect($sarah->action_points)->toBe(20);

    $outcomes = $service->simulateDailyTurn($session, 1);

    expect($outcomes)->not->toBeEmpty();
    $sarah->refresh();
    expect($sarah->action_points)->toBeLessThan(20);
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

    // Second joint prayer walk on the same day must be rejected by rate limit
    $apBefore = $human->action_points;
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->call('conductJointPrayerWalk', $sarah->id)
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'error' && str_contains($data['message'] ?? '', 'only 1 walk is allowed per day');
        });

    $human->refresh();
    expect($human->action_points)->toBe($apBefore);
});

test('joint prayer walk is restricted to once per day', function () {
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
        'action_points' => 12,
        'current_game_month' => 1,
        'current_game_day' => 1,
    ]);

    $ai = User::factory()->create([
        'name' => 'Marcus Silva',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'mercy_catalyst',
    ]);

    $service = app(AiColaborerService::class);

    // 1st walk on Day 1 succeeds
    $service->conductJointPrayerWalk($human, $ai, $ward);

    $status = $service->getJointPrayerWalkStatus($human);
    expect($status['allowed'])->toBeFalse()
        ->and($status['walks_today'])->toBe(1)
        ->and($status['reason'])->toContain('only 1 walk is allowed per day');

    // 2nd walk on same Day 1 is blocked by DomainException
    expect(fn () => $service->conductJointPrayerWalk($human, $ai, $ward))
        ->toThrow(DomainException::class, 'only 1 walk is allowed per day');
});

test('joint prayer walk is restricted to three times per week', function () {
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
        'action_points' => 12,
        'current_game_month' => 1,
        'current_game_day' => 1, // Week 1 (Days 1-7)
    ]);

    $ai = User::factory()->create([
        'name' => 'Marcus Silva',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'mercy_catalyst',
    ]);

    $service = app(AiColaborerService::class);

    // Walk 1 on Day 1
    $service->conductJointPrayerWalk($human, $ai, $ward);

    // Advance to Day 2: Walk 2
    $human->update(['current_game_day' => 2, 'action_points' => 12]);
    $service->conductJointPrayerWalk($human, $ai, $ward);

    // Advance to Day 3: Walk 3 (reaches weekly limit 3/3)
    $human->update(['current_game_day' => 3, 'action_points' => 12]);
    $service->conductJointPrayerWalk($human, $ai, $ward);

    // Advance to Day 4 (still Week 1)
    $human->update(['current_game_day' => 4, 'action_points' => 12]);
    $status = $service->getJointPrayerWalkStatus($human);

    expect($status['allowed'])->toBeFalse()
        ->and($status['walks_this_week'])->toBe(3)
        ->and($status['remaining_this_week'])->toBe(0)
        ->and($status['reason'])->toContain('only 3 walks are allowed per week');

    // Attempting a 4th walk in the same week throws DomainException
    expect(fn () => $service->conductJointPrayerWalk($human, $ai, $ward))
        ->toThrow(DomainException::class, 'only 3 walks are allowed per week');
});

test('iron on iron is restricted to once per person per day and max 3 per day total', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
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
        'spiritual_openness' => 35,
    ]);

    $human = User::factory()->create([
        'name' => 'Human Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'action_points' => 10,
        'current_game_month' => 1,
        'current_game_day' => 1,
    ]);

    $sarah = User::factory()->create([
        'name' => 'Sarah Jenkins',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'campus_sower',
    ]);

    $marcus = User::factory()->create([
        'name' => 'Marcus Silva',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'mercy_catalyst',
    ]);

    $kenji = User::factory()->create([
        'name' => 'Kenji Yamada',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'discipleship_trainer',
    ]);

    $service = app(AiColaborerService::class);

    // 1. First IoI with Sarah on Day 1 succeeds (1/3 today)
    $res1 = $service->conductIronOnIron($human, $sarah);
    expect($res1['bonus_ap'])->toBe(2);

    // 2. Second IoI with Sarah on Day 1 fails (per-person limit)
    expect(fn () => $service->conductIronOnIron($human, $sarah))
        ->toThrow(DomainException::class, 'already conducted Iron on Iron with Sarah Jenkins today');

    // 3. IoI with Marcus on Day 1 succeeds (2/3 today)
    $res2 = $service->conductIronOnIron($human, $marcus);
    expect($res2['bonus_ap'])->toBe(2);

    // 4. IoI with Kenji on Day 1 succeeds (3/3 today)
    $res3 = $service->conductIronOnIron($human, $kenji);
    expect($res3['bonus_ap'])->toBe(2);

    // 5. Attempting 4th IoI on Day 1 fails (max 3/day)
    $anotherColaborer = User::factory()->create([
        'name' => 'Jun Tanaka',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
    ]);

    expect(fn () => $service->conductIronOnIron($human, $anotherColaborer))
        ->toThrow(DomainException::class, 'max 3 sessions allowed per day');

    // 6. Advance to Day 2: Sarah can be sharpened again
    $human->update(['current_game_day' => 2]);
    $statusDay2 = $service->getIronOnIronStatus($human, $sarah);
    expect($statusDay2['allowed'])->toBeTrue()
        ->and($statusDay2['total_today'])->toBe(0)
        ->and($statusDay2['remaining_today'])->toBe(3);

    $resDay2 = $service->conductIronOnIron($human, $sarah);
    expect($resDay2['bonus_ap'])->toBe(2);
});

test('action queue planner enforces iron on iron day caps for co-laborers and resident npcs', function () {
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
        'spiritual_openness' => 35,
    ]);

    $human = User::factory()->create([
        'name' => 'Human Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'action_points' => 20,
        'current_game_month' => 1,
        'current_game_day' => 1,
    ]);

    $sarah = User::factory()->create([
        'name' => 'Sarah Jenkins',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'colaborer_archetype' => 'campus_sower',
    ]);

    $npcLeader1 = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenzo Takahashi',
        'name_ja' => '高橋健三',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
        'lat' => 35.1667,
        'lng' => 136.9667,
    ]);

    $npcLeader2 = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Aoi Sato',
        'name_ja' => '佐藤葵',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
        'lat' => 35.1667,
        'lng' => 136.9667,
    ]);

    $this->actingAs($human);

    // 1. Conduct IoI with Sarah in Livewire
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->call('conductIronOnIron', $sarah->id)
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'success';
        });

    // 2. Second IoI with Sarah in Livewire must be blocked by rate limit
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->call('conductIronOnIron', $sarah->id)
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'error' && str_contains($data['message'] ?? '', 'already conducted Iron on Iron with Sarah Jenkins today');
        });

    // 3. Queue IoI with NPC 1 (2nd session today)
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $npcLeader1->id)
        ->set('selectedActionType', MinistryActionType::IronOnIron->value)
        ->call('queueAction')
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'success';
        });

    // 4. Queuing IoI again with NPC 1 today must be blocked
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $npcLeader1->id)
        ->set('selectedActionType', MinistryActionType::IronOnIron->value)
        ->call('queueAction')
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'error' && str_contains($data['message'] ?? '', 'already conducted Iron on Iron with Kenzo Takahashi');
        });

    // 5. Queue IoI with NPC 2 (3rd session today)
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $npcLeader2->id)
        ->set('selectedActionType', MinistryActionType::IronOnIron->value)
        ->call('queueAction')
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'success';
        });

    // 6. Attempting 4th IoI (e.g. with another co-laborer) must be blocked by daily limit (3/day)
    $marcus = User::factory()->create([
        'name' => 'Marcus Silva',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
    ]);

    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->call('conductIronOnIron', $marcus->id)
        ->assertDispatched('notify', function ($name, $params): bool {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'error' && str_contains($data['message'] ?? '', 'max 3 sessions allowed per day');
        });
});
