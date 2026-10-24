<?php

namespace App\Enums;

enum NpcNeedState: string
{
    case None = 'none';
    case Lonely = 'lonely';
    case EmotionalCrisis = 'emotional_crisis';
    case SickNeedsHealing = 'sick_needs_healing';
    case HomelessOrMaterialNeed = 'homeless_material_need';
    case SeekingCommunity = 'seeking_community';

    /**
     * English description of the life situation
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'Stable Routine (No Acute Crisis)',
            self::Lonely => 'Deep Loneliness / Social Isolation',
            self::EmotionalCrisis => 'Workplace Burnout / Anxiety / Depression',
            self::SickNeedsHealing => 'Physical Sickness / Chronic Ailment',
            self::HomelessOrMaterialNeed => 'Homeless / Urgent Material Need (Food/Clothing)',
            self::SeekingCommunity => 'Newcomer / Seeking Friends & Community',
        };
    }

    /**
     * Japanese description
     */
    public function labelJa(): string
    {
        return match ($this) {
            self::None => '日常（平穏）',
            self::Lonely => '孤独・孤立',
            self::EmotionalCrisis => '過労・不安・うつ状態',
            self::SickNeedsHealing => '病気・身体の不調',
            self::HomelessOrMaterialNeed => '生活困窮・食料支援が必要',
            self::SeekingCommunity => '居場所・交流を求めている',
        };
    }

    /**
     * Icon representation
     */
    public function icon(): string
    {
        return match ($this) {
            self::None => '🌿',
            self::Lonely => '🌧️',
            self::EmotionalCrisis => '⚡',
            self::SickNeedsHealing => '🩺',
            self::HomelessOrMaterialNeed => '🍲',
            self::SeekingCommunity => '☕',
        };
    }

    /**
     * The most effective ministry response for this specific need
     */
    public function recommendedAction(): MinistryActionType
    {
        return match ($this) {
            self::None => MinistryActionType::CasualTalk,
            self::Lonely => MinistryActionType::CasualTalk,
            self::EmotionalCrisis => MinistryActionType::DeepTalk,
            self::SickNeedsHealing => MinistryActionType::PrayerForHealing,
            self::HomelessOrMaterialNeed => MinistryActionType::MercyCareFoodBible,
            self::SeekingCommunity => MinistryActionType::InternationalEvent,
        };
    }

    /**
     * Severity / urgency rating (1 to 5)
     */
    public function urgency(): int
    {
        return match ($this) {
            self::None => 1,
            self::SeekingCommunity => 2,
            self::Lonely => 3,
            self::EmotionalCrisis => 4,
            self::SickNeedsHealing => 4,
            self::HomelessOrMaterialNeed => 5,
        };
    }

    /**
     * Badge CSS class for the need state
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::None => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400',
            self::Lonely => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400',
            self::EmotionalCrisis => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400',
            self::SickNeedsHealing => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400',
            self::HomelessOrMaterialNeed => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-950/60 dark:text-red-400',
            self::SeekingCommunity => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-400',
        };
    }
}
