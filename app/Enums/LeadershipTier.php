<?php

namespace App\Enums;

enum LeadershipTier: string
{
    // The 5 Levels of Leadership Training and Coaching Toolbox
    case None = 'none';
    case L1_SeedSower = 'l1_seed_sower';
    case L2_ChurchPlanter = 'l2_church_planter';
    case L3_Multiplier = 'l3_multiplier';
    case L4_MovementTrainer = 'l4_movement_trainer';
    case L5_StrategyCoordinator = 'l5_strategy_coordinator';

    // Legacy cases preserved for database backward-compatibility
    case Apprentice = 'apprentice';
    case Timothy = 'timothy';
    case Elder = 'elder';

    /**
     * Integer numeric leadership level (0 to 5)
     */
    public function level(): int
    {
        return match ($this) {
            self::None => 0,
            self::L1_SeedSower, self::Apprentice => 1,
            self::L2_ChurchPlanter, self::Timothy => 2,
            self::L3_Multiplier => 3,
            self::L4_MovementTrainer, self::Elder => 4,
            self::L5_StrategyCoordinator => 5,
        };
    }

    /**
     * Short level code (e.g. L1, L2, L3...)
     */
    public function levelCode(): string
    {
        return 'L'.$this->level();
    }

    /**
     * Official role title from the 5 Levels Matrix
     */
    public function roleTitle(): string
    {
        return match ($this->level()) {
            0 => 'Contact / Seeker',
            1 => 'Seed Sower',
            2 => 'Church Planter (CP)',
            3 => 'Church Plant Multiplier (CPMer)',
            4 => 'Movement Trainer (MT)',
            5 => 'Strategy Coordinator (SC)',
            default => 'Leader',
        };
    }

    /**
     * Japanese role title
     */
    public function roleTitleJa(): string
    {
        return match ($this->level()) {
            0 => '求道者・コンタクト',
            1 => '種蒔く人（パーソナル伝道者）',
            2 => '教会開拓者（チャーチプランター）',
            3 => '乗数開拓者（マルチプライヤー）',
            4 => 'ムーブメント・トレーナー',
            5 => '戦略コーディネーター',
            default => 'リーダー',
        };
    }

    /**
     * Full human-friendly display label with level prefix
     */
    public function label(): string
    {
        return match ($this->level()) {
            0 => 'Seeker / Contact',
            1 => 'L1: Seed Sower (Personal Evangelism & 411)',
            2 => 'L2: Church Planter (Abiding & Church Circles)',
            3 => 'L3: Church Plant Multiplier (Generational Multiplication)',
            4 => 'L4: Movement Trainer (Training Teams & Swarms)',
            5 => 'L5: Strategy Coordinator (Unreached Regional Mobilization)',
            default => 'Disciple',
        };
    }

    /**
     * Core Focus from the Leadership Roles Matrix
     */
    public function coreFocus(): string
    {
        return match ($this->level()) {
            0 => 'Exploring spiritual truth and discovering the Gospel of Jesus Christ.',
            1 => 'Personal evangelism, entry, and basic discipleship tools.',
            2 => 'Abiding in Christ, planting churches, and local discipleship.',
            3 => 'Generational multiplication, releasing authority, and leadership coaching.',
            4 => 'Training teams, regional strategy, and movement troubleshooting.',
            5 => 'Unreached peoples, regional/global mobilization, and high-level catalyzing.',
            default => 'Spiritual growth.',
        };
    }

    /**
     * Core Toolset from Section 2
     */
    public function coreToolset(): string
    {
        return match ($this->level()) {
            0 => 'None',
            1 => '411 Discipleship',
            2 => '4 Fields Practitioner 1-Page Toolset',
            3 => '5 Levels of Leadership',
            4 => '4 Fields Manual',
            5 => 'Foundations & Confessions',
            default => 'None',
        };
    }

    /**
     * Studies & Mapping Framework
     */
    public function studiesAndMapping(): string
    {
        return match ($this->level()) {
            0 => 'None',
            1 => 'John 17:20 Oikos Relational Mapping',
            2 => 'Acts 2 Church Circles',
            3 => '4 Fields Luke/Acts Study & Generational Mapping',
            4 => "Paul's Journeys Study & Baptism Hammer",
            5 => 'Acts-Epistles Timeline & 3-Legged Stool',
            default => 'None',
        };
    }

    /**
     * Heart & Spiritual Formation biblical foundations
     *
     * @return array<int, string>
     */
    public function spiritualFoundations(): array
    {
        return match ($this->level()) {
            0 => ['Curiosity', 'Need Encounter', 'Spiritual Openness'],
            1 => ['Matt 28:18–20 (Hand Motions)', 'John 4: Woman at the Well', 'Identity in Christ (2 Cor 5:17–20)', 'Love for the Lost', 'Faithfulness'],
            2 => ['Abiding in Christ (Holy Spirit, Prayer, Word)', 'Character: Titus 1 & 1 Timothy 3'],
            3 => ["Father's Heart", 'Multiplying Churches in Acts', 'Release Authority (4th Gen Vision)', '2 Tim 2:1–7: Faithfulness', 'Models Prayer and Fasting'],
            4 => ["God's Mission: Genesis to Revelation", '5 Training Team Principles', 'Vision Beyond Own Network', 'Flexibility / Modality', 'Coordinates Prayer and Fasting'],
            5 => ['NoPlaceLeft Vision (Romans 15)', 'Die to Self', 'Holy Discontent', 'Concern for the Churches', 'Nehemiah Vision, Prayer & Fasting', 'One-Anothering Study'],
            default => [],
        };
    }

    /**
     * Leadership skills progression (L1+ -> L2+ -> L3+ -> L4+ -> L5+)
     *
     * @return array<int, string>
     */
    public function skillsProgression(): array
    {
        return match ($this->level()) {
            0 => ['Listening', 'Seeking'],
            1 => ['Prayer', 'Active Gospelling'],
            2 => ['Vision Casting for Church Identity', 'Practicing 4 Fields Locally', 'Practicing Ordinances', 'Teaching', 'Appointing Elders'],
            3 => ['Vision Casting for Multiplication', 'Coaching 4 Fields Locally', 'Releasing Ordinances', 'Asking Questions', '4 Fields Coaching Principles', 'Quarterly Iron on Iron (IoI)'],
            4 => ['Vision Casting for Training', 'Listening & Diagnosing', 'Non-Residential Training', 'Networking / Scheduling', 'Indigenization of Tools', 'Wise Use of Financial Resources'],
            5 => ['Vision Casting for the Unreached', 'Indigenization of Tools', 'U/UPG Vision Casting', 'NPL Coalition Forming', 'Training Team Coaching', 'Catalyzing Organizations'],
            default => [],
        };
    }

    /**
     * Residential training rhythm
     */
    public function residentialTraining(): string
    {
        return match ($this->level()) {
            0 => 'None',
            1 => '411 Discipleship',
            2 => 'Weekly Commands of Christ; Bivocational Weekly Rhythm',
            3 => 'Weekly Residency with L3–4 Tools',
            4 => 'Bring with to Swarm Trainings',
            5 => 'Send Residents to Field Hubs',
            default => 'None',
        };
    }

    /**
     * Multiplication multiplier for autonomous monthly ticks
     */
    public function multiplicationRate(): float
    {
        return match ($this->level()) {
            0 => 0.0,
            1 => 0.40,
            2 => 0.85,
            3 => 1.45,
            4 => 2.10,
            5 => 3.20,
            default => 0.0,
        };
    }

    /**
     * Whether this leader can pastor or plant an autonomous church circle
     */
    public function canLeadChurch(): bool
    {
        return $this->level() >= 2;
    }

    /**
     * Tailwind CSS badge styling classes
     */
    public function badgeClasses(): string
    {
        return match ($this->level()) {
            0 => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            1 => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700',
            2 => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-700',
            3 => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-700',
            4 => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700',
            5 => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-700',
            default => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        };
    }
}
