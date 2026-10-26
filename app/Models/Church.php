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
 * @property int $attenders_count
 * @property int $believers_count
 * @property int $baptized_count
 * @property bool $is_dissolved
 * @property int|null $dissolved_game_month
 * @property string|null $dissolution_reason
 * @property string|null $started_by_name
 * @property int|null $last_attrition_month
 * @property string|null $last_attrition_event
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
    'attenders_count',
    'believers_count',
    'baptized_count',
    'is_dissolved',
    'dissolved_game_month',
    'dissolution_reason',
    'started_by_name',
    'last_attrition_month',
    'last_attrition_event',
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
     * PHP 8.4 Property Hook: Whether church is currently active and gathering
     */
    public bool $isActive {
        get => ! $this->is_dissolved;
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly status label
     */
    public string $statusLabel {
        get => $this->is_dissolved
            ? 'Dissolved Group'
            : ($this->is_healthy_church ? 'Healthy Acts 2 Church' : 'Developing Bible Study');
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
     * PHP 8.4 Property Hook: Effective leader display name
     */
    public string $leaderDisplayName {
        get => $this->leaderNpc?->displayName
            ?? $this->leaderUser?->name
            ?? $this->started_by_name
            ?? 'Local Servant Leader';
    }

    /**
     * PHP 8.4 Property Hook: Map marker color based on generation
     */
    public string $generationColor {
        get => $this->is_dissolved ? '#64748b' : match ($this->generation) {
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
            'attenders_count' => 'integer',
            'believers_count' => 'integer',
            'baptized_count' => 'integer',
            'is_dissolved' => 'boolean',
            'dissolved_game_month' => 'integer',
            'last_attrition_month' => 'integer',
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

    /**
     * Resident disciples/believers attending this church
     *
     * @return HasMany<Npc, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Npc::class, 'church_id');
    }

    /**
     * Applies attrition (relocation, sickness, or interpersonal conflict) to this church.
     * Decreases attendance, believers count, and health score.
     * Dissolves the church if health score falls too low (<= 15) or attenders drops <= 1.
     */
    public function applyAttrition(
        string $reason,
        int $lostAttenders,
        int $lostBelievers,
        int $healthLoss,
        int $gameMonth
    ): bool {
        if ($this->is_dissolved) {
            return false;
        }

        $newAttenders = max(0, $this->attenders_count - $lostAttenders);
        $newBelievers = max(0, min($newAttenders, $this->believers_count - $lostBelievers));
        $newBaptized = max(0, min($newBelievers, $this->baptized_count));
        $newHealth = max(0, $this->health_score - $healthLoss);
        $isHealthy = $newHealth >= 50 && $this->hasAllCoreElements();

        $eventSummary = "Month {$gameMonth}: {$reason} (-{$lostAttenders} attenders, -{$healthLoss}% health)";

        // Dissolve church if attendance drops to critical minimum or health collapsed
        if ($newHealth <= 15 || $newAttenders <= 1) {
            $this->dissolve($reason, $gameMonth);

            return true;
        }

        $this->update([
            'attenders_count' => $newAttenders,
            'believers_count' => $newBelievers,
            'baptized_count' => $newBaptized,
            'health_score' => $newHealth,
            'is_healthy_church' => $isHealthy,
            'last_attrition_month' => $gameMonth,
            'last_attrition_event' => $eventSummary,
        ]);

        return false;
    }

    /**
     * Dissolves this church gathering.
     */
    public function dissolve(string $reason, int $gameMonth): void
    {
        $this->update([
            'is_dissolved' => true,
            'dissolved_game_month' => $gameMonth,
            'dissolution_reason' => $reason,
            'health_score' => 0,
            'is_healthy_church' => false,
            'last_attrition_month' => $gameMonth,
            'last_attrition_event' => "Month {$gameMonth}: Dissolved — {$reason}",
        ]);

        // Unlink resident NPC members
        Npc::where('church_id', $this->id)->update(['church_id' => null]);
    }

    /**
     * Revitalizes a struggling or dissolved church through pastoral coaching / restart.
     */
    public function revitalize(int $gameMonth, ?User $revitalizedBy = null): void
    {
        $practiced = $this->elements_json ?? [];
        if (empty($practiced)) {
            $practiced = [
                ChurchElement::RepentanceBelief->value,
                ChurchElement::Word->value,
                ChurchElement::Prayer->value,
                ChurchElement::Fellowship->value,
            ];
        }

        $this->update([
            'is_dissolved' => false,
            'dissolved_game_month' => null,
            'dissolution_reason' => null,
            'health_score' => 50,
            'is_healthy_church' => false,
            'attenders_count' => max(4, $this->attenders_count + 3),
            'believers_count' => max(2, $this->believers_count + 2),
            'baptized_count' => max(1, $this->baptized_count + 1),
            'elements_json' => $practiced,
            'last_attrition_event' => "Month {$gameMonth}: Revitalized through apostolic coaching!",
        ]);
    }
}
