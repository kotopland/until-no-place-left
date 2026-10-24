<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks the 1 Corinthians 3:6-8 "Paul planted, Apollos watered, God gave growth"
     * collaborative chain of ministry across multiplayer participants.
     */
    public function up(): void
    {
        Schema::create('npc_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npc_id')->constrained('npcs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type'); // MinistryActionType
            $table->string('contribution_role')->default('waterer'); // pioneer_sower, waterer, harvester, discipler
            $table->unsignedInteger('game_month');
            $table->unsignedTinyInteger('trust_gained')->default(0);
            $table->unsignedInteger('points_awarded')->default(0);
            $table->boolean('triggered_breakthrough')->default(false); // e.g. Red->Yellow, Yellow->Green, Baptism
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'npc_id']);
            $table->index(['game_session_id', 'user_id']);
            $table->index(['npc_id', 'user_id']);
        });

        // Also add collaborative_points column to users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('coop_synergy_score')->default(0)->after('total_generational_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('npc_contributions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('coop_synergy_score');
        });
    }
};
