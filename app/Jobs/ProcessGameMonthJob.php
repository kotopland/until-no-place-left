<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\FourFieldsEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithQueue;
use Illuminate\Support\Facades\Log;

#[WithQueue('game-engine')]
class ProcessGameMonthJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public GameSession $session
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FourFieldsEngine $engine): void
    {
        Log::info("Resolving Game Month for Session [{$this->session->code}], Month {$this->session->current_game_month}");

        $result = $engine->resolveGameMonth($this->session);

        // Record MonthlyReport for each participating player
        $players = User::where('current_game_session_id', $this->session->id)->get();

        foreach ($players as $player) {
            $playerOutcomes = array_filter(
                $result->actionOutcomes,
                fn ($out) => $out->userId === $player->id
            );

            $highlights = [];
            foreach ($playerOutcomes as $out) {
                $highlights[] = $out->narrativeOutcome;
            }

            // Include autonomous movement highlights
            foreach ($result->autonomousHighlights as $highlight) {
                $highlights[] = $highlight;
            }

            $playerTips = array_filter(
                $result->coachTips,
                fn ($tip) => $tip['user_id'] === $player->id
            );

            MonthlyReport::create([
                'game_session_id' => $this->session->id,
                'user_id' => $player->id,
                'game_month' => $result->gameMonth,
                'summary_title' => "Nagoya Movement Digest — Month {$result->gameMonth}",
                'highlights_json' => $highlights,
                'answered_prayers_json' => [
                    'count' => $result->prayersAnswered,
                    'notes' => $result->prayersAnswered > 0 ? "God moved in power through intercession this month!" : "Continue faithful prayer walking and intercession.",
                ],
                'disciples_multiplied_count' => $result->autonomousMultiplications,
                'churches_planted_count' => $result->churchesPlanted,
                'generational_score_gained' => $result->playerScoreGains[$player->id] ?? 0,
                'total_generational_score' => $player->total_generational_score,
                'coach_tips_json' => array_values($playerTips),
                'read_at' => null,
            ]);
        }

        Log::info("Completed Game Month for Session [{$this->session->code}]: {$result->headline}");
    }
}
