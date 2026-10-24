<?php

declare(strict_types=1);

use App\Enums\ChurchElement;
use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('game:reset command resets game session to month 1 day 1 and re-seeds initial state', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 4,
        'current_game_day' => 18,
        'calendar_date' => '2026-07-18',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'center_lat' => 35.1666,
        'center_lng' => 137.0000,
    ]);

    $user = User::factory()->create([
        'name' => 'Active Missionary',
        'email' => 'active@nagoyaharvest.jp',
        'current_game_session_id' => $session->id,
        'action_points' => 3,
        'max_action_points' => 12,
        'total_generational_score' => 240,
        'coop_synergy_score' => 50,
        'days_played_today' => 12,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Generated Multiplier NPC',
        'name_ja' => '生成された弟子',
        'lat' => 35.16,
        'lng' => 137.00,
        'trust_level' => 90,
    ]);

    // Create transient records (queued actions, reports, churches)
    QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'action_type' => MinistryActionType::CasualTalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 4,
        'game_day' => 18,
        'ap_cost' => 1,
    ]);

    MonthlyReport::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'game_month' => 3,
        'summary_title' => 'Month 3 Harvest Report',
        'highlights_json' => ['Disciples multiplied'],
        'answered_prayers_json' => ['Seekers opened home'],
        'coach_tips_json' => ['Multiply 411 groups'],
        'generational_score_gained' => 40,
        'total_generational_score' => 240,
        'disciples_multiplied_count' => 2,
        'churches_planted_count' => 1,
    ]);

    Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Advanced G3 Discovery Church',
        'generation' => 3,
        'elements_json' => [ChurchElement::Word->value],
        'is_healthy_church' => false,
        'health_score' => 30,
        'lat' => 35.16,
        'lng' => 137.00,
        'founded_game_month' => 3,
    ]);

    // Execute the game:reset command with --force
    $this->artisan('game:reset', ['--force' => true])
        ->expectsOutputToContain('Resetting session: Nagoya Test Session [nagoya-2026]...')
        ->expectsOutputToContain('Nagoya Oikos game simulation has been successfully reset!')
        ->assertSuccessful();

    // Verify session timeline is reset to Month 1, Day 1
    $session->refresh();
    expect($session->current_game_month)->toBe(1)
        ->and($session->current_game_day)->toBe(1)
        ->and($session->calendar_date->toDateString())->toBe('2026-04-01');

    // Verify transient records are cleared
    expect(QueuedMonthlyAction::where('game_session_id', $session->id)->count())->toBe(0)
        ->and(MonthlyReport::where('game_session_id', $session->id)->count())->toBe(0)
        ->and(Church::where('name', 'Advanced G3 Discovery Church')->count())->toBe(0);

    // Verify player is preserved and AP restored to 12
    $user->refresh();
    expect($user->action_points)->toBe(12)
        ->and($user->days_played_today)->toBe(0)
        ->and($user->total_generational_score)->toBe(0)
        ->and($user->email)->toBe('active@nagoyaharvest.jp');

    // Verify pristine archetypal G1 church and NPCs are re-seeded
    expect(Church::where('name', 'Meito Living Seed (G1)')->exists())->toBeTrue()
        ->and(Npc::where('name', 'Daisuke Kato')->exists())->toBeTrue()
        ->and(Npc::where('name', 'Kenji Tanaka')->exists())->toBeTrue();
});

test('game:reset command respects confirmation prompt when cancelled', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 5,
        'current_game_day' => 20,
        'calendar_date' => '2026-08-20',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $this->artisan('game:reset')
        ->expectsConfirmation('Are you sure you want to reset [Nagoya Test Session (nagoya-2026)] back to Month 1 Day 1? This will clear queued actions, dynamic churches, reports, and restore starting Action Points.', 'no')
        ->expectsOutputToContain('Game reset cancelled.')
        ->assertSuccessful();

    $session->refresh();
    expect($session->current_game_month)->toBe(5);
});
