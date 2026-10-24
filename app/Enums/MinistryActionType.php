<?php

namespace App\Enums;

enum MinistryActionType: string
{
    case CasualTalk = 'casual_talk';
    case DeepTalk = 'deep_talk';
    case PrayerForHealing = 'prayer_for_healing';
    case MercyCareFoodBible = 'mercy_care_food_bible';
    case InternationalEvent = 'international_event';
    case Share15SecTestimony = 'share_15sec_testimony';
    case Share3Circles = 'share_3circles';
    case PraySalvation = 'pray_salvation';
    case CommitToBaptism = 'commit_to_baptism';
    case Train411 = 'train_411';
    case MentorTimothy = 'mentor_timothy';

    /**
     * Display title of the action
     */
    public function label(): string
    {
        return match ($this) {
            self::CasualTalk => 'Casual Talk & Friendship',
            self::DeepTalk => 'Deep Talk & Active Listening',
            self::PrayerForHealing => 'Prayer for Healing & Peace',
            self::MercyCareFoodBible => 'Mercy Care (Food, Practical Aid & Bible)',
            self::InternationalEvent => 'Host International / Cultural Event',
            self::Share15SecTestimony => 'Share 15-Second Testimony',
            self::Share3Circles => 'Share 3 Circles Gospel Presentation',
            self::PraySalvation => "Prayer of Salvation / Sinner's Prayer",
            self::CommitToBaptism => 'Commitment to Baptism & Cost of Discipleship',
            self::Train411 => 'Equip with 411 Discipleship Training',
            self::MentorTimothy => 'Mentor Timothy Leader (Field 5 Multiplication)',
        };
    }

    /**
     * Japanese title
     */
    public function labelJa(): string
    {
        return match ($this) {
            self::CasualTalk => '雑談・友情作り',
            self::DeepTalk => '傾聴・心のケア（深い対話）',
            self::PrayerForHealing => '癒やしと平安の祈り',
            self::MercyCareFoodBible => '愛の奉仕（炊き出し・支援・聖書贈呈）',
            self::InternationalEvent => '国際交流イベント・英会話カフェ開催',
            self::Share15SecTestimony => '15秒証しを分かち合う',
            self::Share3Circles => '3サークル福音提示',
            self::PraySalvation => '救いの祈り・罪人の祈り（信仰告白）',
            self::CommitToBaptism => '洗礼への決意と信仰告白（家族・文化的葛藤のケア）',
            self::Train411 => '411弟子訓練の実施',
            self::MentorTimothy => 'テモテ育成（次世代リーダー訓練）',
        };
    }

    /**
     * Four Fields Framework category (1 to 5)
     */
    public function field(): int
    {
        return match ($this) {
            self::CasualTalk, self::InternationalEvent => 1, // Field 1: Entry Strategy
            self::DeepTalk, self::PrayerForHealing => 1,    // Field 1: Heart Preparation / Sowing Pre-Gospel
            self::MercyCareFoodBible => 1,                  // Field 1 & 4: Compassion / Kingdom Platform
            self::Share15SecTestimony, self::Share3Circles, self::PraySalvation => 2, // Field 2: Gospel Sowing & Decision
            self::CommitToBaptism, self::Train411 => 3,     // Field 3: Discipleship & 3/3rds Formation
            self::MentorTimothy => 5,                       // Field 5: Leadership Multiplication
        };
    }

    /**
     * Field name badge
     */
    public function fieldName(): string
    {
        return match ($this->field()) {
            1 => 'Field 1: Entry / Friendship',
            2 => 'Field 2: Gospel Presentation',
            3 => 'Field 3: Discipleship Growth',
            4 => 'Field 4: Healthy Church Formation',
            5 => 'Field 5: Leadership Multiplication',
            default => 'Four Fields',
        };
    }

    /**
     * Action Points (AP) consumed per execution
     */
    public function apCost(): int
    {
        return match ($this) {
            self::CasualTalk => 1,
            self::DeepTalk => 2,
            self::PrayerForHealing => 1,
            self::MercyCareFoodBible => 2,
            self::InternationalEvent => 3,
            self::Share15SecTestimony => 1,
            self::Share3Circles => 2,
            self::PraySalvation => 2,
            self::CommitToBaptism => 2,
            self::Train411 => 3,
            self::MentorTimothy => 3,
        };
    }

    /**
     * Explanatory pedagogical description of the action
     */
    public function description(): string
    {
        return match ($this) {
            self::CasualTalk => 'Low-barrier friendly conversation at a cafe, park, or station. Builds trust steadily and reveals family and relational circles.',
            self::DeepTalk => 'Listen attentively to emotional burdens, work burnout, or family pain. Validates feelings before jumping into solutions; deeply softens hearts.',
            self::PrayerForHealing => 'Offer sincere, compassionate prayer for physical sickness, anxiety, or hardship. Answered prayer often opens entire households (Luke 10:9).',
            self::MercyCareFoodBible => 'Provide nutritious hot meals, basic dignity, warm fellowship, and Scripture portions to unhoused and vulnerable neighbors around Nagoya hubs.',
            self::InternationalEvent => 'Organize a cultural exchange, cooking class, or English cafe in a ward. Attracts groups of seekers and rapidly surfaces Persons of Peace.',
            self::Share15SecTestimony => 'Share how you transitioned from brokenness to rest and restoration in Jesus in just two simple sentences.',
            self::Share3Circles => 'A clear visual presentation: God’s Design -> Brokenness -> Gospel Rescue -> Repent & Believe -> Recover & Pursue.',
            self::PraySalvation => 'Lead an open seeker in a heartfelt prayer of repentance and receiving Jesus Christ as Lord and Savior (Romans 10:9-10).',
            self::CommitToBaptism => 'Walk alongside a new believer through Japanese family dynamics and ancestral heritage fears to joyfully step forward into water baptism (Acts 2:38).',
            self::Train411 => 'Train a Green believer in the 4 questions: Why make disciples? Who to reach (Oikos)? What to say (15s & 3 Circles)? When to start (Set goals)?',
            self::MentorTimothy => 'Invest in a faithful local disciple through intentional coaching so they multiply autonomous groups and break missionary bottlenecks.',
        };
    }

    /**
     * Icon representation
     */
    public function icon(): string
    {
        return match ($this) {
            self::CasualTalk => '💬',
            self::DeepTalk => '👂',
            self::PrayerForHealing => '🙏',
            self::MercyCareFoodBible => '🍲',
            self::InternationalEvent => '🎉',
            self::Share15SecTestimony => '⏱️',
            self::Share3Circles => '⭕',
            self::PraySalvation => '✝️',
            self::CommitToBaptism => '🌊',
            self::Train411 => '📖',
            self::MentorTimothy => '👑',
        };
    }

    /**
     * Requires an explicit target NPC (vs a ward-wide action like International Event)
     */
    public function requiresTargetNpc(): bool
    {
        return match ($this) {
            self::InternationalEvent => false,
            default => true,
        };
    }
}
