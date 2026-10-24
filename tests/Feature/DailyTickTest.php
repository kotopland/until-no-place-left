<?php

use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('daily tick does not run if no players are online within the 15-minute threshold', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Offline Session',
        'code' => 'offline-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    // User was active 30 minutes ago (offline)
    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now()->subMinutes(30),
        'days_played_today' => 5,
        'last_played_real_date' => now()->startOfDay(),
    ]);

    $result = $engine->resolveDailyTick($session);

    expect($result)->toBeNull();
    $session->refresh();
    expect($session->current_game_day)->toBe(1)
        ->and($session->days_processed_this_month)->toBe(0);
});

test('daily tick processes game day, advances day counters, and replenishes AP when player is online', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Active Session',
        'code' => 'active-1',
        'current_game_month' => 1,
        'current_game_day' => 5,
        'days_processed_this_month' => 4,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now()->subMinutes(2),
        'days_played_today' => 4,
        'last_played_real_date' => now()->startOfDay(),
        'action_points' => 4,
        'max_action_points' => 12,
        'daily_action_points' => 2,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Takashi Mori',
        'name_ja' => '森 貴志',
        'need_state' => NpcNeedState::Lonely,
        'status' => ReceptivityStatus::Red,
        'trust_level' => 20,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    // Action queued for day 5
    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CasualTalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 5,
        'ap_cost' => 1,
    ]);

    $result = $engine->resolveDailyTick($session);

    expect($result)->not->toBeNull()
        ->and($result->gameDay)->toBe(5)
        ->and($result->actionsProcessed)->toBe(1)
        ->and($result->onlinePlayersCount)->toBe(1);

    $session->refresh();
    $user->refresh();
    $action->refresh();

    // Session advanced to Day 6
    expect($session->current_game_day)->toBe(6)
        ->and($session->days_processed_this_month)->toBe(5)
        ->and($session->last_daily_tick_at)->not->toBeNull()
        ->and($session->next_daily_tick_at)->not->toBeNull();

    // Player incremented played today and received daily AP replenishment
    expect($user->days_played_today)->toBe(5)
        ->and($user->action_points)->toBe(6); // 4 + 2 daily AP

    // Action marked processed
    expect($action->status)->toBe(QueuedActionStatus::Processed);
});

test('user cannot play more than 30 game days per real life day', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Cap Session',
        'code' => 'cap-1',
        'current_game_month' => 1,
        'current_game_day' => 10,
        'days_processed_this_month' => 9,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    // User is online but already played 30 days today
    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now()->subMinutes(1),
        'days_played_today' => 30,
        'last_played_real_date' => now()->startOfDay(),
    ]);

    expect($user->hasReachedDailyPlayLimit)->toBeTrue();

    $result = $engine->resolveDailyTick($session);

    expect($result)->toBeNull();
    $session->refresh();
    expect($session->current_game_day)->toBe(10)
        ->and($session->days_processed_this_month)->toBe(9);
});

test('monthly tick batch processes remaining unplayed days in 30 day month and resets for next month', function () {
    $engine = new FourFieldsEngine;

    // User played 10 days online during the day. 20 days remain unplayed.
    $session = GameSession::create([
        'name' => 'Monthly Batch Session',
        'code' => 'batch-1',
        'current_game_month' => 1,
        'current_game_day' => 11,
        'days_processed_this_month' => 10,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->subMinutes(5), // overdue for monthly tick
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'days_played_today' => 10,
        'action_points' => 2,
        'max_action_points' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健二',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Red,
        'trust_level' => 30,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    // An action that was queued for day 15 (unplayed during active hours)
    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CasualTalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 15,
        'ap_cost' => 1,
    ]);

    expect($session->remainingDaysInMonth)->toBe(20);

    // Run the once-a-day monthly tick
    $result = $engine->resolveGameMonth($session);

    expect($result->actionsProcessed)->toBe(1);

    $session->refresh();
    $user->refresh();
    $action->refresh();

    // Session progressed to Month 2, resetting Day to 1 and processed days to 0
    expect($session->current_game_month)->toBe(2)
        ->and($session->current_game_day)->toBe(1)
        ->and($session->days_processed_this_month)->toBe(0)
        ->and($session->last_monthly_tick_at)->not->toBeNull();

    // User days played reset to 0, AP refreshed
    expect($user->days_played_today)->toBe(0)
        ->and($user->action_points)->toBe(12);

    // Remaining action was processed in the batch
    expect($action->status)->toBe(QueuedActionStatus::Processed);
});

test('process daily tick command executes successfully via artisan', function () {
    $session = GameSession::create([
        'name' => 'Artisan Session',
        'code' => 'artisan-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    User::factory()->create([
        'current_game_session_id' => $session->id,
        'last_active_at' => now()->subMinutes(1),
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
    ]);

    $exitCode = Artisan::call('game:process-daily-tick', ['--force' => true]);

    expect($exitCode)->toBe(0);

    $session->refresh();
    expect($session->current_game_day)->toBe(2)
        ->and($session->days_processed_this_month)->toBe(1);
});
