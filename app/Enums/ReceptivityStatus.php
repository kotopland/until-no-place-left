<?php

namespace App\Enums;

enum ReceptivityStatus: string
{
    case Unreached = 'unreached';
    case Red = 'red';
    case Yellow = 'yellow';
    case Green = 'green';

    /**
     * Human-friendly label with descriptive status
     */
    public function label(): string
    {
        return match ($this) {
            self::Unreached => 'Unreached / Not Engaged',
            self::Red => 'Red: Closed / Needs Relational Care',
            self::Yellow => 'Yellow: Curious / Person of Peace Candidate',
            self::Green => 'Green: Believer / Ready for Discipleship',
        };
    }

    /**
     * Color representation (Hex)
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::Unreached => '#94a3b8', // slate-400
            self::Red => '#ef4444',       // red-500
            self::Yellow => '#eab308',    // yellow-500
            self::Green => '#22c55e',     // green-500
        };
    }

    /**
     * Tailwind CSS badge styling classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Unreached => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            self::Red => 'bg-red-100 text-red-700 border-red-300 dark:bg-red-950/60 dark:text-red-400 dark:border-red-800',
            self::Yellow => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-400 dark:border-amber-800',
            self::Green => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800',
        };
    }

    /**
     * Unicode visual indicator
     */
    public function icon(): string
    {
        return match ($this) {
            self::Unreached => '⚪',
            self::Red => '🔴',
            self::Yellow => '🟡',
            self::Green => '🟢',
        };
    }

    /**
     * Whether this status represents an active believer
     */
    public function isBeliever(): bool
    {
        return $this === self::Green;
    }

    /**
     * Whether this status is open to spiritual conversation
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Yellow, self::Green], true);
    }
}
