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
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('game_month');
            $table->string('summary_title');
            $table->json('highlights_json');
            $table->json('answered_prayers_json');
            $table->unsignedInteger('disciples_multiplied_count')->default(0);
            $table->unsignedInteger('churches_planted_count')->default(0);
            $table->unsignedInteger('generational_score_gained')->default(0);
            $table->unsignedInteger('total_generational_score')->default(0);
            $table->json('coach_tips_json');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'user_id', 'game_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
