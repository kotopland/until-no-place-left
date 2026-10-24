<?php

namespace App\Models;

use App\Enums\ChurchElement;
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
 * @property int|null $parent_church_id
 * @property int|null $leader_npc_id
 * @property int|null $leader_user_id
 * @property int $generation
 * @property array<string> $elements_json
 * @property bool $is_healthy_church
 * @property int $health_score
 * @property float $lat
 * @property float $lng
 * @property int $founded_game_month
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('churches')]
#[Fillable([
    'game_session_id',
    'ward_id',
    'name',
    'parent_church_id',
    'leader_npc_id',
    'leader_user_id',
    'generation',
    'elements_json',
    'is_healthy_church',
    'health_score',
    'lat',
    'lng',
    'founded_game_month',
])]
class Church extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Generational badge label (G1, G2, G3, G4+)
     */
    public string $generationBadge {
        get => "G{$this->generation}";
    }

    /**
     * PHP 8.4 Property Hook: Number of practiced Acts 2 elements
     */
    public int $practicedElementsCount {
        get => count($this->elements_json ?? []);
    }

    /**
     * PHP 8.4 Property Hook: Whether this church is autonomous (led by a local Timothy/Elder NPC)
     */
    public bool $isAutonomous {
        get => $this->leader_npc_id !== null && $this->leader_user_id === null;
    }

    /**
     * PHP 8.4 Property Hook: Map marker color based on generation
     */
    public string $generationColor {
        get => match ($this->generation) {
            1 => '#3b82f6', // blue-500 (G1 Player-planted)
            2 => '#8b5cf6', // purple-500 (G2)
            3 => '#ec4899', // pink-500 (G3)
            default => '#eab308', // gold/yellow-500 (G4+ Movement stream!)
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation' => 'integer',
            'elements_json' => 'array',
            'is_healthy_church' => 'boolean',
            'health_score' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'founded_game_month' => 'integer',
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
     * Ward where this church meets
     *
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Mother church in the generational stream
     *
     * @return BelongsTo<Church, $this>
     */
    public function parentChurch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_church_id');
    }

    /**
     * Daughter churches planted by this church
     *
     * @return HasMany<Church, $this>
     */
    public function daughterChurches(): HasMany
    {
        return $this->hasMany(self::class, 'parent_church_id');
    }

    /**
     * Local Timothy/Elder NPC leading this church
     *
     * @return BelongsTo<Npc, $this>
     */
    public function leaderNpc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'leader_npc_id');
    }

    /**
     * Player directly leading this church (if G1)
     *
     * @return BelongsTo<User, $this>
     */
    public function leaderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    /**
     * Checks if this church practices a specific Acts 2 Church Circle element.
     * Leverages PHP 8.4 native array_any().
     */
    public function practicesElement(ChurchElement $element): bool
    {
        $practiced = $this->elements_json ?? [];

        return array_any($practiced, fn ($item) => $item === $element->value);
    }

    /**
     * Evaluates if all essential Church Circle elements are practiced for full autonomous maturity.
     * Leverages PHP 8.4 native array_all().
     */
    public function hasAllCoreElements(): bool
    {
        $coreElements = [
            ChurchElement::RepentanceBelief,
            ChurchElement::Baptism,
            ChurchElement::Word,
            ChurchElement::Prayer,
            ChurchElement::Fellowship,
            ChurchElement::Communion,
            ChurchElement::GivingMercy,
            ChurchElement::LocalLeaders,
            ChurchElement::MakingDisciples,
        ];

        return array_all(
            $coreElements,
            fn (ChurchElement $elem) => $this->practicesElement($elem)
        );
    }
}
