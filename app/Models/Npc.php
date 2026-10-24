<?php

namespace App\Models;

use App\Enums\LeadershipTier;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
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
 * @property int $trust_level
 * @property bool $is_person_of_peace
 * @property bool $is_baptized
 * @property bool $trained_411
 * @property LeadershipTier $leadership_tier
 * @property int $generation
 * @property int|null $discipler_npc_id
 * @property int|null $discipler_user_id
 * @property float $lat
 * @property float $lng
 * @property string|null $backstory
 * @property array<string, mixed>|null $spiritual_journal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('npcs')]
#[Fillable([
    'game_session_id',
    'ward_id',
    'name',
    'name_ja',
    'role_archetype',
    'need_state',
    'status',
    'trust_level',
    'is_person_of_peace',
    'is_baptized',
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
        get => $this->generation > 0 ? "G{$this->generation}" : "Seeker";
    }

    /**
     * PHP 8.4 Property Hook: Has an active real-life crisis or urgent care need
     */
    public bool $hasActiveCrisis {
        get => $this->need_state !== NpcNeedState::None;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_archetype' => NpcRoleArchetype::class,
            'need_state' => NpcNeedState::class,
            'status' => ReceptivityStatus::class,
            'leadership_tier' => LeadershipTier::class,
            'trust_level' => 'integer',
            'is_person_of_peace' => 'boolean',
            'is_baptized' => 'boolean',
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
