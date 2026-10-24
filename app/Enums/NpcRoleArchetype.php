<?php

namespace App\Enums;

enum NpcRoleArchetype: string
{
    case Salaryman = 'salaryman';
    case UniversityStudent = 'university_student';
    case ElderlyResident = 'elderly_resident';
    case InternationalExpat = 'international_expat';
    case Shopkeeper = 'shopkeeper';
    case SingleParent = 'single_parent';
    case HomelessIndividual = 'homeless_individual';
    case ExistingBeliever = 'existing_believer';

    /**
     * English title
     */
    public function label(): string
    {
        return match ($this) {
            self::Salaryman => 'Corporate Salaryman / Office Lady',
            self::UniversityStudent => 'University Student',
            self::ElderlyResident => 'Elderly Neighborhood Resident',
            self::InternationalExpat => 'International Expat / Foreign Worker',
            self::Shopkeeper => 'Local Shopkeeper / Merchant',
            self::SingleParent => 'Single Parent',
            self::HomelessIndividual => 'Homeless / Unhoused Individual',
            self::ExistingBeliever => 'Existing Believer (Untrained / Passive)',
        };
    }

    /**
     * Japanese title
     */
    public function labelJa(): string
    {
        return match ($this) {
            self::Salaryman => '会社員・OL',
            self::UniversityStudent => '大学生',
            self::ElderlyResident => '地域のお年寄り',
            self::InternationalExpat => '留学生・外国人労働者',
            self::Shopkeeper => '商店主・店長',
            self::SingleParent => 'ひとり親',
            self::HomelessIndividual => 'ホームレス・生活困窮者',
            self::ExistingBeliever => '既信者（受動的・未訓練）',
        };
    }

    /**
     * Baseline trust level with strangers (1-100)
     */
    public function baselineTrust(): int
    {
        return match ($this) {
            self::Salaryman => 15,
            self::UniversityStudent => 30,
            self::ElderlyResident => 25,
            self::InternationalExpat => 40,
            self::Shopkeeper => 35,
            self::SingleParent => 20,
            self::HomelessIndividual => 15,
            self::ExistingBeliever => 50,
        };
    }

    /**
     * Default need state often experienced by this archetype
     */
    public function defaultNeed(): NpcNeedState
    {
        return match ($this) {
            self::Salaryman => NpcNeedState::EmotionalCrisis,
            self::UniversityStudent => NpcNeedState::SeekingCommunity,
            self::ElderlyResident => NpcNeedState::Lonely,
            self::InternationalExpat => NpcNeedState::SeekingCommunity,
            self::Shopkeeper => NpcNeedState::None,
            self::SingleParent => NpcNeedState::EmotionalCrisis,
            self::HomelessIndividual => NpcNeedState::HomelessOrMaterialNeed,
            self::ExistingBeliever => NpcNeedState::None,
        };
    }
}
