<?php

namespace App\DTOs;

use App\Enums\ContributionRole;
use App\Enums\MinistryActionType;
use App\Enums\ReceptivityStatus;

class ActionResolutionOutcome
{
    /**
     * PHP 8.4 Asymmetric Visibility protects internal game state mutation
     *
     * @param  array<int, mixed>  $collaboratorsImpacted
     */
    public function __construct(
        public private(set) int $actionId,
        public private(set) MinistryActionType $actionType,
        public private(set) int $userId,
        public private(set) ?int $targetNpcId,
        public private(set) int $wardId,
        public private(set) int $trustChange,
        public private(set) int $newTrustLevel,
        public private(set) ReceptivityStatus $previousStatus,
        public private(set) ReceptivityStatus $newStatus,
        public private(set) bool $statusChanged,
        public private(set) int $pointsAwarded,
        public private(set) int $coopPointsAwarded,
        public private(set) ?ContributionRole $roleAssigned,
        public private(set) array $collaboratorsImpacted,
        public private(set) string $narrativeOutcome,
        public private(set) string $pedagogicalTip,
        public private(set) bool $prayerAnswered = false,
        public private(set) bool $newOikosUnlocked = false,
    ) {}

    /**
     * PHP 8.4 Property Hook: Whether this action unlocked a spiritual harvest or breakthrough
     */
    public bool $isMajorBreakthrough {
        get => $this->statusChanged || $this->prayerAnswered || $this->coopPointsAwarded > 0;
    }

    /**
     * PHP 8.4 Property Hook: Formatted summary string
     */
    public string $summary {
        get {
            $changeStr = ($this->trustChange >= 0 ? '+' : '').$this->trustChange;

            return "{$this->actionType->icon()} {$this->actionType->label()} -> Trust: {$this->newTrustLevel}% ({$changeStr}%) | Status: {$this->newStatus->icon()} {$this->newStatus->label()}";
        }
    }

    /**
     * PHP 8.4 Property Hook: Alias for narrativeOutcome
     */
    public string $outcome_notes {
        get => $this->narrativeOutcome;
    }
}
