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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_game_session_id')->nullable()->after('remember_token')->constrained('game_sessions')->nullOnDelete();
            $table->unsignedInteger('action_points')->default(12)->after('current_game_session_id');
            $table->unsignedInteger('max_action_points')->default(12)->after('action_points');
            $table->unsignedInteger('total_generational_score')->default(0)->after('max_action_points');
            $table->json('badges')->nullable()->after('total_generational_score');
            $table->boolean('notify_email')->default(true)->after('badges');
            $table->boolean('notify_in_app')->default(true)->after('notify_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_game_session_id']);
            $table->dropColumn([
                'current_game_session_id',
                'action_points',
                'max_action_points',
                'total_generational_score',
                'badges',
                'notify_email',
                'notify_in_app',
            ]);
        });
    }
};
