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
        Schema::table('npcs', function (Blueprint $table) {
            $table->unsignedTinyInteger('seeking_months')->default(0)->after('trust_level');
            $table->unsignedSmallInteger('yellow_since_game_month')->nullable()->after('seeking_months');
            $table->unsignedTinyInteger('target_seeking_months')->default(4)->after('yellow_since_game_month');
            $table->unsignedTinyInteger('target_baptism_months')->default(6)->after('baptism_cost_months');
            $table->unsignedSmallInteger('prayed_salvation_month')->nullable()->after('target_baptism_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn([
                'seeking_months',
                'yellow_since_game_month',
                'target_seeking_months',
                'target_baptism_months',
                'prayed_salvation_month',
            ]);
        });
    }
};
