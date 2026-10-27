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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel')->default('citywide'); // 'citywide', 'ward', 'prayer', 'announcements'
            $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->text('message');
            $table->unsignedInteger('game_month')->default(1);
            $table->unsignedInteger('game_week')->default(1);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_system')->default(false);
            $table->string('system_event_type')->nullable(); // 'church_planted', 'baptism', 'salvation', 'milestone', 'prayer_walk'
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'channel', 'created_at']);
            $table->index(['game_session_id', 'ward_id']);
            $table->index(['game_session_id', 'is_pinned']);
        });

        Schema::create('chat_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction'); // 'amen', 'praise', 'fire', 'handshake', 'idea'
            $table->timestamps();

            $table->unique(['chat_message_id', 'user_id', 'reaction']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_read_chat_at')->nullable()->after('home_ward_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_read_chat_at');
        });

        Schema::dropIfExists('chat_message_reactions');
        Schema::dropIfExists('chat_messages');
    }
};
