<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\PrayerWalk;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrayerWalk>
 */
class PrayerWalkFactory extends Factory
{
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
            'ward_id' => Ward::first()?->id ?? 1,
            'game_month' => 1,
            'game_day' => 1,
            'route_name' => 'Sakae & Hisaya Odori Central Avenue',
            'path_coords' => [
                [35.1700, 136.9050],
                [35.1730, 136.9050],
                [35.1730, 136.9080],
                [35.1700, 136.9080],
            ],
            'pop_discovered' => false,
            'discovered_npc_id' => null,
        ];
    }
}
