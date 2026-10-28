<?php

declare(strict_types=1);

use App\Enums\ChurchElement;
use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\ChurchGenerationMap;
use App\Models\Church;
use App\Models\GameSession;
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
        'center_lat' => 35.1675,
        'center_lng' => 136.9990,
    ]);

    $this->session = GameSession::create([
        'name' => 'Gen Map Test Session',
        'code' => 'genmap-1',
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
        'name' => 'Missionary John',
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'action_points' => 10,
        'max_action_points' => 12,
        'daily_action_points' => 10,
        'coop_synergy_score' => 0,
        'days_played_today' => 0,
        'last_played_real_date' => now()->startOfDay(),
    ]);

    // G1 Mother Church
    $this->g1Church = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'John Meito Seed Church (G1)',
        'parent_church_id' => null,
        'leader_user_id' => $this->user->id,
        'generation' => 1,
        'elements_json' => [
            ChurchElement::RepentanceBelief->value,
            ChurchElement::Baptism->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
            ChurchElement::Fellowship->value,
            ChurchElement::Communion->value,
            ChurchElement::GivingMercy->value,
            ChurchElement::LocalLeaders->value,
            ChurchElement::MakingDisciples->value,
        ],
        'is_healthy_church' => true,
        'health_score' => 85,
        'attenders_count' => 10,
        'believers_count' => 8,
        'baptized_count' => 6,
        'is_dissolved' => false,
        'started_by_name' => $this->user->name,
        'lat' => 35.1675,
        'lng' => 136.9990,
        'founded_game_month' => 1,
    ]);

    // Timothy Leader NPC
    $this->timothy = Npc::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'church_id' => $this->g1Church->id,
        'name' => 'Daisuke Kato',
        'name_ja' => '加藤 大輔',
        'status' => ReceptivityStatus::Green,
        'trained_411' => true,
        'is_baptized' => true,
        'trust_level' => 80,
        'generation' => 1,
    ]);
});

test('church member attrition reduces quality, attenders, and believers', function () {
    expect($this->g1Church->attenders_count)->toBe(10)
        ->and($this->g1Church->health_score)->toBe(85);

    // Apply relocation attrition
    $dissolved = $this->g1Church->applyAttrition(
        reason: '2 families relocated to Tokyo for work',
        lostAttenders: 3,
        lostBelievers: 2,
        healthLoss: 20,
        gameMonth: 2
    );

    expect($dissolved)->toBeFalse()
        ->and($this->g1Church->attenders_count)->toBe(7)
        ->and($this->g1Church->believers_count)->toBe(6)
        ->and($this->g1Church->health_score)->toBe(65)
        ->and($this->g1Church->is_dissolved)->toBeFalse()
        ->and($this->g1Church->last_attrition_month)->toBe(2);
});

test('severe attrition dissolves church when health or attenders drop below critical threshold', function () {
    $dissolved = $this->g1Church->applyAttrition(
        reason: 'Relational conflict and offenses caused members to leave',
        lostAttenders: 9,
        lostBelievers: 8,
        healthLoss: 80,
        gameMonth: 3
    );

    expect($dissolved)->toBeTrue()
        ->and($this->g1Church->is_dissolved)->toBeTrue()
        ->and($this->g1Church->health_score)->toBe(0)
        ->and($this->g1Church->dissolved_game_month)->toBe(3)
        ->and($this->g1Church->dissolution_reason)->toContain('Relational conflict');
});

test('dissolved church unlinks resident npc members', function () {
    expect($this->timothy->church_id)->toBe($this->g1Church->id);

    $this->g1Church->dissolve('Prolonged illness halted gatherings', 2);

    $this->timothy->refresh();
    expect($this->timothy->church_id)->toBeNull();
});

test('dissolved church can be revitalized', function () {
    $this->g1Church->dissolve('Conflict among members', 2);
    expect($this->g1Church->is_dissolved)->toBeTrue();

    $this->g1Church->revitalize(3, $this->user);

    $this->g1Church->refresh();
    expect($this->g1Church->is_dissolved)->toBeFalse()
        ->and($this->g1Church->dissolved_game_month)->toBeNull()
        ->and($this->g1Church->health_score)->toBe(50)
        ->and($this->g1Church->attenders_count)->toBeGreaterThanOrEqual(4);
});

test('simulateChurchAttrition executes in monthly resolution and records narrative highlights', function () {
    // Seed G2 daughter church
    $g2Church = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => "Daisuke's G2 Gathering",
        'parent_church_id' => $this->g1Church->id,
        'leader_npc_id' => $this->timothy->id,
        'generation' => 2,
        'elements_json' => [
            ChurchElement::RepentanceBelief->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
        ],
        'is_healthy_church' => false,
        'health_score' => 25, // Low health makes it vulnerable
        'attenders_count' => 3,
        'believers_count' => 2,
        'baptized_count' => 2,
        'is_dissolved' => false,
        'lat' => 35.1675,
        'lng' => 136.9990,
        'founded_game_month' => 1,
    ]);

    $engine = app(FourFieldsEngine::class);
    $result = $engine->simulateChurchAttrition($this->session, 2);

    expect($result)->toHaveKey('attrition_count')
        ->and($result)->toHaveKey('dissolved_count')
        ->and($result)->toHaveKey('highlights');
});

test('church circles coaching on dissolved church revitalizes it', function () {
    $this->g1Church->dissolve('Members moved away', 2);

    $action = QueuedMonthlyAction::create([
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'game_session_id' => $this->session->id,
        'game_month' => 3,
        'game_day' => 1,
        'action_type' => MinistryActionType::ChurchCircles,
        'target_npc_id' => $this->timothy->id,
        'ap_cost' => 2,
        'status' => 'pending',
    ]);

    // Associate timothy as leader of g1Church
    $this->g1Church->update(['leader_npc_id' => $this->timothy->id]);

    $engine = app(FourFieldsEngine::class);
    $outcome = $engine->resolveSingleAction($action, 3);

    $this->g1Church->refresh();
    expect($this->g1Church->is_dissolved)->toBeFalse()
        ->and($outcome->narrativeOutcome)->toContain('Revitalized');
});

test('church generation map renders generational tiers and metric icons', function () {
    // Daughter church G2
    $g2 = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'Daisuke G2 Church',
        'parent_church_id' => $this->g1Church->id,
        'leader_npc_id' => $this->timothy->id,
        'generation' => 2,
        'elements_json' => [
            ChurchElement::RepentanceBelief->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
        ],
        'is_healthy_church' => false,
        'health_score' => 60,
        'attenders_count' => 6,
        'believers_count' => 4,
        'baptized_count' => 3,
        'is_dissolved' => false,
        'lat' => 35.1675,
        'lng' => 136.9990,
        'founded_game_month' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->assertSee('John Meito Seed Church (G1)')
        ->assertSee('Daisuke G2 Church')
        ->assertSee('Gen 1')
        ->assertSee('Gen 2')
        ->assertSee('Church Generation Map')
        ->assertSet('selectedChurchId', $this->g1Church->id);
});

test('church generation map can select a church and switch view modes', function () {
    $g2 = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'G2 Daughter Circle',
        'parent_church_id' => $this->g1Church->id,
        'generation' => 2,
        'elements_json' => [ChurchElement::RepentanceBelief->value],
        'is_healthy_church' => false,
        'health_score' => 50,
        'attenders_count' => 5,
        'believers_count' => 3,
        'baptized_count' => 2,
        'lat' => 35.1675,
        'lng' => 136.9990,
        'founded_game_month' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->call('selectChurch', $g2->id)
        ->assertSet('selectedChurchId', $g2->id)
        ->call('setViewMode', 'tiers')
        ->assertSet('viewMode', 'tiers')
        ->call('setFilter', 'active')
        ->assertSet('filterStatus', 'active')
        ->call('zoomIn')
        ->assertSet('zoomLevel', 1.15)
        ->call('resetZoom')
        ->assertSet('zoomLevel', 1.0);
});

test('church generation map can revitalize dissolved church via component action', function () {
    $this->g1Church->dissolve('Conflict between members', 2);

    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->call('revitalizeChurch', $this->g1Church->id)
        ->assertDispatched('notify');

    $this->g1Church->refresh();
    expect($this->g1Church->is_dissolved)->toBeFalse()
        ->and($this->g1Church->health_score)->toBe(50);
});

test('church generation map can queue church circles coaching with 8 ap deduction and once per week cooldown', function () {
    $this->g1Church->update(['leader_npc_id' => $this->timothy->id]);
    $initialAp = $this->user->action_points; // 10 AP

    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->call('queueCoachingForChurch', $this->g1Church->id)
        ->assertDispatched('action-queued');

    $this->user->refresh();
    // 8 AP base cost deducted (leaving 2 AP - other work is hard to do)
    expect($this->user->action_points)->toBe($initialAp - 8);

    expect(QueuedMonthlyAction::where('user_id', $this->user->id)
        ->where('action_type', MinistryActionType::ChurchCircles)
        ->where('target_npc_id', $this->timothy->id)
        ->count())->toBe(1);

    // Attempting to queue again within 7 days (e.g. Day 3) is rejected by weekly cooldown
    $this->user->update(['current_game_day' => 3, 'action_points' => 10]);
    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->call('queueCoachingForChurch', $this->g1Church->id)
        ->assertDispatched('notify');

    // Action count remains 1 and AP was not deducted
    $this->user->refresh();
    expect($this->user->action_points)->toBe(10)
        ->and(QueuedMonthlyAction::where('user_id', $this->user->id)->where('action_type', MinistryActionType::ChurchCircles)->count())->toBe(1);

    // After 7 days have passed (Day 8), coaching can be queued again!
    $this->user->update(['current_game_day' => 8, 'action_points' => 10]);
    Livewire::actingAs($this->user)
        ->test(ChurchGenerationMap::class)
        ->call('queueCoachingForChurch', $this->g1Church->id)
        ->assertDispatched('action-queued');

    $this->user->refresh();
    expect($this->user->action_points)->toBe(2)
        ->and(QueuedMonthlyAction::where('user_id', $this->user->id)->where('action_type', MinistryActionType::ChurchCircles)->count())->toBe(2);
});

test('action queue planner enforces 8 ap base cost and weekly limit on church circles', function () {
    $this->g1Church->update(['leader_npc_id' => $this->timothy->id]);
    $this->timothy->update(['status' => ReceptivityStatus::Green, 'trained_411' => true]);

    $this->user->update(['action_points' => 10, 'current_game_day' => 1]);

    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->set('selectedWardId', $this->ward->id)
        ->set('selectedNpcId', $this->timothy->id)
        ->set('selectedActionType', MinistryActionType::ChurchCircles->value)
        ->call('queueAction')
        ->assertDispatched('action-queued');

    $this->user->refresh();
    expect($this->user->action_points)->toBe(2); // 10 - 8 = 2 AP remaining

    // Attempting to schedule again on Day 3 via planner is blocked by weekly limit
    $this->user->update(['current_game_day' => 3, 'action_points' => 10]);
    Livewire::actingAs($this->user)
        ->test(ActionQueuePlanner::class)
        ->set('selectedWardId', $this->ward->id)
        ->set('selectedNpcId', $this->timothy->id)
        ->set('selectedActionType', MinistryActionType::ChurchCircles->value)
        ->call('queueAction')
        ->assertDispatched('notify');

    $this->user->refresh();
    expect($this->user->action_points)->toBe(10);
});

test('bible study groups have high probability to become a church after 6-12 months when coached and trained and can merge into neighboring groups', function () {
    $this->timothy->update([
        'status' => ReceptivityStatus::Green,
        'trained_411' => true,
        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
    ]);

    $bibleStudy = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => "Timothy's Chikusa Discovery Bible Study (G2)",
        'parent_church_id' => $this->g1Church->id,
        'leader_npc_id' => $this->timothy->id,
        'generation' => 2,
        'elements_json' => [
            ChurchElement::RepentanceBelief->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
            ChurchElement::Fellowship->value,
            ChurchElement::MakingDisciples->value,
        ],
        'is_healthy_church' => false,
        'health_score' => 72,
        'attenders_count' => 6,
        'believers_count' => 4,
        'baptized_count' => 2,
        'founded_game_month' => 2,
        'last_coached_game_month' => 2,
        'lat' => 35.1675,
        'lng' => 136.9990,
    ]);

    // Before 6 months (e.g., Month 5 => 3 months as Bible study), transition probability is 0%
    expect($bibleStudy->monthsAsBibleStudy(5))->toBe(3)
        ->and($bibleStudy->churchTransitionProbability(5))->toBe(0);

    // At 6-12 months (e.g., Month 10 => 8 months as Bible study) when coached & leader trained, probability is high (75%-95%)
    $bibleStudy->update(['last_coached_game_month' => 9]);
    expect($bibleStudy->monthsAsBibleStudy(10))->toBe(8)
        ->and($bibleStudy->isCoachedAndTrainedForChurchTransition(10))->toBeTrue()
        ->and($bibleStudy->churchTransitionProbability(10))->toBeGreaterThanOrEqual(75);

    // If uncoached for > 6 months, probability drops significantly
    $bibleStudy->update(['last_coached_game_month' => 1]);
    expect($bibleStudy->isCoachedAndTrainedForChurchTransition(10))->toBeFalse()
        ->and($bibleStudy->churchTransitionProbability(10))->toBeLessThanOrEqual(25);

    // Transitioning to a Healthy Church equips all 9 Acts 2 elements and updates status
    $bibleStudy->transitionToHealthyChurch(10);
    $bibleStudy->refresh();
    expect($bibleStudy->is_healthy_church)->toBeTrue()
        ->and($bibleStudy->hasAllCoreElements())->toBeTrue()
        ->and($bibleStudy->name)->toContain('Acts 2 Circle');

    // Another smaller Bible study group in the same ward merges into the healthy church
    $smallerStudy = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'Neighbor Household Discovery Bible Study (G2)',
        'parent_church_id' => $this->g1Church->id,
        'generation' => 2,
        'elements_json' => [ChurchElement::Word->value, ChurchElement::Prayer->value],
        'is_healthy_church' => false,
        'health_score' => 48,
        'attenders_count' => 4,
        'believers_count' => 3,
        'baptized_count' => 1,
        'founded_game_month' => 6,
        'lat' => 35.1680,
        'lng' => 136.9995,
    ]);

    $memberNpc = Npc::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'church_id' => $smallerStudy->id,
        'name' => 'Yoko Suzuki',
        'name_ja' => '鈴木 陽子',
        'status' => ReceptivityStatus::Green,
        'is_baptized' => true,
        'lat' => 35.1680,
        'lng' => 136.9995,
    ]);

    $smallerStudy->mergeInto($bibleStudy, 11);
    $smallerStudy->refresh();
    $bibleStudy->refresh();
    $memberNpc->refresh();

    expect($smallerStudy->is_dissolved)->toBeTrue()
        ->and($smallerStudy->dissolution_reason)->toContain('merged into')
        ->and($smallerStudy->attenders_count)->toBe(0)
        ->and($memberNpc->church_id)->toBe($bibleStudy->id)
        ->and($bibleStudy->attenders_count)->toBeGreaterThan(6);
});
