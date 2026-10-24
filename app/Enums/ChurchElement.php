<?php

namespace App\Enums;

enum ChurchElement: string
{
    case RepentanceBelief = 'repentance_belief';
    case Baptism = 'baptism';
    case HolySpirit = 'holy_spirit';
    case Word = 'word';
    case Communion = 'communion';
    case Prayer = 'prayer';
    case Fellowship = 'fellowship';
    case GivingMercy = 'giving_mercy';
    case Worship = 'worship';
    case LocalLeaders = 'local_leaders';
    case MakingDisciples = 'making_disciples';

    /**
     * English display name
     */
    public function label(): string
    {
        return match ($this) {
            self::RepentanceBelief => 'Repentance & Belief',
            self::Baptism => 'Baptism',
            self::HolySpirit => 'Holy Spirit',
            self::Word => 'The Word (Sword Study / Bible)',
            self::Communion => 'Communion (Lord\'s Supper)',
            self::Prayer => 'Prayer & Intercession',
            self::Fellowship => 'Fellowship & Mutual Care',
            self::GivingMercy => 'Giving & Mercy to the Needy',
            self::Worship => 'Worship & Praise',
            self::LocalLeaders => 'Local Leaders Appointed',
            self::MakingDisciples => 'Multiplying Disciples & Groups',
        };
    }

    /**
     * Acts 2 scripture reference
     */
    public function scriptureRef(): string
    {
        return match ($this) {
            self::RepentanceBelief => 'Acts 2:38, 41',
            self::Baptism => 'Acts 2:38, 41',
            self::HolySpirit => 'Acts 2:38',
            self::Word => 'Acts 2:42',
            self::Communion => 'Acts 2:42, 46',
            self::Prayer => 'Acts 2:42',
            self::Fellowship => 'Acts 2:42, 44',
            self::GivingMercy => 'Acts 2:44-45',
            self::Worship => 'Acts 2:47',
            self::LocalLeaders => 'Acts 14:23 / Titus 1:5',
            self::MakingDisciples => 'Acts 2:47 / Matt 28:19',
        };
    }

    /**
     * SVG icon or symbol identifier
     */
    public function symbol(): string
    {
        return match ($this) {
            self::RepentanceBelief => '⚓',
            self::Baptism => '💧',
            self::HolySpirit => '🕊️',
            self::Word => '📖',
            self::Communion => '🍷',
            self::Prayer => '🙏',
            self::Fellowship => '🤝',
            self::GivingMercy => '🤲',
            self::Worship => '🎵',
            self::LocalLeaders => '👤',
            self::MakingDisciples => '🌱',
        };
    }
}
