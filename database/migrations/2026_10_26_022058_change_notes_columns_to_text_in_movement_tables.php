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
        Schema::table('npc_contributions', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });

        Schema::table('oikos_relationships', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npc_contributions', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
        });

        Schema::table('queued_monthly_actions', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
        });

        Schema::table('oikos_relationships', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
        });
    }
};
