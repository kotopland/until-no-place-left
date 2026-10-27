<?php

declare(strict_types=1);

use App\Livewire\GameChat;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\GameSession;
use App\Models\MovementCelebration;
use App\Models\User;
use App\Models\Ward;
use App\Services\GameChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->session = GameSession::create([
        'name' => 'Nagoya Movement Session',
        'is_active' => true,
        'current_game_month' => 2,
        'current_game_week' => 3,
    ]);

    $this->ward = Ward::create([
        'game_session_id' => $this->session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1678,
        'longitude' => 136.9639,
        'population' => 160000,
        'spiritual_openness' => 35,
    ]);

    $this->nakaWard = Ward::create([
        'game_session_id' => $this->session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1709,
        'longitude' => 136.9077,
        'population' => 90000,
        'spiritual_openness' => 25,
    ]);

    $this->user = User::factory()->create([
        'name' => 'Missionary John',
        'nationality' => 'American',
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 2,
        'current_game_week' => 3,
        'screening_passed' => true,
        'has_seen_host_intro' => true,
    ]);

    $this->coLaborer = User::factory()->create([
        'name' => 'Sarah Jenkins',
        'nationality' => 'American',
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'is_ai_colaborer' => true,
        'current_game_month' => 2,
        'current_game_week' => 3,
    ]);
});

test('chat messages are created with in-game turn context and real-world timestamp', function () {
    $chatService = app(GameChatService::class);

    $msg = $chatService->postMessage(
        user: $this->user,
        message: 'Starting a prayer walk around Chikusa station.',
        channel: 'citywide'
    );

    expect($msg)->toBeInstanceOf(ChatMessage::class)
        ->and($msg->message)->toBe('Starting a prayer walk around Chikusa station.')
        ->and($msg->channel)->toBe('citywide')
        ->and($msg->game_month)->toBe(2)
        ->and($msg->game_week)->toBe(3)
        ->and($msg->timeContextLabel)->toBe('Year 1, M2 • Wk 3')
        ->and($this->user->fresh()->last_read_chat_at)->not->toBeNull();
});

test('messages can be targeted to specific wards and pinned as directives', function () {
    $chatService = app(GameChatService::class);

    $msg = $chatService->postMessage(
        user: $this->user,
        message: 'Calling all workers to focus on Sakae university campus this month.',
        channel: 'ward',
        wardId: $this->nakaWard->id,
        isPinned: true
    );

    expect($msg->channel)->toBe('ward')
        ->and($msg->ward_id)->toBe($this->nakaWard->id)
        ->and($msg->is_pinned)->toBeTrue();
});

test('reactions can be toggled and accurately grouped by reaction type', function () {
    $chatService = app(GameChatService::class);

    $msg = $chatService->postMessage(
        user: $this->coLaborer,
        message: 'God is opening hearts among medical students in Chikusa!',
        channel: 'citywide'
    );

    // Toggle Amen on
    $added = $chatService->toggleReaction($this->user, $msg, 'amen');
    expect($added)->toBeTrue();
    expect(ChatMessageReaction::where('chat_message_id', $msg->id)->count())->toBe(1);

    $grouped = $msg->fresh()->groupedReactions($this->user->id);
    expect($grouped['amen']['count'])->toBe(1)
        ->and($grouped['amen']['reacted_by_auth'])->toBeTrue()
        ->and($grouped['amen']['user_names'])->toContain('Missionary John');

    // Toggle Amen off
    $removed = $chatService->toggleReaction($this->user, $msg, 'amen');
    expect($removed)->toBeFalse();
    expect(ChatMessageReaction::where('chat_message_id', $msg->id)->count())->toBe(0);
});

test('unread chat count properly tracks other players messages since last read', function () {
    $chatService = app(GameChatService::class);

    // Initial state: John is caught up
    $this->user->update(['last_read_chat_at' => now()->subHour()]);

    expect($this->user->unreadChatCount())->toBe(0);

    // Sarah posts a new dispatch 10 minutes ago
    $msg = ChatMessage::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->coLaborer->id,
        'channel' => 'citywide',
        'message' => 'New seeker attended discovery Bible study.',
        'game_month' => 2,
        'game_week' => 3,
        'created_at' => now()->subMinutes(10),
    ]);

    expect($this->user->unreadChatCount())->toBe(1);

    // John posts his own message - should not count as unread to himself
    $chatService->postMessage($this->user, 'Amen Sarah, great news!');

    expect($this->user->unreadChatCount())->toBe(0);
});

test('posting in prayer channel triggers asynchronous AI co-laborer amen reaction', function () {
    $chatService = app(GameChatService::class);

    $prayerMsg = $chatService->postMessage(
        user: $this->user,
        message: 'Please pray for Mr. Sato who is asking about the resurrection.',
        channel: 'prayer'
    );

    expect($prayerMsg->channel)->toBe('prayer');

    // AI Co-laborer should have added an 'amen' reaction
    $reaction = ChatMessageReaction::where('chat_message_id', $prayerMsg->id)
        ->where('user_id', $this->coLaborer->id)
        ->where('reaction', 'amen')
        ->first();

    expect($reaction)->not->toBeNull();
});

test('creating a movement celebration automatically broadcasts to announcements channel', function () {
    $celebration = MovementCelebration::recordSalvation(
        gameSessionId: $this->session->id,
        wardId: $this->ward->id,
        wardName: 'Chikusa-ku',
        personName: 'Kenji Sato',
        personRole: 'University Student',
        disciplerName: 'Sarah Jenkins',
        details: 'Confessed Jesus as Lord after hearing the 3 Circles.',
        generation: 1,
        gameMonth: 2,
        gameWeek: 3,
        userId: $this->coLaborer->id
    );

    expect($celebration)->toBeInstanceOf(MovementCelebration::class);

    $announcement = ChatMessage::where('channel', 'announcements')
        ->where('system_event_type', 'salvation')
        ->first();

    expect($announcement)->not->toBeNull()
        ->and($announcement->message)->toContain('New Believer Saved in Nagoya!')
        ->and($announcement->message)->toContain('Kenji Sato')
        ->and($announcement->ward_id)->toBe($this->ward->id);
});

test('livewire game chat component sends messages and toggles reactions', function () {
    Livewire::actingAs($this->user)
        ->test(GameChat::class)
        ->assertSee('Missionary Dispatch & Co-op Chat')
        ->assertSee('Citywide')
        ->assertSee('Ward Dispatch')
        ->assertSee('Prayer Wall')
        ->set('newMessage', 'Reporting in from Meito ward prayer walk.')
        ->call('sendMessage')
        ->assertSet('newMessage', '');

    $msg = ChatMessage::where('message', 'Reporting in from Meito ward prayer walk.')->first();
    expect($msg)->not->toBeNull()
        ->and($msg->user_id)->toBe($this->user->id);

    // Toggle reaction through component
    Livewire::actingAs($this->user)
        ->test(GameChat::class)
        ->call('toggleReaction', $msg->id, 'fire');

    expect(ChatMessageReaction::where('chat_message_id', $msg->id)->where('reaction', 'fire')->count())->toBe(1);
});

test('livewire game chat filters by channel and ward', function () {
    ChatMessage::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->coLaborer->id,
        'channel' => 'ward',
        'ward_id' => $this->nakaWard->id,
        'message' => 'Naka ward specific strategy message.',
        'game_month' => 2,
        'game_week' => 3,
    ]);

    ChatMessage::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->coLaborer->id,
        'channel' => 'citywide',
        'message' => 'Citywide broadcast to all wards.',
        'game_month' => 2,
        'game_week' => 3,
    ]);

    Livewire::actingAs($this->user)
        ->test(GameChat::class)
        ->set('activeChannel', 'citywide')
        ->assertSee('Citywide broadcast to all wards.')
        ->assertDontSee('Naka ward specific strategy message.')
        ->set('activeChannel', 'ward')
        ->set('selectedWardId', $this->nakaWard->id)
        ->assertSee('Naka ward specific strategy message.');
});

test('nagoya movement dashboard integrates chat tab and displays unread badge', function () {
    // Sarah posts a message while John has not read it yet
    $this->user->update(['last_read_chat_at' => now()->subHour()]);

    ChatMessage::create([
        'game_session_id' => $this->session->id,
        'user_id' => $this->coLaborer->id,
        'channel' => 'citywide',
        'message' => 'Unread message for John',
        'game_month' => 2,
        'game_week' => 3,
        'created_at' => now()->subMinutes(5),
    ]);

    Livewire::actingAs($this->user)
        ->test(NagoyaMovementDashboard::class)
        ->assertSeeHtml('Chat')
        ->call('setTab', 'chat')
        ->assertSet('currentTab', 'chat')
        ->assertSeeLivewire(GameChat::class);
});
