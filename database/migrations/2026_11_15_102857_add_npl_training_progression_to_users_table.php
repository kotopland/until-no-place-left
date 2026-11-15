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
            $table->unsignedTinyInteger('training_level')->default(0)->after('language_training_completed');
            $table->unsignedInteger('level_1_completed_month')->nullable()->after('training_level');
            $table->unsignedInteger('level_2_completed_month')->nullable()->after('level_1_completed_month');
            $table->unsignedInteger('level_3_completed_month')->nullable()->after('level_2_completed_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'training_level',
                'level_1_completed_month',
                'level_2_completed_month',
                'level_3_completed_month',
            ]);
        });
    }
};
