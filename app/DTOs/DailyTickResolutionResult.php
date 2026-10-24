<?php

namespace App\DTOs;

class DailyTickResolutionResult
{
    /**
     * PHP 8.4 Asymmetric Visibility protects daily tick simulation results
     *
     * @param  array<int, ActionResolutionOutcome>  $actionOutcomes
     * @param  array<int, int>  $playerScoreGains
     * @param  array<int>  $onlineUserIds
     */
    public function __construct(
        public private(set) int $gameSessionId,
        public private(set) int $gameMonth,
        public private(set) int $gameDay,
        public private(set) int $actionsProcessed = 0,
        public private(set) int $onlinePlayersCount = 0,
        public private(set) array $actionOutcomes = [],
        public private(set) array $playerScoreGains = [],
        public private(set) array $onlineUserIds = [],
    ) {}

    /**
     * PHP 8.4 Property Hook: Formatted daily tick summary headline
     */
    public string $headline {
        get => "Month {$this->gameMonth}, Day {$this->gameDay}/30 Tick: {$this->actionsProcessed} Actions Processed | {$this->onlinePlayersCount} Online Players Engaged";
    }
}
