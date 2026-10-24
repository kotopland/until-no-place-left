<?php

namespace App\Enums;

enum RelationshipType: string
{
    case Family = 'family';
    case Friend = 'friend';
    case Coworker = 'coworker';
    case Neighbor = 'neighbor';

    /**
     * English display name
     */
    public function label(): string
    {
        return match ($this) {
            self::Family => 'Family (家族 - Kazoku)',
            self::Friend => 'Friend (友人 - Yūjin)',
            self::Coworker => 'Coworker (同僚 - Dōryō)',
            self::Neighbor => 'Neighbor (近所 - Kinjo)',
        };
    }

    /**
     * Japanese display name
     */
    public function labelJa(): string
    {
        return match ($this) {
            self::Family => '家族',
            self::Friend => '友人',
            self::Coworker => '同僚',
            self::Neighbor => '近所',
        };
    }

    /**
     * Baseline relational trust before engagement (1-100)
     */
    public function defaultTrust(): int
    {
        return match ($this) {
            self::Family => 65,
            self::Friend => 50,
            self::Coworker => 35,
            self::Neighbor => 25,
        };
    }

    /**
     * Weighting for ripple effect when primary node accepts Christ or experiences answered prayer
     */
    public function viralInfluenceFactor(): float
    {
        return match ($this) {
            self::Family => 1.75,
            self::Friend => 1.40,
            self::Coworker => 1.15,
            self::Neighbor => 1.05,
        };
    }
}
