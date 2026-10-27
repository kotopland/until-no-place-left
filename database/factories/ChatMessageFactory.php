<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'user_id' => User::factory(),
            'channel' => 'citywide',
            'ward_id' => null,
            'message' => fake()->sentence(8),
            'game_month' => 1,
            'game_week' => 1,
            'is_pinned' => false,
            'is_system' => false,
            'system_event_type' => null,
            'metadata' => null,
        ];
    }

    public function prayer(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'prayer',
            'message' => 'Please intercede for our oikos outreach near the station. Asking the Lord of the Harvest for open hearts.',
        ]);
    }

    public function ward(int $wardId): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'ward',
            'ward_id' => $wardId,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'channel' => 'announcements',
            'system_event_type' => 'church_planted',
        ]);
    }
}
