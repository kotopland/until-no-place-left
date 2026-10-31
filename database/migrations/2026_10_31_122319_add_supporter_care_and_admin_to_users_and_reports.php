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
            $table->unsignedInteger('last_prayer_letter_game_month')->nullable()->after('last_played_game_month');
            $table->unsignedInteger('consecutive_admin_neglect_months')->default(0)->after('last_prayer_letter_game_month');
            $table->string('active_prayer_focus')->nullable()->after('consecutive_admin_neglect_months');
            $table->unsignedInteger('prayer_shield_expires_at_month')->nullable()->after('active_prayer_focus');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->timestamp('prayer_letter_dispatched_at')->nullable()->after('read_at');
            $table->string('selected_prayer_focus')->nullable()->after('prayer_letter_dispatched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_prayer_letter_game_month',
                'consecutive_admin_neglect_months',
                'active_prayer_focus',
                'prayer_shield_expires_at_month',
            ]);
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn([
                'prayer_letter_dispatched_at',
                'selected_prayer_focus',
            ]);
        });
    }
};
