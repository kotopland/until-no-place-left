<?php

declare(strict_types=1);

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\NagoyaMapCanvas;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\PrayerWalk;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('prayer walking action type is configured for field 1 entry strategy and does not require target npc', function (): void {
    $action = MinistryActionType::PrayerWalk;

    expect($action->field())->toBe(1)
        ->and($action->fieldName())->toBe('Field 1: Entry / Prayer Walk')
        ->and($action->apCost())->toBe(1)
        ->and($action->requiresTargetNpc())->toBeFalse()
        ->and($action->icon())->toBe('🚶');
});

test('player can queue prayer walk in selected ward without selecting an npc', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'calendar_date' => '2026-04-01',
        'is_active' => true,
        'next_tick_at' => now()->addMinutes(10),
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.1650,
        'center_lng' => 136.9050,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
        'daily_action_points' => 12,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', null)
        ->set('selectedActionType', MinistryActionType::PrayerWalk->value)
        ->set('notes', 'Praying for Sakae and Osu seekers')
        ->call('queueAction')
        ->assertDispatched('action-queued');

    $queued = QueuedMonthlyAction::where('user_id', $user->id)
        ->where('action_type', MinistryActionType::PrayerWalk)
        ->first();

    expect($queued)->not->toBeNull()
        ->and($queued->ward_id)->toBe($ward->id)
        ->and($queued->target_npc_id)->toBeNull()
        ->and($queued->ap_cost)->toBe(1)
        ->and($queued->status)->toBe(QueuedActionStatus::Pending);

    $user->refresh();
    expect($user->action_points)->toBe(11);
});

test('day tick resolves prayer walk, generates street route coords and records prayer walk', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'calendar_date' => '2026-04-01',
        'is_active' => true,
        'next_tick_at' => now()->addMinutes(10),
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.1650,
        'center_lng' => 136.9050,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 11,
        'max_action_points' => 12,
        'daily_action_points' => 12,
        'total_generational_score' => 0,
        'last_active_at' => now(),
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Resident Seeker',
        'name_ja' => '求道者',
        'status' => ReceptivityStatus::Red,
        'trust_level' => 20,
        'lat' => 35.165,
        'lng' => 136.905,
        'is_person_of_peace' => false,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => null,
        'action_type' => MinistryActionType::PrayerWalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 1,
    ]);

    $engine = new FourFieldsEngine;
    $result = $engine->resolveDailyTick($session, force: true);

    expect($result)->not->toBeNull()
        ->and($result->actionsProcessed)->toBe(1);

    // Verify PrayerWalk record was created
    $walk = PrayerWalk::where('game_session_id', $session->id)
        ->where('ward_id', $ward->id)
        ->first();

    expect($walk)->not->toBeNull()
        ->and($walk->user_id)->toBe($user->id)
        ->and($walk->game_month)->toBe(1)
        ->and($walk->game_day)->toBe(1)
        ->and($walk->route_name)->toContain('Sakae & Hisaya Odori')
        ->and($walk->path_coords)->toBeArray()
        ->and(count($walk->path_coords))->toBeGreaterThanOrEqual(4);

    // Verify first coordinate has lat and lng
    expect($walk->path_coords[0])->toBeArray()
        ->and(count($walk->path_coords[0]))->toBe(2);

    // Ward openness softened
    $ward->refresh();
    expect($ward->spiritual_openness)->toBeGreaterThan(20);

    // Resident trust softened
    $npc->refresh();
    expect($npc->trust_level)->toBeGreaterThan(20);
});

test('successive prayer walks in same ward increase chance to discover a person of peace', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'current_game_day' => 5,
        'calendar_date' => '2026-04-05',
        'is_active' => true,
        'next_tick_at' => now()->addMinutes(10),
    ]);

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'center_lat' => 35.1750,
        'center_lng' => 137.0100,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
        'daily_action_points' => 12,
    ]);

    // Pre-create 3 previous prayer walks in this ward
    for ($i = 1; $i <= 3; $i++) {
        PrayerWalk::create([
            'game_session_id' => $session->id,
            'user_id' => $user->id,
            'ward_id' => $ward->id,
            'game_month' => 1,
            'game_day' => $i,
            'route_name' => "Route {$i}",
            'path_coords' => [[35.175, 137.010], [35.178, 137.015]],
            'pop_discovered' => false,
        ]);
    }

    $existingResident = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Unreached Resident',
        'name_ja' => '住民',
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
        'lat' => 35.175,
        'lng' => 137.010,
        'is_person_of_peace' => false,
    ]);

    // 4th prayer walk in the ward: chance is min(90, 35 + 3*15) = 80%!
    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => null,
        'action_type' => MinistryActionType::PrayerWalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 5,
        'ap_cost' => 1,
    ]);

    $engine = new FourFieldsEngine;
    $outcome = $engine->resolveSingleAction($action, 1);

    expect(PrayerWalk::where('game_session_id', $session->id)->count())->toBe(4);

    // If Person of Peace was discovered on this high-probability walk:
    if ($outcome->statusChanged) {
        $existingResident->refresh();
        expect($existingResident->is_person_of_peace)->toBeTrue()
            ->and($existingResident->status)->toBe(ReceptivityStatus::Yellow);

        $latestWalk = PrayerWalk::latest('id')->first();
        expect($latestWalk->pop_discovered)->toBeTrue()
            ->and($latestWalk->discovered_npc_id)->toBe($existingResident->id);
    }
});

test('nagoya map canvas loads prayer walks data and toggles prayer walk layer', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'calendar_date' => '2026-04-01',
        'is_active' => true,
        'next_tick_at' => now()->addMinutes(10),
    ]);

    $ward = Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'center_lat' => 35.1666,
        'center_lng' => 136.9500,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
    ]);

    PrayerWalk::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'game_month' => 1,
        'game_day' => 1,
        'route_name' => 'Motoyama Station & Nagoya Univ Campus Loop',
        'path_coords' => [[35.166, 136.950], [35.170, 136.955]],
        'pop_discovered' => true,
    ]);

    Livewire::test(NagoyaMapCanvas::class)
        ->assertSee('Prayer Walks (1)')
        ->assertSee('Motoyama Station')
        ->assertSet('showPrayerWalks', true)
        ->call('toggleLayer', 'prayer_walks')
        ->assertSet('showPrayerWalks', false);
});

test('game:reset command deletes all prayer walk records', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 2,
        'current_game_day' => 10,
        'calendar_date' => '2026-05-10',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.1650,
        'center_lng' => 136.9050,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
    ]);

    PrayerWalk::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'game_month' => 2,
        'game_day' => 10,
        'route_name' => 'Sakae Avenue Circuit',
        'path_coords' => [[35.165, 136.905], [35.168, 136.908]],
        'pop_discovered' => false,
    ]);

    expect(PrayerWalk::where('game_session_id', $session->id)->count())->toBe(1);

    $this->artisan('game:reset', ['--force' => true])
        ->assertSuccessful();

    expect(PrayerWalk::where('game_session_id', $session->id)->count())->toBe(0);
});
