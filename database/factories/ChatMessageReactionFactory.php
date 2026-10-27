<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessageReaction>
 */
class ChatMessageReactionFactory extends Factory
{
    protected $model = ChatMessageReaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'user_id' => User::factory(),
            'reaction' => fake()->randomElement(['amen', 'praise', 'fire', 'handshake', 'idea']),
        ];
    }
}
