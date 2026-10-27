<?php

namespace App\Models;

use App\Services\GameChatService;
use Database\Factories\MovementCelebrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int|null $user_id
 * @property int $ward_id
 * @property int|null $npc_id
 * @property int|null $church_id
 * @property string $type
 * @property string $title
 * @property string|null $title_ja
 * @property string|null $headline
 * @property string $person_name
 * @property string|null $person_role
 * @property string|null $discipler_name
 * @property string $ward_name
 * @property string $details
 * @property string|null $scripture_ref
 * @property int $generation
 * @property int $game_month
 * @property int $game_week
 * @property bool $is_dismissed
 */
#[Table('movement_celebrations')]
#[Fillable([
    'game_session_id',
    'user_id',
    'ward_id',
    'npc_id',
    'church_id',
    'type',
    'title',
    'title_ja',
    'headline',
    'person_name',
    'person_role',
    'discipler_name',
    'ward_name',
    'details',
    'scripture_ref',
    'generation',
    'game_month',
    'game_week',
    'is_dismissed',
])]
class MovementCelebration extends Model
{
    /** @use HasFactory<MovementCelebrationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (MovementCelebration $celebration): void {
            try {
                app(GameChatService::class)->broadcastCelebration($celebration);
            } catch (\Throwable) {
                // Ignore to avoid blocking celebration creation
            }
        });
    }

    /**
     * PHP 8.4 Property Hook: Icon representing celebration type
     */
    public string $icon {
        get => match ($this->type) {
            'salvation' => '🎉',
            'baptism' => '🌊',
            'church_planted' => '⛪',
            'group_formed' => '📖',
            default => '✨',
        };
    }

    /**
     * PHP 8.4 Property Hook: Badge color theme
     */
    public string $themeColor {
        get => match ($this->type) {
            'salvation' => 'emerald',
            'baptism' => 'cyan',
            'church_planted' => 'indigo',
            'group_formed' => 'amber',
            default => 'purple',
        };
    }

    /**
     * PHP 8.4 Property Hook: Japanese celebration badge label
     */
    public string $typeLabelJa {
        get => match ($this->type) {
            'salvation' => '救いと新生',
            'baptism' => '水のバプテスマ',
            'church_planted' => '教会誕生',
            'group_formed' => '聖書グループ誕生',
            default => '御国の実り',
        };
    }

    /**
     * Relationship to GameSession
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * Relationship to User (Catalyst / Discipler)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to Ward
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Relationship to NPC
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * Relationship to Church
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Record a new salvation celebration
     */
    public static function recordSalvation(
        int $gameSessionId,
        int $wardId,
        string $wardName,
        string $personName,
        ?string $personRole = null,
        ?string $disciplerName = null,
        string $details = '',
        int $generation = 1,
        int $gameMonth = 1,
        int $gameWeek = 1,
        ?int $userId = null,
        ?int $npcId = null,
    ): self {
        return self::create([
            'game_session_id' => $gameSessionId,
            'user_id' => $userId,
            'ward_id' => $wardId,
            'npc_id' => $npcId,
            'type' => 'salvation',
            'title' => '🎉 New Believer Saved in Nagoya!',
            'title_ja' => '救いと新生 • Sinner\'s Prayer Confessed',
            'headline' => "{$personName} has repented and accepted Jesus Christ as Lord!",
            'person_name' => $personName,
            'person_role' => $personRole,
            'discipler_name' => $disciplerName,
            'ward_name' => $wardName,
            'details' => $details,
            'scripture_ref' => 'Luke 15:7 - "There is joy in heaven over one sinner who repents."',
            'generation' => $generation,
            'game_month' => $gameMonth,
            'game_week' => $gameWeek,
            'is_dismissed' => false,
        ]);
    }

    /**
     * Record a water baptism celebration
     */
    public static function recordBaptism(
        int $gameSessionId,
        int $wardId,
        string $wardName,
        string $personName,
        ?string $personRole = null,
        ?string $disciplerName = null,
        string $details = '',
        int $generation = 1,
        int $gameMonth = 1,
        int $gameWeek = 1,
        ?int $userId = null,
        ?int $npcId = null,
    ): self {
        return self::create([
            'game_session_id' => $gameSessionId,
            'user_id' => $userId,
            'ward_id' => $wardId,
            'npc_id' => $npcId,
            'type' => 'baptism',
            'title' => '🌊 Water Baptism Milestone Celebrated!',
            'title_ja' => '水のバプテスマ • Public Confession of Christ',
            'headline' => "{$personName} was publicly baptized in water!",
            'person_name' => $personName,
            'person_role' => $personRole,
            'discipler_name' => $disciplerName,
            'ward_name' => $wardName,
            'details' => $details,
            'scripture_ref' => 'Matthew 28:19 - "Go therefore and make disciples of all nations, baptizing them."',
            'generation' => $generation,
            'game_month' => $gameMonth,
            'game_week' => $gameWeek,
            'is_dismissed' => false,
        ]);
    }

    /**
     * Record a new simple church planted celebration
     */
    public static function recordChurchPlanted(
        int $gameSessionId,
        int $wardId,
        string $wardName,
        string $churchName,
        string $leaderName,
        ?string $startedByName = null,
        string $details = '',
        int $generation = 1,
        int $gameMonth = 1,
        int $gameWeek = 1,
        ?int $userId = null,
        ?int $npcId = null,
        ?int $churchId = null,
    ): self {
        return self::create([
            'game_session_id' => $gameSessionId,
            'user_id' => $userId,
            'ward_id' => $wardId,
            'npc_id' => $npcId,
            'church_id' => $churchId,
            'type' => 'church_planted',
            'title' => '⛪ New Acts 2 Simple Church Planted!',
            'title_ja' => 'シンプルチャーチ誕生 • 3/3rds Pattern Gathered',
            'headline' => "{$churchName} has been established in {$wardName}!",
            'person_name' => $leaderName,
            'person_role' => 'Church Circle Leader',
            'discipler_name' => $startedByName,
            'ward_name' => $wardName,
            'details' => $details,
            'scripture_ref' => 'Acts 2:47 - "Praising God and having favor with all the people. And the Lord added daily those being saved."',
            'generation' => $generation,
            'game_month' => $gameMonth,
            'game_week' => $gameWeek,
            'is_dismissed' => false,
        ]);
    }

    /**
     * Record a discovery Bible study group formed
     */
    public static function recordGroupFormed(
        int $gameSessionId,
        int $wardId,
        string $wardName,
        string $personName,
        ?string $personRole = null,
        ?string $disciplerName = null,
        string $details = '',
        int $generation = 1,
        int $gameMonth = 1,
        int $gameWeek = 1,
        ?int $userId = null,
        ?int $npcId = null,
    ): self {
        return self::create([
            'game_session_id' => $gameSessionId,
            'user_id' => $userId,
            'ward_id' => $wardId,
            'npc_id' => $npcId,
            'type' => 'group_formed',
            'title' => '📖 Discovery Bible Study Group Formed!',
            'title_ja' => 'ディスカバリー聖書グループ • Direct Word Encounter',
            'headline' => "A new SWORD Bible study group gathered around {$personName}!",
            'person_name' => $personName,
            'person_role' => $personRole,
            'discipler_name' => $disciplerName,
            'ward_name' => $wardName,
            'details' => $details,
            'scripture_ref' => 'Hebrews 4:12 - "For the word of God is living and active, sharper than any two-edged sword."',
            'generation' => $generation,
            'game_month' => $gameMonth,
            'game_week' => $gameWeek,
            'is_dismissed' => false,
        ]);
    }

    /**
     * Get attribute casting
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation' => 'integer',
            'game_month' => 'integer',
            'game_week' => 'integer',
            'is_dismissed' => 'boolean',
        ];
    }
}
