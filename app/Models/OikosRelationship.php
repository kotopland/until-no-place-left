<?php

namespace App\Models;

use App\Enums\RelationshipType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $npc_id
 * @property int $related_npc_id
 * @property RelationshipType $relationship_type
 * @property int $trust_level
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('oikos_relationships')]
#[Fillable([
    'game_session_id',
    'npc_id',
    'related_npc_id',
    'relationship_type',
    'trust_level',
    'notes',
])]
class OikosRelationship extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Relational description badge
     */
    public string $badgeLabel {
        get => "{$this->relationship_type->label()} (Trust: {$this->trust_level}%)";
    }

    /**
     * PHP 8.4 Property Hook: Whether this connection is ripe for organic gospel bridging
     */
    public bool $isHighTrustBridge {
        get => $this->trust_level >= 65;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship_type' => RelationshipType::class,
            'trust_level' => 'integer',
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
     * Primary NPC node
     *
     * @return BelongsTo<Npc, $this>
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'npc_id');
    }

    /**
     * Related target NPC in their Oikos
     *
     * @return BelongsTo<Npc, $this>
     */
    public function relatedNpc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'related_npc_id');
    }
}
