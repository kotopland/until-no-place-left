<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $current_game_session_id
 * @property int $action_points
 * @property int $max_action_points
 * @property int $total_generational_score
 * @property int $coop_synergy_score
 * @property array<string>|null $badges
 * @property bool $notify_email
 * @property bool $notify_in_app
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('users')]
#[Fillable([
    'name',
    'email',
    'password',
    'current_game_session_id',
    'action_points',
    'max_action_points',
    'total_generational_score',
    'coop_synergy_score',
    'badges',
    'notify_email',
    'notify_in_app',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * PHP 8.4 Property Hook: percentage of bandwidth Action Points remaining
     */
    public int $remainingBandwidthPercent {
        get => $this->max_action_points > 0
            ? (int) round(($this->action_points / $this->max_action_points) * 100)
            : 0;
    }

    /**
     * PHP 8.4 Property Hook: Total unified score (generational depth + cooperative synergy)
     */
    public int $compositeScore {
        get => $this->total_generational_score + $this->coop_synergy_score;
    }

    /**
     * PHP 8.4 Property Hook: whether player has run out of Action Points for the month
     */
    public bool $isExhausted {
        get => $this->action_points <= 0;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'action_points' => 'integer',
            'max_action_points' => 'integer',
            'total_generational_score' => 'integer',
            'coop_synergy_score' => 'integer',
            'badges' => 'array',
            'notify_email' => 'boolean',
            'notify_in_app' => 'boolean',
        ];
    }

    /**
     * Current multiplayer game session
     *
     * @return BelongsTo<GameSession, $this>
     */
    public function currentGameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'current_game_session_id');
    }

    /**
     * Queued monthly actions submitted by this player
     *
     * @return HasMany<QueuedMonthlyAction, $this>
     */
    public function queuedActions(): HasMany
    {
        return $this->hasMany(QueuedMonthlyAction::class);
    }

    /**
     * Churches or 3/3rds groups directly led by the player (G1)
     *
     * @return HasMany<Church, $this>
     */
    public function ledChurches(): HasMany
    {
        return $this->hasMany(Church::class, 'leader_user_id');
    }

    /**
     * Monthly report digests generated at each cron tick
     *
     * @return HasMany<MonthlyReport, $this>
     */
    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    /**
     * NPCs directly discipled by this player
     *
     * @return HasMany<Npc, $this>
     */
    public function discipledNpcs(): HasMany
    {
        return $this->hasMany(Npc::class, 'discipler_user_id');
    }

    /**
     * Cooperative ministry touchpoints (sowing, watering, harvesting)
     *
     * @return HasMany<NpcContribution, $this>
     */
    public function npcContributions(): HasMany
    {
        return $this->hasMany(NpcContribution::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
