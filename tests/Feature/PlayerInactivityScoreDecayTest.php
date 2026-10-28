<?php

declare(strict_types=1);

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        'name' => 'Inactivity Decay Session',
        'code' => 'inactive-decay-1',
        'current_game_month' => 1,
        'current_game_week' => 1,
        'current_game_day' => 1,
        'weeks_processed_this_month' => 0,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_daily_tick_at' => now()->addMinutes(15),
        'is_active' => true,
    ]);

    $this->user = User::factory()->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 1,
        'current_game_week' => 1,
        'current_game_day' => 1,
        'weeks_processed_this_month' => 0,
        'days_processed_this_month' => 0,
        'consecutive_inactive_months' => 0,
        'total_generational_score' => 200,
        'coop_synergy_score' => 100,
    ]);

    $this->engine = app(FourFieldsEngine::class);
});

test('player score does not decline during the first 3 game months of inactivity', function () {
    // Month 1 (1st inactive month)
    $this->engine->resolveGameMonth($this->session);
    $this->user->refresh();
    expect($this->user->consecutive_inactive_months)->toBe(1)
        ->and($this->user->isScoreDecliningFromInactivity)->toBeFalse()
        ->and($this->user->total_generational_score)->toBe(200)
        ->and($this->user->coop_synergy_score)->toBe(100)
        ->and($this->user->compositeScore)->toBe(300);

    // Month 2 (2nd inactive month)
    $this->engine->resolveGameMonth($this->session);
    $this->user->refresh();
    expect($this->user->consecutive_inactive_months)->toBe(2)
        ->and($this->user->isScoreDecliningFromInactivity)->toBeFalse()
        ->and($this->user->total_generational_score)->toBe(200)
        ->and($this->user->coop_synergy_score)->toBe(100);

    // Month 3 (3rd inactive month)
    $this->engine->resolveGameMonth($this->session);
    $this->user->refresh();
    expect($this->user->consecutive_inactive_months)->toBe(3)
        ->and($this->user->isScoreDecliningFromInactivity)->toBeFalse()
        ->and($this->user->total_generational_score)->toBe(200)
        ->and($this->user->coop_synergy_score)->toBe(100);
});

test('player score declines by 5 percent per month starting on the 4th game month of not playing', function () {
    // Months 1, 2, 3: no decline yet
    for ($m = 1; $m <= 3; $m++) {
        $this->engine->resolveGameMonth($this->session);
    }

    $this->user->refresh();
    expect($this->user->consecutive_inactive_months)->toBe(3)
        ->and($this->user->total_generational_score)->toBe(200)
        ->and($this->user->coop_synergy_score)->toBe(100);

    // Month 4 (4th consecutive inactive month -> -5% score decline)
    $resultMonth4 = $this->engine->resolveGameMonth($this->session);
    $this->user->refresh();

    expect($this->user->consecutive_inactive_months)->toBe(4)
        ->and($this->user->isScoreDecliningFromInactivity)->toBeTrue()
        ->and($this->user->total_generational_score)->toBe(190) // 200 - 5% (10)
        ->and($this->user->coop_synergy_score)->toBe(95)        // 100 - 5% (5)
        ->and($this->user->compositeScore)->toBe(285)           // 300 - 5% (15)
        ->and($resultMonth4->playerScoreGains[$this->user->id])->toBe(-10);

    // Month 5 (5th consecutive inactive month -> another -5% score decline)
    $this->engine->resolveGameMonth($this->session);
    $this->user->refresh();

    expect($this->user->consecutive_inactive_months)->toBe(5)
        ->and($this->user->total_generational_score)->toBe(180) // 190 - round(190 * 0.05) = 180
        ->and($this->user->coop_synergy_score)->toBe(90)        // 95 - round(95 * 0.05) = 90
        ->and($this->user->compositeScore)->toBe(270);
});

test('resuming play resets consecutive inactive months and stops score decline', function () {
    // Advance 4 inactive months so score declines once (200 -> 190, 100 -> 95)
    for ($m = 1; $m <= 4; $m++) {
        $this->engine->resolveGameMonth($this->session);
    }

    $this->user->refresh();
    expect($this->user->consecutive_inactive_months)->toBe(4)
        ->and($this->user->total_generational_score)->toBe(190)
        ->and($this->user->coop_synergy_score)->toBe(95);

    // In Month 5, player queues a Prayer Walk action
    QueuedMonthlyAction::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'action_type' => MinistryActionType::PrayerWalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => $this->session->current_game_month,
        'game_week' => 1,
        'game_day' => 1,
        'ap_cost' => 1,
    ]);

    $this->engine->resolveGameMonth($this->session);
    $this->user->refresh();

    expect($this->user->consecutive_inactive_months)->toBe(0)
        ->and($this->user->isScoreDecliningFromInactivity)->toBeFalse()
        ->and($this->user->total_generational_score)->toBeGreaterThanOrEqual(190)
        ->and($this->user->coop_synergy_score)->toBe(95);
});

test('resolveGameMonthForUser also applies 5 percent score decline after 4 inactive months and records report', function () {
    $this->user->update([
        'total_generational_score' => 400,
        'coop_synergy_score' => 0,
        'consecutive_inactive_months' => 3,
    ]);

    // 4th inactive month for this user
    $result = $this->engine->resolveGameMonthForUser($this->user);
    $this->user->refresh();

    expect($this->user->consecutive_inactive_months)->toBe(4)
        ->and($this->user->total_generational_score)->toBe(380) // 400 - 20 (5%)
        ->and($result->playerScoreGains[$this->user->id])->toBe(-20);

    $report = MonthlyReport::where('user_id', $this->user->id)->latest('id')->first();
    expect($report)->not->toBeNull()
        ->and($report->generational_score_gained)->toBe(-20)
        ->and($report->total_generational_score)->toBe(380);

    // 5th inactive month for this user
    $this->engine->resolveGameMonthForUser($this->user);
    $this->user->refresh();

    expect($this->user->consecutive_inactive_months)->toBe(5)
        ->and($this->user->total_generational_score)->toBe(361); // 380 - 19 (5%)
});
