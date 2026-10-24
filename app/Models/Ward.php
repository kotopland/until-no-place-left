<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $name_ja
 * @property string $slug
 * @property float $center_lat
 * @property float $center_lng
 * @property int $spiritual_openness
 * @property int $poverty_need_index
 * @property int $population
 * @property array<string, mixed>|null $geojson_polygon
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('wards')]
#[Fillable([
    'name',
    'name_ja',
    'slug',
    'center_lat',
    'center_lng',
    'spiritual_openness',
    'poverty_need_index',
    'population',
    'geojson_polygon',
    'description',
])]
class Ward extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Bilingual formatted name
     */
    public string $fullName {
        get => "{$this->name} ({$this->name_ja})";
    }

    /**
     * PHP 8.4 Property Hook: Qualitative openness classification
     */
    public string $opennessLevelLabel {
        get => match (true) {
            $this->spiritual_openness >= 60 => 'High Openness / Harvest Ready',
            $this->spiritual_openness >= 35 => 'Moderate Interest / Emerging Field',
            default => 'Resistant / Pre-Sowing & Mercy Needed',
        };
    }

    /**
     * PHP 8.4 Property Hook: Poverty & material distress badge
     */
    public string $povertyLevelLabel {
        get => match (true) {
            $this->poverty_need_index >= 60 => 'High Urgent Need (Station / Parks)',
            $this->poverty_need_index >= 30 => 'Moderate Need',
            default => 'Standard Urban Baseline',
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
            'center_lat' => 'float',
            'center_lng' => 'float',
            'spiritual_openness' => 'integer',
            'poverty_need_index' => 'integer',
            'population' => 'integer',
            'geojson_polygon' => 'array',
        ];
    }

    /**
     * Simulated residents located in this ward
     *
     * @return HasMany<Npc, $this>
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    /**
     * Church plants and 3/3rds groups established in this ward
     *
     * @return HasMany<Church, $this>
     */
    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }

    /**
     * Actions scheduled in this ward
     *
     * @return HasMany<QueuedMonthlyAction, $this>
     */
    public function queuedActions(): HasMany
    {
        return $this->hasMany(QueuedMonthlyAction::class);
    }
}
