<?php

declare(strict_types=1);

use App\Livewire\Auth\PasswordlessLogin;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\GameSession;
use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('login page renders successfully with player name creation and email fields', function (): void {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Create Your Player Name');
    $response->assertSee('Missionary / Player Name');
    $response->assertSee('Email Address');
});

test('unauthenticated user hitting dashboard is redirected to login', function (): void {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('new user registration requires a player name and valid email', function (): void {
    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'newplayer@example.com')
        ->set('name', '')
        ->call('sendMagicLink')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'not-an-email')
        ->set('name', 'Sarah')
        ->call('sendMagicLink')
        ->assertHasErrors(['email']);
});

test('submitting login form dispatches magic login notification with signed url', function (): void {
    Notification::fake();

    Livewire::test(PasswordlessLogin::class)
        ->set('name', 'Kenji Tanaka')
        ->set('email', 'kenji@example.com')
        ->call('sendMagicLink')
        ->assertSet('sent', true)
        ->assertSet('sentEmail', 'kenji@example.com');

    Notification::assertSentOnDemand(
        MagicLoginLinkNotification::class,
        function (MagicLoginLinkNotification $notification, array $channels, object $notifiable): bool {
            return $notifiable->routes['mail'] === 'kenji@example.com'
                && str_contains($notification->url, 'auth/magic-link')
                && $notification->name === 'Kenji Tanaka'
                && $notification->isNewUser === true;
        }
    );
});

test('verifying signed magic link creates new user with player name and logs them in', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest 2026',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'auth.magic-link',
        now()->addMinutes(30),
        [
            'email' => 'pioneer@nagoya.jp',
            'name' => 'Missionary Paul',
        ]
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect('/');

    $this->assertAuthenticated();
    $user = Auth::user();
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('pioneer@nagoya.jp')
        ->and($user->name)->toBe('Missionary Paul')
        ->and($user->current_game_session_id)->toBe($session->id)
        ->and($user->action_points)->toBe(12)
        ->and($user->max_action_points)->toBe(12);
});

test('existing user clicking magic link logs in without duplicating record', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Harvest',
        'code' => 'nagoya-harvest',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $existing = User::create([
        'name' => 'Original Leader',
        'email' => 'leader@nagoya.jp',
        'password' => bcrypt('secret'),
        'current_game_session_id' => $session->id,
        'action_points' => 9,
        'max_action_points' => 12,
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'auth.magic-link',
        now()->addMinutes(30),
        [
            'email' => 'leader@nagoya.jp',
            'name' => 'Original Leader',
        ]
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($existing);
    expect(User::where('email', 'leader@nagoya.jp')->count())->toBe(1);
});

test('invalid or expired signature redirects to login with error message', function (): void {
    $tamperedUrl = route('auth.magic-link', [
        'email' => 'hacker@example.com',
        'signature' => 'invalid-signature-hash',
    ]);

    $response = $this->get($tamperedUrl);

    $response->assertRedirect('/login');
    $response->assertSessionHas('error');
    $this->assertGuest();
});

test('authenticated player can customize their name from dashboard', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-test-name',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'name' => 'Old Name',
        'current_game_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->call('openEditNameModal')
        ->assertSet('showEditNameModal', true)
        ->set('editingName', 'New Missionary Name')
        ->call('savePlayerName')
        ->assertSet('showEditNameModal', false)
        ->assertDispatched('notify');

    $user->refresh();
    expect($user->name)->toBe('New Missionary Name');
});

test('logging out invalidates session and redirects to welcome page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/welcome');
    $this->assertGuest();
});
