<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_game_day')->default(1)->after('current_game_month');
            $table->unsignedTinyInteger('days_processed_this_month')->default(0)->after('current_game_day');
            $table->timestamp('last_daily_tick_at')->nullable()->after('next_tick_at');
            $table->timestamp('next_daily_tick_at')->nullable()->after('last_daily_tick_at');
            $table->timestamp('last_monthly_tick_at')->nullable()->after('next_daily_tick_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('notify_in_app');
            $table->unsignedTinyInteger('days_played_today')->default(0)->after('last_active_at');
            $table->date('last_played_real_date')->nullable()->after('days_played_today');
            $table->unsignedSmallInteger('daily_action_points')->default(2)->after('last_played_real_date');
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            $table->unsignedTinyInteger('game_day')->default(1)->after('game_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'current_game_day',
                'days_processed_this_month',
                'last_daily_tick_at',
                'next_daily_tick_at',
                'last_monthly_tick_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_active_at',
                'days_played_today',
                'last_played_real_date',
                'daily_action_points',
            ]);
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            $table->dropColumn('game_day');
        });
    }
};
