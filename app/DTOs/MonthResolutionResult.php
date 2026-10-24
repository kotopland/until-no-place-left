<?php

namespace App\DTOs;

class MonthResolutionResult
{
    /**
     * PHP 8.4 Asymmetric Visibility protects batch simulation results
     */
    public function __construct(
        public private(set) int $gameSessionId,
        public private(set) int $gameMonth,
        public private(set) int $actionsProcessed = 0,
        public private(set) int $breakthroughsCount = 0,
        public private(set) int $newBelieversCount = 0,
        public private(set) int $baptismsCount = 0,
        public private(set) int $trained411Count = 0,
        public private(set) int $autonomousMultiplications = 0,
        public private(set) int $churchesPlanted = 0,
        public private(set) int $prayersAnswered = 0,
        public private(set) int $coopCollaborationsCount = 0,
        public private(set) array $actionOutcomes = [],
        public private(set) array $playerScoreGains = [],
        public private(set) array $autonomousHighlights = [],
        public private(set) array $coachTips = [],
    ) {}

    /**
     * PHP 8.4 Property Hook: Formatted monthly executive headline
     */
    public string $headline {
        get => "Month {$this->gameMonth} Resolution: {$this->actionsProcessed} Actions Processed | {$this->newBelieversCount} New Believers | {$this->autonomousMultiplications} Autonomous Multiplications | {$this->churchesPlanted} Churches Planted";
    }

    /**
     * PHP 8.4 Property Hook: Whether church multiplication occurred in this month
     */
    public bool $hadChurchMultiplication {
        get => $this->churchesPlanted > 0 || $this->autonomousMultiplications > 0;
    }
}
