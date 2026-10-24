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
        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_ja');
            $table->string('role_archetype')->default('salaryman');
            $table->string('need_state')->default('none');
            $table->string('status')->default('unreached');
            $table->unsignedTinyInteger('trust_level')->default(20); // 1-100
            $table->boolean('is_person_of_peace')->default(false);
            $table->boolean('is_baptized')->default(false);
            $table->boolean('trained_411')->default(false);
            $table->string('leadership_tier')->default('none');
            $table->unsignedTinyInteger('generation')->default(0);
            $table->foreignId('discipler_npc_id')->nullable()->references('id')->on('npcs')->nullOnDelete();
            $table->foreignId('discipler_user_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->text('backstory')->nullable();
            $table->json('spiritual_journal')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'ward_id']);
            $table->index(['game_session_id', 'status']);
            $table->index(['game_session_id', 'leadership_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('npcs');
    }
};
