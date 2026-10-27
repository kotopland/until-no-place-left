<?php

declare(strict_types=1);

use App\Enums\BaptismStatus;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\MovementCelebration;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('movement celebration record helpers create celebration records with complete details', function () {
    $session = GameSession::create([
        'name' => 'Celebration Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1678,
        'longitude' => 136.9639,
        'population' => 160000,
        'spiritual_openness' => 35,
    ]);

    $user = User::factory()->create([
        'name' => 'Missionary Paul',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Hana Mori',
        'name_ja' => '森 ハナ',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'trust_level' => 80,
        'lat' => 35.1678,
        'lng' => 136.9639,
    ]);

    // Test recordSalvation
    $salvation = MovementCelebration::recordSalvation(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Chikusa-ku (千種区)',
        personName: 'Hana Mori (森 ハナ)',
        disciplerName: $user->name,
        details: 'Hana repented and believed into Christ after months of Bible study!',
        generation: 1,
        gameMonth: 2,
        gameWeek: 1,
        userId: $user->id,
        npcId: $npc->id,
    );

    expect($salvation->type)->toBe('salvation')
        ->and($salvation->headline)->toBe('Hana Mori (森 ハナ) has repented and accepted Jesus Christ as Lord!')
        ->and($salvation->ward_name)->toBe('Chikusa-ku (千種区)')
        ->and($salvation->person_name)->toBe('Hana Mori (森 ハナ)')
        ->and($salvation->discipler_name)->toBe('Missionary Paul')
        ->and($salvation->is_dismissed)->toBeFalse();

    // Test recordBaptism
    $baptism = MovementCelebration::recordBaptism(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Chikusa-ku (千種区)',
        personName: 'Hana Mori (森 ハナ)',
        disciplerName: $user->name,
        details: 'Hana stepped into the waters of baptism in Chikusa-ku!',
        generation: 1,
        gameMonth: 2,
        gameWeek: 2,
        userId: $user->id,
        npcId: $npc->id,
    );

    expect($baptism->type)->toBe('baptism')
        ->and($baptism->headline)->toBe('Hana Mori (森 ハナ) was publicly baptized in water!')
        ->and($baptism->scripture_ref)->toContain('Matthew 28:19');

    // Test recordChurchPlanted
    $church = Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => "Hana's Acts 2 Church Circle (G1)",
        'leader_npc_id' => $npc->id,
        'generation' => 1,
        'elements_json' => [],
        'is_healthy_church' => false,
        'health_score' => 70,
        'attenders_count' => 6,
        'believers_count' => 4,
        'baptized_count' => 3,
        'is_dissolved' => false,
        'started_by_name' => $user->name,
        'lat' => 35.1678,
        'lng' => 136.9639,
    ]);

    $churchPlant = MovementCelebration::recordChurchPlanted(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Chikusa-ku (千種区)',
        churchName: $church->name,
        leaderName: 'Hana Mori (森 ハナ)',
        startedByName: $user->name,
        details: 'A new simple church has been birthed in Chikusa-ku!',
        generation: 1,
        gameMonth: 3,
        gameWeek: 1,
        userId: $user->id,
        npcId: $npc->id,
        churchId: $church->id,
    );

    expect($churchPlant->type)->toBe('church_planted')
        ->and($churchPlant->headline)->toContain("Hana's Acts 2 Church Circle (G1) has been established in Chikusa-ku (千種区)!")
        ->and($churchPlant->church_id)->toBe($church->id);

    // Test recordGroupFormed
    $group = MovementCelebration::recordGroupFormed(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Chikusa-ku (千種区)',
        personName: 'Hana Mori (森 ハナ)',
        personRole: 'Discovery Group Host',
        disciplerName: $user->name,
        details: 'A new Discovery Bible Group has formed in Chikusa-ku!',
        generation: 1,
        gameMonth: 3,
        gameWeek: 2,
        userId: $user->id,
        npcId: $npc->id,
    );

    expect($group->type)->toBe('group_formed')
        ->and($group->headline)->toContain('A new SWORD Bible study group gathered around Hana Mori (森 ハナ)!');
});

test('engine automatically triggers salvation celebration when seeker prays prayer of salvation', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Engine Celebration Session',
        'current_game_month' => 4,
        'current_game_day' => 1,
        'calendar_date' => '2026-07-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.17,
        'longitude' => 136.90,
    ]);

    $user = User::factory()->create([
        'name' => 'Missionary David',
        'current_game_session_id' => $session->id,
        'action_points' => 20,
        'max_action_points' => 20,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健二',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 60,
        'seeking_months' => 4,
        'target_seeking_months' => 4,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::PraySalvation,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 4,
        'game_week' => 1,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $engine->resolveSingleAction($action, 4);

    $celebration = MovementCelebration::where('game_session_id', $session->id)
        ->where('type', 'salvation')
        ->first();

    expect($celebration)->not->toBeNull()
        ->and($celebration->person_name)->toContain('Kenji Tanaka')
        ->and($celebration->ward_name)->toContain('Naka-ku')
        ->and($celebration->discipler_name)->toBe('Missionary David')
        ->and($celebration->is_dismissed)->toBeFalse();
});

test('engine automatically triggers church planted celebration when acts 2 church circle is formed', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Church Plant Celebration Session',
        'current_game_month' => 6,
        'current_game_day' => 1,
        'calendar_date' => '2026-09-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'latitude' => 35.175,
        'longitude' => 137.00,
    ]);

    $user = User::factory()->create([
        'name' => 'Catalyst John',
        'current_game_session_id' => $session->id,
        'action_points' => 20,
        'max_action_points' => 20,
    ]);

    $leaderNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Takeshi Sato',
        'name_ja' => '佐藤 武',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'trust_level' => 85,
        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
        'baptism_status' => BaptismStatus::Baptized,
        'generation' => 1,
        'lat' => 35.175,
        'lng' => 137.00,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $leaderNpc->id,
        'action_type' => MinistryActionType::ChurchCircles,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 6,
        'game_week' => 2,
        'game_day' => 8,
        'ap_cost' => 3,
    ]);

    $engine->resolveSingleAction($action, 6);

    $celebration = MovementCelebration::where('game_session_id', $session->id)
        ->where('type', 'church_planted')
        ->first();

    expect($celebration)->not->toBeNull()
        ->and($celebration->person_name)->toContain('Takeshi Sato')
        ->and($celebration->ward_name)->toContain('Meito-ku')
        ->and($celebration->discipler_name)->toBe('Catalyst John')
        ->and($celebration->is_dismissed)->toBeFalse();
});

test('action queue planner displays celebratory info banner and allows dismissing celebration', function () {
    $session = GameSession::create([
        'name' => 'Planner Banner Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Showa-ku',
        'name_ja' => '昭和区',
        'slug' => 'showa-ku',
        'latitude' => 35.148,
        'longitude' => 136.936,
        'population' => 110000,
        'spiritual_openness' => 40,
    ]);

    $user = User::factory()->create([
        'name' => 'Missionary Sarah',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
        'action_points' => 20,
        'weekly_action_points' => 20,
    ]);

    $this->actingAs($user);

    $celebration = MovementCelebration::recordSalvation(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Showa-ku (昭和区)',
        personName: 'Yuki Takahashi (高橋 由紀)',
        disciplerName: $user->name,
        details: 'Yuki has crossed from death to life after finding peace in Christ!',
        generation: 1,
        gameMonth: 2,
        gameWeek: 3,
        userId: $user->id,
    );

    // Initial render should show the celebration banner
    $component = Livewire::test(ActionQueuePlanner::class)
        ->assertSee('New Believer Saved in Nagoya!')
        ->assertSee('Showa-ku (昭和区)')
        ->assertSee('Yuki Takahashi (高橋 由紀)')
        ->assertSee('Missionary Sarah')
        ->assertSee('Luke 15:7')
        ->assertSee('Rejoice! (ハレルヤ)');

    // Dismissing the celebration should mark it dismissed and hide banner
    $component->call('dismissCelebration', $celebration->id)
        ->assertDontSee('New Believer Saved in Nagoya!');

    $celebration->refresh();
    expect($celebration->is_dismissed)->toBeTrue();
});

test('nagoya movement dashboard renders celebration banner on non-planner tabs and handles dismiss', function () {
    $session = GameSession::create([
        'name' => 'Dashboard Banner Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Mizuho-ku',
        'name_ja' => '瑞穂区',
        'slug' => 'mizuho-ku',
        'latitude' => 35.132,
        'longitude' => 136.935,
        'population' => 105000,
        'spiritual_openness' => 38,
    ]);

    $user = User::factory()->create([
        'name' => 'Catalyst Luke',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
        'action_points' => 20,
        'weekly_action_points' => 20,
    ]);

    $this->actingAs($user);

    $celebration = MovementCelebration::recordBaptism(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Mizuho-ku (瑞穂区)',
        personName: 'Ren Suzuki (鈴木 蓮)',
        disciplerName: $user->name,
        details: 'Ren was baptized publicly today in Mizuho-ku!',
        generation: 2,
        gameMonth: 3,
        gameWeek: 1,
        userId: $user->id,
    );

    $component = Livewire::test(NagoyaMovementDashboard::class)
        ->set('currentTab', 'churches')
        ->assertSee('Water Baptism Milestone Celebrated!')
        ->assertSee('Mizuho-ku (瑞穂区)')
        ->assertSee('Ren Suzuki (鈴木 蓮)')
        ->assertSee('Catalyst Luke')
        ->assertSee('Matthew 28:19');

    $component->call('dismissCelebration', $celebration->id)
        ->assertDontSee('Water Baptism Milestone Celebrated!');

    $celebration->refresh();
    expect($celebration->is_dismissed)->toBeTrue();
});

test('nagoya movement dashboard handles celebration-dismissed event without throwing binding resolution exception', function () {
    $session = GameSession::create([
        'name' => 'Event Dispatch Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.17,
        'longitude' => 136.90,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
    ]);

    $this->actingAs($user);

    $celebration = MovementCelebration::recordSalvation(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Naka-ku (中区)',
        personName: 'Kenji Tanaka',
        details: 'Saved today!',
        userId: $user->id,
    );

    // Test dispatching without parameter (e.g. from child component or broadcast)
    Livewire::test(NagoyaMovementDashboard::class)
        ->dispatch('celebration-dismissed')
        ->assertSuccessful();

    // Test dispatching with parameter
    Livewire::test(NagoyaMovementDashboard::class)
        ->dispatch('celebration-dismissed', celebrationId: $celebration->id)
        ->assertSuccessful();

    $celebration->refresh();
    expect($celebration->is_dismissed)->toBeTrue();
});
