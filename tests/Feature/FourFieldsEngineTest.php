<?php

use App\Enums\ContributionRole;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Enums\RelationshipType;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\OikosRelationship;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('four fields engine resolves casual talk and deep talk based on emotional need', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Test Session',
        'code' => 'test-1',
        'current_game_month' => 1,
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
        'spiritual_openness' => 40,
        'poverty_need_index' => 60,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Hiroshi Sato',
        'name_ja' => '佐藤 浩',
        'need_state' => NpcNeedState::EmotionalCrisis,
        'status' => ReceptivityStatus::Red,
        'trust_level' => 20,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    // Action 1: Deep Talk
    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::DeepTalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'ap_cost' => 2,
    ]);

    $outcome = $engine->resolveSingleAction($action, 1);

    expect($outcome->trustChange)->toBeGreaterThan(20)
        ->and($outcome->newTrustLevel)->toBeGreaterThan(40)
        ->and($outcome->newStatus)->toBe(ReceptivityStatus::Yellow); // Heart opened from Red to Yellow!
});

test('co-op synergy distributes shared harvest points when multiple players water and reap', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Co-op Session',
        'code' => 'coop-1',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Osu / Naka-ku',
        'name_ja' => '中区大須',
        'slug' => 'osu-naka-ku',
        'center_lat' => 35.15,
        'center_lng' => 136.90,
    ]);

    $playerA = User::factory()->create(['name' => 'Player A', 'current_game_session_id' => $session->id]);
    $playerB = User::factory()->create(['name' => 'Player B', 'current_game_session_id' => $session->id]);
    $playerC = User::factory()->create(['name' => 'Player C', 'current_game_session_id' => $session->id]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健二',
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 60,
        'is_person_of_peace' => true,
        'lat' => 35.15,
        'lng' => 136.90,
    ]);

    // Player A previously pioneered contact
    NpcContribution::create([
        'game_session_id' => $session->id,
        'npc_id' => $npc->id,
        'user_id' => $playerA->id,
        'action_type' => MinistryActionType::CasualTalk,
        'contribution_role' => ContributionRole::PioneerSower,
        'game_month' => 1,
        'trust_gained' => 20,
        'points_awarded' => 10,
    ]);

    // Player B previously watered with prayer
    NpcContribution::create([
        'game_session_id' => $session->id,
        'npc_id' => $npc->id,
        'user_id' => $playerB->id,
        'action_type' => MinistryActionType::PrayerForHealing,
        'contribution_role' => ContributionRole::Waterer,
        'game_month' => 1,
        'trust_gained' => 20,
        'points_awarded' => 15,
    ]);

    // Player C shares 3 Circles and harvests!
    $harvestAction = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $playerC->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::Share3Circles,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'ap_cost' => 2,
    ]);

    $outcome = $engine->resolveSingleAction($harvestAction, 1);

    expect($outcome->newStatus)->toBe(ReceptivityStatus::Green)
        ->and($outcome->coopPointsAwarded)->toBeGreaterThan(0)
        ->and(count($outcome->collaboratorsImpacted))->toBe(2);

    $playerA->refresh();
    $playerB->refresh();
    $playerC->refresh();

    // Verify all 3 players received collaborative synergy points!
    expect($playerA->coop_synergy_score)->toBeGreaterThan(0)
        ->and($playerB->coop_synergy_score)->toBeGreaterThan(0)
        ->and($playerC->coop_synergy_score)->toBeGreaterThan(0);
});

test('autonomous multiplication allows 411 believers to witness to oikos and plant daughter churches', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Multiplication Session',
        'code' => 'mult-1',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'month_duration_minutes' => 720,
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'center_lat' => 35.16,
        'center_lng' => 137.00,
    ]);

    $user = User::factory()->create(['current_game_session_id' => $session->id]);

    // Parent G1 Timothy leader
    $timothy = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Daisuke Kato',
        'name_ja' => '加藤 大輔',
        'status' => ReceptivityStatus::Green,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::Timothy,
        'generation' => 1,
        'discipler_user_id' => $user->id,
        'trust_level' => 80,
        'lat' => 35.16,
        'lng' => 137.00,
    ]);

    // Give Timothy 2 existing disciples so he qualifies to plant an autonomous daughter church
    Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Disciple A',
        'name_ja' => '弟子A',
        'status' => ReceptivityStatus::Green,
        'trained_411' => true,
        'generation' => 2,
        'discipler_npc_id' => $timothy->id,
        'lat' => 35.16,
        'lng' => 137.00,
    ]);
    Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Disciple B',
        'name_ja' => '弟子B',
        'status' => ReceptivityStatus::Green,
        'trained_411' => true,
        'generation' => 2,
        'discipler_npc_id' => $timothy->id,
        'lat' => 35.16,
        'lng' => 137.00,
    ]);

    // Unsaved relative in Timothy's Oikos
    $relative = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Ryosuke Kato',
        'name_ja' => '加藤 亮介',
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 55,
        'lat' => 35.16,
        'lng' => 137.00,
    ]);

    OikosRelationship::create([
        'game_session_id' => $session->id,
        'npc_id' => $timothy->id,
        'related_npc_id' => $relative->id,
        'relationship_type' => RelationshipType::Family,
        'trust_level' => 85,
    ]);

    $result = $engine->runAutonomousMultiplication($session, 1);

    expect($result['new_believers_count'])->toBe(1)
        ->and($result['new_churches_count'])->toBe(1);

    $relative->refresh();
    expect($relative->status)->toBe(ReceptivityStatus::Green)
        ->and($relative->generation)->toBe(2); // G2 disciple born autonomously!

    // Verify autonomous daughter church was planted
    $newChurch = Church::where('leader_npc_id', $timothy->id)->first();
    expect($newChurch)->not->toBeNull()
        ->and($newChurch->isAutonomous)->toBeTrue();
});

test('action resolution saves long narrative exceeding 255 chars in npc contribution notes', function () {
    $engine = new FourFieldsEngine;

    $session = GameSession::create([
        'name' => 'Long Narrative Session',
        'code' => 'long-narrative-test',
        'current_game_month' => 1,
    ]);

    $homeWard = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);

    $distantWard = Ward::create([
        'name' => 'Minato-ku',
        'name_ja' => '港区',
        'slug' => 'minato-ku',
        'center_lat' => 35.09,
        'center_lng' => 136.88,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $homeWard->id,
        'action_points' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $distantWard->id,
        'name' => 'Kenjiro Takahashi',
        'name_ja' => '高橋 健二郎',
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 65,
        'lat' => 35.09,
        'lng' => 136.88,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $distantWard->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::Share3Circles,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $outcome = $engine->resolveSingleAction($action, 1);

    $contribution = NpcContribution::where('npc_id', $npc->id)
        ->where('user_id', $user->id)
        ->latest()
        ->first();

    expect($contribution)->not->toBeNull()
        ->and(mb_strlen($contribution->notes))->toBeGreaterThan(150)
        ->and($contribution->notes)->toBe($outcome->narrativeOutcome);

    // Also test explicitly storing a note with length > 255 chars
    $longText = str_repeat('Long gospel conversation and mentoring ripple across Nagoya. ', 10);
    expect(strlen($longText))->toBeGreaterThan(255);

    $contribution->update(['notes' => $longText]);
    $contribution->refresh();
    expect($contribution->notes)->toBe($longText);
});
