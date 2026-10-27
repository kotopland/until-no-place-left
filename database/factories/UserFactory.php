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
            'gender' => fake()->randomElement(['male', 'female']),
            'age' => fake()->numberBetween(22, 60),
            'nationality' => fake()->randomElement(['Japanese', 'American', 'Brazilian']),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'current_game_month' => 1,
            'current_game_week' => 1,
            'weeks_processed_this_month' => 0,
            'weeks_played_today' => 0,
            'current_game_day' => 1,
            'days_processed_this_month' => 0,
            'weekly_action_points' => 20,
            'daily_action_points' => 20,
            'action_points' => 20,
            'max_action_points' => 26,
            'next_weekly_tick_at' => now()->addMinutes(15),
            'next_daily_tick_at' => now()->addMinutes(15),
            'screening_passed' => true,
            'screening_score' => 85,
            'screening_profile_title' => 'Cross-Cultural Ambassador',
            'has_seen_host_intro' => true,
            'committed_to_language_training' => false,
            'language_training_completed' => true,
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

    public function unintroduced(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_seen_host_intro' => false,
            'committed_to_language_training' => false,
            'language_training_completed' => false,
        ]);
    }

    public function withLanguageBarrierHandicap(): static
    {
        return $this->state(fn (array $attributes) => [
            'committed_to_language_training' => false,
            'language_training_completed' => false,
            'action_points' => 14,
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
