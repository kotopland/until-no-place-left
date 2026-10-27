<?php

declare(strict_types=1);

use App\Enums\MinistryActionType;
use App\Livewire\ActionQueuePlanner;
use App\Models\GameSession;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);

    $this->session = GameSession::create([
        'name' => 'Rhythm Session',
        'code' => 'rhythm-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(10),
        'is_active' => true,
    ]);

    $this->user = User::factory()->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'action_points' => 10,
        'max_action_points' => 12,
        'daily_action_points' => 10,
        'coop_synergy_score' => 0,
        'total_generational_score' => 0,
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
    ]);
});

test('worker can schedule mentoring and gains 30 points upon day resolution', function () {
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::ReceiveMentoring->value)
        ->assertDispatched('notify');

    $queued = QueuedMonthlyAction::where('user_id', $this->user->id)->first();
    expect($queued)->not->toBeNull()
        ->and($queued->action_type)->toBe(MinistryActionType::ReceiveMentoring)
        ->and($queued->game_day)->toBe(1)
        ->and($queued->ap_cost)->toBe(0);

    // Resolve day tick via engine
    $engine = app(FourFieldsEngine::class);
    $engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->compositeScore)->toBe(30);
});

test('worker can schedule weekly sabbath rest, gaining 25 points and resetting AP', function () {
    $this->user->update(['action_points' => 2]);

    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SabbathRest->value)
        ->assertDispatched('notify');

    $engine = app(FourFieldsEngine::class);
    $engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->compositeScore)->toBe(25)
        ->and($this->user->action_points)->toBe(12);
});

test('worker can schedule 1-week fasting retreat which blocks 1 week and awards 100 points total', function () {
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::FastingRetreat->value)
        ->assertDispatched('notify');

    $queuedCount = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->where('action_type', MinistryActionType::FastingRetreat)
        ->count();

    expect($queuedCount)->toBe(1);

    $weeks = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->pluck('game_week')
        ->sort()
        ->values()
        ->all();

    expect($weeks)->toBe([1]);

    // Advance 1 week
    $engine = app(FourFieldsEngine::class);
    $this->user->update(['current_game_week' => 1, 'current_game_day' => 1]);
    $this->session->update(['current_game_week' => 1, 'current_game_day' => 1]);
    $engine->resolveWeeklyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->compositeScore)->toBe(100);
});

test('worker can schedule 1-week NPL mid-level conference which blocks 1 week and awards 75 points total', function () {
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::MidLevelConference->value)
        ->assertDispatched('notify');

    $queuedCount = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->where('action_type', MinistryActionType::MidLevelConference)
        ->count();

    expect($queuedCount)->toBe(1);

    $weeks = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->pluck('game_week')
        ->sort()
        ->values()
        ->all();

    expect($weeks)->toBe([1]);

    $engine = app(FourFieldsEngine::class);
    $this->user->update(['current_game_week' => 1, 'current_game_day' => 1]);
    $this->session->update(['current_game_week' => 1, 'current_game_day' => 1]);
    $engine->resolveWeeklyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->compositeScore)->toBe(75);
});

test('active worker rhythm blocks scheduling standard field actions', function () {
    // Schedule sabbath on Day 1
    QueuedMonthlyAction::create([
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'game_session_id' => $this->session->id,
        'game_month' => 1,
        'game_day' => 1,
        'action_type' => MinistryActionType::SabbathRest,
        'ap_cost' => 0,
        'status' => 'pending',
    ]);

    // Attempt to queue a field action (e.g. casual talk)
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->set('selectedActionType', MinistryActionType::CasualTalk->value)
        ->set('selectedWardId', $this->ward->id)
        ->call('queueAction')
        ->assertDispatched('notify');

    // Verify casual talk was NOT scheduled
    $casualTalkCount = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->where('action_type', MinistryActionType::CasualTalk)
        ->count();

    expect($casualTalkCount)->toBe(0);
});

test('cancelling a retreat cancels all pending days of the multi-day retreat', function () {
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::MidLevelConference->value);

    $firstAction = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->where('action_type', MinistryActionType::MidLevelConference)
        ->first();

    expect($firstAction)->not->toBeNull();

    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('cancelAction', $firstAction->id)
        ->assertDispatched('notify');

    $remaining = QueuedMonthlyAction::where('user_id', $this->user->id)
        ->where('action_type', MinistryActionType::MidLevelConference)
        ->count();

    expect($remaining)->toBe(0);
});

test('enforces frequency limits and month boundary limits on worker rhythms', function () {
    // 1. Weekly limit: cannot schedule sabbath twice in same week
    QueuedMonthlyAction::create([
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'game_session_id' => $this->session->id,
        'game_month' => 1,
        'game_day' => 2,
        'action_type' => MinistryActionType::SabbathRest,
        'ap_cost' => 0,
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SabbathRest->value)
        ->assertDispatched('notify');

    expect(QueuedMonthlyAction::where('user_id', $this->user->id)->count())->toBe(1);

    // 2. Month boundary limit: cannot schedule fasting retreat once all 4 weeks completed
    $this->user->update(['current_game_week' => 5, 'current_game_day' => 28]);
    $this->session->update(['current_game_week' => 5, 'current_game_day' => 28]);
    QueuedMonthlyAction::query()->delete();

    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::FastingRetreat->value)
        ->assertDispatched('notify');

    expect(QueuedMonthlyAction::where('user_id', $this->user->id)->count())->toBe(0);
});
