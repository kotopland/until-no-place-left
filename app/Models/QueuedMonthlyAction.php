<?php

namespace App\Models;

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $user_id
 * @property MinistryActionType $action_type
 * @property int|null $target_npc_id
 * @property int $ward_id
 * @property QueuedActionStatus $status
 * @property int $game_month
 * @property int $ap_cost
 * @property string|null $notes
 * @property string|null $outcome_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('queued_monthly_actions')]
#[Fillable([
    'game_session_id',
    'user_id',
    'action_type',
    'target_npc_id',
    'ward_id',
    'status',
    'game_month',
    'ap_cost',
    'notes',
    'outcome_notes',
])]
class QueuedMonthlyAction extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Formatted action title with icon
     */
    public string $actionTitle {
        get => "{$this->action_type->icon()} {$this->action_type->label()}";
    }

    /**
     * PHP 8.4 Property Hook: Whether this action is awaiting server monthly tick resolution
     */
    public bool $isPending {
        get => $this->status === QueuedActionStatus::Pending;
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
            'status' => QueuedActionStatus::class,
            'game_month' => 'integer',
            'ap_cost' => 'integer',
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
     * The player who queued this action
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Target NPC (null for ward-wide actions like International Events)
     *
     * @return BelongsTo<Npc, $this>
     */
    public function targetNpc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'target_npc_id');
    }

    /**
     * Nagoya Ward where the action takes place
     *
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
