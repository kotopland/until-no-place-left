<?php

declare(strict_types=1);

use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\MonthInReviewModal;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'center_lat' => 35.17,
        'center_lng' => 137.00,
    ]);

    $this->session = GameSession::create([
        'name' => 'Stewardship Session',
        'code' => 'steward-1',
        'current_game_month' => 1,
        'current_game_week' => 1,
        'current_game_day' => 1,
        'days_processed_this_month' => 0,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'next_weekly_tick_at' => now()->addMinutes(15),
        'is_active' => true,
    ]);

    $this->user = User::factory()->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'action_points' => 20,
        'max_action_points' => 26,
        'weekly_action_points' => 20,
        'daily_action_points' => 20,
        'current_game_month' => 1,
        'current_game_week' => 1,
        'coop_synergy_score' => 0,
        'total_generational_score' => 0,
        'consecutive_admin_neglect_months' => 0,
    ]);
});

test('supporter letter and admin has correct enum properties', function () {
    $action = MinistryActionType::SupporterLetterAndAdmin;

    expect($action->apCost())->toBe(2)
        ->and($action->isWorkerRhythm())->toBeTrue()
        ->and($action->blocksEntireWeek())->toBeFalse()
        ->and($action->rhythmPoints())->toBe(30)
        ->and($action->icon())->toBe('✉️');
});

test('worker can schedule supporter letter and admin which costs 2 AP', function () {
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SupporterLetterAndAdmin->value)
        ->assertDispatched('notify');

    $queued = QueuedMonthlyAction::where('user_id', $this->user->id)->first();
    expect($queued)->not->toBeNull()
        ->and($queued->action_type)->toBe(MinistryActionType::SupporterLetterAndAdmin)
        ->and($queued->ap_cost)->toBe(2);

    $this->user->refresh();
    expect($this->user->action_points)->toBe(18);
});

test('worker cannot schedule supporter letter and admin more than once in the same week', function () {
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SupporterLetterAndAdmin->value);

    // Second attempt in same week should be blocked
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SupporterLetterAndAdmin->value)
        ->assertDispatched('notify');

    expect(QueuedMonthlyAction::where('user_id', $this->user->id)->count())->toBe(1);
});

test('resolving supporter letter and admin clears admin neglect and activates overseas prayer shield', function () {
    $this->user->update([
        'consecutive_admin_neglect_months' => 2,
        'active_prayer_focus' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->call('scheduleWorkerRhythm', MinistryActionType::SupporterLetterAndAdmin->value);

    $engine = app(FourFieldsEngine::class);
    $engine->resolveWeeklyTick($this->session, $this->user);

    $this->user->refresh();
    expect($this->user->consecutive_admin_neglect_months)->toBe(0)
        ->and($this->user->hasAdminClutterPenalty)->toBeFalse()
        ->and($this->user->hasOverseasPrayerShield)->toBeTrue()
        ->and($this->user->last_prayer_letter_game_month)->toBe(1)
        ->and($this->user->compositeScore)->toBeGreaterThanOrEqual(30);
});

test('monthly resolution tracks admin neglect and applies clutter penalty after 2 consecutive months', function () {
    $engine = app(FourFieldsEngine::class);

    // Month 1 resolves without supporter letter or admin
    $engine->resolveGameMonthForUser($this->user);

    $this->user->refresh();
    expect($this->user->consecutive_admin_neglect_months)->toBe(1)
        ->and($this->user->hasAdminClutterPenalty)->toBeFalse();

    // Month 2 resolves still without supporter care
    $engine->resolveGameMonthForUser($this->user);

    $this->user->refresh();
    expect($this->user->consecutive_admin_neglect_months)->toBe(2)
        ->and($this->user->hasAdminClutterPenalty)->toBeTrue()
        ->and($this->user->adminClutterPenaltyAp)->toBe(1);
});

test('dispatching prayer letter from MonthInReviewModal activates chosen prayer focus and clears neglect', function () {
    $this->user->update([
        'consecutive_admin_neglect_months' => 2,
    ]);

    $report = MonthlyReport::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->user->id,
        'game_month' => 1,
        'summary_title' => 'Month 1 Digest',
        'highlights_json' => ['Sowed seed in Meito-ku'],
        'answered_prayers_json' => ['count' => 1],
        'coach_tips_json' => [],
    ]);

    Livewire::actingAs($this->user)
        ->test(MonthInReviewModal::class)
        ->call('openModal', $report->id)
        ->call('dispatchPrayerLetter', 'pioneering')
        ->assertDispatched('notify');

    $report->refresh();
    $this->user->refresh();

    expect($report->isPrayerLetterDispatched)->toBeTrue()
        ->and($report->selected_prayer_focus)->toBe('pioneering')
        ->and($this->user->consecutive_admin_neglect_months)->toBe(0)
        ->and($this->user->hasOverseasPrayerShield)->toBeTrue()
        ->and($this->user->active_prayer_focus)->toBe('pioneering')
        ->and($this->user->prayerFocusLabel)->toContain('Pioneer Sowing');
});

test('active pioneering prayer focus boosts Field 1 and 2 trust and score outcomes', function () {
    $npc = Npc::factory()->create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
        'need_state' => NpcNeedState::None,
    ]);

    $this->user->update([
        'active_prayer_focus' => 'pioneering',
        'prayer_shield_expires_at_month' => 2,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CasualTalk,
        'game_month' => 1,
        'game_week' => 1,
        'game_day' => 1,
        'ap_cost' => 1,
    ]);

    $engine = app(FourFieldsEngine::class);
    $outcome = $engine->resolveSingleAction($action, 1);

    expect($outcome->narrativeOutcome)->toContain('Overseas Prayer Shield: Pioneer Sowing +15%');
});
