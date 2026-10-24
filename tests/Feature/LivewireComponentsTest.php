<?php

use App\Enums\ChurchElement;
use App\Enums\MinistryActionType;
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
        ->assertSee('Nagoya Oikos');
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
