<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ward;
use Illuminate\Support\Collection;

class NagoyaMiniMapService
{
    /**
     * Cached simplified ward polygons and bounding box for the current request.
     *
     * @var array{bounds: array{minLng: float, maxLng: float, minLat: float, maxLat: float}, wards: array<int, array{id: int|null, slug: string|null, name: string, name_ja: string|null, center_lat: float, center_lng: float, rings: array<int, array<int, array{0: float, 1: float}>>}>}|null
     */
    protected static ?array $cachedGeometry = null;

    public function __construct(
        protected NagoyaGeoJsonService $geoJsonService,
    ) {}

    /**
     * Return projected SVG polygon data and centroids for each ward so Blade/Livewire
     * can render an interactive clickable Nagoya ward picker map.
     *
     * @param  Collection<int, Ward>  $wards
     * @return array{
     *     width: int,
     *     height: int,
     *     wards: array<int, array{
     *         id: int,
     *         name: string,
     *         name_ja: string,
     *         slug: string,
     *         openness: int,
     *         poverty: int,
     *         cx: float,
     *         cy: float,
     *         polygons: array<int, string>
     *     }>
     * }
     */
    public function getInteractiveWardMapData(Collection $wards, int $width = 360, int $height = 380, int $padding = 14): array
    {
        $geometry = $this->getSimplifiedGeometry();
        $bounds = $geometry['bounds'];

        $polyBySlug = [];
        $polyByName = [];
        foreach ($geometry['wards'] as $wardPoly) {
            if ($wardPoly['slug']) {
                $polyBySlug[$wardPoly['slug']] = $wardPoly['rings'];
            }
            $polyByName[$wardPoly['name']] = $wardPoly['rings'];
        }

        $items = [];
        foreach ($wards as $ward) {
            $rings = $polyBySlug[$ward->slug] ?? $polyByName[$ward->name] ?? [];

            if ($rings === []) {
                // Fallback diamond around ward center if no GeoJSON polygon matched
                $d = 0.015;
                $lat = (float) $ward->center_lat;
                $lng = (float) $ward->center_lng;
                $rings = [[
                    [$lng - $d, $lat - $d * 0.8],
                    [$lng + $d, $lat - $d * 0.8],
                    [$lng + $d * 1.1, $lat + $d * 0.8],
                    [$lng - $d * 0.8, $lat + $d],
                ]];
            }

            $polygons = [];
            foreach ($rings as $ring) {
                $pts = $this->projectRingToSvgPoints($ring, $bounds, $width, $height, $padding);
                if ($pts !== '') {
                    $polygons[] = $pts;
                }
            }

            [$cx, $cy] = $this->projectPoint((float) $ward->center_lat, (float) $ward->center_lng, $bounds, $width, $height, $padding);

            $items[] = [
                'id' => $ward->id,
                'name' => $ward->name,
                'name_ja' => $ward->name_ja,
                'slug' => $ward->slug,
                'openness' => (int) $ward->spiritual_openness,
                'poverty' => (int) $ward->poverty_need_index,
                'cx' => round($cx, 1),
                'cy' => round($cy, 1),
                'polygons' => $polygons,
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'wards' => $items,
        ];
    }

    /**
     * Project lat/lng markers into the same SVG coordinate space as getInteractiveWardMapData.
     *
     * @param  array<int, array{lat: float, lng: float, label?: string, generation?: int, healthy?: bool}>  $markers
     * @return array<int, array{x: float, y: float, label: string, generation: int, healthy: bool}>
     */
    public function projectMarkers(array $markers, int $width = 360, int $height = 380, int $padding = 14): array
    {
        $geometry = $this->getSimplifiedGeometry();
        $bounds = $geometry['bounds'];
        $projected = [];

        foreach ($markers as $marker) {
            [$x, $y] = $this->projectPoint((float) $marker['lat'], (float) $marker['lng'], $bounds, $width, $height, $padding);
            $projected[] = [
                'x' => round($x, 1),
                'y' => round($y, 1),
                'label' => (string) ($marker['label'] ?? ''),
                'generation' => (int) ($marker['generation'] ?? 1),
                'healthy' => (bool) ($marker['healthy'] ?? false),
            ];
        }

        return $projected;
    }

    /**
     * Render a crisp, self-contained SVG mini-map of Nagoya's 16 wards with highlighted ward(s) and markers.
     *
     * @param  array<int, array{lat: float, lng: float, label?: string, type?: string, color?: string}>  $markers
     * @param  array<int, int>  $highlightWardIds
     */
    public function renderSvg(
        ?Ward $focusWard = null,
        array $markers = [],
        array $highlightWardIds = [],
        string $accentColor = '#10b981',
        int $width = 340,
        int $height = 220,
        ?string $subtitle = null,
    ): string {
        $geometry = $this->getSimplifiedGeometry();
        $bounds = $geometry['bounds'];
        $padding = 16;

        if ($focusWard && ! in_array($focusWard->id, $highlightWardIds, true)) {
            $highlightWardIds[] = $focusWard->id;
        }

        $focusSlug = $focusWard?->slug;
        $focusName = $focusWard?->name;

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" class="w-full h-full select-none" role="img" aria-label="Nagoya location map">',
            $width,
            $height
        );

        // Defs: subtle glow & gradients
        $svg[] = '<defs>';
        $svg[] = '  <linearGradient id="nagoyaMapBg" x1="0" y1="0" x2="1" y2="1">';
        $svg[] = '    <stop offset="0%" stop-color="#0f172a"/>';
        $svg[] = '    <stop offset="100%" stop-color="#020617"/>';
        $svg[] = '  </linearGradient>';
        $svg[] = '  <filter id="wardGlow" x="-20%" y="-20%" width="140%" height="140%">';
        $svg[] = '    <feGaussianBlur stdDeviation="2.5" result="blur"/>';
        $svg[] = '    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>';
        $svg[] = '  </filter>';
        $svg[] = '</defs>';

        // Background
        $svg[] = sprintf('<rect width="%d" height="%d" rx="14" fill="url(#nagoyaMapBg)"/>', $width, $height);

        // Subtle coordinate grid lines
        for ($gx = 40; $gx < $width; $gx += 48) {
            $svg[] = sprintf('<line x1="%d" y1="0" x2="%d" y2="%d" stroke="#1e293b" stroke-width="0.6" stroke-dasharray="3,4"/>', $gx, $gx, $height);
        }
        for ($gy = 36; $gy < $height; $gy += 44) {
            $svg[] = sprintf('<line x1="0" y1="%d" x2="%d" y2="%d" stroke="#1e293b" stroke-width="0.6" stroke-dasharray="3,4"/>', $gy, $width, $gy);
        }

        // Draw non-highlighted wards first, then highlighted wards on top
        $nonHighlighted = [];
        $highlighted = [];

        foreach ($geometry['wards'] as $wardPoly) {
            $isHighlighted = ($wardPoly['id'] !== null && in_array($wardPoly['id'], $highlightWardIds, true))
                || ($focusSlug !== null && $wardPoly['slug'] === $focusSlug)
                || ($focusName !== null && $wardPoly['name'] === $focusName);

            if ($isHighlighted) {
                $highlighted[] = $wardPoly;
            } else {
                $nonHighlighted[] = $wardPoly;
            }
        }

        foreach ($nonHighlighted as $wardPoly) {
            foreach ($wardPoly['rings'] as $ring) {
                $points = $this->projectRingToSvgPoints($ring, $bounds, $width, $height, $padding);
                if ($points !== '') {
                    $svg[] = sprintf(
                        '<polygon points="%s" fill="#1e293b" fill-opacity="0.72" stroke="#334155" stroke-width="0.9" stroke-linejoin="round"/>',
                        $points
                    );
                }
            }
        }

        foreach ($highlighted as $wardPoly) {
            foreach ($wardPoly['rings'] as $ring) {
                $points = $this->projectRingToSvgPoints($ring, $bounds, $width, $height, $padding);
                if ($points !== '') {
                    $svg[] = sprintf(
                        '<polygon points="%s" fill="%s" fill-opacity="0.32" stroke="%s" stroke-width="2" stroke-linejoin="round" filter="url(#wardGlow)"/>',
                        $points,
                        htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }

        // If markers include both a person and a church, draw a subtle relational connection line
        $personMarker = null;
        $churchMarker = null;
        foreach ($markers as $m) {
            if (($m['type'] ?? '') === 'person' && $personMarker === null) {
                $personMarker = $m;
            }
            if (($m['type'] ?? '') === 'church' && $churchMarker === null) {
                $churchMarker = $m;
            }
        }

        if ($personMarker !== null && $churchMarker !== null) {
            [$px, $py] = $this->projectPoint((float) $personMarker['lat'], (float) $personMarker['lng'], $bounds, $width, $height, $padding);
            [$cx, $cy] = $this->projectPoint((float) $churchMarker['lat'], (float) $churchMarker['lng'], $bounds, $width, $height, $padding);
            $svg[] = sprintf(
                '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#a5b4fc" stroke-width="1.5" stroke-dasharray="3,3" stroke-opacity="0.85"/>',
                $px,
                $py,
                $cx,
                $cy
            );
        }

        // If no explicit markers provided, place a ward center marker
        if ($markers === [] && $focusWard !== null) {
            $markers[] = [
                'lat' => (float) $focusWard->center_lat,
                'lng' => (float) $focusWard->center_lng,
                'label' => $focusWard->name,
                'type' => 'ward',
                'color' => $accentColor,
            ];
        }

        // Draw markers
        foreach ($markers as $index => $marker) {
            $lat = (float) $marker['lat'];
            $lng = (float) $marker['lng'];
            $type = $marker['type'] ?? 'ward';
            $color = $marker['color'] ?? match ($type) {
                'church' => '#818cf8',
                'person' => '#34d399',
                default => $accentColor,
            };
            $label = $marker['label'] ?? null;

            [$mx, $my] = $this->projectPoint($lat, $lng, $bounds, $width, $height, $padding);
            $safeColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');

            // Outer pulse ring
            $svg[] = sprintf(
                '<circle cx="%.1f" cy="%.1f" r="10" fill="%s" fill-opacity="0.22" stroke="%s" stroke-width="1" stroke-opacity="0.6"/>',
                $mx,
                $my,
                $safeColor,
                $safeColor
            );

            if ($type === 'church') {
                // Church diamond / house badge
                $svg[] = sprintf(
                    '<circle cx="%.1f" cy="%.1f" r="6" fill="#1e1b4b" stroke="%s" stroke-width="2"/>',
                    $mx,
                    $my,
                    $safeColor
                );
                $svg[] = sprintf(
                    '<path d="M%.1f %.1f v-4 M%.1f %.1f h4" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round"/>',
                    $mx,
                    $my + 2,
                    $mx - 2,
                    $my - 0.3
                );
            } else {
                // Person / Ward pin dot
                $svg[] = sprintf(
                    '<circle cx="%.1f" cy="%.1f" r="5" fill="%s" stroke="#ffffff" stroke-width="1.6"/>',
                    $mx,
                    $my,
                    $safeColor
                );
            }

            if ($label !== null && $label !== '') {
                $escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
                $labelY = $index % 2 === 0 ? max(16.0, $my - 12.0) : min((float) ($height - 10), $my + 18.0);
                $labelX = max(44.0, min((float) ($width - 44), $mx));
                $pillWidth = min(132, max(44, strlen($label) * 5.6 + 14));

                $svg[] = sprintf(
                    '<rect x="%.1f" y="%.1f" width="%.1f" height="15" rx="7.5" fill="#020617" fill-opacity="0.88" stroke="%s" stroke-width="0.8"/>',
                    $labelX - ($pillWidth / 2),
                    $labelY - 10.5,
                    $pillWidth,
                    $safeColor
                );
                $svg[] = sprintf(
                    '<text x="%.1f" y="%.1f" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif" font-size="8.5" font-weight="700" fill="#f8fafc">%s</text>',
                    $labelX,
                    $labelY,
                    $escapedLabel
                );
            }
        }

        // Top-left Nagoya badge
        $titleText = $focusWard ? "{$focusWard->name}" : 'Nagoya • 16 Wards';
        $safeTitle = htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8');
        $badgeWidth = min(170, max(74, strlen($titleText) * 6.2 + 22));
        $svg[] = sprintf('<rect x="10" y="10" width="%.1f" height="20" rx="10" fill="#020617" fill-opacity="0.82" stroke="#334155" stroke-width="0.9"/>', $badgeWidth);
        $svg[] = sprintf('<text x="20" y="23" font-family="ui-sans-serif, system-ui, sans-serif" font-size="9.5" font-weight="800" fill="#f8fafc">📍 %s</text>', $safeTitle);

        if ($subtitle !== null && $subtitle !== '') {
            $safeSub = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
            $svg[] = sprintf(
                '<text x="%d" y="%d" text-anchor="end" font-family="ui-sans-serif, system-ui, sans-serif" font-size="8.5" font-weight="600" fill="#94a3b8">%s</text>',
                $width - 10,
                $height - 10,
                $safeSub
            );
        }

        // Subtle compass indicator (top-right)
        $cx = $width - 20;
        $cy = 20;
        $svg[] = sprintf('<circle cx="%d" cy="%d" r="9" fill="#020617" fill-opacity="0.75" stroke="#334155" stroke-width="0.8"/>', $cx, $cy);
        $svg[] = sprintf('<text x="%d" y="%d" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif" font-size="8" font-weight="800" fill="#94a3b8">N</text>', $cx, $cy + 3);

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Render a crisp PNG mini-map of Nagoya's 16 wards for emails using PHP GD.
     *
     * @param  array<int, array{lat: float, lng: float, label?: string, type?: string, color?: string}>  $markers
     * @param  array<int, int>  $highlightWardIds
     */
    public function renderPng(
        ?Ward $focusWard = null,
        array $markers = [],
        array $highlightWardIds = [],
        string $accentHex = '#10b981',
        int $width = 560,
        int $height = 300,
        ?string $title = null,
    ): string {
        $geometry = $this->getSimplifiedGeometry();
        $bounds = $geometry['bounds'];
        $padding = 24;

        if ($focusWard && ! in_array($focusWard->id, $highlightWardIds, true)) {
            $highlightWardIds[] = $focusWard->id;
        }

        $focusSlug = $focusWard?->slug;
        $focusName = $focusWard?->name;

        $width = max(1, $width);
        $height = max(1, $height);

        $im = imagecreatetruecolor($width, $height);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        if (function_exists('imageantialias')) {
            imageantialias($im, true);
        }

        // Colors
        $bgColor = $this->allocateColor($im, 15, 23, 42);
        $gridColor = $this->allocateColor($im, 30, 41, 59);
        $wardFill = $this->allocateColor($im, 30, 41, 59);
        $wardStroke = $this->allocateColor($im, 71, 85, 105);
        [$ar, $ag, $ab] = $this->hexToRgb($accentHex);
        $accentFill = $this->allocateColor($im, $ar, $ag, $ab, 68);
        $accentStroke = $this->allocateColor($im, $ar, $ag, $ab);
        $white = $this->allocateColor($im, 248, 250, 252);
        $muted = $this->allocateColor($im, 148, 163, 184);
        $badgeBg = $this->allocateColor($im, 2, 6, 23, 20);
        $indigo = $this->allocateColor($im, 129, 140, 248);
        $emerald = $this->allocateColor($im, 52, 211, 153);
        $amber = $this->allocateColor($im, 251, 191, 36);

        imagefilledrectangle($im, 0, 0, $width, $height, $bgColor);

        // Subtle grid
        for ($gx = 48; $gx < $width; $gx += 56) {
            imageline($im, $gx, 0, $gx, $height, $gridColor);
        }
        for ($gy = 40; $gy < $height; $gy += 48) {
            imageline($im, 0, $gy, $width, $gy, $gridColor);
        }

        // Draw wards
        $highlighted = [];
        foreach ($geometry['wards'] as $wardPoly) {
            $isHighlighted = ($wardPoly['id'] !== null && in_array($wardPoly['id'], $highlightWardIds, true))
                || ($focusSlug !== null && $wardPoly['slug'] === $focusSlug)
                || ($focusName !== null && $wardPoly['name'] === $focusName);

            if ($isHighlighted) {
                $highlighted[] = $wardPoly;

                continue;
            }

            foreach ($wardPoly['rings'] as $ring) {
                $flat = $this->projectRingToFlatGdPoints($ring, $bounds, $width, $height, $padding);
                if (count($flat) >= 6) {
                    imagefilledpolygon($im, $flat, $wardFill);
                    imagepolygon($im, $flat, $wardStroke);
                }
            }
        }

        foreach ($highlighted as $wardPoly) {
            foreach ($wardPoly['rings'] as $ring) {
                $flat = $this->projectRingToFlatGdPoints($ring, $bounds, $width, $height, $padding);
                if (count($flat) >= 6) {
                    imagefilledpolygon($im, $flat, $accentFill);
                    imagesetthickness($im, 2);
                    imagepolygon($im, $flat, $accentStroke);
                    imagesetthickness($im, 1);
                }
            }
        }

        if ($markers === [] && $focusWard !== null) {
            $markers[] = [
                'lat' => (float) $focusWard->center_lat,
                'lng' => (float) $focusWard->center_lng,
                'label' => $focusWard->name,
                'type' => 'ward',
            ];
        }

        // Draw markers
        foreach ($markers as $marker) {
            $lat = (float) $marker['lat'];
            $lng = (float) $marker['lng'];
            $type = $marker['type'] ?? 'ward';
            $label = $marker['label'] ?? null;

            [$mx, $my] = $this->projectPoint($lat, $lng, $bounds, $width, $height, $padding);
            $ix = (int) round($mx);
            $iy = (int) round($my);

            $pinColor = match ($type) {
                'church' => $indigo,
                'person' => $emerald,
                'highlight' => $amber,
                default => $accentStroke,
            };

            $haloColor = $this->allocateColor($im, $ar, $ag, $ab, 95);
            imagefilledellipse($im, $ix, $iy, 22, 22, $haloColor);
            imagefilledellipse($im, $ix, $iy, 12, 12, $white);
            imagefilledellipse($im, $ix, $iy, 8, 8, $pinColor);

            if ($label !== null && $label !== '') {
                $cleanLabel = preg_replace('/[^\x20-\x7E]/', '', $label) ?: $label;
                $cleanLabel = substr(trim($cleanLabel), 0, 24);
                if ($cleanLabel !== '') {
                    $textW = strlen($cleanLabel) * 6 + 10;
                    $lx = max(6, min($width - $textW - 6, $ix - (int) ($textW / 2)));
                    $ly = max(28, $iy - 24);
                    imagefilledrectangle($im, $lx, $ly, $lx + $textW, $ly + 16, $badgeBg);
                    imagerectangle($im, $lx, $ly, $lx + $textW, $ly + 16, $pinColor);
                    imagestring($im, 2, $lx + 5, $ly + 2, $cleanLabel, $white);
                }
            }
        }

        // Header badge
        $headerText = $title ?: ($focusWard ? "Nagoya: {$focusWard->name}" : 'Nagoya City • 16 Wards');
        $cleanHeader = preg_replace('/[^\x20-\x7E]/', '', $headerText) ?: 'Nagoya Map';
        $headerW = strlen($cleanHeader) * 7 + 18;
        imagefilledrectangle($im, 12, 10, 12 + $headerW, 32, $badgeBg);
        imagerectangle($im, 12, 10, 12 + $headerW, 32, $wardStroke);
        imagestring($im, 3, 21, 14, $cleanHeader, $white);

        // Footer watermark
        imagestring($im, 2, $width - 148, $height - 18, 'Nagoya Oikos Frontier', $muted);

        ob_start();
        imagepng($im);
        $bytes = (string) ob_get_clean();
        imagedestroy($im);

        return $bytes;
    }

    /**
     * Load and simplify Nagoya's 16-ward polygons once per request.
     *
     * @return array{bounds: array{minLng: float, maxLng: float, minLat: float, maxLat: float}, wards: array<int, array{id: int|null, slug: string|null, name: string, name_ja: string|null, center_lat: float, center_lng: float, rings: array<int, array<int, array{0: float, 1: float}>>}>}
     */
    protected function getSimplifiedGeometry(): array
    {
        if (self::$cachedGeometry !== null) {
            return self::$cachedGeometry;
        }

        /** @var Collection<int, Ward> $wards */
        $wards = Ward::query()->get();
        $featureCollection = $this->geoJsonService->getWardsGeoJson($wards);

        $minLng = 180.0;
        $maxLng = -180.0;
        $minLat = 90.0;
        $maxLat = -90.0;

        $simplifiedWards = [];

        foreach ($featureCollection['features'] as $feature) {
            $geom = $feature['geometry'] ?? null;
            if (! is_array($geom)) {
                continue;
            }

            $rawRings = [];
            if (($geom['type'] ?? '') === 'Polygon') {
                if (isset($geom['coordinates'][0]) && is_array($geom['coordinates'][0])) {
                    $rawRings[] = $geom['coordinates'][0];
                }
            } elseif (($geom['type'] ?? '') === 'MultiPolygon') {
                foreach ($geom['coordinates'] ?? [] as $polygon) {
                    if (isset($polygon[0]) && is_array($polygon[0])) {
                        $rawRings[] = $polygon[0];
                    }
                }
            }

            $rings = [];
            foreach ($rawRings as $rawRing) {
                $simplified = $this->simplifyRing($rawRing, 60);
                if (count($simplified) >= 3) {
                    $rings[] = $simplified;
                    foreach ($simplified as $coord) {
                        $minLng = min($minLng, $coord[0]);
                        $maxLng = max($maxLng, $coord[0]);
                        $minLat = min($minLat, $coord[1]);
                        $maxLat = max($maxLat, $coord[1]);
                    }
                }
            }

            $props = $feature['properties'] ?? [];
            $simplifiedWards[] = [
                'id' => isset($props['id']) ? (int) $props['id'] : null,
                'slug' => isset($props['slug']) ? (string) $props['slug'] : null,
                'name' => (string) ($props['name'] ?? 'Ward'),
                'name_ja' => isset($props['name_ja']) ? (string) $props['name_ja'] : null,
                'center_lat' => ($minLat + $maxLat) / 2,
                'center_lng' => ($minLng + $maxLng) / 2,
                'rings' => $rings,
            ];
        }

        if ($minLng >= $maxLng || $minLat >= $maxLat) {
            $minLng = 136.78;
            $maxLng = 137.06;
            $minLat = 35.03;
            $maxLat = 35.26;
        }

        self::$cachedGeometry = [
            'bounds' => [
                'minLng' => $minLng,
                'maxLng' => $maxLng,
                'minLat' => $minLat,
                'maxLat' => $maxLat,
            ],
            'wards' => $simplifiedWards,
        ];

        return self::$cachedGeometry;
    }

    /**
     * Downsample a GeoJSON coordinate ring to at most $maxPoints vertices.
     *
     * @param  array<int, array<int, float|int>>  $ring
     * @return array<int, array{0: float, 1: float}>
     */
    protected function simplifyRing(array $ring, int $maxPoints = 60): array
    {
        $count = count($ring);
        if ($count === 0) {
            return [];
        }

        $step = max(1, (int) floor($count / $maxPoints));
        $result = [];

        for ($i = 0; $i < $count; $i += $step) {
            if (isset($ring[$i][0], $ring[$i][1])) {
                $result[] = [(float) $ring[$i][0], (float) $ring[$i][1]];
            }
        }

        return $result;
    }

    /**
     * Project lat/lng to 2D canvas coordinates preserving aspect ratio.
     *
     * @param  array{minLng: float, maxLng: float, minLat: float, maxLat: float}  $bounds
     * @return array{0: float, 1: float}
     */
    protected function projectPoint(float $lat, float $lng, array $bounds, int $width, int $height, int $padding): array
    {
        $usableW = max(1.0, (float) ($width - ($padding * 2)));
        $usableH = max(1.0, (float) ($height - ($padding * 2)));

        $lngSpan = max(0.001, $bounds['maxLng'] - $bounds['minLng']);
        $latSpan = max(0.001, $bounds['maxLat'] - $bounds['minLat']);

        // Adjust longitude scale for Nagoya's latitude (~35.17 deg -> cos(35.17) ~ 0.817)
        $cosLat = 0.817;
        $scaledLngSpan = $lngSpan * $cosLat;

        $scale = min($usableW / $scaledLngSpan, $usableH / $latSpan);
        $renderedW = $scaledLngSpan * $scale;
        $renderedH = $latSpan * $scale;

        $offsetX = $padding + (($usableW - $renderedW) / 2);
        $offsetY = $padding + (($usableH - $renderedH) / 2);

        $x = $offsetX + (($lng - $bounds['minLng']) * $cosLat * $scale);
        $y = $offsetY + (($bounds['maxLat'] - $lat) * $scale);

        return [$x, $y];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     * @param  array{minLng: float, maxLng: float, minLat: float, maxLat: float}  $bounds
     */
    protected function projectRingToSvgPoints(array $ring, array $bounds, int $width, int $height, int $padding): string
    {
        $pts = [];
        foreach ($ring as $coord) {
            [$x, $y] = $this->projectPoint($coord[1], $coord[0], $bounds, $width, $height, $padding);
            $pts[] = sprintf('%.1f,%.1f', $x, $y);
        }

        return implode(' ', $pts);
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     * @param  array{minLng: float, maxLng: float, minLat: float, maxLat: float}  $bounds
     * @return array<int, int>
     */
    protected function projectRingToFlatGdPoints(array $ring, array $bounds, int $width, int $height, int $padding): array
    {
        $flat = [];
        foreach ($ring as $coord) {
            [$x, $y] = $this->projectPoint($coord[1], $coord[0], $bounds, $width, $height, $padding);
            $flat[] = (int) round($x);
            $flat[] = (int) round($y);
        }

        return $flat;
    }

    /**
     * Allocate a clamped GD color safely.
     */
    protected function allocateColor(\GdImage $im, int $r, int $g, int $b, int $alpha = 0): int
    {
        $cr = max(0, min(255, $r));
        $cg = max(0, min(255, $g));
        $cb = max(0, min(255, $b));
        $ca = max(0, min(127, $alpha));

        $color = $ca > 0
            ? imagecolorallocatealpha($im, $cr, $cg, $cb, $ca)
            : imagecolorallocate($im, $cr, $cg, $cb);

        return $color === false ? 0 : $color;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function hexToRgb(string $hex): array
    {
        $clean = ltrim($hex, '#');
        if (strlen($clean) !== 6) {
            return [16, 185, 129];
        }

        return [
            (int) hexdec(substr($clean, 0, 2)),
            (int) hexdec(substr($clean, 2, 2)),
            (int) hexdec(substr($clean, 4, 2)),
        ];
    }
}
