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
        Schema::table('churches', function (Blueprint $table) {
            $table->unsignedSmallInteger('last_coached_game_month')->nullable()->after('last_attrition_event');
            $table->unsignedSmallInteger('last_coached_game_day')->nullable()->after('last_coached_game_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['last_coached_game_month', 'last_coached_game_day']);
        });
    }
};
