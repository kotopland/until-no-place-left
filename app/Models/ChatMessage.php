<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $user_id
 * @property string $channel
 * @property int|null $ward_id
 * @property string $message
 * @property int $game_month
 * @property int $game_week
 * @property bool $is_pinned
 * @property bool $is_system
 * @property string|null $system_event_type
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('chat_messages')]
#[Fillable([
    'game_session_id',
    'user_id',
    'channel',
    'ward_id',
    'message',
    'game_month',
    'game_week',
    'is_pinned',
    'is_system',
    'system_event_type',
    'metadata',
])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned' => 'boolean',
        'is_system' => 'boolean',
        'metadata' => 'array',
        'game_month' => 'integer',
        'game_week' => 'integer',
    ];

    /**
     * PHP 8.4 Property Hook: In-game calendar turn stamp (e.g. "Year 1, Month 3 • Week 2")
     */
    public string $timeContextLabel {
        get {
            $yearNum = (int) ceil($this->game_month / 12);
            $monthInYear = (($this->game_month - 1) % 12) + 1;

            return "Year {$yearNum}, M{$monthInYear} • Wk {$this->game_week}";
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class);
    }

    /**
     * Group reactions with counts and users.
     *
     * @return array<string, array{count: int, user_ids: array<int>, user_names: array<string>, reacted_by_auth: bool}>
     */
    public function groupedReactions(?int $currentUserId = null): array
    {
        $grouped = [];

        foreach ($this->reactions as $reaction) {
            $type = $reaction->reaction;
            if (! isset($grouped[$type])) {
                $grouped[$type] = [
                    'count' => 0,
                    'user_ids' => [],
                    'user_names' => [],
                    'reacted_by_auth' => false,
                ];
            }

            $grouped[$type]['count']++;
            $grouped[$type]['user_ids'][] = $reaction->user_id;
            if ($reaction->user) {
                $grouped[$type]['user_names'][] = $reaction->user->name;
            }
            if ($currentUserId && $reaction->user_id === $currentUserId) {
                $grouped[$type]['reacted_by_auth'] = true;
            }
        }

        return $grouped;
    }
}
