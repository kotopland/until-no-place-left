<?php

use App\Enums\BaptismStatus;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Enums\RelationshipType;
use App\Livewire\ActionQueuePlanner;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\OikosRelationship;
use App\Models\PrayerWalk;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('leadership tier enum models the 5 levels matrix accurately', function () {
    expect(LeadershipTier::None->level())->toBe(0)
        ->and(LeadershipTier::None->roleTitle())->toBe('Contact / Seeker')
        ->and(LeadershipTier::None->canLeadChurch())->toBeFalse();

    expect(LeadershipTier::L1_SeedSower->level())->toBe(1)
        ->and(LeadershipTier::L1_SeedSower->levelCode())->toBe('L1')
        ->and(LeadershipTier::L1_SeedSower->roleTitle())->toBe('Seed Sower')
        ->and(LeadershipTier::L1_SeedSower->coreToolset())->toBe('411 Discipleship')
        ->and(LeadershipTier::L1_SeedSower->canLeadChurch())->toBeFalse();

    expect(LeadershipTier::L2_ChurchPlanter->level())->toBe(2)
        ->and(LeadershipTier::L2_ChurchPlanter->levelCode())->toBe('L2')
        ->and(LeadershipTier::L2_ChurchPlanter->roleTitle())->toBe('Church Planter (CP)')
        ->and(LeadershipTier::L2_ChurchPlanter->canLeadChurch())->toBeTrue();

    expect(LeadershipTier::L3_Multiplier->level())->toBe(3)
        ->and(LeadershipTier::L3_Multiplier->levelCode())->toBe('L3')
        ->and(LeadershipTier::L3_Multiplier->roleTitle())->toBe('Church Plant Multiplier (CPMer)')
        ->and(LeadershipTier::L3_Multiplier->canLeadChurch())->toBeTrue();

    expect(LeadershipTier::L4_MovementTrainer->level())->toBe(4)
        ->and(LeadershipTier::L4_MovementTrainer->levelCode())->toBe('L4')
        ->and(LeadershipTier::L4_MovementTrainer->roleTitle())->toBe('Movement Trainer (MT)')
        ->and(LeadershipTier::L4_MovementTrainer->canLeadChurch())->toBeTrue();

    expect(LeadershipTier::L5_StrategyCoordinator->level())->toBe(5)
        ->and(LeadershipTier::L5_StrategyCoordinator->levelCode())->toBe('L5')
        ->and(LeadershipTier::L5_StrategyCoordinator->roleTitle())->toBe('Strategy Coordinator (SC)')
        ->and(LeadershipTier::L5_StrategyCoordinator->canLeadChurch())->toBeTrue();
});

test('actions are filtered strictly by resident abilities and spiritual readiness', function () {
    $session = GameSession::create([
        'name' => 'Session',
        'code' => 'session-1',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'population' => 90000,
        'christian_population_pct' => 0.45,
        'center_lat' => 35.16,
        'center_lng' => 136.90,
    ]);

    // 1. Unreached Person (never heard about Jesus)
    $unreached = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Unreached Resident',
        'name_ja' => '未信者',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
        'leadership_tier' => LeadershipTier::None,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    // Cannot do Timothy mentoring, church planting, swarm training, or joint prayer walking without prior rapport
    expect(MinistryActionType::MentorTimothy->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::MentorTimothy->ineligibilityReason($unreached))->toContain('never heard about Jesus')
        ->and(MinistryActionType::CommandsOfChrist->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::CommitToBaptism->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::ChurchCircles->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::IronOnIron->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::SwarmTraining->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::PrayerWalk->isEligibleFor($unreached))->toBeFalse()
        ->and(MinistryActionType::PrayerWalk->ineligibilityReason($unreached))->toContain('Casual Talk first');

    // CAN do Casual Talk, Deep Listening, Healing Prayer, Mercy Outreach, International Event, and solo prayer walk
    expect(MinistryActionType::CasualTalk->isEligibleFor($unreached))->toBeTrue()
        ->and(MinistryActionType::DeepTalk->isEligibleFor($unreached))->toBeTrue()
        ->and(MinistryActionType::PrayerForHealing->isEligibleFor($unreached))->toBeTrue()
        ->and(MinistryActionType::MercyCareFoodBible->isEligibleFor($unreached))->toBeTrue()
        ->and(MinistryActionType::InternationalEvent->isEligibleFor($unreached))->toBeTrue()
        ->and(MinistryActionType::PrayerWalk->isEligibleFor(null))->toBeTrue();

    // Once initial trust reaches 20+ via Casual Talk, joint prayer walking unlocks
    $unreached->update(['trust_level' => 25]);
    expect(MinistryActionType::PrayerWalk->isEligibleFor($unreached))->toBeTrue();

    // Verify all 17 enum cases execute safely without UnhandledMatchError
    foreach (MinistryActionType::cases() as $actionCase) {
        expect($actionCase->isEligibleFor($unreached))->toBeBool()
            ->and($actionCase->isEligibleFor(null))->toBeBool();
        $reason = $actionCase->ineligibilityReason($unreached);
        expect($reason === null || is_string($reason))->toBeTrue();
    }

    // 2. Green Saved Believer (not yet trained in 411)
    $believer = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'New Believer',
        'name_ja' => '新信者',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trust_level' => 60,
        'trained_411' => false,
        'leadership_tier' => LeadershipTier::None,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    expect(MinistryActionType::CommandsOfChrist->isEligibleFor($believer))->toBeTrue()
        ->and(MinistryActionType::SwordBibleStudy->isEligibleFor($believer))->toBeTrue()
        ->and(MinistryActionType::CommitToBaptism->isEligibleFor($believer))->toBeTrue()
        ->and(MinistryActionType::Train411->isEligibleFor($believer))->toBeTrue()
        ->and(MinistryActionType::MentorTimothy->isEligibleFor($believer))->toBeFalse()
        ->and(MinistryActionType::SwarmTraining->isEligibleFor($believer))->toBeFalse(); // Not yet L3+

    // 3. L1 Seed Sower (equipped in 411)
    $sower = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'L1 Leader',
        'name_ja' => 'L1リーダー',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L1_SeedSower,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    expect(MinistryActionType::ChurchCircles->isEligibleFor($sower))->toBeTrue()
        ->and(MinistryActionType::MentorTimothy->isEligibleFor($sower))->toBeTrue()
        ->and(MinistryActionType::IronOnIron->isEligibleFor($sower))->toBeFalse()
        ->and(MinistryActionType::SwarmTraining->isEligibleFor($sower))->toBeFalse(); // Requires L3

    // 4. L2 Church Planter
    $planter = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'L2 Planter',
        'name_ja' => 'L2開拓者',
        'role_archetype' => NpcRoleArchetype::Shopkeeper,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    expect(MinistryActionType::IronOnIron->isEligibleFor($planter))->toBeTrue()
        ->and(MinistryActionType::SwarmTraining->isEligibleFor($planter))->toBeFalse(); // Requires L3+

    // 5. L3 Multiplier (CPMer)
    $multiplier = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'L3 Multiplier',
        'name_ja' => 'L3乗数開拓者',
        'role_archetype' => NpcRoleArchetype::Shopkeeper,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L3_Multiplier,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    expect(MinistryActionType::SwarmTraining->isEligibleFor($multiplier))->toBeTrue();
});

test('action queue planner blocks ineligible actions with pedagogical error and queues eligible actions', function () {
    $session = GameSession::create([
        'name' => 'Session',
        'code' => 'session-livewire',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'population' => 90000,
        'christian_population_pct' => 0.45,
        'center_lat' => 35.16,
        'center_lng' => 136.90,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
        'current_game_month' => 1,
        'current_game_day' => 1,
    ]);

    $unreached = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健司',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
        'leadership_tier' => LeadershipTier::None,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    $this->actingAs($user);

    // Try to queue Timothy Mentoring for someone who has never heard about Jesus!
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $unreached->id)
        ->set('selectedActionType', MinistryActionType::MentorTimothy->value)
        ->call('queueAction')
        ->assertDispatched('notify');

    // Verify nothing was queued in DB
    expect(QueuedMonthlyAction::count())->toBe(0);

    // Now queue Casual Talk (eligible for everyone)
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $unreached->id)
        ->set('selectedActionType', MinistryActionType::CasualTalk->value)
        ->call('queueAction')
        ->assertDispatched('notify')
        ->assertDispatched('action-queued');

    expect(QueuedMonthlyAction::count())->toBe(1)
        ->and(QueuedMonthlyAction::first()->action_type)->toBe(MinistryActionType::CasualTalk);
});

test('four fields engine resolves commands of christ, church circles, and advances leadership tiers', function () {
    $session = GameSession::create([
        'name' => 'Session',
        'code' => 'session-engine',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'population' => 165000,
        'christian_population_pct' => 0.55,
        'center_lat' => 35.17,
        'center_lng' => 136.95,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $engine = app(FourFieldsEngine::class);

    // 1. Resolve Commands of Christ on new believer counting cost
    $believer = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Aoi Yamada',
        'name_ja' => '山田 葵',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trust_level' => 50,
        'is_baptized' => false,
        'baptism_status' => BaptismStatus::CountingCost,
        'trained_411' => false,
        'leadership_tier' => LeadershipTier::None,
        'lat' => 35.17,
        'lng' => 136.95,
    ]);

    $action1 = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $believer->id,
        'action_type' => MinistryActionType::CommandsOfChrist,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 2,
    ]);

    $outcome1 = $engine->resolveSingleAction($action1, 1);
    expect($outcome1->narrativeOutcome)->toContain('Commands of Christ')
        ->and($believer->fresh()->baptism_status)->toBe(BaptismStatus::Committed);

    // 2. Resolve Church Circles on equipped L1 Seed Sower -> elevated to L2 Church Planter & church birthed!
    $sower = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Ren Suzuki',
        'name_ja' => '鈴木 蓮',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'trust_level' => 75,
        'is_baptized' => true,
        'baptism_status' => BaptismStatus::Baptized,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L1_SeedSower,
        'generation' => 1,
        'lat' => 35.17,
        'lng' => 136.95,
    ]);

    $action2 = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => $sower->id,
        'action_type' => MinistryActionType::ChurchCircles,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 2,
        'ap_cost' => 3,
    ]);

    $outcome2 = $engine->resolveSingleAction($action2, 1);
    expect($outcome2->narrativeOutcome)->toContain('Acts 2 Church Circle')
        ->and($sower->fresh()->leadership_tier)->toBe(LeadershipTier::L2_ChurchPlanter);

    expect(Church::where('leader_npc_id', $sower->id)->exists())->toBeTrue();
});

test('four fields engine executes joint prayer walk with selected resident (option b)', function () {
    $session = GameSession::create([
        'name' => 'Session',
        'code' => 'session-joint-pw',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'population' => 90000,
        'christian_population_pct' => 0.45,
        'center_lat' => 35.16,
        'center_lng' => 136.90,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
        'current_game_month' => 1,
        'current_game_day' => 1,
    ]);

    $seeker = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Daiki Ito',
        'name_ja' => '伊藤 大樹',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 30,
        'leadership_tier' => LeadershipTier::None,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    $familyMember = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Emi Ito',
        'name_ja' => '伊藤 えみ',
        'role_archetype' => NpcRoleArchetype::Shopkeeper,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
        'leadership_tier' => LeadershipTier::None,
        'lat' => 35.16,
        'lng' => 136.90,
    ]);

    $oikosRel = OikosRelationship::create([
        'game_session_id' => $session->id,
        'npc_id' => $seeker->id,
        'related_npc_id' => $familyMember->id,
        'relationship_type' => RelationshipType::Family,
        'trust_level' => 10,
    ]);

    $this->actingAs($user);

    // Queue Joint Prayer Walk via ActionQueuePlanner
    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $seeker->id)
        ->set('selectedActionType', MinistryActionType::PrayerWalk->value)
        ->call('queueAction')
        ->assertDispatched('notify')
        ->assertDispatched('action-queued');

    $queued = QueuedMonthlyAction::first();
    expect($queued)->not->toBeNull()
        ->and($queued->action_type)->toBe(MinistryActionType::PrayerWalk)
        ->and($queued->target_npc_id)->toBe($seeker->id);

    // Resolve via FourFieldsEngine
    $engine = app(FourFieldsEngine::class);
    $outcome = $engine->resolveSingleAction($queued, 1);

    expect($outcome->targetNpcId)->toBe($seeker->id)
        ->and($outcome->pointsAwarded)->toBe(40)
        ->and($outcome->narrativeOutcome)->toContain('Joint Luke 10 prayer walk with Daiki Ito')
        ->and($seeker->fresh()->trust_level)->toBeGreaterThanOrEqual(52)
        ->and($oikosRel->fresh()->trust_level)->toBe(25);

    $prayerWalkRecord = PrayerWalk::where('discovered_npc_id', $seeker->id)->first();
    expect($prayerWalkRecord)->not->toBeNull()
        ->and($prayerWalkRecord->ward_id)->toBe($ward->id);
});
