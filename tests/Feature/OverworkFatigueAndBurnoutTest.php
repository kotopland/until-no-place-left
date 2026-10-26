<?php

declare(strict_types=1);

use App\Enums\ChurchElement;
use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\ChurchGenerationMap;
use App\Models\Church;
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
        'name' => 'Burnout Test Session',
        'code' => 'burnout-1',
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
        'action_points' => 12,
        'max_action_points' => 12,
        'daily_action_points' => 12,
        'consecutive_exhaustion_days' => 0,
        'fatigue_penalty_ap' => 0,
        'is_burned_out' => false,
        'burned_out_until_game_month' => null,
        'burnout_count' => 0,
        'total_generational_score' => 0,
        'coop_synergy_score' => 0,
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
    ]);

    $this->engine = app(FourFieldsEngine::class);
});

test('exhausting ap consecutively reduces available ap on subsequent days', function () {
    // Day 1: End day with 0 AP (1st exhaustion day)
    $this->user->update(['action_points' => 0]);
    $this->engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->consecutive_exhaustion_days)->toBe(1)
        ->and($this->user->fatigue_penalty_ap)->toBe(0)
        ->and($this->user->action_points)->toBe(12);

    // Day 2: End day with 1 AP (<= 1 is exhausted, 2nd consecutive day)
    $this->user->update(['action_points' => 1]);
    $this->engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->consecutive_exhaustion_days)->toBe(2)
        ->and($this->user->fatigue_penalty_ap)->toBe(2)
        ->and($this->user->action_points)->toBe(10); // 12 - 2

    // Day 3: End day with 0 AP (3rd consecutive day)
    $this->user->update(['action_points' => 0]);
    $this->engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->consecutive_exhaustion_days)->toBe(3)
        ->and($this->user->fatigue_penalty_ap)->toBe(4)
        ->and($this->user->action_points)->toBe(8); // 12 - 4
});

test('exhausting ap for 5 consecutive days triggers 3-month burnout sabbatical', function () {
    // Simulate 4 consecutive exhausted days
    $this->user->update([
        'consecutive_exhaustion_days' => 4,
        'fatigue_penalty_ap' => 6,
        'action_points' => 0,
    ]);

    // 5th consecutive exhaustion day
    $this->engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->is_burned_out)->toBeTrue()
        ->and($this->user->burned_out_until_game_month)->toBe(4) // month 1 + 3
        ->and($this->user->burnout_count)->toBe(1)
        ->and($this->user->action_points)->toBe(0)
        ->and($this->user->effectiveDailyActionPoints)->toBe(0);
});

test('taking sabbath rest clears consecutive exhaustion and resets fatigue to zero', function () {
    // Player has 3 consecutive exhausted days and 4 AP penalty
    $this->user->update([
        'consecutive_exhaustion_days' => 3,
        'fatigue_penalty_ap' => 4,
        'action_points' => 0,
    ]);

    // Schedule Sabbath Rest
    QueuedMonthlyAction::create([
        'user_id' => $this->user->id,
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'action_type' => MinistryActionType::SabbathRest,
        'game_month' => $this->user->current_game_month,
        'game_day' => $this->user->current_game_day,
        'target_demographic' => 'general',
        'status' => QueuedActionStatus::Pending,
        'action_points_spent' => 0,
    ]);

    $this->engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->consecutive_exhaustion_days)->toBe(0)
        ->and($this->user->fatigue_penalty_ap)->toBe(0)
        ->and($this->user->action_points)->toBe(12);
});

test('ending day with leftover ap clears overwork fatigue and restores full ap', function () {
    $this->user->update([
        'consecutive_exhaustion_days' => 3,
        'fatigue_penalty_ap' => 4,
        'action_points' => 5, // Leftover AP (> 1)
    ]);

    $this->engine->resolveDailyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->consecutive_exhaustion_days)->toBe(0)
        ->and($this->user->fatigue_penalty_ap)->toBe(0)
        ->and($this->user->action_points)->toBe(12);
});

test('burned out player cannot queue actions in action queue planner or coaching in gen map', function () {
    $this->user->update([
        'is_burned_out' => true,
        'burned_out_until_game_month' => 4,
        'action_points' => 0,
    ]);

    // Cannot schedule rhythms
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SabbathRest->value)
        ->assertDispatched('notify');

    expect(QueuedMonthlyAction::where('user_id', $this->user->id)->count())->toBe(0);

    // Create a church for coaching test
    $church = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'generation' => 1,
        'name' => 'Test Oikos Church',
        'is_active' => true,
        'elements_json' => [
            ChurchElement::Baptism->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
            ChurchElement::Fellowship->value,
            ChurchElement::Communion->value,
            ChurchElement::GivingMercy->value,
            ChurchElement::LocalLeaders->value,
        ],
        'health_score' => 70,
        'attenders_count' => 8,
        'believers_count' => 6,
        'baptized_count' => 4,
        'is_dissolved' => false,
        'lat' => 35.17,
        'lng' => 136.90,
        'founded_game_month' => 1,
    ]);

    // Cannot queue church coaching in ChurchGenerationMap
    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->call('queueCoachingForChurch', $church->id)
        ->assertDispatched('notify');

    expect(QueuedMonthlyAction::where('user_id', $this->user->id)->count())->toBe(0);
});

test('player recovers from burnout after 3 months', function () {
    $this->user->update([
        'is_burned_out' => true,
        'burned_out_until_game_month' => 4,
        'action_points' => 0,
        'daily_action_points' => 12,
    ]);

    // Advance user to month 4
    $this->user->update(['current_game_month' => 4]);

    // Month resolution triggers recovery
    $this->engine->resolveGameMonthForUser($this->user);

    $this->user->refresh();
    expect($this->user->is_burned_out)->toBeFalse()
        ->and($this->user->burned_out_until_game_month)->toBeNull()
        ->and($this->user->action_points)->toBe(12);
});
