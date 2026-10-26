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
            $table->string('gender')->default('male')->after('name_ja');
            $table->unsignedTinyInteger('age')->default(30)->after('gender');
            $table->string('nationality')->default('Japanese')->after('age');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable()->default('male')->after('name');
            $table->unsignedTinyInteger('age')->nullable()->default(30)->after('gender');
            $table->string('nationality')->nullable()->default('Japanese')->after('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn(['gender', 'age', 'nationality']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'age', 'nationality']);
        });
    }
};
