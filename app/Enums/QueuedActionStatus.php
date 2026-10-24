<?php

namespace App\Enums;

enum QueuedActionStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Cancelled = 'cancelled';

    /**
     * Human-friendly label
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Queued (Executes at Month End)',
            self::Processed => 'Processed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Badge CSS class
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/50 dark:text-amber-400',
            self::Processed => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-400',
            self::Cancelled => 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-400',
        };
    }
}
