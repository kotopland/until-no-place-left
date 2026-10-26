<?php

use App\Enums\BaptismStatus;
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

uses(RefreshDatabase::class);

test('seeking journey requires gradual process over months before conversion decision', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Gradual Seeking Session',
        'code' => 'gradual-seeking-1',
        'current_game_month' => 1,
        'current_game_day' => 1,
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
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健二',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 55,
        'seeking_months' => 0,
        'target_seeking_months' => 4,
        'is_person_of_peace' => true,
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
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    // In month 1 (0 months seeking), conversion is premature in Japanese context
    $outcome = $engine->resolveSingleAction($action, 1);

    expect($outcome->newStatus)->toBe(ReceptivityStatus::Yellow)
        ->and($outcome->narrativeOutcome)->toContain('momentous step affecting my family and ancestors')
        ->and($outcome->pedagogicalTip)->toContain('for Japanese seekers, moving from Yellow to Green is a gradual process');

    $npc->refresh();
    expect($npc->status)->toBe(ReceptivityStatus::Yellow)
        ->and($npc->prayed_salvation)->toBeFalse();

    // After 4 months of seeking and relationship building, conversion decision occurs
    $npc->update(['seeking_months' => 4]);

    $action2 = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::PraySalvation,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 5,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $outcome2 = $engine->resolveSingleAction($action2, 5);

    expect($outcome2->newStatus)->toBe(ReceptivityStatus::Green);
    $npc->refresh();
    expect($npc->status)->toBe(ReceptivityStatus::Green)
        ->and($npc->prayed_salvation)->toBeTrue()
        ->and($npc->baptism_status)->toBe(BaptismStatus::CountingCost);
});

test("sinner's prayer leads seeker to green status and begins baptism cost journey", function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Salvation Session',
        'code' => 'salvation-1',
        'current_game_month' => 5,
        'current_game_day' => 1,
        'calendar_date' => '2026-08-01',
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
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健二',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 55,
        'seeking_months' => 4,
        'target_seeking_months' => 4,
        'is_person_of_peace' => true,
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
        'game_month' => 5,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $outcome = $engine->resolveSingleAction($action, 5);

    expect($outcome->newStatus)->toBe(ReceptivityStatus::Green)
        ->and($outcome->pointsAwarded)->toBeGreaterThan(50);

    $npc->refresh();
    expect($npc->prayed_salvation)->toBeTrue()
        ->and($npc->status)->toBe(ReceptivityStatus::Green)
        ->and($npc->baptism_status)->toBe(BaptismStatus::CountingCost)
        ->and($npc->is_baptized)->toBeFalse(); // Not instantly baptized yet; counting the cost!
});

test('commitment to baptism before 411 training advances status to committed and then baptized', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Baptism Before 411 Session',
        'code' => 'bap-before-411',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'center_lat' => 35.16,
        'center_lng' => 136.95,
    ]);

    $user = User::factory()->create(['current_game_session_id' => $session->id]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Aoi Yamada',
        'name_ja' => '山田 葵',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'baptism_status' => BaptismStatus::CountingCost,
        'trust_level' => 70,
        'target_baptism_months' => 6,
        'baptism_cost_months' => 0,
        'lat' => 35.16,
        'lng' => 136.95,
    ]);

    // Step 1: Commitment to Baptism action before 411 advances to Committed
    $commitAction = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CommitToBaptism,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $engine->resolveSingleAction($commitAction, 1);
    $npc->refresh();

    expect($npc->baptism_status)->toBe(BaptismStatus::Committed)
        ->and($npc->is_baptized)->toBeFalse();

    // In month 1, disciple has not yet reached the 6-month baptism readiness threshold
    $earlyBaptizeAction = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CommitToBaptism,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 2,
        'ap_cost' => 2,
    ]);

    $earlyOutcome = $engine->resolveSingleAction($earlyBaptizeAction, 1);
    $npc->refresh();
    expect($npc->is_baptized)->toBeFalse()
        ->and($earlyOutcome->narrativeOutcome)->toContain('They are committed to water baptism and counting the cost with their family');

    // Step 2: After counting the cost for target duration (6 months in Japanese context), water baptism is celebrated!
    $npc->update(['baptism_cost_months' => 6]);

    $baptizeAction = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CommitToBaptism,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 7,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $outcome = $engine->resolveSingleAction($baptizeAction, 7);
    $npc->refresh();

    expect($npc->is_baptized)->toBeTrue()
        ->and($npc->baptism_status)->toBe(BaptismStatus::Baptized)
        ->and($npc->baptized_at)->not->toBeNull()
        ->and($npc->trained_411)->toBeFalse(); // Successfully baptized BEFORE 411 training!
});

test('411 discipleship training catalyzes baptism commitment during the training session', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Baptism During 411 Session',
        'code' => 'bap-during-411',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Meieki / Nakamura-ku',
        'name_ja' => '中村区名駅',
        'slug' => 'meieki-nakamura-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.88,
    ]);

    $user = User::factory()->create(['current_game_session_id' => $session->id]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenta Suzuki',
        'name_ja' => '鈴木 健太',
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'baptism_status' => BaptismStatus::CountingCost, // Hesitant about family
        'trust_level' => 65,
        'lat' => 35.17,
        'lng' => 136.88,
    ]);

    // Conduct 411 Discipleship Training
    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::Train411,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 3,
    ]);

    $outcome = $engine->resolveSingleAction($action, 1);
    $npc->refresh();

    expect($npc->trained_411)->toBeTrue()
        ->and($npc->baptism_status)->toBe(BaptismStatus::Committed) // Convicted by 411 command to commit!
        ->and($outcome->narrativeOutcome)->toContain('committed to water baptism');
});

test('commitment to baptism after 411 training completes baptism for an equipped disciple', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Baptism After 411 Session',
        'code' => 'bap-after-411',
        'current_game_month' => 1,
        'current_game_day' => 1,
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

    $user = User::factory()->create(['current_game_session_id' => $session->id]);

    // Already 411-trained, but was committed and now ready for the baptism step
    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Yuki Ito',
        'name_ja' => '伊藤 由紀',
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trained_411' => true, // Already 411 trained!
        'baptism_status' => BaptismStatus::Committed,
        'trust_level' => 75,
        'target_baptism_months' => 6,
        'baptism_cost_months' => 6,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CommitToBaptism,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $outcome = $engine->resolveSingleAction($action, 1);
    $npc->refresh();

    expect($npc->is_baptized)->toBeTrue()
        ->and($npc->baptism_status)->toBe(BaptismStatus::Baptized)
        ->and($npc->trained_411)->toBeTrue() // Both baptized AND 411 trained!
        ->and($outcome->pointsAwarded)->toBe(80);
});
