<?php

namespace App\DTOs;

class WeeklyTickResolutionResult
{
    /**
     * PHP 8.4 Asymmetric Visibility protects weekly tick simulation results
     *
     * @param  array<int, ActionResolutionOutcome>  $actionOutcomes
     * @param  array<int, int>  $playerScoreGains
     * @param  array<int>  $onlineUserIds
     */
    public int $gameDay;

    /**
     * PHP 8.4 Asymmetric Visibility protects weekly tick simulation results
     *
     * @param  array<int, ActionResolutionOutcome>  $actionOutcomes
     * @param  array<int, int>  $playerScoreGains
     * @param  array<int>  $onlineUserIds
     */
    public function __construct(
        public private(set) int $gameSessionId,
        public private(set) int $gameMonth,
        public private(set) int $gameWeek,
        public private(set) int $actionsProcessed = 0,
        public private(set) int $onlinePlayersCount = 0,
        public private(set) array $actionOutcomes = [],
        public private(set) array $playerScoreGains = [],
        public private(set) array $onlineUserIds = [],
        ?int $gameDay = null,
    ) {
        $this->gameDay = $gameDay ?? $gameWeek;
    }

    /**
     * PHP 8.4 Property Hook: Formatted weekly tick summary headline
     */
    public string $headline {
        get => "Month {$this->gameMonth}, Week {$this->gameWeek}/4 Tick: {$this->actionsProcessed} Actions Processed | {$this->onlinePlayersCount} Online Players Engaged";
    }
}
