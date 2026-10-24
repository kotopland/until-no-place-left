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
        Schema::create('queued_monthly_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->foreignId('target_npc_id')->nullable()->constrained('npcs')->nullOnDelete();
            $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('game_month');
            $table->unsignedTinyInteger('ap_cost')->default(1);
            $table->string('notes')->nullable();
            $table->text('outcome_notes')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'user_id', 'game_month']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queued_monthly_actions');
    }
};
