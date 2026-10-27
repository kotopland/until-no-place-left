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
            $table->unsignedSmallInteger('current_game_week')->default(1)->after('current_game_month');
            $table->unsignedSmallInteger('weeks_processed_this_month')->default(0)->after('current_game_week');
            $table->timestamp('last_weekly_tick_at')->nullable()->after('weeks_processed_this_month');
            $table->timestamp('next_weekly_tick_at')->nullable()->after('last_weekly_tick_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('current_game_week')->default(1)->after('current_game_month');
            $table->unsignedSmallInteger('weeks_processed_this_month')->default(0)->after('current_game_week');
            $table->unsignedSmallInteger('weeks_played_today')->default(0)->after('weeks_processed_this_month');
            $table->unsignedSmallInteger('weekly_action_points')->default(20)->after('weeks_played_today');
            $table->unsignedSmallInteger('consecutive_exhaustion_weeks')->default(0)->after('weekly_action_points');
            $table->timestamp('last_weekly_tick_at')->nullable()->after('consecutive_exhaustion_weeks');
            $table->timestamp('next_weekly_tick_at')->nullable()->after('last_weekly_tick_at');
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            $table->unsignedSmallInteger('game_week')->default(1)->after('game_month');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->unsignedSmallInteger('last_coached_game_week')->nullable()->after('last_coached_game_month');
        });

        Schema::table('prayer_walks', function (Blueprint $table) {
            $table->unsignedSmallInteger('game_week')->default(1)->after('game_month');
        });

        // Backfill data from existing day columns
        DB::table('game_sessions')->update([
            'current_game_week' => DB::raw('CASE WHEN current_game_day IS NOT NULL THEN MIN(4, MAX(1, CAST(((current_game_day - 1) / 7) + 1 AS INTEGER))) ELSE 1 END'),
            'weeks_processed_this_month' => DB::raw('CASE WHEN days_processed_this_month IS NOT NULL THEN MIN(4, MAX(0, CAST(days_processed_this_month / 7 AS INTEGER))) ELSE 0 END'),
            'last_weekly_tick_at' => DB::raw('last_daily_tick_at'),
            'next_weekly_tick_at' => DB::raw('next_daily_tick_at'),
        ]);

        DB::table('users')->update([
            'current_game_week' => DB::raw('CASE WHEN current_game_day IS NOT NULL THEN MIN(4, MAX(1, CAST(((current_game_day - 1) / 7) + 1 AS INTEGER))) ELSE 1 END'),
            'weeks_processed_this_month' => DB::raw('CASE WHEN days_processed_this_month IS NOT NULL THEN MIN(4, MAX(0, CAST(days_processed_this_month / 7 AS INTEGER))) ELSE 0 END'),
            'weeks_played_today' => DB::raw('CASE WHEN days_played_today IS NOT NULL THEN MIN(4, MAX(0, CAST(days_played_today / 7 AS INTEGER))) ELSE 0 END'),
            'weekly_action_points' => 20,
            'daily_action_points' => 20,
            'max_action_points' => 26,
            'action_points' => DB::raw('CASE WHEN action_points < 20 THEN 20 ELSE action_points END'),
            'last_weekly_tick_at' => DB::raw('last_daily_tick_at'),
            'next_weekly_tick_at' => DB::raw('next_daily_tick_at'),
        ]);

        DB::table('queued_monthly_actions')->update([
            'game_week' => DB::raw('CASE WHEN game_day IS NOT NULL THEN MIN(4, MAX(1, CAST(((game_day - 1) / 7) + 1 AS INTEGER))) ELSE 1 END'),
        ]);

        DB::table('churches')->update([
            'last_coached_game_week' => DB::raw('CASE WHEN last_coached_game_day IS NOT NULL THEN MIN(4, MAX(1, CAST(((last_coached_game_day - 1) / 7) + 1 AS INTEGER))) ELSE NULL END'),
        ]);

        DB::table('prayer_walks')->update([
            'game_week' => DB::raw('CASE WHEN game_day IS NOT NULL THEN MIN(4, MAX(1, CAST(((game_day - 1) / 7) + 1 AS INTEGER))) ELSE 1 END'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['current_game_week', 'weeks_processed_this_month', 'last_weekly_tick_at', 'next_weekly_tick_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'current_game_week',
                'weeks_processed_this_month',
                'weeks_played_today',
                'weekly_action_points',
                'consecutive_exhaustion_weeks',
                'last_weekly_tick_at',
                'next_weekly_tick_at',
            ]);
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            $table->dropColumn('game_week');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn('last_coached_game_week');
        });

        Schema::table('prayer_walks', function (Blueprint $table) {
            $table->dropColumn('game_week');
        });
    }
};
