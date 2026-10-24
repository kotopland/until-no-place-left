<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyReport>
 */
class MonthlyReportFactory extends Factory
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
            'game_month' => 1,
            'summary_title' => 'Month 1 Movement Executive Briefing',
            'highlights_json' => ['Sowed gospel in Meito-ku', 'Began 411 discipleship'],
            'answered_prayers_json' => ['Prayed with salaryman', 'Family opened home'],
            'disciples_multiplied_count' => 0,
            'churches_planted_count' => 0,
            'generational_score_gained' => 10,
            'total_generational_score' => 10,
            'coach_tips_json' => ['Look for Luke 10 Persons of Peace'],
            'read_at' => null,
        ];
    }
}
