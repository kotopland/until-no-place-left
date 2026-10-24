<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
    'current_game_day',
    'days_processed_this_month',
    'calendar_date',
    'month_duration_minutes',
    'next_tick_at',
    'last_daily_tick_at',
    'next_daily_tick_at',
    'last_monthly_tick_at',
    'is_active',
    'settings',
])]
class GameSession extends Model
{
    use HasFactory;

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
     * PHP 8.4 Property Hook: Human-friendly Day in Month label (e.g., Day 12 / 30)
     */
    public string $dayInMonthLabel {
        get => "Day {$this->current_game_day}/30 (Month {$this->current_game_month})";
    }

    /**
     * PHP 8.4 Property Hook: Remaining unplayed days in the current 30-day game month
     */
    public int $remainingDaysInMonth {
        get => max(0, 30 - $this->days_processed_this_month);
    }

    /**
     * PHP 8.4 Property Hook: whether the 10-minute daily tick is due and within the 30-day month limit
     */
    public bool $isDailyTickDue {
        get => ($this->next_daily_tick_at === null || now()->greaterThanOrEqualTo($this->next_daily_tick_at))
            && $this->days_processed_this_month < 30;
    }

    /**
     * PHP 8.4 Property Hook: Remaining seconds until next 10-minute online daily tick
     */
    public int $secondsUntilNextDailyTick {
        get => $this->next_daily_tick_at
            ? max(0, (int) now()->diffInSeconds($this->next_daily_tick_at, false))
            : 0;
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_game_month' => 'integer',
            'current_game_day' => 'integer',
            'days_processed_this_month' => 'integer',
            'calendar_date' => 'date',
            'month_duration_minutes' => 'integer',
            'next_tick_at' => 'datetime',
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
