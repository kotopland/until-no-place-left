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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('daily_action_points')->default(12)->change();
        });

        // Heal existing users who had daily AP default 2 or insufficient AP on current day
        DB::table('users')
            ->where('daily_action_points', '<', 12)
            ->update([
                'daily_action_points' => 12,
                'max_action_points' => 12,
                'action_points' => DB::raw('CASE WHEN action_points < 12 THEN 12 ELSE action_points END'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('daily_action_points')->default(2)->change();
        });
    }
};
