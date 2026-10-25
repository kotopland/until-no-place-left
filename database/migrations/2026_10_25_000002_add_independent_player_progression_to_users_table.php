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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('current_game_month')->default(1)->after('daily_action_points');
            $table->unsignedTinyInteger('current_game_day')->default(1)->after('current_game_month');
            $table->unsignedTinyInteger('days_processed_this_month')->default(0)->after('current_game_day');
            $table->timestamp('last_daily_tick_at')->nullable()->after('days_processed_this_month');
            $table->timestamp('next_daily_tick_at')->nullable()->after('last_daily_tick_at');
            $table->timestamp('last_monthly_tick_at')->nullable()->after('next_daily_tick_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'current_game_month',
                'current_game_day',
                'days_processed_this_month',
                'last_daily_tick_at',
                'next_daily_tick_at',
                'last_monthly_tick_at',
            ]);
        });
    }
};
