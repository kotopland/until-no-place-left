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
        Schema::create('oikos_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npc_id')->constrained('npcs')->cascadeOnDelete();
            $table->foreignId('related_npc_id')->constrained('npcs')->cascadeOnDelete();
            $table->string('relationship_type');
            $table->unsignedTinyInteger('trust_level')->default(50); // 1-100
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'npc_id', 'related_npc_id'], 'oikos_network_pair_unique');
            $table->index('npc_id');
            $table->index('related_npc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oikos_relationships');
    }
};
