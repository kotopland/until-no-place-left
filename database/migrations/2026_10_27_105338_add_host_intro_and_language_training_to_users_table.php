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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('has_seen_host_intro')->default(false)->after('screening_answers');
            $table->boolean('committed_to_language_training')->default(false)->after('has_seen_host_intro');
            $table->unsignedInteger('language_training_enrolled_month')->nullable()->after('committed_to_language_training');
            $table->boolean('language_training_completed')->default(false)->after('language_training_enrolled_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'has_seen_host_intro',
                'committed_to_language_training',
                'language_training_enrolled_month',
                'language_training_completed',
            ]);
        });
    }
};
