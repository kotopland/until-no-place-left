<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\GameSession;
use App\Models\MovementCelebration;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Collection;

class GameChatService
{
    /**
     * Post a new message into the game chat.
     */
    public function postMessage(
        User $user,
        string $message,
        string $channel = 'citywide',
        ?int $wardId = null,
        bool $isPinned = false,
        bool $isSystem = false,
        ?string $systemEventType = null,
        ?array $metadata = null
    ): ChatMessage {
        $cleanMessage = trim($message);
        if ($cleanMessage === '') {
            throw new \InvalidArgumentException('Chat message cannot be empty.');
        }

        $validChannels = ['citywide', 'ward', 'prayer', 'announcements'];
        if (! in_array($channel, $validChannels, true)) {
            $channel = 'citywide';
        }

        $session = GameSession::find($user->current_game_session_id)
            ?? GameSession::where('is_active', true)->first();

        $gameMonth = (int) ($user->current_game_month ?: ($session?->current_game_month ?: 1));
        $gameWeek = (int) ($user->current_game_week ?: ($session?->current_game_week ?: 1));

        $chatMessage = ChatMessage::create([
            'game_session_id' => $session?->id ?? 1,
            'user_id' => $user->id,
            'channel' => $channel,
            'ward_id' => $wardId,
            'message' => $cleanMessage,
            'game_month' => $gameMonth,
            'game_week' => $gameWeek,
            'is_pinned' => $isPinned,
            'is_system' => $isSystem,
            'system_event_type' => $systemEventType,
            'metadata' => $metadata,
        ]);

        $user->update(['last_read_chat_at' => now()]);

        // If human player posted a prayer request, trigger an asynchronous AI co-laborer intercession reaction
        if ($channel === 'prayer' && ! $user->is_ai_colaborer && $session) {
            $this->triggerAiPrayerResponse($session, $chatMessage);
        }

        return $chatMessage;
    }

    /**
     * Toggle a reaction on a chat message.
     * Returns true if added, false if removed.
     */
    public function toggleReaction(User $user, ChatMessage $message, string $reaction): bool
    {
        $validReactions = ['amen', 'praise', 'fire', 'handshake', 'idea'];
        if (! in_array($reaction, $validReactions, true)) {
            $reaction = 'amen';
        }

        $existing = ChatMessageReaction::where('chat_message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('reaction', $reaction)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        ChatMessageReaction::create([
            'chat_message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => $reaction,
        ]);

        return true;
    }

    /**
     * Mark all chat messages as read for a given user.
     */
    public function markAsRead(User $user, ?int $gameSessionId = null): void
    {
        $user->update(['last_read_chat_at' => now()]);
    }

    /**
     * Broadcast a movement celebration event into the announcements channel.
     */
    public function broadcastCelebration(MovementCelebration $celebration): ChatMessage
    {
        $session = GameSession::find($celebration->game_session_id)
            ?? GameSession::where('is_active', true)->first();

        // System user or first available user
        $systemUser = User::where('current_game_session_id', $session?->id)
            ->where('is_ai_colaborer', true)
            ->first() ?? User::first();

        $emoji = match ($celebration->type) {
            'salvation' => '🕊️',
            'baptism' => '🌊',
            'church_planted' => '⛪',
            'group_formed' => '👥',
            default => '🎉',
        };

        $content = "{$emoji} **{$celebration->title}**\n{$celebration->headline}\n{$celebration->details}";
        if ($celebration->scripture_ref) {
            $content .= "\n_\"{$celebration->scripture_ref}\"_";
        }

        return ChatMessage::create([
            'game_session_id' => $session?->id ?? 1,
            'user_id' => $systemUser?->id ?? 1,
            'channel' => 'announcements',
            'ward_id' => $celebration->ward_id,
            'message' => $content,
            'game_month' => $celebration->game_month,
            'game_week' => $celebration->game_week,
            'is_pinned' => in_array($celebration->type, ['church_planted', 'baptism'], true),
            'is_system' => true,
            'system_event_type' => $celebration->type,
            'metadata' => [
                'celebration_id' => $celebration->id,
                'ward_name' => $celebration->ward_name,
                'person_name' => $celebration->person_name,
                'generation' => $celebration->generation,
            ],
        ]);
    }

    /**
     * Asynchronously trigger an AI co-laborer to react to a prayer request.
     */
    protected function triggerAiPrayerResponse(GameSession $session, ChatMessage $message): void
    {
        $aiColaborer = User::where('current_game_session_id', $session->id)
            ->where('is_ai_colaborer', true)
            ->inRandomOrder()
            ->first();

        if ($aiColaborer) {
            ChatMessageReaction::firstOrCreate([
                'chat_message_id' => $message->id,
                'user_id' => $aiColaborer->id,
                'reaction' => 'amen',
            ]);
        }
    }

    /**
     * Simulate an authentic field dispatch from an AI co-laborer for asynchronous immersion.
     */
    public function simulateAiColaborerChat(GameSession $session, ?string $channel = null): ?ChatMessage
    {
        $aiColaborer = User::where('current_game_session_id', $session->id)
            ->where('is_ai_colaborer', true)
            ->inRandomOrder()
            ->first();

        if (! $aiColaborer) {
            return null;
        }

        $homeWard = $aiColaborer->homeWard ?? Ward::find($aiColaborer->home_ward_id) ?? Ward::first();
        $wardName = $homeWard?->name ?? 'Nagoya';

        $dispatches = [
            'citywide' => [
                "Finished a long prayer walk this week through {$wardName}. The spiritual openness around the train hubs is noticeably increasing. Let's keep contending!",
                "Sharing a reminder from Matthew 9:38: 'Ask the Lord of the harvest to send out workers into His harvest field.' Praying for every missionary co-laboring across Nagoya's 16 wards.",
                "Coaching session with local disciples in {$wardName} went well. They are practicing the 3 Circles and sharing with their university classmates.",
                'Until no place left in Nagoya! Standing with everyone pressing in for 4th generation movements.',
            ],
            'prayer' => [
                "Please intercede for an open home near {$wardName} station. We are meeting the family this week for a Discovery Bible Study.",
                "Praying for peace and boldness for young disciples balancing grueling overtime with church gatherings in {$wardName}.",
                "Asking the Holy Spirit for Persons of Peace in the dense residential areas of {$wardName}. Join us in interceding!",
            ],
            'ward' => [
                "Local field dispatch for {$wardName}: Met several receptive students yesterday during campus outreach. Looking for follow-up partners!",
                "Mapped out 3 key prayer walk corridors in {$wardName}. Ping me if you'd like to coordinate joint field hours.",
            ],
        ];

        $targetChannel = $channel ?? (fake()->randomElement(['citywide', 'prayer', 'ward']));
        $messages = $dispatches[$targetChannel] ?? $dispatches['citywide'];
        $selectedText = fake()->randomElement($messages);

        return $this->postMessage(
            user: $aiColaborer,
            message: $selectedText,
            channel: $targetChannel,
            wardId: $targetChannel === 'ward' ? $homeWard?->id : null,
            isPinned: false,
            isSystem: false
        );
    }

    /**
     * Retrieve chat messages query for a specific session and channel.
     *
     * @return Collection<int, ChatMessage>
     */
    public function getMessages(
        GameSession $session,
        string $channel = 'citywide',
        ?int $wardId = null,
        ?string $search = null,
        int $limit = 50
    ): Collection {
        $query = ChatMessage::where('game_session_id', $session->id)
            ->with(['user.homeWard', 'ward', 'reactions.user']);

        if ($channel !== 'all') {
            $query->where('channel', $channel);
        }

        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        if ($search && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('message', 'like', $term)
                    ->orWhereHas('user', function ($uq) use ($term): void {
                        $uq->where('name', 'like', $term);
                    });
            });
        }

        return $query->orderBy('created_at', 'asc')
            ->take($limit)
            ->get();
    }
}
