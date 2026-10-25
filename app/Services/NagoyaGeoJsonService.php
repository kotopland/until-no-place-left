<?php

namespace App\Services;

use App\Models\Ward;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class NagoyaGeoJsonService
{
    /**
     * Official Japanese Ministry of Land, Infrastructure, Transport and Tourism (MLIT)
     * National Land Numerical Information (N03_004) to Ward Slug & English Name mapping.
     *
     * @var array<string, array{slug: string, name: string}>
     */
    public const WARD_MAPPING = [
        '千種区' => ['slug' => 'chikusa-ku', 'name' => 'Chikusa-ku'],
        '東区' => ['slug' => 'higashi-ku', 'name' => 'Higashi-ku'],
        '北区' => ['slug' => 'kita-ku', 'name' => 'Kita-ku'],
        '西区' => ['slug' => 'nishi-ku', 'name' => 'Nishi-ku'],
        '中村区' => ['slug' => 'nakamura-ku', 'name' => 'Nakamura-ku'],
        '中区' => ['slug' => 'naka-ku', 'name' => 'Naka-ku'],
        '昭和区' => ['slug' => 'showa-ku', 'name' => 'Showa-ku'],
        '瑞穂区' => ['slug' => 'mizuho-ku', 'name' => 'Mizuho-ku'],
        '熱田区' => ['slug' => 'atsuta-ku', 'name' => 'Atsuta-ku'],
        '中川区' => ['slug' => 'nakagawa-ku', 'name' => 'Nakagawa-ku'],
        '港区' => ['slug' => 'minato-ku', 'name' => 'Minato-ku'],
        '南区' => ['slug' => 'minami-ku', 'name' => 'Minami-ku'],
        '守山区' => ['slug' => 'moriyama-ku', 'name' => 'Moriyama-ku'],
        '緑区' => ['slug' => 'midori-ku', 'name' => 'Midori-ku'],
        '名東区' => ['slug' => 'meito-ku', 'name' => 'Meito-ku'],
        '天白区' => ['slug' => 'tempaku-ku', 'name' => 'Tempaku-ku'],
    ];

    /**
     * Source paths to search for authentic 16-ward GeoJSON boundaries.
     *
     * @var array<int, string>
     */
    protected array $sourceCandidates = [
        '/Users/ko/code/development/praystation/public/geojson/nagoya-border.json',
    ];

    /**
     * Get or build the rich GeoJSON FeatureCollection for Nagoya's 16 wards.
     *
     * @param  Collection<int, Ward>  $wards
     * @return array{type: string, features: array<int, array<string, mixed>>}
     */
    public function getWardsGeoJson(Collection $wards): array
    {
        $targetFile = storage_path('app/nagoya-border.json');
        $geoData = $this->loadOrImportWardGeoJson($targetFile);

        if ($geoData && ! empty($geoData['features'])) {
            $wardsBySlug = $wards->keyBy('slug');
            $wardsByNameJa = $wards->keyBy('name_ja');

            $features = [];
            foreach ($geoData['features'] as $feature) {
                $nameJa = $feature['properties']['name_ja'] ?? $feature['properties']['N03_004'] ?? null;
                $slug = $feature['properties']['slug'] ?? (isset(self::WARD_MAPPING[$nameJa]) ? self::WARD_MAPPING[$nameJa]['slug'] : null);

                $ward = ($slug ? $wardsBySlug->get($slug) : null) ?? ($nameJa ? $wardsByNameJa->get($nameJa) : null);

                if ($ward) {
                    $feature['id'] = $ward->id;
                    $feature['properties']['id'] = $ward->id;
                    $feature['properties']['name'] = $ward->name;
                    $feature['properties']['name_ja'] = $ward->name_ja;
                    $feature['properties']['slug'] = $ward->slug;
                    $feature['properties']['openness'] = $ward->spiritual_openness;
                    $feature['properties']['poverty'] = $ward->poverty_need_index;
                    $feature['properties']['population'] = $ward->population;
                    $feature['properties']['npcs_count'] = $ward->npcs_count;
                    $feature['properties']['churches_count'] = $ward->churches_count;
                } else {
                    $feature['properties']['name'] = $feature['properties']['name'] ?? (isset(self::WARD_MAPPING[$nameJa]) ? self::WARD_MAPPING[$nameJa]['name'] : ($nameJa ?: 'Ward'));
                    $feature['properties']['name_ja'] = $nameJa;
                    $feature['properties']['slug'] = $slug;
                    $feature['properties']['openness'] = 20;
                    $feature['properties']['poverty'] = 20;
                    $feature['properties']['population'] = 100000;
                    $feature['properties']['npcs_count'] = 0;
                    $feature['properties']['churches_count'] = 0;
                }

                $features[] = $feature;
            }

            if (count($features) >= 16) {
                return [
                    'type' => 'FeatureCollection',
                    'features' => $features,
                ];
            }
        }

        // Fallback: Generate approximate polygonal bounds if geojson is unavailable
        return [
            'type' => 'FeatureCollection',
            'features' => $wards->map(function (Ward $ward): array {
                $lat = (float) $ward->center_lat;
                $lng = (float) $ward->center_lng;
                $d = 0.016;

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
    }

    /**
     * Load existing GeoJSON or automatically import from source files with MLIT ward tags.
     *
     * @param  string  $targetFile
     * @return array<string, mixed>|null
     */
    public function loadOrImportWardGeoJson(string $targetFile): ?array
    {
        // 1. Check if targetFile already exists and contains all 16 ward features
        if (File::exists($targetFile)) {
            $content = File::get($targetFile);
            $decoded = json_decode($content, true);

            if (is_array($decoded) && isset($decoded['features']) && count($decoded['features']) >= 16) {
                return $decoded;
            }
        }

        // 2. Try importing from candidates
        foreach ($this->sourceCandidates as $candidatePath) {
            if (File::exists($candidatePath)) {
                $imported = $this->importFromSource($candidatePath, $targetFile);
                if ($imported) {
                    return $imported;
                }
            }
        }

        return null;
    }

    /**
     * Import and normalize raw MLIT GeoJSON into standard ward features.
     *
     * @param  string  $sourcePath
     * @param  string|null  $destinationPath
     * @return array<string, mixed>|null
     */
    public function importFromSource(string $sourcePath, ?string $destinationPath = null): ?array
    {
        if (! File::exists($sourcePath)) {
            return null;
        }

        $raw = File::get($sourcePath);
        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['features'])) {
            return null;
        }

        $normalizedFeatures = [];

        foreach ($data['features'] as $feature) {
            $props = $feature['properties'] ?? [];
            $nameJa = $props['N03_004'] ?? $props['name_ja'] ?? null;

            if ($nameJa && isset(self::WARD_MAPPING[$nameJa])) {
                $mapping = self::WARD_MAPPING[$nameJa];
                $props['name'] = $mapping['name'];
                $props['slug'] = $mapping['slug'];
                $props['name_ja'] = $nameJa;
            }

            $feature['properties'] = $props;
            $normalizedFeatures[] = $feature;
        }

        $result = [
            'type' => 'FeatureCollection',
            'features' => $normalizedFeatures,
        ];

        if ($destinationPath && count($normalizedFeatures) >= 16) {
            File::ensureDirectoryExists(dirname($destinationPath));
            File::put($destinationPath, json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }
}
