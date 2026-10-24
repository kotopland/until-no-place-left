<?php

namespace App\Enums;

enum LeadershipTier: string
{
    case None = 'none';
    case Apprentice = 'apprentice';
    case Timothy = 'timothy';
    case Elder = 'elder';

    /**
     * Human-friendly leadership tier title
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'Seeker / Disciple',
            self::Apprentice => 'Apprentice (Co-leading 3/3rds)',
            self::Timothy => 'Timothy (Autonomous Sower & Planter)',
            self::Elder => 'Elder (Movement Overseer)',
        };
    }

    /**
     * Multiplication multiplier for autonomous monthly ticks
     */
    public function multiplicationRate(): float
    {
        return match ($this) {
            self::None => 0.0,
            self::Apprentice => 0.35,
            self::Timothy => 0.85,
            self::Elder => 1.50,
        };
    }

    /**
     * Whether this leader can pastor an autonomous church circle
     */
    public function canLeadChurch(): bool
    {
        return in_array($this, [self::Timothy, self::Elder], true);
    }

    /**
     * Tailwind CSS badge styling classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::None => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300',
            self::Apprentice => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/60 dark:text-blue-300',
            self::Timothy => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/60 dark:text-purple-300',
            self::Elder => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300',
        };
    }
}
