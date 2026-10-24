<?php

namespace App\Enums;

enum ContributionRole: string
{
    case PioneerSower = 'pioneer_sower';
    case Waterer = 'waterer';
    case Harvester = 'harvester';
    case Discipler = 'discipler';

    /**
     * Display title for the cooperative ministry role
     */
    public function label(): string
    {
        return match ($this) {
            self::PioneerSower => 'Pioneer Sower (First Contact / Bridge)',
            self::Waterer => 'Relational Waterer (Prayer, Care & Listening)',
            self::Harvester => 'Harvester (Gospel Call & Decision)',
            self::Discipler => 'Discipler (411 Equipping & Multiplication)',
        };
    }

    /**
     * Biblical motif from 1 Corinthians 3:6-8
     */
    public function scriptureMotif(): string
    {
        return match ($this) {
            self::PioneerSower => 'Paul Planted (1 Cor 3:6)',
            self::Waterer => 'Apollos Watered (1 Cor 3:6)',
            self::Harvester => 'One Gathers Fruit (John 4:36)',
            self::Discipler => 'Entrust to Faithful People (2 Tim 2:2)',
        };
    }

    /**
     * Icon representation
     */
    public function icon(): string
    {
        return match ($this) {
            self::PioneerSower => '🌱',
            self::Waterer => '💧',
            self::Harvester => '🌾',
            self::Discipler => '👑',
        };
    }

    /**
     * Badge CSS classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PioneerSower => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-300',
            self::Waterer => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/50 dark:text-blue-300',
            self::Harvester => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/50 dark:text-amber-300',
            self::Discipler => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/50 dark:text-purple-300',
        };
    }
}
