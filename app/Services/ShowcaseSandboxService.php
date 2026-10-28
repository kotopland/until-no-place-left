<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ShowcaseSandboxService
{
    public const string CONNECTION = 'showcase';

    public const string GUEST_SUFFIX = 'guest';

    protected string $primaryConnection = 'sqlite';

    protected ?int $activeUserId = null;

    protected bool $isGuestSession = false;

    public function __construct()
    {
        $currentDefault = (string) config('database.default', 'sqlite');
        if ($currentDefault !== self::CONNECTION) {
            $this->primaryConnection = $currentDefault;
        }

        // Always pin Laravel HTTP session storage to the primary database connection
        config(['session.connection' => $this->primaryConnection]);
    }

    /**
     * Get the primary live game database connection name.
     */
    public function primaryConnection(): string
    {
        return $this->primaryConnection;
    }

    /**
     * Check whether the isolated showcase SQLite connection is currently active.
     */
    public function isActive(): bool
    {
        return DB::getDefaultConnection() === self::CONNECTION;
    }

    /**
     * Resolve the filesystem path for a player's or shared guest's temporary showcase SQLite database.
     */
    public function getSandboxDatabasePath(?int $userId = null, bool $isGuest = false): string
    {
        $dir = storage_path('app/showcase');
        File::ensureDirectoryExists($dir);

        if ($userId !== null && $userId > 0) {
            $suffix = "player_{$userId}";
        } elseif ($isGuest) {
            $suffix = self::GUEST_SUFFIX;
        } else {
            $suffix = 'cli';
        }

        return "{$dir}/showcase_{$suffix}.sqlite";
    }

    /**
     * Ensure the temporary SQLite database exists, has the full schema, and is registered under the 'showcase' connection.
     */
    public function ensureSandboxDatabase(?int $userId = null, bool $recreate = false, bool $isGuest = false): string
    {
        $dbPath = $this->getSandboxDatabasePath($userId, $isGuest);

        if ($recreate && File::exists($dbPath)) {
            DB::purge(self::CONNECTION);
            @File::delete([$dbPath, "{$dbPath}-wal", "{$dbPath}-shm"]);
        }

        if (! File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        $currentConfiguredPath = config('database.connections.'.self::CONNECTION.'.database');
        if ($currentConfiguredPath !== $dbPath) {
            DB::purge(self::CONNECTION);
            config([
                'database.connections.'.self::CONNECTION => [
                    'driver' => 'sqlite',
                    'database' => $dbPath,
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                    'busy_timeout' => 5000,
                    'journal_mode' => 'WAL',
                    'synchronous' => 'NORMAL',
                    'transaction_mode' => 'DEFERRED',
                ],
            ]);
        }

        if (! Schema::connection(self::CONNECTION)->hasTable('wards')) {
            $this->cloneSchemaFromPrimaryConnection();
        }

        return $dbPath;
    }

    /**
     * Activate the isolated temporary SQLite database for the given player (or CLI) and ensure a baseline session exists.
     */
    public function activate(?int $userId = null, bool $recreate = false): GameSession
    {
        $resolvedUserId = $userId ?? $this->resolvePrimaryUserId();
        $this->activeUserId = $resolvedUserId;
        $this->isGuestSession = false;

        $this->ensureSandboxDatabase($resolvedUserId, $recreate);

        config(['session.connection' => $this->primaryConnection]);
        DB::setDefaultConnection(self::CONNECTION);

        $session = GameSession::where('code', 'nagoya-2026')->first();

        if (! $session) {
            $session = GameSession::create([
                'code' => 'nagoya-2026',
                'name' => 'Nagoya Harvest 2026 (Isolated Computer Simulation)',
                'current_game_month' => 1,
                'current_game_week' => 1,
                'weeks_processed_this_month' => 0,
                'current_game_day' => 1,
                'days_processed_this_month' => 0,
                'calendar_date' => '2026-04-01',
                'month_duration_minutes' => 720,
                'next_tick_at' => now()->addHours(12),
                'next_weekly_tick_at' => now()->addMinutes(15),
                'next_daily_tick_at' => now()->addMinutes(15),
                'is_active' => true,
                'settings' => [
                    'city' => 'Nagoya',
                    'target_generations' => 4,
                    'ap_per_month' => 80,
                    'coop_enabled' => true,
                    'isolated_showcase' => true,
                ],
            ]);

            app(RealLifeSimulationService::class)->resetSessionToBaseline($session, 'coop');
            $session->refresh();
        }

        return $session;
    }

    /**
     * Activate the shared SQLite database for non-registered (guest) players.
     * To reduce CPU load, this shared database is only generated/regenerated at most once per real-life calendar day.
     */
    public function activateGuest(bool $forceRegenerate = false): GameSession
    {
        $this->activeUserId = null;
        $this->isGuestSession = true;

        $this->ensureSandboxDatabase(userId: null, recreate: $forceRegenerate, isGuest: true);

        config(['session.connection' => $this->primaryConnection]);
        DB::setDefaultConnection(self::CONNECTION);

        $session = GameSession::where('code', 'nagoya-2026')->first();

        if (! $session) {
            $session = GameSession::create([
                'code' => 'nagoya-2026',
                'name' => 'Nagoya Harvest 2026 (Shared Guest Simulation)',
                'current_game_month' => 1,
                'current_game_week' => 1,
                'weeks_processed_this_month' => 0,
                'current_game_day' => 1,
                'days_processed_this_month' => 0,
                'calendar_date' => '2026-04-01',
                'month_duration_minutes' => 720,
                'next_tick_at' => now()->addHours(12),
                'next_weekly_tick_at' => now()->addMinutes(15),
                'next_daily_tick_at' => now()->addMinutes(15),
                'is_active' => true,
                'settings' => [
                    'city' => 'Nagoya',
                    'target_generations' => 4,
                    'ap_per_month' => 80,
                    'coop_enabled' => true,
                    'isolated_showcase' => true,
                    'shared_guest_db' => true,
                ],
            ]);
        }

        if ($forceRegenerate || ! $this->isGuestDatabaseGeneratedToday($session)) {
            $simulator = app(RealLifeSimulationService::class);
            $simulator->resetSessionToBaseline($session, 'coop');
            $session = GameSession::where('code', 'nagoya-2026')->first() ?? $session->fresh();

            $leadPioneer = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->first()
                ?? User::where('is_ai_colaborer', true)->first();
            $resetSnapshot = $simulator->captureSnapshot($session, $leadPioneer, 1, 'reset');

            $result = $simulator->simulate(
                session: $session,
                targetMonths: 180,
                mode: 'coop',
                resetFirst: true,
            );

            $session = GameSession::where('code', 'nagoya-2026')->first() ?? $session->fresh();
            $settings = is_array($session->settings) ? $session->settings : [];
            $settings['shared_guest_db'] = true;
            $settings['guest_generated_date'] = now()->toDateString();
            $settings['guest_generated_at'] = now()->toIso8601String();
            $settings['guest_reset_snapshot'] = $resetSnapshot;
            $settings['guest_milestones'] = $result['milestones'];
            $settings['guest_final_snapshot'] = $result['final_snapshot'];

            $session->update(['settings' => $settings]);
            $session->refresh();
        }

        return $session;
    }

    /**
     * Check whether the shared guest SQLite database has already been generated today (current real-life calendar date).
     */
    public function isGuestDatabaseGeneratedToday(?GameSession $session = null): bool
    {
        $generatedDate = $this->getGuestGeneratedDate($session);

        return $generatedDate !== null && $generatedDate === now()->toDateString();
    }

    /**
     * Get the real-life calendar date (Y-m-d) when the shared guest SQLite database was last generated.
     */
    public function getGuestGeneratedDate(?GameSession $session = null): ?string
    {
        if ($session === null) {
            $dbPath = $this->getSandboxDatabasePath(userId: null, isGuest: true);
            if (! File::exists($dbPath) || File::size($dbPath) === 0) {
                return null;
            }

            $this->ensureSandboxDatabase(userId: null, recreate: false, isGuest: true);
            $session = GameSession::on(self::CONNECTION)->where('code', 'nagoya-2026')->first();
        }

        if (! $session || ! is_array($session->settings)) {
            return null;
        }

        $date = $session->settings['guest_generated_date'] ?? null;

        return is_string($date) && $date !== '' ? $date : null;
    }

    /**
     * Attempt to regenerate the shared guest SQLite database, enforcing the once-per-real-life-day limit.
     *
     * @return array{regenerated: bool, generated_date: string, session: GameSession}
     */
    public function regenerateGuestDatabaseIfAllowed(): array
    {
        $this->ensureSandboxDatabase(userId: null, recreate: false, isGuest: true);
        config(['session.connection' => $this->primaryConnection]);
        DB::setDefaultConnection(self::CONNECTION);

        $existingSession = GameSession::where('code', 'nagoya-2026')->first();
        if ($existingSession && $this->isGuestDatabaseGeneratedToday($existingSession)) {
            return [
                'regenerated' => false,
                'generated_date' => (string) ($existingSession->settings['guest_generated_date'] ?? now()->toDateString()),
                'session' => $existingSession,
            ];
        }

        $session = $this->activateGuest(forceRegenerate: true);

        return [
            'regenerated' => true,
            'generated_date' => (string) ($session->settings['guest_generated_date'] ?? now()->toDateString()),
            'session' => $session,
        ];
    }

    /**
     * If the current browser session has Showcase Mode active, switch the default DB connection to the player's or shared guest's sandbox SQLite DB.
     */
    public function syncConnectionFromSession(): void
    {
        if (! session('showcase_mode_active', false) && auth()->check()) {
            return;
        }

        if (session('showcase_is_guest', false) || ! auth()->check()) {
            if (session('showcase_mode_active', false)) {
                $this->activateGuest();
            }

            return;
        }

        $userId = session('showcase_user_id') ?? $this->resolvePrimaryUserId();
        $this->activate($userId !== null ? (int) $userId : null);
    }

    /**
     * Check whether the active showcase connection is the shared guest SQLite database.
     */
    public function isGuestSession(): bool
    {
        return $this->isGuestSession;
    }

    /**
     * Deactivate the isolated showcase database and restore the primary live game database connection.
     */
    public function deactivate(): void
    {
        $this->isGuestSession = false;
        DB::setDefaultConnection($this->primaryConnection);
    }

    /**
     * Resolve the authenticated user's ID from the primary database connection without mutating it.
     */
    public function resolvePrimaryUserId(): ?int
    {
        if ($this->activeUserId !== null) {
            return $this->activeUserId;
        }

        $authUser = auth()->user();
        if ($authUser instanceof User) {
            return (int) $authUser->getKey();
        }

        $firstHuman = User::on($this->primaryConnection)
            ->where('is_ai_colaborer', false)
            ->orderBy('id')
            ->first();

        return $firstHuman ? (int) $firstHuman->getKey() : null;
    }

    /**
     * Clone all table and index DDLs (plus initial 16 Nagoya wards) from the primary SQLite database into the temporary SQLite database.
     */
    protected function cloneSchemaFromPrimaryConnection(): void
    {
        $showcaseConn = DB::connection(self::CONNECTION);
        $primaryConn = DB::connection($this->primaryConnection);

        $showcaseConn->statement('PRAGMA foreign_keys = OFF;');

        $ddls = $primaryConn->select(
            "SELECT type, name, sql FROM sqlite_master WHERE type IN ('table', 'index') AND name NOT LIKE 'sqlite_%' AND sql IS NOT NULL ORDER BY CASE type WHEN 'table' THEN 1 ELSE 2 END"
        );

        foreach ($ddls as $ddl) {
            if (! empty($ddl->sql)) {
                $showcaseConn->statement($ddl->sql);
            }
        }

        // Copy the 16 Nagoya wards (including imported GeoJSON boundaries) if already seeded in primary DB
        $existingWards = $primaryConn->table('wards')->get();
        if ($existingWards->isNotEmpty()) {
            foreach ($existingWards as $wardRow) {
                $showcaseConn->table('wards')->insert((array) $wardRow);
            }
        }

        $showcaseConn->statement('PRAGMA foreign_keys = ON;');
    }
}
