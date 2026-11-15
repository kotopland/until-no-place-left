<?php

declare(strict_types=1);

use App\Jobs\ProcessGameMonthJob;
use App\Livewire\Auth\PasswordlessLogin;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Models\Ward;
use App\Notifications\MagicLoginLinkNotification;
use App\Notifications\MonthlyMovementReportNotification;
use App\Notifications\PlayerInactivityReminderNotification;
use App\Services\AiColaborerService;
use App\Services\FourFieldsEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('computer generated players are never emailed during monthly game tick', function (): void {
    Notification::fake();

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'population' => 165000,
        'center_lat' => 35.1667,
        'center_lng' => 137.0000,
    ]);

    $session = GameSession::create([
        'name' => 'Nagoya Harvest',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $computerPlayer = User::factory()->create([
        'name' => 'Kenji Yamada',
        'email' => User::LEAD_PIONEER_EMAIL,
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => true,
        'notify_email' => true,
    ]);

    $humanPlayer = User::factory()->create([
        'name' => 'Human Missionary',
        'email' => 'human@example.com',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'is_ai_colaborer' => false,
        'notify_email' => true,
    ]);

    $job = new ProcessGameMonthJob($session);
    $job->handle(app(FourFieldsEngine::class));

    Notification::assertNotSentTo($computerPlayer, MonthlyMovementReportNotification::class);
    Notification::assertSentTo($humanPlayer, MonthlyMovementReportNotification::class);
});

test('notification channels and mail routing block computer generated players and invalid domains', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $computerPlayer = User::factory()->create([
        'email' => User::SARAH_EMAIL,
        'current_game_session_id' => $session->id,
        'is_ai_colaborer' => true,
        'notify_email' => true,
    ]);

    $invalidDomainUser = User::factory()->create([
        'email' => 'npc.bot@nagoyaharvest.invalid',
        'current_game_session_id' => $session->id,
        'is_ai_colaborer' => false,
        'notify_email' => true,
    ]);

    $report = MonthlyReport::create([
        'game_session_id' => $session->id,
        'user_id' => $computerPlayer->id,
        'game_month' => 1,
        'summary_title' => 'Month 1 Summary',
        'highlights_json' => [],
        'answered_prayers_json' => ['count' => 0, 'notes' => ''],
        'disciples_multiplied_count' => 0,
        'churches_planted_count' => 0,
        'generational_score_gained' => 0,
        'total_generational_score' => 0,
        'coach_tips_json' => [],
    ]);

    $monthlyNotification = new MonthlyMovementReportNotification($report);
    $inactivityNotification = new PlayerInactivityReminderNotification(3);
    $magicNotification = new MagicLoginLinkNotification('http://example.com/magic', 'Bot');

    expect($computerPlayer->routeNotificationForMail())->toBeNull()
        ->and($invalidDomainUser->routeNotificationForMail())->toBeNull()
        ->and($monthlyNotification->via($computerPlayer))->not->toContain('mail')
        ->and($monthlyNotification->via($invalidDomainUser))->not->toContain('mail')
        ->and($inactivityNotification->via($computerPlayer))->not->toContain('mail')
        ->and($inactivityNotification->via($invalidDomainUser))->not->toContain('mail')
        ->and($magicNotification->via($computerPlayer))->toBe([]);
});

test('passwordless login refuses to email or sign in computer generated players', function (): void {
    Notification::fake();

    $computerPlayer = User::factory()->create([
        'name' => 'Sarah Jenkins',
        'email' => User::SARAH_EMAIL,
        'is_ai_colaborer' => true,
    ]);

    Livewire::test(PasswordlessLogin::class)
        ->set('email', User::SARAH_EMAIL)
        ->call('sendMagicLink')
        ->assertHasErrors(['email'])
        ->assertSet('sent', false);

    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'anyone@nagoyaharvest.invalid')
        ->set('name', 'Bot Player')
        ->set('nationality', 'Japanese')
        ->set('age', 25)
        ->call('sendMagicLink')
        ->assertHasErrors(['email'])
        ->assertSet('sent', false);

    Notification::assertNothingSent();

    $signedUrl = URL::temporarySignedRoute(
        'auth.magic-link',
        now()->addMinutes(30),
        ['email' => $computerPlayer->email]
    );

    $this->get($signedUrl)
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

test('ai colaborers are provisioned with non-existing invalid emails and mail disabled', function (): void {
    Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'population' => 165000,
        'center_lat' => 35.1667,
        'center_lng' => 137.0000,
    ]);

    $session = GameSession::create([
        'name' => 'Nagoya Harvest',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $colaborers = app(AiColaborerService::class)->ensureAiColaborers($session);

    expect($colaborers)->toHaveCount(4);

    foreach ($colaborers as $colaborer) {
        expect($colaborer->email)->toEndWith('.invalid')
            ->and($colaborer->notify_email)->toBeFalse()
            ->and($colaborer->isComputerGeneratedPlayer())->toBeTrue();
    }
});
