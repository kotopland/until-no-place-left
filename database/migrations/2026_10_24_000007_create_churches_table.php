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
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('parent_church_id')->nullable()->references('id')->on('churches')->nullOnDelete();
            $table->foreignId('leader_npc_id')->nullable()->references('id')->on('npcs')->nullOnDelete();
            $table->foreignId('leader_user_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->unsignedTinyInteger('generation')->default(1); // G1, G2, G3, G4+
            $table->json('elements_json'); // array of ChurchElement
            $table->boolean('is_healthy_church')->default(false);
            $table->unsignedTinyInteger('health_score')->default(0); // 0-100%
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('founded_game_month')->default(1);
            $table->timestamps();

            $table->index(['game_session_id', 'ward_id']);
            $table->index(['game_session_id', 'generation']);
            $table->index('is_healthy_church');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
