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
            $table->unsignedSmallInteger('consecutive_exhaustion_days')->default(0)->after('daily_action_points');
            $table->unsignedSmallInteger('fatigue_penalty_ap')->default(0)->after('consecutive_exhaustion_days');
            $table->boolean('is_burned_out')->default(false)->after('fatigue_penalty_ap');
            $table->unsignedSmallInteger('burned_out_until_game_month')->nullable()->after('is_burned_out');
            $table->unsignedSmallInteger('burnout_count')->default(0)->after('burned_out_until_game_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'consecutive_exhaustion_days',
                'fatigue_penalty_ap',
                'is_burned_out',
                'burned_out_until_game_month',
                'burnout_count',
            ]);
        });
    }
};
