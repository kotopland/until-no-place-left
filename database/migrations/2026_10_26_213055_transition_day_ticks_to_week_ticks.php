<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('game_sessions', 'current_game_week')) {
                $table->unsignedSmallInteger('current_game_week')->default(1)->after('current_game_month');
            }
            if (! Schema::hasColumn('game_sessions', 'weeks_processed_this_month')) {
                $table->unsignedSmallInteger('weeks_processed_this_month')->default(0)->after('current_game_week');
            }
            if (! Schema::hasColumn('game_sessions', 'last_weekly_tick_at')) {
                $table->timestamp('last_weekly_tick_at')->nullable()->after('weeks_processed_this_month');
            }
            if (! Schema::hasColumn('game_sessions', 'next_weekly_tick_at')) {
                $table->timestamp('next_weekly_tick_at')->nullable()->after('last_weekly_tick_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_game_week')) {
                $table->unsignedSmallInteger('current_game_week')->default(1)->after('current_game_month');
            }
            if (! Schema::hasColumn('users', 'weeks_processed_this_month')) {
                $table->unsignedSmallInteger('weeks_processed_this_month')->default(0)->after('current_game_week');
            }
            if (! Schema::hasColumn('users', 'weeks_played_today')) {
                $table->unsignedSmallInteger('weeks_played_today')->default(0)->after('weeks_processed_this_month');
            }
            if (! Schema::hasColumn('users', 'weekly_action_points')) {
                $table->unsignedSmallInteger('weekly_action_points')->default(20)->after('weeks_played_today');
            }
            if (! Schema::hasColumn('users', 'consecutive_exhaustion_weeks')) {
                $table->unsignedSmallInteger('consecutive_exhaustion_weeks')->default(0)->after('weekly_action_points');
            }
            if (! Schema::hasColumn('users', 'last_weekly_tick_at')) {
                $table->timestamp('last_weekly_tick_at')->nullable()->after('consecutive_exhaustion_weeks');
            }
            if (! Schema::hasColumn('users', 'next_weekly_tick_at')) {
                $table->timestamp('next_weekly_tick_at')->nullable()->after('last_weekly_tick_at');
            }
        });

        if (! Schema::hasColumn('queued_monthly_actions', 'game_week')) {
            Schema::table('queued_monthly_actions', function (Blueprint $table) {
                $table->unsignedSmallInteger('game_week')->default(1)->after('game_month');
            });
        }

        if (! Schema::hasColumn('churches', 'last_coached_game_week')) {
            Schema::table('churches', function (Blueprint $table) {
                $table->unsignedSmallInteger('last_coached_game_week')->nullable()->after('last_coached_game_month');
            });
        }

        if (! Schema::hasColumn('prayer_walks', 'game_week')) {
            Schema::table('prayer_walks', function (Blueprint $table) {
                $table->unsignedSmallInteger('game_week')->default(1)->after('game_month');
            });
        }

        // Backfill data from existing day columns using database-agnostic CASE statements
        if (Schema::hasColumn('game_sessions', 'current_game_week')) {
            $sessionUpdates = [
                'current_game_week' => DB::raw('CASE WHEN current_game_day IS NULL THEN 1 WHEN current_game_day <= 7 THEN 1 WHEN current_game_day <= 14 THEN 2 WHEN current_game_day <= 21 THEN 3 ELSE 4 END'),
                'weeks_processed_this_month' => DB::raw('CASE WHEN days_processed_this_month IS NULL THEN 0 WHEN days_processed_this_month < 7 THEN 0 WHEN days_processed_this_month < 14 THEN 1 WHEN days_processed_this_month < 21 THEN 2 WHEN days_processed_this_month < 28 THEN 3 ELSE 4 END'),
            ];

            if (Schema::hasColumn('game_sessions', 'last_daily_tick_at')) {
                $sessionUpdates['last_weekly_tick_at'] = DB::raw('last_daily_tick_at');
            }
            if (Schema::hasColumn('game_sessions', 'next_daily_tick_at')) {
                $sessionUpdates['next_weekly_tick_at'] = DB::raw('next_daily_tick_at');
            }

            DB::table('game_sessions')->update($sessionUpdates);
        }

        if (Schema::hasColumn('users', 'current_game_week')) {
            $userUpdates = [
                'current_game_week' => DB::raw('CASE WHEN current_game_day IS NULL THEN 1 WHEN current_game_day <= 7 THEN 1 WHEN current_game_day <= 14 THEN 2 WHEN current_game_day <= 21 THEN 3 ELSE 4 END'),
                'weeks_processed_this_month' => DB::raw('CASE WHEN days_processed_this_month IS NULL THEN 0 WHEN days_processed_this_month < 7 THEN 0 WHEN days_processed_this_month < 14 THEN 1 WHEN days_processed_this_month < 21 THEN 2 WHEN days_processed_this_month < 28 THEN 3 ELSE 4 END'),
                'weeks_played_today' => DB::raw('CASE WHEN days_played_today IS NULL THEN 0 WHEN days_played_today < 7 THEN 0 WHEN days_played_today < 14 THEN 1 WHEN days_played_today < 21 THEN 2 WHEN days_played_today < 28 THEN 3 ELSE 4 END'),
                'weekly_action_points' => 20,
                'max_action_points' => 26,
                'action_points' => DB::raw('CASE WHEN action_points < 20 THEN 20 ELSE action_points END'),
            ];

            if (Schema::hasColumn('users', 'daily_action_points')) {
                $userUpdates['daily_action_points'] = 20;
            }
            if (Schema::hasColumn('users', 'last_daily_tick_at')) {
                $userUpdates['last_weekly_tick_at'] = DB::raw('last_daily_tick_at');
            }
            if (Schema::hasColumn('users', 'next_daily_tick_at')) {
                $userUpdates['next_weekly_tick_at'] = DB::raw('next_daily_tick_at');
            }

            DB::table('users')->update($userUpdates);
        }

        if (Schema::hasColumn('queued_monthly_actions', 'game_week') && Schema::hasColumn('queued_monthly_actions', 'game_day')) {
            DB::table('queued_monthly_actions')->update([
                'game_week' => DB::raw('CASE WHEN game_day IS NULL THEN 1 WHEN game_day <= 7 THEN 1 WHEN game_day <= 14 THEN 2 WHEN game_day <= 21 THEN 3 ELSE 4 END'),
            ]);
        }

        if (Schema::hasColumn('churches', 'last_coached_game_week') && Schema::hasColumn('churches', 'last_coached_game_day')) {
            DB::table('churches')->update([
                'last_coached_game_week' => DB::raw('CASE WHEN last_coached_game_day IS NULL THEN NULL WHEN last_coached_game_day <= 7 THEN 1 WHEN last_coached_game_day <= 14 THEN 2 WHEN last_coached_game_day <= 21 THEN 3 ELSE 4 END'),
            ]);
        }

        if (Schema::hasColumn('prayer_walks', 'game_week') && Schema::hasColumn('prayer_walks', 'game_day')) {
            DB::table('prayer_walks')->update([
                'game_week' => DB::raw('CASE WHEN game_day IS NULL THEN 1 WHEN game_day <= 7 THEN 1 WHEN game_day <= 14 THEN 2 WHEN game_day <= 21 THEN 3 ELSE 4 END'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $columns = array_filter([
                'current_game_week',
                'weeks_processed_this_month',
                'last_weekly_tick_at',
                'next_weekly_tick_at',
            ], fn (string $col) => Schema::hasColumn('game_sessions', $col));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                'current_game_week',
                'weeks_processed_this_month',
                'weeks_played_today',
                'weekly_action_points',
                'consecutive_exhaustion_weeks',
                'last_weekly_tick_at',
                'next_weekly_tick_at',
            ], fn (string $col) => Schema::hasColumn('users', $col));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            if (Schema::hasColumn('queued_monthly_actions', 'game_week')) {
                $table->dropColumn('game_week');
            }
        });

        Schema::table('churches', function (Blueprint $table) {
            if (Schema::hasColumn('churches', 'last_coached_game_week')) {
                $table->dropColumn('last_coached_game_week');
            }
        });

        Schema::table('prayer_walks', function (Blueprint $table) {
            if (Schema::hasColumn('prayer_walks', 'game_week')) {
                $table->dropColumn('game_week');
            }
        });
    }
};
