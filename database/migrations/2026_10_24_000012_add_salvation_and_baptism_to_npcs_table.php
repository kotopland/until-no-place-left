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
        Schema::table('npcs', function (Blueprint $table) {
            $table->boolean('prayed_salvation')->default(false)->after('status');
            $table->string('baptism_status')->default('uncommitted')->after('is_baptized');
            $table->unsignedTinyInteger('baptism_cost_months')->default(0)->after('baptism_status');
            $table->timestamp('prayed_salvation_at')->nullable()->after('baptism_cost_months');
            $table->timestamp('baptized_at')->nullable()->after('prayed_salvation_at');

            $table->index(['game_session_id', 'baptism_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropIndex(['game_session_id', 'baptism_status']);
            $table->dropColumn([
                'prayed_salvation',
                'baptism_status',
                'baptism_cost_months',
                'prayed_salvation_at',
                'baptized_at',
            ]);
        });
    }
};
