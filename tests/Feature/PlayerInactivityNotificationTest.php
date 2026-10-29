<?php

declare(strict_types=1);

use App\Console\Commands\SendInactivityRemindersCommand;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use App\Notifications\PlayerInactivityReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = GameSession::create([
        'name' => 'Inactivity Test Session',
        'code' => 'inactivity-test-session',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'is_active' => true,
    ]);

    $this->ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.17,
        'center_lng' => 136.90,
    ]);
});

test('player who played today or yesterday does not receive inactivity reminder', function () {
    Notification::fake();

    $playedToday = User::factory()->create([
        'name' => 'Active Player',
        'email' => 'active@example.com',
        'notify_email' => true,
        'is_ai_colaborer' => false,
        'last_played_real_date' => now()->toDateString(),
        'current_game_session_id' => $this->session->id,
    ]);

    $playedYesterday = User::factory()->create([
        'name' => 'Yesterday Player',
        'email' => 'yesterday@example.com',
        'notify_email' => true,
        'is_ai_colaborer' => false,
        'last_played_real_date' => now()->subDay()->toDateString(),
        'current_game_session_id' => $this->session->id,
    ]);

    $this->artisan(SendInactivityRemindersCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($playedToday, PlayerInactivityReminderNotification::class);
    Notification::assertNotSentTo($playedYesterday, PlayerInactivityReminderNotification::class);
});

test('player who forgot to play 2 real life days in a row receives email reminder', function () {
    Notification::fake();

    $inactivePlayer = User::factory()->create([
        'name' => 'Sarah Disciple',
        'email' => 'sarah@example.com',
        'notify_email' => true,
        'is_ai_colaborer' => false,
        'home_ward_id' => $this->ward->id,
        'last_played_real_date' => now()->subDays(2)->toDateString(),
        'current_game_session_id' => $this->session->id,
    ]);

    $this->artisan(SendInactivityRemindersCommand::class)
        ->expectsOutputToContain('Sent inactivity reminder to Sarah Disciple')
        ->assertSuccessful();

    Notification::assertSentTo($inactivePlayer, PlayerInactivityReminderNotification::class, function ($notification) use ($inactivePlayer) {
        $mail = $notification->toMail($inactivePlayer);

        expect($notification->daysInactive)->toBe(2);
        expect($mail->subject)->toContain('Nagoya misses you, Sarah Disciple');

        return true;
    });

    $inactivePlayer->refresh();
    expect($inactivePlayer->last_inactivity_reminder_sent_at)->not->toBeNull();
});

test('player who opted out of notifications does not receive email reminder', function () {
    Notification::fake();

    $optedOutPlayer = User::factory()->create([
        'name' => 'Quiet Disciple',
        'email' => 'quiet@example.com',
        'notify_email' => false,
        'is_ai_colaborer' => false,
        'last_played_real_date' => now()->subDays(3)->toDateString(),
        'current_game_session_id' => $this->session->id,
    ]);

    $this->artisan(SendInactivityRemindersCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($optedOutPlayer, PlayerInactivityReminderNotification::class);
});

test('ai colaborers never receive inactivity reminder emails', function () {
    Notification::fake();

    $aiColaborer = User::factory()->create([
        'name' => 'Kenji Tanaka (AI)',
        'email' => 'ai.kenji@example.com',
        'notify_email' => true,
        'is_ai_colaborer' => true,
        'last_played_real_date' => now()->subDays(5)->toDateString(),
        'current_game_session_id' => $this->session->id,
    ]);

    $this->artisan(SendInactivityRemindersCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($aiColaborer, PlayerInactivityReminderNotification::class);
});

test('duplicate inactivity reminders are not sent for the same inactivity streak', function () {
    Notification::fake();

    $inactivePlayer = User::factory()->create([
        'name' => 'Lydia',
        'email' => 'lydia@example.com',
        'notify_email' => true,
        'is_ai_colaborer' => false,
        'last_played_real_date' => now()->subDays(2)->toDateString(),
        'current_game_session_id' => $this->session->id,
    ]);

    // First run sends reminder
    $this->artisan(SendInactivityRemindersCommand::class)->assertSuccessful();
    Notification::assertSentToTimes($inactivePlayer, PlayerInactivityReminderNotification::class, 1);

    // Second run should NOT send a duplicate
    $this->artisan(SendInactivityRemindersCommand::class)->assertSuccessful();
    Notification::assertSentToTimes($inactivePlayer, PlayerInactivityReminderNotification::class, 1);
});

test('player can opt out of email notifications via signed unsubscribe link', function () {
    $player = User::factory()->create([
        'name' => 'Timothy',
        'email' => 'timothy@example.com',
        'notify_email' => true,
        'current_game_session_id' => $this->session->id,
    ]);

    $signedOptOutUrl = URL::signedRoute('notifications.opt-out', ['user' => $player->id]);

    $response = $this->get($signedOptOutUrl);

    $response->assertOk();
    $response->assertSee('Opted Out');
    $response->assertSee('timothy@example.com');

    $player->refresh();
    expect($player->notify_email)->toBeFalse();
});

test('tampered opt-out link is rejected', function () {
    $player = User::factory()->create([
        'notify_email' => true,
        'current_game_session_id' => $this->session->id,
    ]);

    $tamperedUrl = route('notifications.opt-out', [
        'user' => $player->id,
        'signature' => 'invalid-signature-hash',
    ]);

    $response = $this->get($tamperedUrl);

    $response->assertForbidden();
    $player->refresh();
    expect($player->notify_email)->toBeTrue();
});

test('player can opt back in to notifications via signed opt-in form', function () {
    $player = User::factory()->create([
        'notify_email' => false,
        'current_game_session_id' => $this->session->id,
    ]);

    $signedOptInUrl = URL::signedRoute('notifications.opt-in', ['user' => $player->id]);

    $response = $this->post($signedOptInUrl);

    $response->assertRedirect();
    $player->refresh();
    expect($player->notify_email)->toBeTrue();
});

test('player can toggle notification preferences from livewire dashboard profile modal', function () {
    $player = User::factory()->create([
        'name' => 'Original Name',
        'nationality' => 'Japanese',
        'age' => 28,
        'notify_email' => true,
        'current_game_session_id' => $this->session->id,
    ]);

    $this->actingAs($player);

    // Turn notifications OFF
    Livewire::test(NagoyaMovementDashboard::class)
        ->call('openEditNameModal')
        ->assertSet('editingNotifyEmail', true)
        ->set('editingNotifyEmail', false)
        ->call('savePlayerName');

    $player->refresh();
    expect($player->notify_email)->toBeFalse();

    // Turn notifications back ON
    Livewire::test(NagoyaMovementDashboard::class)
        ->call('openEditNameModal')
        ->assertSet('editingNotifyEmail', false)
        ->set('editingNotifyEmail', true)
        ->call('savePlayerName');

    $player->refresh();
    expect($player->notify_email)->toBeTrue();
});
