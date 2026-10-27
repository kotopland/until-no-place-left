<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChatMessageReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $chat_message_id
 * @property int $user_id
 * @property string $reaction
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('chat_message_reactions')]
#[Fillable([
    'chat_message_id',
    'user_id',
    'reaction',
])]
class ChatMessageReaction extends Model
{
    /** @use HasFactory<ChatMessageReactionFactory> */
    use HasFactory;

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
