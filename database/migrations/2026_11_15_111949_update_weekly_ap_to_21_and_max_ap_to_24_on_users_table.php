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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedSmallInteger('weekly_action_points')->default(21)->change();
            $table->unsignedInteger('max_action_points')->default(24)->change();
        });

        DB::table('users')->where('weekly_action_points', 20)->update([
            'weekly_action_points' => 21,
            'daily_action_points' => 21,
        ]);
        DB::table('users')->where('max_action_points', 26)->update([
            'max_action_points' => 24,
        ]);
        DB::table('users')->where('action_points', 20)->update([
            'action_points' => 21,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedSmallInteger('weekly_action_points')->default(20)->change();
            $table->unsignedInteger('max_action_points')->default(26)->change();
        });
    }
};
