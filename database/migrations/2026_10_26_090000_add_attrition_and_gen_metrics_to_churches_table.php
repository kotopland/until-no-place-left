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
            $table->unsignedInteger('attenders_count')->default(6)->after('health_score');
            $table->unsignedInteger('believers_count')->default(4)->after('attenders_count');
            $table->unsignedInteger('baptized_count')->default(3)->after('believers_count');
            $table->boolean('is_dissolved')->default(false)->after('baptized_count');
            $table->unsignedInteger('dissolved_game_month')->nullable()->after('is_dissolved');
            $table->text('dissolution_reason')->nullable()->after('dissolved_game_month');
            $table->string('started_by_name')->nullable()->after('dissolution_reason');
            $table->unsignedInteger('last_attrition_month')->nullable()->after('started_by_name');
            $table->text('last_attrition_event')->nullable()->after('last_attrition_month');

            $table->index(['game_session_id', 'is_dissolved']);
        });

        Schema::table('npcs', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->after('ward_id')->references('id')->on('churches')->nullOnDelete();
            $table->index(['game_session_id', 'church_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
            $table->dropColumn('church_id');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->dropIndex(['game_session_id', 'is_dissolved']);
            $table->dropColumn([
                'attenders_count',
                'believers_count',
                'baptized_count',
                'is_dissolved',
                'dissolved_game_month',
                'dissolution_reason',
                'started_by_name',
                'last_attrition_month',
                'last_attrition_event',
            ]);
        });
    }
};
