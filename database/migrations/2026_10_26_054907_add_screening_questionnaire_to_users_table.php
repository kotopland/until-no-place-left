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
            $table->boolean('screening_passed')->default(false)->after('colaborer_archetype');
            $table->timestamp('screening_completed_at')->nullable()->after('screening_passed');
            $table->integer('screening_score')->nullable()->after('screening_completed_at');
            $table->json('screening_dimension_scores')->nullable()->after('screening_score');
            $table->string('screening_profile_title')->nullable()->after('screening_dimension_scores');
            $table->json('screening_answers')->nullable()->after('screening_profile_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'screening_passed',
                'screening_completed_at',
                'screening_score',
                'screening_dimension_scores',
                'screening_profile_title',
                'screening_answers',
            ]);
        });
    }
};
