<?php

declare(strict_types=1);

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
        Schema::create('prayer_walks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('game_month');
            $table->unsignedInteger('game_day');
            $table->string('route_name');
            $table->json('path_coords');
            $table->boolean('pop_discovered')->default(false);
            $table->foreignId('discovered_npc_id')->nullable()->references('id')->on('npcs')->nullOnDelete();
            $table->timestamps();

            $table->index(['game_session_id', 'ward_id']);
            $table->index(['game_session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_walks');
    }
};
