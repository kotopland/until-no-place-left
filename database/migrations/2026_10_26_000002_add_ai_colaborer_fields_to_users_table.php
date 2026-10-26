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
            $table->boolean('is_ai_colaborer')->default(false)->after('home_ward_id');
            $table->string('colaborer_archetype')->nullable()->after('is_ai_colaborer');
            $table->text('ai_strategy_notes')->nullable()->after('colaborer_archetype');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_ai_colaborer',
                'colaborer_archetype',
                'ai_strategy_notes',
            ]);
        });
    }
};
