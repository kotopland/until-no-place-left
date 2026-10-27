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
        Schema::create('movement_celebrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
            $table->foreignId('npc_id')->nullable()->constrained('npcs')->nullOnDelete();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('type'); // 'salvation', 'baptism', 'church_planted', 'group_formed'
            $table->string('title');
            $table->string('title_ja')->nullable();
            $table->string('headline')->nullable();
            $table->string('person_name');
            $table->string('person_role')->nullable();
            $table->string('discipler_name')->nullable();
            $table->string('ward_name');
            $table->text('details');
            $table->string('scripture_ref')->nullable();
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedInteger('game_month')->default(1);
            $table->unsignedInteger('game_week')->default(1);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_celebrations');
    }
};
