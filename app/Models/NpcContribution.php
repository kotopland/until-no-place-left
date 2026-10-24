<?php

namespace App\Models;

use App\Enums\ContributionRole;
use App\Enums\MinistryActionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $npc_id
 * @property int $user_id
 * @property MinistryActionType $action_type
 * @property ContributionRole $contribution_role
 * @property int $game_month
 * @property int $trust_gained
 * @property int $points_awarded
 * @property bool $triggered_breakthrough
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('npc_contributions')]
#[Fillable([
    'game_session_id',
    'npc_id',
    'user_id',
    'action_type',
    'contribution_role',
    'game_month',
    'trust_gained',
    'points_awarded',
    'triggered_breakthrough',
    'notes',
])]
class NpcContribution extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Formatted milestone badge
     */
    public string $roleBadge {
        get => "{$this->contribution_role->icon()} {$this->contribution_role->label()}";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_type' => MinistryActionType::class,
            'contribution_role' => ContributionRole::class,
            'game_month' => 'integer',
            'trust_gained' => 'integer',
            'points_awarded' => 'integer',
            'triggered_breakthrough' => 'boolean',
        ];
    }

    /**
     * Associated Game Session
     *
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * Target NPC who was impacted
     *
     * @return BelongsTo<Npc, $this>
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * Contributing Player
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
