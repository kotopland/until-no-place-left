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

beforeEach(function () {
    Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);
});

test('logged in player can manually advance day tick via action queue planner', function () {
    $session = GameSession::create([
        'name' => 'Manual Advance Session',
        'code' => 'adv-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now(),
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
        'action_points' => 2,
        'max_action_points' => 26,
        'weekly_action_points' => 20,
        'daily_action_points' => 20,
    ]);

    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('advanceDay', true)
        ->assertDispatched('day-advanced')
        ->assertDispatched('notify');

    $session->refresh();
    $user->refresh();

    expect($session->current_game_day)->toBe(2)
        ->and($session->days_processed_this_month)->toBe(1)
        ->and($user->days_played_today)->toBe(1)
        ->and($user->action_points)->toBe(22); // 2 + 20 replenished
});

test('logged in player can manually advance day tick via nagoya movement dashboard', function () {
    $session = GameSession::create([
        'name' => 'Dashboard Advance Session',
        'code' => 'adv-2',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now(),
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
        'action_points' => 5,
        'max_action_points' => 26,
        'weekly_action_points' => 20,
        'daily_action_points' => 20,
    ]);

    Livewire::actingAs($user)
        ->test(NagoyaMovementDashboard::class)
        ->call('advanceDay', true)
        ->assertDispatched('day-advanced')
        ->assertDispatched('notify');

    $session->refresh();
    $user->refresh();

    expect($session->current_game_day)->toBe(2)
        ->and($session->days_processed_this_month)->toBe(1)
        ->and($user->days_played_today)->toBe(1)
        ->and($user->action_points)->toBe(25); // 5 + 20 replenished
});

test('auto day tick triggered after countdown passes successfully without manual notifications', function () {
    $session = GameSession::create([
        'name' => 'Auto Tick Session',
        'code' => 'adv-3',
        'current_game_month' => 1,
        'current_game_week' => 3,
        'current_game_day' => 3,
        'weeks_processed_this_month' => 2,
        'days_processed_this_month' => 2,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->subSecond(),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'current_game_week' => 3,
        'current_game_day' => 3,
        'weeks_processed_this_month' => 2,
        'days_processed_this_month' => 2,
        'last_active_at' => now(),
        'weeks_played_today' => 2,
        'days_played_today' => 2,
        'last_played_real_date' => now()->startOfDay(),
        'action_points' => 4,
        'max_action_points' => 26,
        'weekly_action_points' => 20,
        'daily_action_points' => 20,
    ]);

    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('advanceDay', false)
        ->assertDispatched('day-advanced');

    $session->refresh();
    $user->refresh();

    expect($session->current_game_day)->toBe(4)
        ->and($session->days_processed_this_month)->toBe(3)
        ->and($user->days_played_today)->toBe(3);
});

test('player is prevented from playing more than 30 days per real life day', function () {
    $session = GameSession::create([
        'name' => 'Daily Cap Session',
        'code' => 'adv-4',
        'current_game_month' => 1,
        'current_game_day' => 10,
        'days_processed_this_month' => 9,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'current_game_day' => 10,
        'days_processed_this_month' => 9,
        'last_active_at' => now(),
        'days_played_today' => 30, // Already hit the 30-day cap today
        'last_played_real_date' => now()->startOfDay(),
        'action_points' => 12,
        'max_action_points' => 12,
        'daily_action_points' => 2,
    ]);

    expect($user->hasReachedDailyPlayLimit)->toBeTrue();

    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('advanceDay', true)
        ->assertDispatched('notify', function (string $name, array $params) {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'warning' && str_contains($data['message'] ?? '', 'Daily play limit reached');
        });

    $session->refresh();
    $user->refresh();

    // Session and user counters must remain unchanged
    expect($session->current_game_day)->toBe(10)
        ->and($session->days_processed_this_month)->toBe(9)
        ->and($user->days_played_today)->toBe(30);

    // Dashboard also respects the 30 day limit
    Livewire::actingAs($user)
        ->test(NagoyaMovementDashboard::class)
        ->call('advanceDay', true)
        ->assertDispatched('notify', function (string $name, array $params) {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'warning' && str_contains($data['message'] ?? '', 'Daily play limit reached');
        });
});

test('advanceDay stops when 30 days have been processed in the current game month', function () {
    $session = GameSession::create([
        'name' => 'Month Cap Session',
        'code' => 'adv-5',
        'current_game_month' => 1,
        'current_game_day' => 30,
        'days_processed_this_month' => 30,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'current_game_month' => 1,
        'current_game_day' => 30,
        'days_processed_this_month' => 30, // All 30 days in month already finished
        'last_active_at' => now(),
        'days_played_today' => 15,
        'last_played_real_date' => now()->startOfDay(),
        'action_points' => 6,
        'max_action_points' => 12,
        'daily_action_points' => 2,
    ]);

    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('advanceDay', true)
        ->assertDispatched('notify', function (string $name, array $params) {
            $data = $params[0] ?? $params;

            return ($data['type'] ?? '') === 'info' && (str_contains($data['message'] ?? '', 'has completed all 4 weeks') || str_contains($data['message'] ?? '', 'has completed all 30 days'));
        });

    $user->refresh();
    expect($user->current_game_day)->toBe(30)
        ->and($user->days_processed_this_month)->toBe(30);
});

test('manual day advance by one player does not change day or replenish AP for other players', function () {
    $session = GameSession::create([
        'name' => 'Multiplayer Independence Session',
        'code' => 'adv-multi',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $playerA = User::factory()->create([
        'name' => 'Player A',
        'current_game_session_id' => $session->id,
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'days_played_today' => 0,
        'last_active_at' => now(),
        'action_points' => 4,
        'max_action_points' => 26,
        'weekly_action_points' => 20,
        'daily_action_points' => 20,
    ]);

    $playerB = User::factory()->create([
        'name' => 'Player B',
        'current_game_session_id' => $session->id,
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'days_played_today' => 0,
        'last_active_at' => now()->subMinutes(2),
        'action_points' => 6,
        'max_action_points' => 26,
        'weekly_action_points' => 20,
        'daily_action_points' => 20,
    ]);

    // Player A advances their day manually
    Livewire::actingAs($playerA)
        ->test(ActionQueuePlanner::class)
        ->call('advanceDay', true)
        ->assertDispatched('day-advanced');

    $playerA->refresh();
    $playerB->refresh();

    // Player A advanced
    expect($playerA->current_game_day)->toBe(2)
        ->and($playerA->days_processed_this_month)->toBe(1)
        ->and($playerA->days_played_today)->toBe(1)
        ->and($playerA->action_points)->toBe(24); // 4 + 20

    // Player B is completely unchanged!
    expect($playerB->current_game_day)->toBe(1)
        ->and($playerB->days_processed_this_month)->toBe(0)
        ->and($playerB->days_played_today)->toBe(0)
        ->and($playerB->action_points)->toBe(6); // Still 6
});

test('player cannot manually advance to next month and receives server midnight notice', function () {
    $session = GameSession::create([
        'name' => 'Month Advance Session',
        'code' => 'adv-month',
        'current_game_month' => 1,
        'current_game_week' => 4,
        'current_game_day' => 30,
        'weeks_processed_this_month' => 4,
        'days_processed_this_month' => 30,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'current_game_month' => 1,
        'current_game_week' => 4,
        'current_game_day' => 30,
        'weeks_processed_this_month' => 4,
        'days_processed_this_month' => 30,
        'weeks_played_today' => 4,
        'days_played_today' => 30,
        'last_active_at' => now(),
        'action_points' => 1,
        'max_action_points' => 26,
    ]);

    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('advanceMonth')
        ->assertNotDispatched('open-month-review')
        ->assertDispatched('notify', fn ($name, $params) => ($params['type'] ?? $params[0]['type'] ?? null) === 'info'
            && str_contains(($params['message'] ?? $params[0]['message'] ?? ''), 'midnight by the server tick')
        );

    $user->refresh();

    // Player month remains unchanged - only the server advances at midnight
    expect($user->current_game_month)->toBe(1)
        ->and($user->action_points)->toBe(1);
});

test('scheduled month advance skips players who have already changed their month today', function () {
    $session = GameSession::create([
        'name' => 'System Tick Guard Session',
        'code' => 'adv-guard',
        'current_game_month' => 1,
        'current_game_day' => 30,
        'days_processed_this_month' => 30,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->subMinute(),
        'is_active' => true,
    ]);

    // Player A already advanced to Month 2 manually today
    $playerA = User::factory()->create([
        'name' => 'Early Bird Player',
        'current_game_session_id' => $session->id,
        'current_game_month' => 2,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'last_monthly_tick_at' => now(),
        'days_played_today' => 30,
    ]);

    // Player B is still on Month 1
    $playerB = User::factory()->create([
        'name' => 'Regular Player',
        'current_game_session_id' => $session->id,
        'current_game_month' => 1,
        'current_game_day' => 18,
        'days_processed_this_month' => 17,
        'last_monthly_tick_at' => now()->subDay(),
        'days_played_today' => 10,
    ]);

    expect($playerA->hasChangedMonthToday)->toBeTrue();
    expect($playerB->hasChangedMonthToday)->toBeFalse();

    // Run system monthly resolution
    $engine = app(FourFieldsEngine::class);
    $engine->resolveGameMonth($session);

    $playerA->refresh();
    $playerB->refresh();

    // Player A was NOT advanced again! Still on Month 2. Days played reset for new day.
    expect($playerA->current_game_month)->toBe(2)
        ->and($playerA->days_played_today)->toBe(0);

    // Player B was advanced from Month 1 to Month 2 by the system!
    expect($playerB->current_game_month)->toBe(2)
        ->and($playerB->current_game_day)->toBe(1)
        ->and($playerB->days_processed_this_month)->toBe(0)
        ->and($playerB->days_played_today)->toBe(0);
});

test('automatic advanceDay in ActionQueuePlanner does not advance if daily tick is not yet due', function () {
    $session = GameSession::create([
        'name' => 'Auto Tick Test Session',
        'code' => 'auto-tick-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'current_game_day' => 1,
        'last_active_at' => now(),
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
        'next_daily_tick_at' => now()->addMinutes(8), // In the future, not due yet!
        'action_points' => 2,
        'max_action_points' => 12,
        'daily_action_points' => 2,
    ]);

    // Automatic tick ($isManual = false) should be ignored because tick is not due
    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('advanceDay', false);

    $user->refresh();
    expect($user->current_game_day)->toBe(1)
        ->and($user->days_played_today)->toBe(0);
});

test('automatic advanceDay in NagoyaMovementDashboard does not advance if daily tick is not yet due', function () {
    $session = GameSession::create([
        'name' => 'Auto Tick Dashboard Session',
        'code' => 'auto-tick-dash',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'current_game_day' => 1,
        'last_active_at' => now(),
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
        'next_daily_tick_at' => now()->addMinutes(8), // In the future
        'action_points' => 2,
        'max_action_points' => 12,
        'daily_action_points' => 2,
    ]);

    // Automatic tick ($isManual = false) should be ignored
    Livewire::actingAs($user)
        ->test(NagoyaMovementDashboard::class)
        ->call('advanceDay', false);

    $user->refresh();
    expect($user->current_game_day)->toBe(1)
        ->and($user->days_played_today)->toBe(0);
});

test('touchHeartbeat in ActionQueuePlanner updates active timestamp cleanly', function () {
    $session = GameSession::create([
        'name' => 'Heartbeat Session',
        'code' => 'hb-sess-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now()->subMinutes(10),
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
    ]);

    Livewire::actingAs($user)
        ->test(ActionQueuePlanner::class)
        ->call('touchHeartbeat');

    $user->refresh();
    expect($user->last_active_at->diffInSeconds(now()))->toBeLessThan(5);
});
