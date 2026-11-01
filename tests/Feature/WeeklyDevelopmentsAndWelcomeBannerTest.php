<?php

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test',
        'current_game_month' => 1,
        'current_game_week' => 2,
        'current_game_day' => 8,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addMinutes(15),
        'is_active' => true,
    ]);

    $this->ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'population' => 90000,
        'district_type' => 'Urban Central',
        'receptivity_level' => 3,
        'coordinates' => ['lat' => 35.17, 'lng' => 136.90],
    ]);

    $this->user = User::factory()->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'action_points' => 20,
        'max_action_points' => 20,
        'current_game_month' => 1,
        'current_game_week' => 2,
        'hide_welcome_guide' => false,
    ]);
});

test('new to nagoya oikos guide is shown by default and can be hidden to remember player choice', function () {
    $this->actingAs($this->user);

    // Initial state: guide is visible
    $component = Livewire::test(ActionQueuePlanner::class)
        ->assertSee('New to Nagoya Oikos?')
        ->assertSee('Read Story')
        ->assertSet('hideWelcomeGuide', false);

    // Toggle/dismiss guide
    $component->call('toggleWelcomeGuide')
        ->assertSet('hideWelcomeGuide', true)
        ->assertDontSee('Read Story')
        ->assertSee('New to Nagoya Oikos? Show Field Guide');

    // Confirm persisted in database
    expect($this->user->fresh()->hide_welcome_guide)->toBeTrue();

    // Re-mount component: remains hidden
    Livewire::test(ActionQueuePlanner::class)
        ->assertSet('hideWelcomeGuide', true)
        ->assertDontSee('Read Story')
        ->assertSee('New to Nagoya Oikos? Show Field Guide')
        // Can be shown again
        ->call('toggleWelcomeGuide')
        ->assertSet('hideWelcomeGuide', false)
        ->assertSee('Read Story');

    expect($this->user->fresh()->hide_welcome_guide)->toBeFalse();
});

test('recent weekly developments card is displayed at top and opens modal for last 4 weeks', function () {
    $this->actingAs($this->user);

    $npc = Npc::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'Kenji Tanaka',
        'gender' => 'Male',
        'age' => 32,
        'receptivity' => ReceptivityStatus::Green,
    ]);

    // Create processed action for Week 1
    QueuedMonthlyAction::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::CasualTalk,
        'status' => QueuedActionStatus::Processed,
        'game_month' => 1,
        'game_week' => 1,
        'game_day' => 3,
        'ap_cost' => 2,
        'outcome_notes' => 'Connected with Kenji over coffee in Sakae.',
    ]);

    // Create processed action for Week 2 (Current)
    QueuedMonthlyAction::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->user->id,
        'ward_id' => $this->ward->id,
        'target_npc_id' => $npc->id,
        'action_type' => MinistryActionType::Share3Circles,
        'status' => QueuedActionStatus::Processed,
        'game_month' => 1,
        'game_week' => 2,
        'game_day' => 8,
        'ap_cost' => 3,
        'outcome_notes' => 'Shared 3 Circles visual gospel; Kenji listened attentively.',
    ]);

    $component = Livewire::test(ActionQueuePlanner::class)
        ->assertSee('Recent Weekly Developments')
        ->assertSee('Last 4 Weeks')
        ->assertSee('View 4-Week Log')
        ->assertSet('showWeeklyDevelopmentsModal', false);

    // Click to open modal
    $component->call('openWeeklyDevelopmentsModal')
        ->assertSet('showWeeklyDevelopmentsModal', true)
        ->assertSee('Connected with Kenji over coffee in Sakae.')
        ->assertSee('Shared 3 Circles visual gospel; Kenji listened attentively.')
        ->assertSee('Month 1, Week 1')
        ->assertSee('Month 1, Week 2');

    // Close modal
    $component->call('closeWeeklyDevelopmentsModal')
        ->assertSet('showWeeklyDevelopmentsModal', false);
});

test('player committed to intensive japanese language school does not see top banner text', function () {
    $this->user->update([
        'committed_to_language_training' => true,
        'language_training_enrolled_month' => 1,
        'language_training_completed' => false,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertDontSee('New to Nagoya Oikos?')
        ->assertDontSee('Explore the biblical narrative, the 5 Fields of CPM multiplication')
        ->assertDontSee('Relational evangelization takes steady faithfulness')
        ->assertSee('Intensive Japanese Language School & First-Level NPL Foundation');
});

test('chosen home ward is displayed under nagoya relational action hq header', function () {
    $this->actingAs($this->user);

    Livewire::test(ActionQueuePlanner::class)
        ->assertSee('Nagoya Relational Action HQ')
        ->assertSee('Home Ward:')
        ->assertSee('Naka-ku');
});
