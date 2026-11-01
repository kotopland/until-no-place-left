<?php

namespace App\Enums;

enum BaptismStatus: string
{
    case Uncommitted = 'uncommitted';
    case CountingCost = 'counting_cost';
    case Committed = 'committed';
    case Baptized = 'baptized';

    /**
     * English description of baptism status
     */
    public function label(): string
    {
        return match ($this) {
            self::Uncommitted => 'Not Yet Considered',
            self::CountingCost => 'Counting Cost & Family Considerations',
            self::Committed => 'Committed to Baptism (Date Set)',
            self::Baptized => 'Baptized in Water',
        };
    }

    /**
     * Japanese label
     */
    public function labelJa(): string
    {
        return match ($this) {
            self::Uncommitted => '未決断',
            self::CountingCost => '思案中（家族への配慮・心の葛藤）',
            self::Committed => '受洗を決意（日程決定）',
            self::Baptized => '洗礼受領済み',
        };
    }

    /**
     * Visual icon
     */
    public function icon(): string
    {
        return match ($this) {
            self::Uncommitted => '⏳',
            self::CountingCost => '💭',
            self::Committed => '🌊',
            self::Baptized => '🕊️',
        };
    }

    /**
     * Tailwind CSS badge styling classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Uncommitted => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            self::CountingCost => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700',
            self::Committed => 'bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-700',
            self::Baptized => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700',
        };
    }

    /**
     * Whether this status represents a believer who has completed water baptism
     */
    public function isBaptized(): bool
    {
        return $this === self::Baptized;
    }
}
