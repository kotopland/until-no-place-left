<?php

namespace App\Models;

use App\Enums\BaptismStatus;
use App\Enums\LeadershipTier;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Services\PersonAvatarService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $ward_id
 * @property string $name
 * @property string $name_ja
 * @property NpcRoleArchetype $role_archetype
 * @property NpcNeedState $need_state
 * @property ReceptivityStatus $status
 * @property bool $prayed_salvation
 * @property int $trust_level
 * @property bool $is_person_of_peace
 * @property bool $is_baptized
 * @property BaptismStatus $baptism_status
 * @property int $baptism_cost_months
 * @property Carbon|null $prayed_salvation_at
 * @property Carbon|null $baptized_at
 * @property bool $trained_411
 * @property LeadershipTier $leadership_tier
 * @property int $generation
 * @property int|null $discipler_npc_id
 * @property int|null $discipler_user_id
 * @property float $lat
 * @property float $lng
 * @property string|null $backstory
 * @property array<string, mixed>|null $spiritual_journal
 * @property string $gender
 * @property int $age
 * @property string $nationality
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('npcs')]
#[Fillable([
    'game_session_id',
    'ward_id',
    'name',
    'name_ja',
    'gender',
    'age',
    'nationality',
    'role_archetype',
    'need_state',
    'status',
    'prayed_salvation',
    'trust_level',
    'is_person_of_peace',
    'is_baptized',
    'baptism_status',
    'baptism_cost_months',
    'prayed_salvation_at',
    'baptized_at',
    'trained_411',
    'leadership_tier',
    'generation',
    'discipler_npc_id',
    'discipler_user_id',
    'lat',
    'lng',
    'backstory',
    'spiritual_journal',
])]
class Npc extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Npc $npc): void {
            if (empty($npc->name_ja)) {
                $npc->name_ja = $npc->name;
            }
            if (empty($npc->gender)) {
                $npc->gender = static::inferGenderFromName((string) $npc->name, $npc->name_ja);
            }
            if (empty($npc->age)) {
                $npc->age = 30;
            }
            if (empty($npc->nationality)) {
                $npc->nationality = 'Japanese';
            }
            if (empty($npc->lat)) {
                $npc->lat = 35.1700;
            }
            if (empty($npc->lng)) {
                $npc->lng = 136.9066;
            }
        });
    }

    /**
     * Infer gender from name and Japanese kanji/kana if not explicitly specified.
     */
    public static function inferGenderFromName(string $name, ?string $nameJa = null): string
    {
        $femaleExact = [
            'tomoko', 'keiko', 'aoi', 'linh', 'yuki', 'sayaka', 'yuka', 'mari', 'emi',
            'sakura', 'hanako', 'yoko', 'sachiko', 'yumiko', 'ryoko', 'kyoko', 'haruka',
            'asuka', 'ayaka', 'erika', 'rika', 'nana', 'kana', 'rina', 'mina', 'chiyo',
            'kayo', 'sanae', 'lisa', 'risa', 'sarah', 'maria', 'chloe', 'emily', 'grace',
            'anna', 'jessica', 'hannah', 'rachel', 'naomi', 'hitomi', 'megumi', 'akari',
            'hina', 'mei', 'yui', 'mio', 'rin', 'saki', 'riko', 'honoka',
        ];

        $maleExact = [
            'kenji', 'hiroshi', 'masao', 'goro', 'carlos', 'kenichi', 'kazuo', 'takumi',
            'satoshi', 'daisuke', 'taro', 'ichiro', 'jiro', 'shinji', 'ryo', 'sho',
            'daiki', 'naoki', 'kenta', 'yuto', 'sota', 'haruto', 'kaito', 'tatsuya',
            'shota', 'ryota', 'makoto', 'takeshi', 'osamu', 'david', 'john', 'michael',
            'james', 'robert', 'william', 'joseph',
        ];

        $nameLower = strtolower(trim($name));
        $tokens = preg_split('/[\s_-]+/', $nameLower) ?: [];

        // 1. Check exact male given name matches first
        foreach ($tokens as $token) {
            if (in_array($token, $maleExact, true)) {
                return 'male';
            }
        }

        // 2. Check exact female given name matches
        foreach ($tokens as $token) {
            if (in_array($token, $femaleExact, true)) {
                return 'female';
            }
        }
        foreach ($femaleExact as $femaleName) {
            if (str_contains($nameLower, $femaleName)) {
                return 'female';
            }
        }

        // 3. Kanji indicators
        if (! empty($nameJa)) {
            $maleKanji = ['健二', '浩', '正雄', '五郎', '健一', '一男', '拓海', '聡', '大輔', '太郎', '一郎', '二郎', '男', '雄', '夫', '介', '助', '輔', '太', '翔'];
            foreach ($maleKanji as $k) {
                if (str_contains($nameJa, $k)) {
                    return 'male';
                }
            }

            $femaleKanji = ['子', '美', '恵', '香', '菜', '奈', '華', '葵', '優香', '智子', 'さやか', 'まり', 'ゆき', 'えみ', 'リン'];
            foreach ($femaleKanji as $k) {
                if (str_contains($nameJa, $k)) {
                    return 'female';
                }
            }
        }

        // 4. Given name suffix checks (exclude known Japanese surnames like Tanaka, Nonaka, etc.)
        $surnames = [
            'tanaka', 'yamamoto', 'suzuki', 'kondo', 'kobayashi', 'ito', 'kato', 'shibata',
            'ishikawa', 'ogawa', 'yamada', 'sato', 'takahashi', 'watanabe', 'nakamura', 'yoshida',
            'nonaka', 'yamanaka', 'otsuka', 'tezuka', 'matsuoka', 'mori', 'inoue', 'kimura',
        ];

        foreach ($tokens as $token) {
            if (! in_array($token, $surnames, true) && strlen($token) >= 4) {
                if (str_ends_with($token, 'ko') || str_ends_with($token, 'ka') || str_ends_with($token, 'mi') || str_ends_with($token, 'na') || str_ends_with($token, 'sa')) {
                    return 'female';
                }
            }
        }

        return 'male';
    }

    /**
     * PHP 8.4 Property Hook: Bilingual formatted name
     */
    public string $displayName {
        get => "{$this->name} ({$this->name_ja})";
    }

    /**
     * PHP 8.4 Property Hook: Whether NPC is an equipped believer capable of autonomous multiplication
     */
    public bool $canMultiplyAutonomously {
        get => $this->status === ReceptivityStatus::Green && $this->trained_411;
    }

    /**
     * PHP 8.4 Property Hook: Generational badge label
     */
    public string $generationLabel {
        get => $this->generation > 0 ? "G{$this->generation}" : 'Seeker';
    }

    /**
     * PHP 8.4 Property Hook: Has an active real-life crisis or urgent care need
     */
    public bool $hasActiveCrisis {
        get => $this->need_state !== NpcNeedState::None;
    }

    /**
     * PHP 8.4 Property Hook: Whether NPC has prayed the prayer of salvation
     */
    public bool $hasPrayedSalvation {
        get => $this->prayed_salvation || $this->status === ReceptivityStatus::Green;
    }

    /**
     * PHP 8.4 Property Hook: Whether NPC has formally committed to or received water baptism
     */
    public bool $isCommittedToBaptism {
        get => in_array($this->baptism_status, [BaptismStatus::Committed, BaptismStatus::Baptized], true);
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly baptism journey description
     */
    public string $baptismJourneyLabel {
        get => $this->baptism_status->label();
    }

    /**
     * PHP 8.4 Property Hook: Gender symbol
     */
    public string $genderIcon {
        get => app(PersonAvatarService::class)->genderIcon($this->gender ?? 'male');
    }

    /**
     * PHP 8.4 Property Hook: Capitalized gender label
     */
    public string $genderLabel {
        get => ucfirst(strtolower($this->gender ?? 'male'));
    }

    /**
     * PHP 8.4 Property Hook: Country flag emoji
     */
    public string $nationalityFlag {
        get => app(PersonAvatarService::class)->flagEmoji($this->nationality ?? 'Japanese');
    }

    /**
     * PHP 8.4 Property Hook: Age bracket key
     */
    public string $ageGroup {
        get => app(PersonAvatarService::class)->ageGroup($this->age ?? 30);
    }

    /**
     * PHP 8.4 Property Hook: Formatted age group label
     */
    public string $ageGroupLabel {
        get => app(PersonAvatarService::class)->ageGroupLabel($this->age ?? 30);
    }

    /**
     * PHP 8.4 Property Hook: Full demographic summary badge string
     */
    public string $demographicSummary {
        get => "{$this->nationalityFlag} {$this->nationality} • {$this->genderIcon} {$this->age} yrs ({$this->ageGroupLabel})";
    }

    /**
     * Returns the demographic SVG avatar markup.
     */
    public function avatarSvg(int $pixelSize = 64, bool $includeBadges = false): string
    {
        return app(PersonAvatarService::class)->forPerson($this, $pixelSize, $includeBadges);
    }

    /**
     * Returns the demographic base64 SVG data URI.
     */
    public function avatarDataUri(int $pixelSize = 64, bool $includeBadges = false): string
    {
        return app(PersonAvatarService::class)->generateDataUri(
            $this->gender ?? 'male',
            $this->age ?? 30,
            $this->nationality ?? 'Japanese',
            $this->role_archetype?->value,
            $this->status?->value,
            $pixelSize,
            $includeBadges,
            $this->name
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => 'string',
            'age' => 'integer',
            'nationality' => 'string',
            'role_archetype' => NpcRoleArchetype::class,
            'need_state' => NpcNeedState::class,
            'status' => ReceptivityStatus::class,
            'baptism_status' => BaptismStatus::class,
            'prayed_salvation' => 'boolean',
            'leadership_tier' => LeadershipTier::class,
            'trust_level' => 'integer',
            'is_person_of_peace' => 'boolean',
            'is_baptized' => 'boolean',
            'baptism_cost_months' => 'integer',
            'prayed_salvation_at' => 'datetime',
            'baptized_at' => 'datetime',
            'trained_411' => 'boolean',
            'generation' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'spiritual_journal' => 'array',
        ];
    }

    /**
     * Associated Game Session
     *
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * Nagoya Ward
     *
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * The discipler NPC (if discipled by another believer)
     *
     * @return BelongsTo<Npc, $this>
     */
    public function disciplerNpc(): BelongsTo
    {
        return $this->belongsTo(self::class, 'discipler_npc_id');
    }

    /**
     * Disciples directly multiplied by this NPC
     *
     * @return HasMany<Npc, $this>
     */
    public function disciples(): HasMany
    {
        return $this->hasMany(self::class, 'discipler_npc_id');
    }

    /**
     * The player who directly coaches or discipled this NPC
     *
     * @return BelongsTo<User, $this>
     */
    public function disciplerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discipler_user_id');
    }

    /**
     * Oikos relational edges originating from this NPC
     *
     * @return HasMany<OikosRelationship, $this>
     */
    public function oikosConnections(): HasMany
    {
        return $this->hasMany(OikosRelationship::class, 'npc_id');
    }

    /**
     * Oikos relational edges pointing to this NPC
     *
     * @return HasMany<OikosRelationship, $this>
     */
    public function inverseOikosConnections(): HasMany
    {
        return $this->hasMany(OikosRelationship::class, 'related_npc_id');
    }

    /**
     * Churches or 3/3rds groups led by this NPC
     *
     * @return HasMany<Church, $this>
     */
    public function ledChurches(): HasMany
    {
        return $this->hasMany(Church::class, 'leader_npc_id');
    }

    /**
     * Queued player actions targeting this NPC
     *
     * @return HasMany<QueuedMonthlyAction, $this>
     */
    public function queuedActions(): HasMany
    {
        return $this->hasMany(QueuedMonthlyAction::class, 'target_npc_id');
    }

    /**
     * History of multiplayer contributions (sowers, waterers, harvesters)
     *
     * @return HasMany<NpcContribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(NpcContribution::class);
    }

    /**
     * Evaluates whether this NPC's immediate Oikos has any members matching a given need state.
     * Leverages PHP 8.4 native array_any().
     */
    public function hasOikosMemberWithNeed(NpcNeedState $need): bool
    {
        $relatedNpcs = $this->oikosConnections->map(fn (OikosRelationship $rel) => $rel->relatedNpc)->filter()->all();

        return array_any($relatedNpcs, fn (?Npc $npc) => $npc !== null && $npc->need_state === $need);
    }

    /**
     * Evaluates whether all members in this NPC's immediate Oikos have achieved Yellow or Green status.
     * Leverages PHP 8.4 native array_all().
     */
    public function areAllOikosMembersReceptive(): bool
    {
        $relatedNpcs = $this->oikosConnections->map(fn (OikosRelationship $rel) => $rel->relatedNpc)->filter()->all();

        if (empty($relatedNpcs)) {
            return false;
        }

        return array_all($relatedNpcs, fn (?Npc $npc) => $npc !== null && $npc->status->isOpen());
    }

    /**
     * Finds the first Person of Peace in this NPC's immediate relational circle.
     * Leverages PHP 8.4 native array_find().
     */
    public function findOikosPersonOfPeace(): ?Npc
    {
        $relatedNpcs = $this->oikosConnections->map(fn (OikosRelationship $rel) => $rel->relatedNpc)->filter()->all();

        return array_find($relatedNpcs, fn (?Npc $npc) => $npc !== null && $npc->is_person_of_peace);
    }
}
