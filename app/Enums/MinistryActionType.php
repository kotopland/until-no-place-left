<?php

namespace App\Enums;

use App\Models\Npc;

enum MinistryActionType: string
{
    // Field 1: Entry Strategy
    case CasualTalk = 'casual_talk';
    case DeepTalk = 'deep_talk';
    case PrayerForHealing = 'prayer_for_healing';
    case MercyCareFoodBible = 'mercy_care_food_bible';
    case InternationalEvent = 'international_event';
    case PrayerWalk = 'prayer_walk';

    // Field 2: Gospel Presentation & Decision
    case Share15SecTestimony = 'share_15sec_testimony';
    case Share3Circles = 'share_3circles';
    case PraySalvation = 'pray_salvation';

    // Field 3: Discipleship Growth
    case CommitToBaptism = 'commit_to_baptism';
    case CommandsOfChrist = 'commands_of_christ';
    case SwordBibleStudy = 'sword_bible_study';
    case Train411 = 'train_411';

    // Field 4: Church Formation
    case ChurchCircles = 'church_circles';

    // Field 5: Leadership Multiplication & Movement
    case MentorTimothy = 'mentor_timothy';
    case IronOnIron = 'iron_on_iron';
    case SwarmTraining = 'swarm_training';

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
            self::PrayerWalk => 'Prayer Walking & Spiritual Mapping',
            self::Share15SecTestimony => 'Share 15-Second Testimony',
            self::Share3Circles => 'Share 3 Circles Gospel Presentation',
            self::PraySalvation => "Prayer of Salvation / Sinner's Prayer",
            self::CommitToBaptism => 'Commitment to Baptism & Cost of Discipleship',
            self::CommandsOfChrist => 'Commands of Christ (& Hand Motions)',
            self::SwordBibleStudy => 'SWORD Bible Study Discovery',
            self::Train411 => 'Equip with 411 Discipleship Training',
            self::ChurchCircles => 'Acts 2 Church Circles (3/3 Pattern)',
            self::MentorTimothy => 'Mentor Timothy Leader (Field 5 Multiplication)',
            self::IronOnIron => 'Generational Mapping & Iron on Iron (IoI)',
            self::SwarmTraining => 'Swarm Training & Movement Catalyzing',
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
            self::PrayerWalk => '祈りの行進（プレイヤーウォーク・霊的マッピング）',
            self::Share15SecTestimony => '15秒証しを分かち合う',
            self::Share3Circles => '3サークル福音提示',
            self::PraySalvation => '救いの祈り・罪人の祈り（信仰告白）',
            self::CommitToBaptism => '洗礼への決意と信仰告白（家族・文化的葛藤のケア）',
            self::CommandsOfChrist => 'キリストの命令（手のジェスチャー付き弟子訓練）',
            self::SwordBibleStudy => 'SWORD聖書発見学習法（4つの質問によるディスカバリースタディ）',
            self::Train411 => '411弟子訓練の実施（L1種蒔く人育成）',
            self::ChurchCircles => '使徒2章 チャーチサークル（3/3パターンによる家の教会形成）',
            self::MentorTimothy => 'テモテ育成（L2教会開拓者コーチング）',
            self::IronOnIron => '世代マップ作成＆アイアン・オン・アイアン（L3リーダー相互コーチング）',
            self::SwarmTraining => 'スウォーム集中訓練＆ムーブメント戦略会議（L4/L5地域拡大）',
        };
    }

    /**
     * Four Fields Framework category (1 to 5)
     */
    public function field(): int
    {
        return match ($this) {
            self::CasualTalk, self::InternationalEvent, self::PrayerWalk => 1, // Field 1: Entry Strategy
            self::DeepTalk, self::PrayerForHealing => 1,    // Field 1: Heart Preparation / Sowing Pre-Gospel
            self::MercyCareFoodBible => 1,                  // Field 1 & 4: Compassion / Kingdom Platform
            self::Share15SecTestimony, self::Share3Circles, self::PraySalvation => 2, // Field 2: Gospel Sowing & Decision
            self::CommitToBaptism, self::CommandsOfChrist, self::SwordBibleStudy, self::Train411 => 3, // Field 3: Discipleship Growth
            self::ChurchCircles => 4,                       // Field 4: Healthy Church Formation
            self::MentorTimothy, self::IronOnIron, self::SwarmTraining => 5, // Field 5: Leadership Multiplication & Movement
        };
    }

    /**
     * Field name badge
     */
    public function fieldName(): string
    {
        return match ($this) {
            self::PrayerWalk => 'Field 1: Entry / Prayer Walk',
            self::ChurchCircles => 'Field 4: Healthy Church Formation',
            self::MentorTimothy => 'Field 5: Leadership Multiplication (L1 -> L2)',
            self::IronOnIron => 'Field 5: Iron on Iron Peer Coaching (L2 -> L3)',
            self::SwarmTraining => 'Field 5: Regional Movement Catalyzing (L4/L5)',
            default => match ($this->field()) {
                1 => 'Field 1: Entry / Friendship',
                2 => 'Field 2: Gospel Presentation',
                3 => 'Field 3: Discipleship Growth',
                4 => 'Field 4: Healthy Church Formation',
                5 => 'Field 5: Leadership Multiplication',
                default => 'Four Fields',
            },
        };
    }

    /**
     * Corresponding level from the 5 Levels of Leadership Training Toolbox
     */
    public function toolboxLevel(): string
    {
        return match ($this) {
            self::CasualTalk, self::DeepTalk, self::PrayerForHealing, self::MercyCareFoodBible,
            self::InternationalEvent, self::PrayerWalk, self::Share15SecTestimony, self::Share3Circles,
            self::PraySalvation, self::Train411 => 'L1: Seed Sower',
            self::CommitToBaptism, self::CommandsOfChrist, self::SwordBibleStudy, self::ChurchCircles => 'L2: Church Planter',
            self::MentorTimothy, self::IronOnIron => 'L3: Church Plant Multiplier',
            self::SwarmTraining => 'L4: Movement Trainer / L5: Strategy Coordinator',
        };
    }

    /**
     * Action Points (AP) consumed per execution
     */
    public function apCost(): int
    {
        return match ($this) {
            self::CasualTalk, self::PrayerWalk => 1,
            self::DeepTalk => 2,
            self::PrayerForHealing => 1,
            self::MercyCareFoodBible => 2,
            self::InternationalEvent => 3,
            self::Share15SecTestimony => 1,
            self::Share3Circles => 2,
            self::PraySalvation => 2,
            self::CommitToBaptism => 2,
            self::CommandsOfChrist => 2,
            self::SwordBibleStudy => 2,
            self::Train411 => 3,
            self::ChurchCircles => 3,
            self::MentorTimothy => 3,
            self::IronOnIron => 3,
            self::SwarmTraining => 4,
        };
    }

    /**
     * Explanatory pedagogical description of the action
     */
    public function description(): string
    {
        return match ($this) {
            self::CasualTalk => 'Low-barrier friendly conversation at a cafe, park, or station. Builds trust steadily and reveals family and relational circles (Oikos).',
            self::DeepTalk => 'Listen attentively to emotional burdens, work burnout, or family pain. Validates feelings before jumping into solutions; deeply softens hearts.',
            self::PrayerForHealing => 'Offer sincere, compassionate prayer for physical sickness, anxiety, or hardship. Answered prayer often opens entire households (Luke 10:9).',
            self::MercyCareFoodBible => 'Provide nutritious hot meals, basic dignity, warm fellowship, and Scripture portions to unhoused and vulnerable neighbors around Nagoya hubs.',
            self::InternationalEvent => 'Organize a cultural exchange, cooking class, or English cafe in a ward. Attracts groups of seekers and rapidly surfaces Persons of Peace.',
            self::PrayerWalk => 'Intercede on site through neighborhood streets, stations, and shrines. Traces street prayer lines on the Nagoya map and unlocks increased probability of discovering a Person of Peace (Luke 10).',
            self::Share15SecTestimony => 'Share how you transitioned from brokenness to rest and restoration in Jesus in just two simple sentences.',
            self::Share3Circles => 'A clear visual presentation: God’s Design -> Brokenness -> Gospel Rescue -> Repent & Believe -> Recover & Pursue.',
            self::PraySalvation => 'Lead an open seeker in a heartfelt prayer of repentance and receiving Jesus Christ as Lord and Savior (Romans 10:9-10).',
            self::CommitToBaptism => 'Walk alongside a new believer through Japanese family dynamics and ancestral heritage fears to joyfully step forward into water baptism (Acts 2:38).',
            self::CommandsOfChrist => 'Discipleship curriculum of Jesus’ foundational commands (Repent, Believe, Be Baptized, Pray, Love, Lord\'s Supper, Give, Make Disciples) with hand motions.',
            self::SwordBibleStudy => 'Discovery Bible study method: 4 questions (What does it say about God? About people? Any command to obey? Example to follow?) that cultivate hearing God directly.',
            self::Train411 => 'Train a Green believer in the 4 questions: Why make disciples? Who to reach (Oikos)? What to say (15s & 3 Circles)? When to start (Set goals)?',
            self::ChurchCircles => 'Gather believers into an autonomous Acts 2 church circle practicing the 3/3rds pattern: Look Back (Care, Worship, Loving Accountability), Look Up (Word), Look Ahead (Practice, Goals).',
            self::MentorTimothy => 'Invest in an equipped L1 Seed Sower through intentional coaching so they become an L2 Church Planter planting autonomous daughter churches.',
            self::IronOnIron => 'Peer-to-peer coaching with generational mapping and 4 Fields diagnostics to celebrate fruit, remove bottlenecks, and cast 4th generation vision.',
            self::SwarmTraining => 'Mobilize catalytic training teams across the ward to model, assist, watch, and leave (MAWL), launching regional midlevel coaching and unreached engagement.',
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
            self::PrayerWalk => '🚶',
            self::Share15SecTestimony => '⏱️',
            self::Share3Circles => '⭕',
            self::PraySalvation => '✝️',
            self::CommitToBaptism => '🌊',
            self::CommandsOfChrist => '📜',
            self::SwordBibleStudy => '🗡️',
            self::Train411 => '📖',
            self::ChurchCircles => '⭕',
            self::MentorTimothy => '👑',
            self::IronOnIron => '⚔️',
            self::SwarmTraining => '🐝',
        };
    }

    /**
     * Requires an explicit target NPC (vs a ward-wide action like International Event)
     */
    public function requiresTargetNpc(): bool
    {
        return match ($this) {
            self::InternationalEvent, self::PrayerWalk => false,
            default => true,
        };
    }

    /**
     * Evaluates whether this action can be scheduled for a given NPC based on their spiritual readiness
     * and the 5 Levels of Leadership Training Toolbox.
     * Guiding Principle: "People who have never heard about Jesus can't do a Timothy mentoring."
     */
    public function isEligibleFor(?Npc $npc): bool
    {
        if (! $npc) {
            return ! $this->requiresTargetNpc();
        }

        $isBeliever = $npc->status === ReceptivityStatus::Green || $npc->hasPrayedSalvation;
        $leaderLevel = $npc->leadership_tier->level();

        return match ($this) {
            // Field 1 Entry & Platform: Open to anyone (Unreached, Red, Yellow, Green, Leaders)
            self::CasualTalk, self::DeepTalk, self::PrayerForHealing, self::MercyCareFoodBible,
            self::InternationalEvent => true,

            // Field 1 & MAWL: Prayer Walk (Solo in ward, or Joint with acquaintance/seeker/believer)
            self::PrayerWalk => $npc->is_person_of_peace || $isBeliever || $npc->status === ReceptivityStatus::Yellow || $npc->trust_level >= 20,

            // Field 2 Gospel: Unreached or Red or Yellow
            self::Share15SecTestimony, self::Share3Circles => true,

            // Field 2 Decision: For open seekers who haven't yet accepted Christ
            self::PraySalvation => ! $isBeliever,

            // Field 3 Discipleship: Must be a saved believer!
            self::CommitToBaptism => $isBeliever,
            self::CommandsOfChrist => $isBeliever,
            self::SwordBibleStudy => $npc->status === ReceptivityStatus::Yellow || $isBeliever,
            self::Train411 => $isBeliever && ! $npc->trained_411,

            // Field 4 Church: For equipped disciples ready to plant/form church circles (L1+)
            self::ChurchCircles => $isBeliever && ($npc->trained_411 || $npc->is_baptized || $leaderLevel >= 1),

            // Field 5 Leadership Mentoring (L1 -> L2): Target must be an equipped L1 Seed Sower (trained in 411)
            self::MentorTimothy => $isBeliever && $npc->trained_411 && $leaderLevel >= 1,

            // Field 5 Iron on Iron (L2 -> L3): Target must be at least an L2 Church Planter
            self::IronOnIron => $leaderLevel >= 2,

            // Field 5 Swarm Training: Target must be at least an L3 Multiplier (or ward platform)
            self::SwarmTraining => $leaderLevel >= 3,
        };
    }

    /**
     * Human-friendly explanation of why an action is locked for a specific resident.
     */
    public function ineligibilityReason(?Npc $npc): ?string
    {
        if ($this->isEligibleFor($npc)) {
            return null;
        }

        if (! $npc) {
            return 'Please select a resident in the ward to schedule this action.';
        }

        return match ($this) {
            self::PrayerWalk => 'To invite someone to prayer walk together, build initial acquaintance through Casual Talk first (requires trust 20+, Yellow seeker, Person of Peace, or believer).',
            self::PraySalvation => 'Resident has already prayed the prayer of salvation and received Jesus Christ.',
            self::CommitToBaptism => 'People who have not yet accepted Jesus cannot commit to baptism. Share the Gospel (Field 2) first.',
            self::CommandsOfChrist => 'Commands of Christ is for new believers growing in loving obedience. Share the 3 Circles and lead them to Christ first.',
            self::SwordBibleStudy => 'Resident is currently unreceptive. Build trust and friendship through Casual Talk or Deep Listening first.',
            self::Train411 => $npc->trained_411
                ? 'Resident is already equipped with 411 Discipleship (L1 Seed Sower).'
                : 'People who have never heard about Jesus cannot be trained in 411. Share the Gospel (Field 2) and lead them to Christ first.',
            self::ChurchCircles => 'Requires an equipped believer (L1 Seed Sower) who is baptized or trained in 411 before gathering an Acts 2 Church Circle.',
            self::MentorTimothy => 'People who have never heard about Jesus cannot do Timothy mentoring! Target must be a Green believer equipped in 411 (L1 Seed Sower).',
            self::IronOnIron => 'Iron on Iron peer coaching requires an active L2 Church Planter who has planted or is co-leading a church circle.',
            self::SwarmTraining => 'Requires an L3 Church Plant Multiplier or regional training team to coordinate swarm training.',
            default => 'Resident does not meet the 5 Levels of Leadership prerequisites for this tool.',
        };
    }
}
