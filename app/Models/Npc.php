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
                $npc->gender = 'male';
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
