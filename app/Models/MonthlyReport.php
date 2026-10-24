<?php

namespace App\Models;

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
 * @property int $game_month
 * @property string $summary_title
 * @property array<string, mixed> $highlights_json
 * @property array<string, mixed> $answered_prayers_json
 * @property int $disciples_multiplied_count
 * @property int $churches_planted_count
 * @property int $generational_score_gained
 * @property int $total_generational_score
 * @property array<string, mixed> $coach_tips_json
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('monthly_reports')]
#[Fillable([
    'game_session_id',
    'user_id',
    'game_month',
    'summary_title',
    'highlights_json',
    'answered_prayers_json',
    'disciples_multiplied_count',
    'churches_planted_count',
    'generational_score_gained',
    'total_generational_score',
    'coach_tips_json',
    'read_at',
])]
class MonthlyReport extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Whether the player has not yet opened this monthly report
     */
    public bool $isUnread {
        get => $this->read_at === null;
    }

    /**
     * PHP 8.4 Property Hook: Whether this month experienced new generational fruit
     */
    public bool $hadMultiplication {
        get => $this->disciples_multiplied_count > 0 || $this->churches_planted_count > 0;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'game_month' => 'integer',
            'highlights_json' => 'array',
            'answered_prayers_json' => 'array',
            'disciples_multiplied_count' => 'integer',
            'churches_planted_count' => 'integer',
            'generational_score_gained' => 'integer',
            'total_generational_score' => 'integer',
            'coach_tips_json' => 'array',
            'read_at' => 'datetime',
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
     * Player receiving this report
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
