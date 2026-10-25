<?php

namespace App\Livewire;

use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\OikosRelationship;
use App\Models\PrayerWalk;
use App\Models\Ward;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NagoyaMapCanvas extends Component
{
    public ?int $selectedChurchId = null;

    public ?int $selectedWardId = null;

    public bool $showHeatmap = true;

    public bool $showChurches = true;

    public bool $showOikosLines = true;

    public bool $showMercySpots = true;

    public bool $showEventPins = true;

    public bool $showPrayerWalks = true;

    /** @var array<string, string> */
    protected $listeners = [
        'map-church-selected' => 'selectChurch',
        'map-ward-selected' => 'selectWard',
    ];

    public function selectChurch(int $churchId): void
    {
        $this->selectedChurchId = $churchId;
    }

    public function closeChurchSlideover(): void
    {
        $this->selectedChurchId = null;
    }

    public function selectWard(int $wardId): void
    {
        $this->selectedWardId = $wardId;
    }

    public function toggleLayer(string $layer): void
    {
        match ($layer) {
            'heatmap' => $this->showHeatmap = ! $this->showHeatmap,
            'churches' => $this->showChurches = ! $this->showChurches,
            'oikos' => $this->showOikosLines = ! $this->showOikosLines,
            'mercy' => $this->showMercySpots = ! $this->showMercySpots,
            'events' => $this->showEventPins = ! $this->showEventPins,
            'prayer_walks' => $this->showPrayerWalks = ! $this->showPrayerWalks,
            default => null,
        };
    }

    public function render(): View
    {
        $session = GameSession::where('is_active', true)->first();

        $wards = Ward::withCount(['npcs', 'churches'])->get();

        $churches = Church::where('game_session_id', $session?->id)
            ->with(['ward', 'leaderNpc', 'leaderUser', 'parentChurch', 'daughterChurches'])
            ->get();

        $npcs = Npc::where('game_session_id', $session?->id)
            ->with(['ward', 'oikosConnections.relatedNpc'])
            ->get();

        $oikosEdges = OikosRelationship::where('game_session_id', $session?->id)
            ->with(['npc', 'relatedNpc'])
            ->get();

        $selectedChurch = $this->selectedChurchId
            ? Church::with(['ward', 'leaderNpc', 'leaderUser', 'parentChurch', 'daughterChurches'])->find($this->selectedChurchId)
            : null;

        // Transform Ward GeoJSON features for Leaflet client-side layer
        $wardsGeoJson = [
            'type' => 'FeatureCollection',
            'features' => $wards->map(function (Ward $ward) {
                // Approximate bounding polygon around ward center
                $lat = (float) $ward->center_lat;
                $lng = (float) $ward->center_lng;
                $d = 0.016; // approx 1.8km radius boundary offset

                return [
                    'type' => 'Feature',
                    'id' => $ward->id,
                    'properties' => [
                        'id' => $ward->id,
                        'name' => $ward->name,
                        'name_ja' => $ward->name_ja,
                        'slug' => $ward->slug,
                        'openness' => $ward->spiritual_openness,
                        'poverty' => $ward->poverty_need_index,
                        'population' => $ward->population,
                        'npcs_count' => $ward->npcs_count,
                        'churches_count' => $ward->churches_count,
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [$lng - $d, $lat - $d * 0.8],
                            [$lng + $d, $lat - $d * 0.8],
                            [$lng + $d * 1.2, $lat + $d * 0.8],
                            [$lng - $d * 0.8, $lat + $d],
                            [$lng - $d, $lat - $d * 0.8],
                        ]],
                    ],
                ];
            })->values()->all(),
        ];

        // Format Oikos lines for client-side Leaflet polylines
        $linesData = $oikosEdges->filter(fn ($edge) => $edge->npc && $edge->relatedNpc)->map(function ($edge) {
            return [
                'id' => $edge->id,
                'type' => $edge->relationship_type->value,
                'type_label' => $edge->relationship_type->label(),
                'trust' => $edge->trust_level,
                'from_name' => $edge->npc->name,
                'to_name' => $edge->relatedNpc->name,
                'coords' => [
                    [(float) $edge->npc->lat, (float) $edge->npc->lng],
                    [(float) $edge->relatedNpc->lat, (float) $edge->relatedNpc->lng],
                ],
            ];
        })->values()->all();

        // Format Churches for markers
        $churchesData = $churches->map(function (Church $ch) {
            return [
                'id' => $ch->id,
                'name' => $ch->name,
                'ward' => $ch->ward->name,
                'gen' => $ch->generation,
                'badge' => $ch->generationBadge,
                'color' => $ch->generationColor,
                'is_healthy' => $ch->is_healthy_church,
                'health_score' => $ch->health_score,
                'lat' => (float) $ch->lat,
                'lng' => (float) $ch->lng,
                'leader' => $ch->leaderNpc ? $ch->leaderNpc->displayName : ($ch->leaderUser ? $ch->leaderUser->name : 'Missionary Leader'),
                'is_autonomous' => $ch->isAutonomous,
            ];
        })->values()->all();

        // Special Platform & Mercy Hubs in Nagoya
        $specialSpots = [
            [
                'type' => 'mercy',
                'name' => 'Shirakawa Park Mercy Outreach Hub',
                'name_ja' => '白川公園・愛の炊き出し拠点',
                'ward' => 'Naka-ku',
                'desc' => 'Weekly hot meal, dignity, and Scripture distribution for 80+ unhoused neighbors.',
                'lat' => 35.1630,
                'lng' => 136.8990,
                'icon' => '🍲',
            ],
            [
                'type' => 'mercy',
                'name' => 'Meieki Station Overpass Shelter Outreach',
                'name_ja' => '名駅高架下・救援ポイント',
                'ward' => 'Nakamura-ku',
                'desc' => 'Relational care and prayer station for day laborers and displaced workers.',
                'lat' => 35.1700,
                'lng' => 136.8790,
                'icon' => '🍲',
            ],
            [
                'type' => 'event',
                'name' => 'Fujigaoka International Cultural Cafe',
                'name_ja' => '藤が丘・国際交流カフェ',
                'ward' => 'Meito-ku',
                'desc' => 'Bilingual exchange connecting international expats, teachers, and Japanese seekers.',
                'lat' => 35.1690,
                'lng' => 137.0020,
                'icon' => '🎉',
            ],
            [
                'type' => 'event',
                'name' => 'Kakuozan Global Youth Exchange',
                'name_ja' => '覚王山・青年ワールドカフェ',
                'ward' => 'Chikusa-ku',
                'desc' => 'College student community hub near Nagoya University exploring worldview questions.',
                'lat' => 35.1645,
                'lng' => 136.9540,
                'icon' => '🎉',
            ],
        ];

        $prayerWalks = PrayerWalk::where('game_session_id', $session?->id)
            ->with(['ward', 'user', 'discoveredNpc'])
            ->latest()
            ->take(50)
            ->get();

        $prayerWalksData = $prayerWalks->map(function (PrayerWalk $walk): array {
            return [
                'id' => $walk->id,
                'ward_name' => $walk->ward->name,
                'ward_name_ja' => $walk->ward->name_ja,
                'route_name' => $walk->route_name ?: 'Prayer Walk Route',
                'walker_name' => $walk->user ? $walk->user->name : 'Missionary',
                'day' => $walk->game_day,
                'month' => $walk->game_month,
                'pop_discovered' => $walk->pop_discovered,
                'discovered_npc' => $walk->discoveredNpc ? $walk->discoveredNpc->displayName : null,
                'coords' => $walk->path_coords,
            ];
        })->values()->all();

        return view('livewire.nagoya-map-canvas', [
            'wardsGeoJson' => $wardsGeoJson,
            'churchesData' => $churchesData,
            'linesData' => $linesData,
            'specialSpots' => $specialSpots,
            'prayerWalksData' => $prayerWalksData,
            'selectedChurch' => $selectedChurch,
            'session' => $session,
        ]);
    }
}
