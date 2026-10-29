<?php

declare(strict_types=1);

use App\Livewire\Auth\PasswordlessLogin;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\GameSession;
use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use App\Services\PersonAvatarService;
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
    $response->assertSee('Country of Origin');
    $response->assertSee('Age (Years)');
    $response->assertSee('Email Address');
});

test('unauthenticated user hitting dashboard is redirected to login', function (): void {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('root view renders public invitation to reach nagoya with jesus until no place left', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Reach Nagoya with Jesus');
    $response->assertSee('Until There is No Place Left');
    $response->assertSee('Romans 15:23');
    $response->assertSee('The Four Fields');
    $response->assertSee('Nagoya Wards');
});

test('new user registration requires a player name and valid email and age over 18', function (): void {
    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'newplayer@example.com')
        ->set('name', '')
        ->set('age', 25)
        ->call('sendMagicLink')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'not-an-email')
        ->set('name', 'Sarah')
        ->set('age', 25)
        ->call('sendMagicLink')
        ->assertHasErrors(['email']);

    // Age 18 must fail validation (must be older than 18)
    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'young@example.com')
        ->set('name', 'Timothy')
        ->set('age', 18)
        ->call('sendMagicLink')
        ->assertHasErrors(['age' => 'gt']);

    // Age 17 must fail validation
    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'minor@example.com')
        ->set('name', 'Timothy')
        ->set('age', 17)
        ->call('sendMagicLink')
        ->assertHasErrors(['age' => 'gt']);

    // Age 19 must pass validation
    Livewire::test(PasswordlessLogin::class)
        ->set('email', 'adult@example.com')
        ->set('name', 'Adult Timothy')
        ->set('age', 19)
        ->call('sendMagicLink')
        ->assertHasNoErrors(['age']);
});

test('submitting login form dispatches magic login notification with signed url and nationality and age', function (): void {
    Notification::fake();

    Livewire::test(PasswordlessLogin::class)
        ->set('name', 'Kenji Tanaka')
        ->set('nationality', 'Japanese')
        ->set('age', 28)
        ->set('email', 'kenji@example.com')
        ->call('sendMagicLink')
        ->assertSet('sent', true)
        ->assertSet('sentEmail', 'kenji@example.com');

    Notification::assertSentOnDemand(
        MagicLoginLinkNotification::class,
        function (MagicLoginLinkNotification $notification, array $channels, object $notifiable): bool {
            return $notifiable->routes['mail'] === 'kenji@example.com'
                && str_contains($notification->url, 'auth/magic-link')
                && str_contains($notification->url, 'nationality=Japanese')
                && str_contains($notification->url, 'age=28')
                && $notification->name === 'Kenji Tanaka'
                && $notification->isNewUser === true;
        }
    );
});

test('verifying signed magic link creates new user with player name and nationality and age and logs them in', function (): void {
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
            'nationality' => 'American',
            'age' => 27,
        ]
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect('/dashboard');

    $this->assertAuthenticated();
    $user = Auth::user();
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('pioneer@nagoya.jp')
        ->and($user->name)->toBe('Missionary Paul')
        ->and($user->nationality)->toBe('American')
        ->and($user->nationalityFlag)->toBe('🇺🇸')
        ->and($user->age)->toBe(27)
        ->and($user->current_game_session_id)->toBe($session->id)
        ->and($user->action_points)->toBe(20)
        ->and($user->max_action_points)->toBe(26);
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

    $response->assertRedirect('/dashboard');
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

test('authenticated player can customize their name, country of origin, and age from dashboard', function (): void {
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
        'nationality' => 'Japanese',
        'age' => 30,
        'current_game_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    // Reject age <= 18
    Livewire::test(NagoyaMovementDashboard::class)
        ->call('openEditNameModal')
        ->set('editingAge', 18)
        ->call('savePlayerName')
        ->assertHasErrors(['editingAge' => 'gt'])
        ->assertSet('showEditNameModal', true);

    // Successfully save valid profile
    Livewire::test(NagoyaMovementDashboard::class)
        ->call('openEditNameModal')
        ->assertSet('showEditNameModal', true)
        ->set('editingName', 'New Missionary Name')
        ->set('editingNationality', 'Brazilian')
        ->set('editingAge', 34)
        ->call('savePlayerName')
        ->assertSet('showEditNameModal', false)
        ->assertDispatched('notify');

    $user->refresh();
    expect($user->name)->toBe('New Missionary Name')
        ->and($user->nationality)->toBe('Brazilian')
        ->and($user->nationalityFlag)->toBe('🇧🇷')
        ->and($user->age)->toBe(34);
});

test('expanded searchable country list contains global nations with flags and ISO codes', function (): void {
    $service = app(PersonAvatarService::class);
    $countries = $service->commonCountries();

    expect(count($countries))->toBeGreaterThanOrEqual(90);

    // Verify key diverse nations across continents
    $nationalities = array_column($countries, 'nationality');
    expect($nationalities)->toContain('Japanese')
        ->toContain('American')
        ->toContain('Brazilian')
        ->toContain('Korean')
        ->toContain('Colombian')
        ->toContain('Kenyan')
        ->toContain('Ukrainian')
        ->toContain('Indian')
        ->toContain('Filipino')
        ->toContain('Ghanaian');

    expect($service->flagEmoji('Colombian'))->toBe('🇨🇴')
        ->and($service->flagEmoji('Kenyan'))->toBe('🇰🇪')
        ->and($service->flagEmoji('Ukrainian'))->toBe('🇺🇦')
        ->and($service->countryCode('Colombian'))->toBe('CO')
        ->and($service->countryCode('Kenyan'))->toBe('KE')
        ->and($service->countryCode('Ukrainian'))->toBe('UA');
});

test('player can select from expanded global countries during registration and profile update', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session 2',
        'code' => 'nagoya-test-colombia',
        'current_game_month' => 1,
        'calendar_date' => '2026-04-01',
        'next_tick_at' => now()->addHours(12),
        'is_active' => true,
    ]);

    // Test registration with Colombian nationality
    Livewire::test(PasswordlessLogin::class)
        ->set('name', 'Carlos Rodriguez')
        ->set('nationality', 'Colombian')
        ->set('age', 29)
        ->set('email', 'carlos@example.com')
        ->assertSet('nationality', 'Colombian')
        ->assertSee('Colombia')
        ->assertSee('🇨🇴');

    // Test profile update with Kenyan nationality
    $user = User::factory()->create([
        'name' => 'Amani Kiprop',
        'nationality' => 'Japanese',
        'age' => 31,
        'current_game_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    Livewire::test(NagoyaMovementDashboard::class)
        ->call('openEditNameModal')
        ->set('editingNationality', 'Kenyan')
        ->call('savePlayerName')
        ->assertSet('showEditNameModal', false);

    $user->refresh();
    expect($user->nationality)->toBe('Kenyan')
        ->and($user->nationalityFlag)->toBe('🇰🇪');
});

test('nationality property hook normalizes array input to prevent TypeError', function (): void {
    // Array payload with 'nationality' key
    $component = new PasswordlessLogin;
    $component->nationality = ['nationality' => 'Brazilian'];
    expect($component->nationality)->toBe('Brazilian');

    // Indexed array payload
    $component->nationality = ['Canadian'];
    expect($component->nationality)->toBe('Canadian');

    // Standard string payload
    $component->nationality = 'German';
    expect($component->nationality)->toBe('German');

    // Dashboard editingNationality property hook
    $dashboard = new NagoyaMovementDashboard;
    $dashboard->editingNationality = ['nationality' => 'Peruvian'];
    expect($dashboard->editingNationality)->toBe('Peruvian');

    $dashboard->editingNationality = ['Indian'];
    expect($dashboard->editingNationality)->toBe('Indian');
});

test('logging out invalidates session and redirects to welcome page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/welcome');
    $this->assertGuest();
});
