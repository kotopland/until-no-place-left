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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedSmallInteger('consecutive_inactive_months')->default(0)->after('burnout_count');
            $table->unsignedSmallInteger('last_played_game_month')->nullable()->after('consecutive_inactive_months');
        });

        Schema::table('monthly_reports', function (Blueprint $table): void {
            $table->integer('generational_score_gained')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'consecutive_inactive_months',
                'last_played_game_month',
            ]);
        });

        Schema::table('monthly_reports', function (Blueprint $table): void {
            $table->unsignedInteger('generational_score_gained')->default(0)->change();
        });
    }
};
