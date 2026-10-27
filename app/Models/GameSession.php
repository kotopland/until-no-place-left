<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $current_game_month
 * @property Carbon $calendar_date
 * @property int $month_duration_minutes
 * @property Carbon $next_tick_at
 * @property int $current_game_day
 * @property int $days_processed_this_month
 * @property Carbon|null $last_daily_tick_at
 * @property Carbon|null $next_daily_tick_at
 * @property Carbon|null $last_monthly_tick_at
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('game_sessions')]
#[Fillable([
    'name',
    'code',
    'current_game_month',
    'current_game_week',
    'weeks_processed_this_month',
    'current_game_day',
    'days_processed_this_month',
    'calendar_date',
    'month_duration_minutes',
    'next_tick_at',
    'last_weekly_tick_at',
    'next_weekly_tick_at',
    'last_daily_tick_at',
    'next_daily_tick_at',
    'last_monthly_tick_at',
    'is_active',
    'settings',
])]
class GameSession extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (GameSession $session): void {
            if (empty($session->code)) {
                $session->code = 'session-'.Str::lower(Str::random(6));
            }
            if (empty($session->next_tick_at)) {
                $session->next_tick_at = now()->addHours(12);
            }
        });
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly calendar month label (e.g., Year 1, Month 1 (Apr 2026))
     */
    public string $monthLabel {
        get {
            $yearNum = (int) ceil($this->current_game_month / 12);
            $monthInYear = (($this->current_game_month - 1) % 12) + 1;
            $calendarLabel = $this->calendar_date->format('M Y');

            return "Year {$yearNum}, Month {$monthInYear} ({$calendarLabel})";
        }
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly Week in Month label (e.g., Week 2/4 (Month 1))
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
     * PHP 8.4 Property Hook: Remaining unplayed weeks in the current 4-week game month
     */
    public int $remainingWeeksInMonth {
        get => max(0, 4 - $this->weeks_processed_this_month);
    }

    /**
     * PHP 8.4 Property Hook: Remaining unplayed days (kept for backward compatibility)
     */
    public int $remainingDaysInMonth {
        get => $this->remainingWeeksInMonth;
    }

    /**
     * PHP 8.4 Property Hook: whether the 15-minute weekly tick is due and within the 4-week month limit
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

            return $this->next_weekly_tick_at === null && $this->next_daily_tick_at === null;
        }
    }

    /**
     * PHP 8.4 Property Hook: whether the daily tick is due (aliased to weekly tick)
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

            if ($weeklySecs !== null && $dailySecs !== null) {
                return min($weeklySecs, $dailySecs);
            }

            return $weeklySecs ?? $dailySecs ?? 0;
        }
    }

    /**
     * PHP 8.4 Property Hook: Remaining seconds until next daily tick (aliased to weekly tick)
     */
    public int $secondsUntilNextDailyTick {
        get => $this->secondsUntilNextWeeklyTick;
    }

    /**
     * PHP 8.4 Property Hook: whether the scheduled cron resolution tick is due
     */
    public bool $isTickOverdue {
        get => now()->greaterThanOrEqualTo($this->next_tick_at);
    }

    /**
     * PHP 8.4 Property Hook: Remaining seconds until next month resolution
     */
    public int $secondsUntilNextTick {
        get => max(0, (int) now()->diffInSeconds($this->next_tick_at, false));
    }

    /**
     * Check if this session has online users who haven't reached their 30-day daily limit.
     */
    public function hasOnlineActiveUsers(int $thresholdMinutes = 15): bool
    {
        return $this->users()
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subMinutes($thresholdMinutes))
            ->get()
            ->contains(fn (User $user) => ! $user->hasReachedDailyPlayLimit);
    }

    /**
     * PHP 8.4 Property Hook: whether automated AI missionary co-laborers are active
     */
    public bool $isCoopMode {
        get => (bool) ($this->settings['coop_team_mode'] ?? true);
    }

    /**
     * Enable AI co-laborers in this session
     */
    public function enableCoopMode(): void
    {
        $settings = $this->settings ?? [];
        $settings['coop_team_mode'] = true;
        $this->update(['settings' => $settings]);
    }

    /**
     * Disable AI co-laborers (100% Solo Pioneer mode)
     */
    public function enableSoloMode(): void
    {
        $settings = $this->settings ?? [];
        $settings['coop_team_mode'] = false;
        $this->update(['settings' => $settings]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_game_month' => 'integer',
            'current_game_week' => 'integer',
            'weeks_processed_this_month' => 'integer',
            'current_game_day' => 'integer',
            'days_processed_this_month' => 'integer',
            'calendar_date' => 'date',
            'month_duration_minutes' => 'integer',
            'next_tick_at' => 'datetime',
            'last_weekly_tick_at' => 'datetime',
            'next_weekly_tick_at' => 'datetime',
            'last_daily_tick_at' => 'datetime',
            'next_daily_tick_at' => 'datetime',
            'last_monthly_tick_at' => 'datetime',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Participating players in this game session
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_game_session_id');
    }

    /**
     * Automated computer-based missionary co-laborers in this session
     *
     * @return HasMany<User, $this>
     */
    public function aiColaborers(): HasMany
    {
        return $this->hasMany(User::class, 'current_game_session_id')->where('is_ai_colaborer', true);
    }

    /**
     * Simulated population of Nagoya NPCs
     *
     * @return HasMany<Npc, $this>
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    /**
     * Churches and 3/3rds groups planted in this session
     *
     * @return HasMany<Church, $this>
     */
    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }

    /**
     * Queued player actions for current or past months
     *
     * @return HasMany<QueuedMonthlyAction, $this>
     */
    public function queuedActions(): HasMany
    {
        return $this->hasMany(QueuedMonthlyAction::class);
    }

    /**
     * Historical monthly reports
     *
     * @return HasMany<MonthlyReport, $this>
     */
    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }
}
