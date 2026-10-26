<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\OikosRelationship;
use App\Models\PrayerWalk;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use Database\Seeders\NagoyaOikosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetGameCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game:reset
                            {--session= : Optional specific GameSession code (e.g. nagoya-2026)}
                            {--all : Reset all game sessions}
                            {--wipe-players : Delete all player accounts (except default missionary coach)}
                            {--solo : Set mode to 100% Solo Pioneer (disable automated AI co-laborers)}
                            {--coop : Set mode to Team Co-op (enable automated AI co-laborers)}
                            {--force : Force reset without confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Reset the Nagoya Oikos game simulation back to Month 1 Day 1';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sessionCode = $this->option('session');
        $all = (bool) $this->option('all');
        $force = (bool) $this->option('force');
        $wipePlayers = (bool) $this->option('wipe-players');
        $solo = (bool) $this->option('solo');
        $coop = (bool) $this->option('coop');

        if ($all) {
            $sessions = GameSession::all();
        } elseif ($sessionCode) {
            $sessions = GameSession::where('code', $sessionCode)->get();
        } else {
            $activeSession = GameSession::where('is_active', true)->first()
                ?? GameSession::where('code', 'nagoya-2026')->first()
                ?? GameSession::first();

            $sessions = $activeSession ? collect([$activeSession]) : collect();
        }

        if ($sessions->isEmpty()) {
            $this->components->warn('No game sessions found to reset.');

            return self::FAILURE;
        }

        $sessionNames = $sessions->map(fn (GameSession $s) => "{$s->name} ({$s->code})")->join(', ');

        if (! $force && ! $this->confirm("Are you sure you want to reset [{$sessionNames}] back to Month 1 Day 1? This will clear queued actions, dynamic churches, reports, and restore starting Action Points.", true)) {
            $this->components->info('Game reset cancelled.');

            return self::SUCCESS;
        }

        foreach ($sessions as $session) {
            $this->components->info("Resetting session: {$session->name} [{$session->code}]...");

            DB::transaction(function () use ($session, $wipePlayers, $solo, $coop): void {
                // 1. Delete transient gameplay records
                QueuedMonthlyAction::where('game_session_id', $session->id)->delete();
                MonthlyReport::where('game_session_id', $session->id)->delete();
                PrayerWalk::where('game_session_id', $session->id)->delete();

                // 2. Delete NPC contributions
                NpcContribution::whereHas('npc', function ($q) use ($session): void {
                    $q->where('game_session_id', $session->id);
                })->delete();

                // 3. Clear Oikos relationship graph
                OikosRelationship::where('game_session_id', $session->id)->delete();

                // 4. Clear churches (break parent recursion before delete)
                Church::where('game_session_id', $session->id)->update(['parent_church_id' => null]);
                Church::where('game_session_id', $session->id)->delete();

                // 5. Clear NPCs (dynamic multiplier disciples and previous state)
                Npc::where('game_session_id', $session->id)->delete();

                // 6. Reset session timeline to Month 1, Day 1
                $session->update([
                    'current_game_month' => 1,
                    'current_game_day' => 1,
                    'calendar_date' => '2026-04-01',
                    'month_duration_minutes' => 720,
                    'next_tick_at' => now()->addMinutes(10),
                    'last_daily_tick_at' => null,
                    'is_active' => true,
                ]);

                if ($solo) {
                    $session->enableSoloMode();
                } elseif ($coop) {
                    $session->enableCoopMode();
                }

                // 7. Reset player Action Points & scoring, and optionally wipe player accounts
                if ($wipePlayers) {
                    User::where('current_game_session_id', $session->id)
                        ->where('email', '!=', 'coach@nagoyaharvest.jp')
                        ->delete();
                }

                User::where('current_game_session_id', $session->id)->each(function (User $user) use ($wipePlayers): void {
                    $isCoach = $user->email === 'coach@nagoyaharvest.jp';

                    $updates = [
                        'current_game_month' => 1,
                        'current_game_day' => 1,
                        'days_processed_this_month' => 0,
                        'action_points' => 12,
                        'max_action_points' => 12,
                        'daily_action_points' => 12,
                        'total_generational_score' => $isCoach ? 60 : 0,
                        'coop_synergy_score' => 0,
                        'days_played_today' => 0,
                        'last_played_real_date' => now()->toDateString(),
                        'last_active_at' => now(),
                        'last_monthly_tick_at' => null,
                        'last_daily_tick_at' => null,
                        'next_daily_tick_at' => now()->addMinutes(10),
                    ];

                    if ($wipePlayers) {
                        $updates['home_ward_id'] = null;
                    }

                    $user->update($updates);
                });

                // 8. Re-seed archetypal Nagoya NPCs, initial Oikos graph & G1 Church
                (new NagoyaOikosSeeder)->run();
            });

            $session->refresh();
            $this->components->twoColumnDetail('Game Month', '1 (Reset)');
            $this->components->twoColumnDetail('Game Day', '1 / 30');
            $this->components->twoColumnDetail('Gameplay Mode', $session->isCoopMode ? 'Team Co-op (AI Co-Laborers Active)' : '100% Solo Pioneer');
            $this->components->twoColumnDetail('Calendar Date', '2026-04-01 (Nagoya Spring)');
            $this->components->twoColumnDetail('Player Action Points', '12 / 12 AP restored');
            if ($wipePlayers) {
                $this->components->twoColumnDetail('Player Accounts', 'Wiped (Only coach restored)');
            }
            $this->components->twoColumnDetail('Root Church', 'Meito Living Seed (G1)');
            $this->components->twoColumnDetail('Archetypal Believer', 'Daisuke Kato (Ready for 411 Training)');
            $this->components->twoColumnDetail('Luke 10 Person of Peace', 'Kenji Tanaka (Osu Market)');
        }

        // 9. Flush application cache
        cache()->flush();

        $this->components->info('🌱 Nagoya Oikos game simulation has been successfully reset!');

        return self::SUCCESS;
    }
}
