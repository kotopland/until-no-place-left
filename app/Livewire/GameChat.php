<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\GameSession;
use App\Models\Ward;
use App\Services\GameChatService;
use App\Services\PersonAvatarService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

class GameChat extends Component
{
    #[Url(as: 'channel')]
    public string $activeChannel = 'citywide'; // 'citywide', 'ward', 'prayer', 'announcements'

    #[Url(as: 'ward')]
    public ?int $selectedWardId = null;

    #[Validate('required|string|min:1|max:1000')]
    public string $newMessage = '';

    public string $searchQuery = '';

    public bool $isPinned = false;

    #[Locked]
    public ?string $initialLastReadAt = null;

    public function mount(GameChatService $chatService): void
    {
        $user = auth()->user();
        if ($user) {
            // Snapshot initial last_read timestamp for the "New Messages Since Your Last Visit" divider
            $this->initialLastReadAt = $user->last_read_chat_at?->toIso8601String();

            if (! $this->selectedWardId && $user->home_ward_id) {
                $this->selectedWardId = $user->home_ward_id;
            }

            $chatService->markAsRead($user);
        }
    }

    public function setChannel(string $channel, GameChatService $chatService): void
    {
        $valid = ['citywide', 'ward', 'prayer', 'announcements'];
        $this->activeChannel = in_array($channel, $valid, true) ? $channel : 'citywide';

        $user = auth()->user();
        if ($user) {
            $chatService->markAsRead($user);
        }
    }

    public function selectWard(?int $wardId): void
    {
        $this->selectedWardId = $wardId;
    }

    public function insertTag(string $tag): void
    {
        $this->newMessage = trim($this->newMessage.' '.$tag).' ';
    }

    public function sendMessage(GameChatService $chatService): void
    {
        $this->validate();

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $chatService->postMessage(
            user: $user,
            message: $this->newMessage,
            channel: $this->activeChannel,
            wardId: $this->activeChannel === 'ward' ? $this->selectedWardId : null,
            isPinned: $this->isPinned
        );

        $this->newMessage = '';
        $this->isPinned = false;

        $this->dispatch('chat-message-sent');
    }

    public function toggleReaction(int $messageId, string $reaction, GameChatService $chatService): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $message = ChatMessage::find($messageId);
        if (! $message) {
            return;
        }

        $chatService->toggleReaction($user, $message, $reaction);
    }

    public function togglePin(int $messageId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $message = ChatMessage::find($messageId);
        if ($message && ($message->user_id === $user->id || $user->is_lead_missionary ?? true)) {
            $message->update(['is_pinned' => ! $message->is_pinned]);
        }
    }

    public function markAllRead(GameChatService $chatService): void
    {
        $user = auth()->user();
        if ($user) {
            $chatService->markAsRead($user);
            $this->initialLastReadAt = now()->toIso8601String();
        }
    }

    public function render(GameChatService $chatService, PersonAvatarService $avatarService): View
    {
        $user = auth()->user();
        $session = GameSession::find($user?->current_game_session_id)
            ?? GameSession::where('is_active', true)->first();

        $wards = Ward::orderBy('name')->get();

        $messages = $session
            ? $chatService->getMessages(
                session: $session,
                channel: $this->activeChannel,
                wardId: $this->activeChannel === 'ward' ? $this->selectedWardId : null,
                search: $this->searchQuery,
                limit: 100
            )
            : collect();

        // Pinned messages for strategic focus banner
        $pinnedMessages = $session
            ? ChatMessage::where('game_session_id', $session->id)
                ->where('is_pinned', true)
                ->with(['user', 'ward'])
                ->latest()
                ->take(3)
                ->get()
            : collect();

        // Channel unread counts for badges
        $lastRead = $user?->last_read_chat_at;
        $unreadCounts = [
            'citywide' => $session ? ChatMessage::where('game_session_id', $session->id)->where('channel', 'citywide')->where('user_id', '!=', $user?->id)->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))->count() : 0,
            'ward' => $session ? ChatMessage::where('game_session_id', $session->id)->where('channel', 'ward')->where('user_id', '!=', $user?->id)->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))->count() : 0,
            'prayer' => $session ? ChatMessage::where('game_session_id', $session->id)->where('channel', 'prayer')->where('user_id', '!=', $user?->id)->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))->count() : 0,
            'announcements' => $session ? ChatMessage::where('game_session_id', $session->id)->where('channel', 'announcements')->where('user_id', '!=', $user?->id)->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))->count() : 0,
        ];

        $parsedLastRead = $this->initialLastReadAt ? Carbon::parse($this->initialLastReadAt) : null;

        return view('livewire.game-chat', [
            'session' => $session,
            'wards' => $wards,
            'messages' => $messages,
            'pinnedMessages' => $pinnedMessages,
            'unreadCounts' => $unreadCounts,
            'currentUser' => $user,
            'parsedLastRead' => $parsedLastRead,
            'avatarService' => $avatarService,
        ]);
    }
}
