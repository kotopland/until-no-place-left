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
            self::Uncommitted => 'bg-slate-800 text-slate-300 border-slate-700',
            self::CountingCost => 'bg-amber-950/60 text-amber-300 border-amber-700',
            self::Committed => 'bg-cyan-950/60 text-cyan-300 border-cyan-700',
            self::Baptized => 'bg-emerald-950/60 text-emerald-300 border-emerald-700',
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
