<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'current_game_month' => 1,
            'current_game_day' => 1,
            'days_processed_this_month' => 0,
            'screening_passed' => true,
            'screening_score' => 85,
            'screening_profile_title' => 'Cross-Cultural Ambassador',
        ];
    }

    /**
     * Indicate that the user has not completed or passed the missionary candidate screening.
     */
    public function unscreened(): static
    {
        return $this->state(fn (array $attributes) => [
            'screening_passed' => false,
            'screening_completed_at' => null,
            'screening_score' => null,
            'screening_dimension_scores' => null,
            'screening_profile_title' => null,
            'screening_answers' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
