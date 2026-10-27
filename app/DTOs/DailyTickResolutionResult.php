<?php

namespace App\DTOs;

class DailyTickResolutionResult extends WeeklyTickResolutionResult
{
    /**
     * @param  array<int, ActionResolutionOutcome>  $actionOutcomes
     * @param  array<int, int>  $playerScoreGains
     * @param  array<int>  $onlineUserIds
     */
    public function __construct(
        int $gameSessionId,
        int $gameMonth,
        int $gameDay,
        int $actionsProcessed = 0,
        int $onlinePlayersCount = 0,
        array $actionOutcomes = [],
        array $playerScoreGains = [],
        array $onlineUserIds = [],
    ) {
        parent::__construct(
            gameSessionId: $gameSessionId,
            gameMonth: $gameMonth,
            gameWeek: $gameDay,
            actionsProcessed: $actionsProcessed,
            onlinePlayersCount: $onlinePlayersCount,
            actionOutcomes: $actionOutcomes,
            playerScoreGains: $playerScoreGains,
            onlineUserIds: $onlineUserIds,
            gameDay: $gameDay,
        );
    }
}
