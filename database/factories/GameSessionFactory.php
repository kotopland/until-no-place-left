<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Nagoya Movement '.fake()->city(),
            'code' => 'session-'.Str::lower(fake()->unique()->lexify('??????')),
            'current_game_month' => 1,
            'current_game_day' => 1,
            'days_processed_this_month' => 0,
            'calendar_date' => '2026-04-01',
            'month_duration_minutes' => 720,
            'next_tick_at' => now()->addMinutes(10),
            'is_active' => true,
            'settings' => [
                'city' => 'Nagoya',
                'target_generations' => 4,
                'ap_per_month' => 12,
            ],
        ];
    }
}
