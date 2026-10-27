<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PersonAvatarService;
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
 * @property int|null $home_ward_id
 * @property int $action_points
 * @property int $max_action_points
 * @property int $total_generational_score
 * @property int $coop_synergy_score
 * @property array<string>|null $badges
 * @property bool $notify_email
 * @property bool $notify_in_app
 * @property Carbon|null $last_active_at
 * @property int $days_played_today
 * @property Carbon|null $last_played_real_date
 * @property int $daily_action_points
 * @property int $current_game_month
 * @property int $current_game_day
 * @property int $days_processed_this_month
 * @property bool $is_ai_colaborer
 * @property string|null $colaborer_archetype
 * @property string|null $ai_strategy_notes
 * @property Carbon|null $last_daily_tick_at
 * @property Carbon|null $next_daily_tick_at
 * @property Carbon|null $last_monthly_tick_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('users')]
#[Fillable([
    'name',
    'gender',
    'age',
    'nationality',
    'email',
    'password',
    'current_game_session_id',
    'home_ward_id',
    'is_ai_colaborer',
    'colaborer_archetype',
    'ai_strategy_notes',
    'action_points',
    'max_action_points',
    'total_generational_score',
    'coop_synergy_score',
    'badges',
    'notify_email',
    'notify_in_app',
    'last_active_at',
    'weeks_played_today',
    'days_played_today',
    'last_played_real_date',
    'weekly_action_points',
    'daily_action_points',
    'consecutive_exhaustion_weeks',
    'consecutive_exhaustion_days',
    'fatigue_penalty_ap',
    'is_burned_out',
    'burned_out_until_game_month',
    'burnout_count',
    'current_game_month',
    'current_game_week',
    'weeks_processed_this_month',
    'current_game_day',
    'days_processed_this_month',
    'last_weekly_tick_at',
    'next_weekly_tick_at',
    'last_daily_tick_at',
    'next_daily_tick_at',
    'last_monthly_tick_at',
    'game_session_id',
    'movement_score',
    'screening_passed',
    'screening_completed_at',
    'screening_score',
    'screening_dimension_scores',
    'screening_profile_title',
    'screening_answers',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function setGameSessionIdAttribute(mixed $value): void
    {
        $this->attributes['current_game_session_id'] = $value;
    }

    public function setMovementScoreAttribute(mixed $value): void
    {
        $this->attributes['total_generational_score'] = $value;
    }

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
     * PHP 8.4 Property Hook: Effective weekly AP replenished taking into account overwork fatigue
     */
    public int $effectiveWeeklyActionPoints {
        get => $this->is_burned_out ? 0 : max(2, ($this->weekly_action_points ?: $this->daily_action_points ?: 20) - $this->fatigue_penalty_ap);
    }

    /**
     * PHP 8.4 Property Hook: Effective daily AP (aliased to weekly)
     */
    public int $effectiveDailyActionPoints {
        get => $this->effectiveWeeklyActionPoints;
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly physical/spiritual fatigue status label
     */
    public string $fatigueStatusLabel {
        get {
            if ($this->is_burned_out) {
                return 'Severe Burnout (Mandatory 3-Month Sabbatical Leave)';
            }

            $exhaustion = max($this->consecutive_exhaustion_weeks, $this->consecutive_exhaustion_days);

            return match ($exhaustion) {
                0 => 'Rested & Healthy',
                1 => 'Fatigued from This Week\'s Labors (-2 AP Next Week)',
                2 => 'Weary & Strained (-4 AP Next Week)',
                3 => 'Critical Exhaustion Danger (-6 AP Next Week, Burnout Imminent!)',
                default => 'Overworked (Burnout Warning)',
            };
        }
    }

    /**
     * Calculates remaining months of sabbatical leave required for burnout recovery.
     */
    public function burnoutMonthsRemaining(int $currentMonth): int
    {
        if (! $this->is_burned_out || $this->burned_out_until_game_month === null) {
            return 0;
        }

        return max(0, $this->burned_out_until_game_month - $currentMonth);
    }

    /**
     * Triggers severe ministry burnout due to sustained overwork without rest.
     */
    public function triggerBurnout(int $currentMonth): void
    {
        $this->update([
            'is_burned_out' => true,
            'burned_out_until_game_month' => $currentMonth + 3,
            'burnout_count' => $this->burnout_count + 1,
            'action_points' => 0,
            'fatigue_penalty_ap' => $this->weekly_action_points ?: $this->daily_action_points ?: 20,
        ]);
    }

    /**
     * Completes 3-month sabbatical recovery from ministry burnout.
     */
    public function recoverFromBurnout(): void
    {
        $this->update([
            'is_burned_out' => false,
            'burned_out_until_game_month' => null,
            'consecutive_exhaustion_weeks' => 0,
            'consecutive_exhaustion_days' => 0,
            'fatigue_penalty_ap' => 0,
            'action_points' => $this->max_action_points ?: 26,
        ]);
    }

    /**
     * PHP 8.4 Property Hook: whether user was active in the last 15 minutes
     */
    public bool $isOnline {
        get => $this->last_active_at !== null && $this->last_active_at->greaterThanOrEqualTo(now()->subMinutes(15));
    }

    /**
     * PHP 8.4 Property Hook: whether user has hit the 4-game-week play limit for this real-life day
     */
    public bool $hasReachedDailyPlayLimit {
        get => $this->isPlayedToday() && ($this->weeks_played_today >= 4 || $this->days_played_today >= 30);
    }

    /**
     * PHP 8.4 Property Hook: remaining playable game weeks available for this real-life day
     */
    public int $remainingPlayableWeeksToday {
        get => $this->isPlayedToday() ? max(0, 4 - max($this->weeks_played_today, min(4, (int) ceil($this->days_played_today / 7)))) : 4;
    }

    /**
     * PHP 8.4 Property Hook: remaining playable game days (aliased to weeks)
     */
    public int $remainingPlayableDaysToday {
        get => $this->remainingPlayableWeeksToday;
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly Week in Month label (e.g. Week 2/4 (Month 1))
     */
    public string $weekInMonthLabel {
        get => "Week {$this->current_game_week}/4 (Month {$this->current_game_month})";
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly Day in Month label (kept for backward compatibility)
     */
    public string $dayInMonthLabel {
        get => $this->weekInMonthLabel;
    }

    /**
     * PHP 8.4 Property Hook: Remaining unplayed weeks in the player's current 4-week game month
     */
    public int $remainingWeeksInMonth {
        get => max(0, 4 - max($this->weeks_processed_this_month, min(4, (int) ceil($this->days_processed_this_month / 7))));
    }

    /**
     * PHP 8.4 Property Hook: Remaining unplayed days in the player's current month (aliased to remaining weeks)
     */
    public int $remainingDaysInMonth {
        get => $this->remainingWeeksInMonth;
    }

    /**
     * PHP 8.4 Property Hook: whether player has completed all 4 weeks of this game month
     */
    public bool $hasCompletedMonth {
        get => $this->weeks_processed_this_month >= 4 || $this->days_processed_this_month >= 30;
    }

    /**
     * PHP 8.4 Property Hook: whether player already changed their month today (e.g. manually)
     */
    public bool $hasChangedMonthToday {
        get => $this->last_monthly_tick_at !== null && $this->last_monthly_tick_at->isToday();
    }

    /**
     * PHP 8.4 Property Hook: whether the 15-minute weekly tick is due for this user
     */
    public bool $isWeeklyTickDue {
        get {
            if ($this->weeks_processed_this_month >= 4 && $this->days_processed_this_month >= 30) {
                return false;
            }

            if ($this->next_weekly_tick_at !== null && now()->greaterThanOrEqualTo($this->next_weekly_tick_at)) {
                return true;
            }

            if ($this->next_daily_tick_at !== null && now()->greaterThanOrEqualTo($this->next_daily_tick_at)) {
                return true;
            }

            if ($this->currentGameSession?->isWeeklyTickDue) {
                return true;
            }

            return $this->next_weekly_tick_at === null && $this->next_daily_tick_at === null;
        }
    }

    /**
     * PHP 8.4 Property Hook: whether daily tick is due (aliased to weekly tick)
     */
    public bool $isDailyTickDue {
        get => $this->isWeeklyTickDue;
    }

    /**
     * PHP 8.4 Property Hook: Remaining seconds until next 15-minute online weekly tick
     */
    public int $secondsUntilNextWeeklyTick {
        get {
            $weeklySecs = $this->next_weekly_tick_at ? max(0, (int) now()->diffInSeconds($this->next_weekly_tick_at, false)) : null;
            $dailySecs = $this->next_daily_tick_at ? max(0, (int) now()->diffInSeconds($this->next_daily_tick_at, false)) : null;

            $userSecs = null;
            if ($weeklySecs !== null && $dailySecs !== null) {
                $userSecs = min($weeklySecs, $dailySecs);
            } else {
                $userSecs = $weeklySecs ?? $dailySecs;
            }

            if ($this->currentGameSession) {
                $sessionSecs = $this->currentGameSession->secondsUntilNextWeeklyTick;

                return $userSecs !== null ? min($userSecs, $sessionSecs) : $sessionSecs;
            }

            return $userSecs ?? 0;
        }
    }

    /**
     * PHP 8.4 Property Hook: Remaining seconds until next daily tick (aliased to weekly tick)
     */
    public int $secondsUntilNextDailyTick {
        get => $this->secondsUntilNextWeeklyTick;
    }

    /**
     * Check if the user's recorded play date matches today's real-life date.
     */
    public function isPlayedToday(): bool
    {
        return $this->last_played_real_date !== null && $this->last_played_real_date->isToday();
    }

    /**
     * Heartbeat touch: updates active timestamp and resets day counter if a new real-life day started.
     */
    public function touchActive(): void
    {
        $today = now()->startOfDay();
        $isNewRealDay = ! $this->isPlayedToday();

        // Throttle updates: avoid redundant database write queries if touched within the last 15 seconds
        if (! $isNewRealDay && $this->last_active_at && $this->last_active_at->diffInSeconds(now()) < 15) {
            return;
        }

        $this->update([
            'last_active_at' => now(),
            'last_played_real_date' => $today,
            'days_played_today' => $isNewRealDay ? 0 : $this->days_played_today,
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => 'string',
            'age' => 'integer',
            'nationality' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'action_points' => 'integer',
            'max_action_points' => 'integer',
            'total_generational_score' => 'integer',
            'coop_synergy_score' => 'integer',
            'badges' => 'array',
            'notify_email' => 'boolean',
            'notify_in_app' => 'boolean',
            'last_active_at' => 'datetime',
            'weeks_played_today' => 'integer',
            'days_played_today' => 'integer',
            'last_played_real_date' => 'date',
            'weekly_action_points' => 'integer',
            'daily_action_points' => 'integer',
            'consecutive_exhaustion_weeks' => 'integer',
            'consecutive_exhaustion_days' => 'integer',
            'fatigue_penalty_ap' => 'integer',
            'is_burned_out' => 'boolean',
            'burned_out_until_game_month' => 'integer',
            'burnout_count' => 'integer',
            'current_game_month' => 'integer',
            'current_game_week' => 'integer',
            'weeks_processed_this_month' => 'integer',
            'current_game_day' => 'integer',
            'days_processed_this_month' => 'integer',
            'is_ai_colaborer' => 'boolean',
            'last_weekly_tick_at' => 'datetime',
            'next_weekly_tick_at' => 'datetime',
            'last_daily_tick_at' => 'datetime',
            'next_daily_tick_at' => 'datetime',
            'last_monthly_tick_at' => 'datetime',
            'screening_passed' => 'boolean',
            'screening_completed_at' => 'datetime',
            'screening_score' => 'integer',
            'screening_dimension_scores' => 'array',
            'screening_answers' => 'array',
        ];
    }

    /**
     * PHP 8.4 Property Hook: whether user has passed the missionary screening questionnaire
     */
    public bool $isScreeningPassed {
        get => (bool) $this->screening_passed;
    }

    /**
     * PHP 8.4 Property Hook: Formatted screening profile badge title
     */
    public string $screeningProfileBadge {
        get => $this->screening_profile_title ?: 'Missionary Disciple';
    }

    /**
     * PHP 8.4 Property Hook: whether user is an automated computer-based missionary co-laborer
     */
    public bool $isAi {
        get => (bool) $this->is_ai_colaborer;
    }

    /**
     * PHP 8.4 Property Hook: Archetype badge label for UI display
     */
    public string $archetypeBadgeLabel {
        get => match ($this->colaborer_archetype) {
            'campus_sower' => '🎓 Campus Sower',
            'mercy_catalyst' => '❤️ Mercy Catalyst',
            'discipleship_trainer' => '📖 Discipleship Trainer',
            default => 'Missionary Disciple',
        };
    }

    /**
     * PHP 8.4 Property Hook: Gender symbol
     */
    public string $genderIcon {
        get => app(PersonAvatarService::class)->genderIcon($this->gender ?? 'male');
    }

    /**
     * PHP 8.4 Property Hook: Country flag emoji
     */
    public string $nationalityFlag {
        get => app(PersonAvatarService::class)->flagEmoji($this->nationality ?? 'Japanese');
    }

    /**
     * PHP 8.4 Property Hook: Formatted age group label
     */
    public string $ageGroupLabel {
        get => app(PersonAvatarService::class)->ageGroupLabel($this->age ?? 30);
    }

    /**
     * Returns the demographic SVG avatar markup.
     */
    public function avatarSvg(int $pixelSize = 64, bool $includeBadges = false): string
    {
        return app(PersonAvatarService::class)->forPerson($this, $pixelSize, $includeBadges);
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
     * Ward where the player resides and establishes their ministry home base
     *
     * @return BelongsTo<Ward, $this>
     */
    public function homeWard(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'home_ward_id');
    }

    /**
     * Resolves distance in kilometers from player's home base to target ward.
     */
    public function distanceToWard(Ward|int|null $ward): float
    {
        if (! $ward || ! $this->home_ward_id) {
            return 0.0;
        }

        $targetWard = $ward instanceof Ward ? $ward : Ward::find($ward);
        if (! $targetWard) {
            return 0.0;
        }

        if ($this->home_ward_id === $targetWard->id) {
            return 0.0;
        }

        $home = $this->homeWard ?? Ward::find($this->home_ward_id);

        return $home ? $home->distanceTo($targetWard) : 0.0;
    }

    /**
     * Estimates one-way transit commute time in minutes from home base.
     */
    public function commuteTransitMinutesForWard(Ward|int|null $ward): int
    {
        $km = $this->distanceToWard($ward);

        return match (true) {
            $km <= 0.0 => 0,       // On-foot neighbor
            $km <= 4.0 => 15,      // Short bike or direct subway
            $km <= 8.0 => 30,      // Moderate subway ride
            $km <= 12.0 => 45,     // Line transfer across town
            default => 60 + (int) round(($km - 12.0) * 3), // Distant cross-metropolis
        };
    }

    /**
     * Calculates Action Points surcharge for commuting across town.
     * Living close (same ward or nearby <= 5km) has zero commute AP penalty.
     */
    public function commuteApCostForWard(Ward|int|null $ward): int
    {
        $km = $this->distanceToWard($ward);

        return match (true) {
            $km <= 5.0 => 0,       // Local neighbor / walking distance / direct short ride
            $km <= 10.0 => 1,      // Moderate commute across city lines
            default => 2,          // Long cross-metropolis transit
        };
    }

    /**
     * Relational trust & follow-up multiplier based on proximity.
     * Living close provides spontaneous follow-up and faster trust building (+25%).
     * Further away creates relational friction and lower follow-up chance (0.85x to 0.70x).
     */
    public function commuteTrustMultiplierForWard(Ward|int|null $ward): float
    {
        if (! $ward || ! $this->home_ward_id) {
            return 1.0;
        }

        $targetWardId = $ward instanceof Ward ? $ward->id : $ward;
        if ($this->home_ward_id === $targetWardId) {
            return 1.25; // +25% Local Neighbor Presence Bonus
        }

        $km = $this->distanceToWard($ward);

        return match (true) {
            $km <= 5.0 => 1.0,     // Standard baseline
            $km <= 10.0 => 0.85,   // Moderate friction (-15%)
            default => 0.70,       // High commute friction / hard to follow up (-30%)
        };
    }

    /**
     * Descriptive badge label for commute & proximity.
     */
    public function commuteTierLabel(Ward|int|null $ward): string
    {
        if (! $ward || ! $this->home_ward_id) {
            return 'Standard Transit';
        }

        $targetWardId = $ward instanceof Ward ? $ward->id : $ward;
        if ($this->home_ward_id === $targetWardId) {
            return '🏡 Home Base (Local Neighbor)';
        }

        $km = $this->distanceToWard($ward);

        return match (true) {
            $km <= 5.0 => "🚇 Nearby Ward ({$km} km)",
            $km <= 10.0 => "🚆 Moderate Commute ({$km} km • +1 AP)",
            default => "⚠️ Distant Commute ({$km} km • +2 AP)",
        };
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
