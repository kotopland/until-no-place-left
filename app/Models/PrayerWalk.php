<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $user_id
 * @property int $ward_id
 * @property int $game_month
 * @property int $game_day
 * @property string $route_name
 * @property array<array<float>> $path_coords
 * @property bool $pop_discovered
 * @property int|null $discovered_npc_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('prayer_walks')]
#[Fillable([
    'game_session_id',
    'user_id',
    'ward_id',
    'game_month',
    'game_day',
    'route_name',
    'path_coords',
    'pop_discovered',
    'discovered_npc_id',
])]
class PrayerWalk extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'path_coords' => 'array',
            'pop_discovered' => 'boolean',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function discoveredNpc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'discovered_npc_id');
    }
}
