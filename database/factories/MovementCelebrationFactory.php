<?php

namespace Database\Factories;

use App\Models\MovementCelebration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovementCelebration>
 */
class MovementCelebrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => 1,
            'ward_id' => 1,
            'type' => 'salvation',
            'title' => '🎉 New Believer Saved in Nagoya!',
            'title_ja' => '救いと新生',
            'headline' => 'Kenji Takahashi accepted Christ!',
            'person_name' => fake()->name(),
            'person_role' => 'Salaried Worker',
            'discipler_name' => 'Missionary',
            'ward_name' => 'Chikusa-ku',
            'details' => 'Prayed the prayer of salvation and repented with tears.',
            'scripture_ref' => 'Luke 15:7',
            'generation' => 1,
            'game_month' => 1,
            'game_week' => 1,
            'is_dismissed' => false,
        ];
    }
}
