<?php

use App\Enums\ChurchElement;
use App\Enums\MinistryActionType;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Livewire\ChurchCircleSvg;
use App\Livewire\NagoyaMapCanvas;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use App\Notifications\MonthlyMovementReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('nagoya movement dashboard renders successfully', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->assertStatus(200)
        ->assertSee('Nagoya Oikos')
        ->call('setTab', 'map')
        ->assertSet('currentTab', 'map')
        ->assertSeeLivewire(NagoyaMapCanvas::class)
        ->call('setTab', 'churches')
        ->assertSet('currentTab', 'churches')
        ->assertSee('Acts 2 Church Circles');
});

test('home route renders full page dashboard without layout error', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test-http',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $response = $this->actingAs($user)->get('/');
    $response->assertStatus(200);
    $response->assertSee('Nagoya Oikos');

    $dashboardResponse = $this->actingAs($user)->get('/dashboard');
    $dashboardResponse->assertStatus(200);
    $dashboardResponse->assertSee('Nagoya Oikos');
});

test('welcome route renders full story and game rules page', function () {
    $response = $this->get('/welcome');
    $response->assertStatus(200);
    $response->assertSee('The Cry of Nagoya');
    $response->assertSee('The 5 Fields of Kingdom Multiplication');
    $response->assertSee('Daily Action Points (12 AP)');
    $response->assertSee('until-no-place-left-logo.jpeg');
    $response->assertSee('until-no-place-left-logo-small.png');
    $response->assertSee('x-data', false);
    $response->assertSee('$store.audio?.toggle()', false);
    $response->assertSee('livewire.js', false);
});

test('action queue planner queues relational action and deducts AP', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test-2',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
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
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 50,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->set('selectedNpcId', $npc->id)
        ->set('selectedActionType', MinistryActionType::CasualTalk->value)
        ->call('queueAction')
        ->assertDispatched('notify');

    $user->refresh();
    expect($user->action_points)->toBe(11); // 1 AP consumed for Casual Talk!
});

test('action queue planner opens and closes action info modal with full descriptive text', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test-info',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->call('openActionInfoModal', MinistryActionType::Train411->value)
        ->assertSet('showActionInfoModal', true)
        ->assertSet('infoActionType', MinistryActionType::Train411->value)
        ->assertSee('Equip with 411 Discipleship Training')
        ->assertSee('4 Questions, 1 sheet of paper, 1 hour')
        ->call('selectActionFromModal', MinistryActionType::Train411->value)
        ->assertSet('showActionInfoModal', false)
        ->assertSet('selectedActionType', MinistryActionType::Train411->value);
});

test('action queue planner supports mobile mission funnel steps with smart auto-advance', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test-funnel',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'slug' => 'meito-ku',
        'name_ja' => '名東区',
        'spiritual_openness' => 40,
        'poverty_need_index' => 20,
        'center_lat' => 35.17,
        'center_lng' => 136.99,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenta',
        'name_ja' => '健太',
        'status' => ReceptivityStatus::Yellow,
        'need_state' => NpcNeedState::None,
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'trust_level' => 30,
        'is_person_of_peace' => false,
        'lat' => 35.17,
        'lng' => 136.99,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSet('mobileStep', 2)
        ->call('setMobileStep', 1)
        ->assertSet('mobileStep', 1)
        ->call('selectWard', $ward->id)
        ->assertSet('selectedWardId', $ward->id)
        ->assertSet('mobileStep', 2)
        ->call('selectNpc', $npc->id)
        ->assertSet('selectedNpcId', $npc->id)
        ->assertSet('mobileStep', 3)
        ->call('selectWardPlatformAction', MinistryActionType::PrayerWalk->value)
        ->assertSet('selectedNpcId', null)
        ->assertSet('selectedActionType', MinistryActionType::PrayerWalk->value)
        ->assertSet('mobileStep', 3);
});

test('church circle svg visualizes acts 2 elements', function () {
    $session = GameSession::create([
        'name' => 'Session',
        'code' => 'session-c',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
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

    $church = Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Living Water Church',
        'generation' => 1,
        'elements_json' => [
            ChurchElement::RepentanceBelief->value,
            ChurchElement::Word->value,
            ChurchElement::Prayer->value,
        ],
        'is_healthy_church' => false,
        'health_score' => 40,
        'lat' => 35.16,
        'lng' => 137.00,
        'founded_game_month' => 1,
    ]);

    Livewire::test(ChurchCircleSvg::class, ['church' => $church])
        ->assertStatus(200)
        ->assertSee('Living Water Church')
        ->assertSee('Repentance & Belief');
});

test('monthly movement report notification formats mail and database payloads', function () {
    $session = GameSession::create([
        'name' => 'Session',
        'code' => 'session-n',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create(['current_game_session_id' => $session->id]);

    $report = MonthlyReport::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'game_month' => 1,
        'summary_title' => 'Month 1 Nagoya Digest',
        'highlights_json' => ['Prayed for Kenji and trust jumped!'],
        'answered_prayers_json' => ['count' => 1],
        'disciples_multiplied_count' => 1,
        'churches_planted_count' => 0,
        'generational_score_gained' => 60,
        'total_generational_score' => 120,
        'coach_tips_json' => [['tip' => 'Faithful prayer softens hearts']],
    ]);

    $notification = new MonthlyMovementReportNotification($report);
    $channels = $notification->via($user);

    expect($channels)->toContain('database')
        ->and($channels)->toContain('mail')
        ->and($channels)->toContain('broadcast');

    $mail = $notification->toMail($user);
    expect($mail->subject)->toContain('Nagoya Movement Report');

    $data = $notification->toArray($user);
    expect($data['score_gained'])->toBe(60);
});

test('nagoya map canvas renders 16 wards and layers', function () {
    $session = GameSession::create([
        'name' => 'Session Map',
        'code' => 'session-m',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);

    Livewire::test(NagoyaMapCanvas::class)
        ->assertStatus(200)
        ->assertSee('Nagoya Movement Canvas')
        ->assertSee('Heatmap')
        ->assertSee('G1–G4 Churches');
});

test('queueing share 3 circles for resident in home ward on day 2 schedules action into scheduled list and consumes 2 AP', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test-day-2',
        'current_game_month' => 1,
        'current_game_day' => 2,
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'center_lat' => 35.17,
        'center_lng' => 137.00,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'current_game_month' => 1,
        'current_game_day' => 2,
        'action_points' => 12,
        'max_action_points' => 12,
        'daily_action_points' => 12,
    ]);

    $mari = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Mari Ishikawa',
        'name_ja' => '石川 まり',
        'role_archetype' => NpcRoleArchetype::SingleParent,
        'need_state' => NpcNeedState::EmotionalCrisis,
        'status' => ReceptivityStatus::Yellow,
        'is_person_of_peace' => true,
        'trust_level' => 52,
        'lat' => 35.17,
        'lng' => 137.00,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ActionQueuePlanner::class)
        ->call('selectWard', $ward->id)
        ->assertSet('selectedWardId', $ward->id)
        ->call('selectNpc', $mari->id)
        ->assertSet('selectedNpcId', $mari->id)
        ->set('selectedActionType', MinistryActionType::Share3Circles->value)
        ->call('queueAction')
        ->assertDispatched('notify', fn ($name, $params) => ($params['type'] ?? ($params[0]['type'] ?? '')) === 'success' || str_contains(json_encode($params), 'Successfully queued'))
        ->assertSee('Scheduled for Week 1')
        ->assertSee('Share 3 Circles Gospel Presentation')
        ->assertSee('Meito-ku');

    $user->refresh();
    expect($user->action_points)->toBe(10); // 12 - 2 AP
});

test('action queue planner renders prominent desktop and mobile queue call-to-action flows', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-cta-test',
        'current_game_month' => 1,
        'current_game_day' => 3,
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'center_lat' => 35.16,
        'center_lng' => 136.96,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'current_game_month' => 1,
        'current_game_day' => 3,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Sato',
        'name_ja' => '佐藤 健二',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::EmotionalCrisis,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 40,
        'lat' => 35.16,
        'lng' => 136.96,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSet('selectedActionType', null)
        ->call('selectWard', $ward->id)
        ->call('selectNpc', $npc->id)
        ->assertSet('selectedActionType', null)
        ->assertSee('Step 1')
        ->assertSee('Step 2')
        ->assertSee('Step 3')
        ->assertSee('ready-to-queue-box')
        ->assertSee('Ready to Queue • Week 1')
        ->assertSee('No action selected yet')
        ->assertSee('Select an Action Above to Queue for Week 1')
        ->call('queueAction')
        ->assertDispatched('notify', fn ($name, $params) => ($params['type'] ?? '') === 'warning' || str_contains(json_encode($params), 'Please select an action'))
        ->set('selectedActionType', MinistryActionType::Share3Circles->value)
        ->assertSee('Share 3 Circles Gospel Presentation')
        ->assertSee('Queue for Week 1 • 2 AP')
        ->call('queueAction')
        ->assertDispatched('scroll-to-step-2')
        ->assertSet('mobileStep', 2);
});

test('action queue planner updates selected ward and refreshes people list in step 2 when ward is clicked', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-ward-switch',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'name' => 'Naka',
        'name_ja' => '中区',
        'slug' => 'naka-test',
        'population' => 90000,
        'spiritual_openness' => 30,
        'poverty_need_index' => 40,
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);

    $wardB = Ward::create([
        'name' => 'Chikusa',
        'name_ja' => '千種区',
        'slug' => 'chikusa-test',
        'population' => 165000,
        'spiritual_openness' => 45,
        'poverty_need_index' => 20,
        'center_lat' => 35.16,
        'center_lng' => 136.96,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
        'current_game_month' => 1,
        'current_game_day' => 1,
        'action_points' => 12,
        'max_action_points' => 12,
    ]);

    $npcA = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'name' => 'Taro Naka',
        'name_ja' => '中 太郎',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 20,
        'lat' => 35.17,
        'lng' => 136.90,
    ]);

    $npcB = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardB->id,
        'name' => 'Hanako Chikusa',
        'name_ja' => '千種 花子',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'need_state' => NpcNeedState::None,
        'status' => ReceptivityStatus::Green,
        'trust_level' => 50,
        'lat' => 35.16,
        'lng' => 136.96,
    ]);

    $this->actingAs($user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSet('selectedWardId', $wardA->id)
        ->assertSee('Taro Naka')
        ->assertDontSee('Hanako Chikusa')
        ->call('selectWard', $wardB->id)
        ->assertSet('selectedWardId', $wardB->id)
        ->assertSet('mobileStep', 2)
        ->assertSet('selectedNpcId', null)
        ->assertSee('Hanako Chikusa')
        ->assertDontSee('Taro Naka');
});
