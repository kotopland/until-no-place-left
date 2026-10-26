<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Npc;
use App\Models\User;

class PersonAvatarService
{
    /**
     * Resolves the country flag emoji for a given nationality name.
     */
    public function flagEmoji(string $nationality): string
    {
        return match (strtolower(trim($nationality))) {
            'japanese', 'japan', 'jp', 'nihon' => '🇯🇵',
            'vietnamese', 'vietnam', 'vn' => '🇻🇳',
            'brazilian', 'brazil', 'br' => '🇧🇷',
            'american', 'united states', 'usa', 'us' => '🇺🇸',
            'filipino', 'philippines', 'ph' => '🇵🇭',
            'chinese', 'china', 'cn' => '🇨🇳',
            'korean', 'south korea', 'korea', 'kr' => '🇰🇷',
            'canadian', 'canada', 'ca' => '🇨🇦',
            'british', 'united kingdom', 'uk', 'gb' => '🇬🇧',
            'australian', 'australia', 'au' => '🇦🇺',
            'taiwanese', 'taiwan', 'tw' => '🇹🇼',
            'indonesian', 'indonesia', 'id' => '🇮🇩',
            'peruvian', 'peru', 'pe' => '🇵🇪',
            default => '🌐',
        };
    }

    /**
     * Resolves the ISO 2-letter country code for a nationality.
     */
    public function countryCode(string $nationality): string
    {
        return match (strtolower(trim($nationality))) {
            'japanese', 'japan', 'jp', 'nihon' => 'JP',
            'vietnamese', 'vietnam', 'vn' => 'VN',
            'brazilian', 'brazil', 'br' => 'BR',
            'american', 'united states', 'usa', 'us' => 'US',
            'filipino', 'philippines', 'ph' => 'PH',
            'chinese', 'china', 'cn' => 'CN',
            'korean', 'south korea', 'korea', 'kr' => 'KR',
            'canadian', 'canada', 'ca' => 'CA',
            'british', 'united kingdom', 'uk', 'gb' => 'GB',
            'australian', 'australia', 'au' => 'AU',
            'taiwanese', 'taiwan', 'tw' => 'TW',
            'indonesian', 'indonesia', 'id' => 'ID',
            'peruvian', 'peru', 'pe' => 'PE',
            default => 'UN',
        };
    }

    /**
     * Resolves gender icon (♂ or ♀).
     */
    public function genderIcon(string $gender): string
    {
        return match (strtolower(trim($gender))) {
            'female', 'f', 'woman', 'girl' => '♀',
            default => '♂',
        };
    }

    /**
     * Resolves age bracket key.
     */
    public function ageGroup(int $age): string
    {
        return match (true) {
            $age < 25 => 'youth',
            $age < 50 => 'adult',
            $age < 65 => 'mature',
            default => 'senior',
        };
    }

    /**
     * Resolves human-readable age group label.
     */
    public function ageGroupLabel(int $age): string
    {
        return match (true) {
            $age < 25 => 'Youth (18-24)',
            $age < 50 => 'Adult (25-49)',
            $age < 65 => 'Mature (50-64)',
            default => 'Senior (65+)',
        };
    }

    /**
     * Generates a richly styled SVG avatar representing nationality, gender, and age.
     */
    public function generateSvg(
        string $gender = 'male',
        int $age = 30,
        string $nationality = 'Japanese',
        ?string $role = null,
        ?string $status = null,
        int $pixelSize = 64,
        bool $includeBadges = false,
        ?string $seed = null
    ): string {
        $isFemale = in_array(strtolower(trim($gender)), ['female', 'f', 'woman', 'girl'], true);
        $ageGroup = $this->ageGroup($age);
        $flag = $this->flagEmoji($nationality);
        $genderSymbol = $this->genderIcon($gender);
        $natLower = strtolower(trim($nationality));

        // Skin tones tailored by nationality & demographic
        $skinBase = match ($natLower) {
            'vietnamese', 'vietnam', 'vn' => '#fde047', // Warm golden
            'brazilian', 'brazil', 'br' => '#f59e0b', // Warm honey caramel
            'american', 'united states', 'usa', 'us' => '#fed7aa', // Light peach
            'filipino', 'philippines', 'ph' => '#f59e0b', // Warm golden-tan
            'chinese', 'china', 'cn' => '#fef08a', // Fair warm
            'korean', 'south korea', 'kr' => '#fef9c3', // Pale ivory
            default => '#fde68a', // Japanese soft warm tone
        };
        $skinShadow = match ($natLower) {
            'brazilian', 'brazil', 'br', 'filipino', 'philippines' => '#d97706',
            'american', 'usa' => '#fdba74',
            default => '#fcd34d',
        };

        // Hair colors based on age & nationality
        $hairColor = match ($ageGroup) {
            'senior' => '#e2e8f0', // Silver / white
            'mature' => '#64748b', // Salt and pepper slate
            default => match ($natLower) {
                'american', 'usa' => '#b45309', // Chestnut / light brown
                default => '#1e1b4b', // Deep midnight black/espresso
            },
        };
        $hairHighlight = match ($ageGroup) {
            'senior' => '#ffffff',
            'mature' => '#94a3b8',
            default => match ($natLower) {
                'american', 'usa' => '#d97706',
                default => '#312e81',
            },
        };

        // Background theme gradient based on status / role
        [$bgGradStart, $bgGradEnd, $ringColor] = match (strtolower((string) $status)) {
            'green', 'saved', 'baptized' => ['#064e3b', '#022c22', '#10b981'],
            'yellow' => ['#78350f', '#451a03', '#f59e0b'],
            'red' => ['#4c0519', '#1e1b4b', '#f43f5e'],
            default => ['#1e1b4b', '#0f172a', '#6366f1'],
        };

        // Clothing colors based on role & gender
        [$clothMain, $clothCollar] = match (strtolower((string) $role)) {
            'salaryman' => ['#1e293b', '#ffffff'], // Charcoal suit & crisp shirt
            'shopkeeper' => ['#0f766e', '#fef3c7'], // Teal shopkeeper apron
            'elderlyresident', 'elderly' => ['#475569', '#cbd5e1'], // Comfortable slate cardigan
            'universitystudent', 'student' => ['#4338ca', '#e0e7ff'], // Indigo student hoodie
            'internationalexpat', 'expat' => ['#0369a1', '#e0f2fe'], // Sky blue casual jacket
            'singleparent' => ['#be185d', '#fce7f3'], // Berry rose cardigan
            'homelessindividual' => ['#52525b', '#71717a'], // Layered neutral coat
            default => $isFemale ? ['#7c3aed', '#ede9fe'] : ['#2563eb', '#dbeafe'],
        };

        $idPrefix = 'av_'.substr(md5(($seed ?? $nationality).$gender.$age.$role), 0, 8);

        // SVG building blocks
        $svg = [];
        $svg[] = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\" width=\"{$pixelSize}\" height=\"{$pixelSize}\" class=\"inline-block select-none overflow-visible\">";

        // Defs (gradients and filters)
        $svg[] = '  <defs>';
        $svg[] = "    <radialGradient id=\"{$idPrefix}_bg\" cx=\"50%\" cy=\"35%\" r=\"65%\">";
        $svg[] = "      <stop offset=\"0%\" stop-color=\"{$bgGradStart}\" />";
        $svg[] = "      <stop offset=\"100%\" stop-color=\"{$bgGradEnd}\" />";
        $svg[] = '    </radialGradient>';
        $svg[] = "    <linearGradient id=\"{$idPrefix}_skin\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\">";
        $svg[] = "      <stop offset=\"0%\" stop-color=\"{$skinBase}\" />";
        $svg[] = "      <stop offset=\"100%\" stop-color=\"{$skinShadow}\" />";
        $svg[] = '    </linearGradient>';
        $svg[] = "    <linearGradient id=\"{$idPrefix}_hair\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\">";
        $svg[] = "      <stop offset=\"0%\" stop-color=\"{$hairHighlight}\" />";
        $svg[] = "      <stop offset=\"100%\" stop-color=\"{$hairColor}\" />";
        $svg[] = '    </linearGradient>';
        $svg[] = "    <filter id=\"{$idPrefix}_shadow\" x=\"-20%\" y=\"-20%\" width=\"140%\" height=\"140%\">";
        $svg[] = '      <feDropShadow dx=\"0\" dy=\"2\" stdDeviation=\"2\" flood-color=\"#000000\" flood-opacity=\"0.4\" />';
        $svg[] = '    </filter>';
        $svg[] = '  </defs>';

        // Background Avatar Canvas with Ring
        $svg[] = "  <rect x=\"2\" y=\"2\" width=\"96\" height=\"96\" rx=\"24\" fill=\"url(#{$idPrefix}_bg)\" stroke=\"{$ringColor}\" stroke-width=\"2\" />";

        // Back Hair (for females with longer hair or ponytail)
        if ($isFemale) {
            if ($ageGroup === 'youth') {
                // High bouncy ponytail behind head
                $svg[] = "  <path d=\"M68 28 C85 20 88 45 74 52 C70 42 66 32 68 28 Z\" fill=\"url(#{$idPrefix}_hair)}\" />";
                $svg[] = '  <circle cx="67" cy="30" r="3.5" fill="#f43f5e" />'; // Hair ribbon
            } elseif ($ageGroup === 'senior') {
                // Elegant silver bun behind head
                $svg[] = "  <circle cx=\"50\" cy=\"18\" r=\"11\" fill=\"url(#{$idPrefix}_hair)}\" />";
                $svg[] = '  <path d="M44 18 Q50 14 56 18" stroke="#cbd5e1" stroke-width="1.5" fill="none" />';
            } else {
                // Flowing hair behind shoulders
                $svg[] = "  <path d=\"M22 45 C20 65 24 82 32 88 L68 88 C76 82 80 65 78 45 Z\" fill=\"url(#{$idPrefix}_hair)}\" />";
            }
        }

        // Torso / Shoulders & Attire
        $svg[] = "  <path d=\"M16 98 C16 78 30 74 50 74 C70 74 84 78 84 98 Z\" fill=\"{$clothMain}\" filter=\"url(#{$idPrefix}_shadow)\" />";

        // Collar & Accessories
        if (strtolower((string) $role) === 'salaryman') {
            // White V-neck collar + tie
            $svg[] = "  <path d=\"M42 74 L50 86 L58 74 Z\" fill=\"{$clothCollar}\" />";
            $svg[] = '  <path d="M48 84 L52 84 L53 98 L47 98 Z" fill="#dc2626" />'; // Red corporate tie
        } elseif (strtolower((string) $role) === 'shopkeeper') {
            // Apron straps
            $svg[] = '  <path d="M34 74 L42 98 L46 98 L38 74 Z" fill="#115e59" />';
            $svg[] = '  <path d="M66 74 L58 98 L54 98 L62 74 Z" fill="#115e59" />';
        } elseif ($ageGroup === 'senior') {
            // Warm cardigan buttoned collar
            $svg[] = "  <path d=\"M44 74 L50 84 L56 74 Z\" fill=\"{$clothCollar}\" />";
            $svg[] = '  <circle cx="50" cy="89" r="1.5" fill="#94a3b8" />';
            $svg[] = '  <circle cx="50" cy="95" r="1.5" fill="#94a3b8" />';
        } else {
            // Casual round / V collar
            $svg[] = "  <path d=\"M43 74 Q50 82 57 74 Z\" fill=\"{$clothCollar}\" />";
        }

        // Neck
        $svg[] = "  <rect x=\"44\" y=\"58\" width=\"12\" height=\"18\" rx=\"3\" fill=\"{$skinShadow}\" />";

        // Head Shape
        if ($isFemale) {
            // Softer feminine chin
            $svg[] = "  <path d=\"M31 40 C31 24 69 24 69 40 C69 58 58 68 50 68 C42 68 31 58 31 40 Z\" fill=\"url(#{$idPrefix}_skin)\" filter=\"url(#{$idPrefix}_shadow)\" />";
        } else {
            // Structured masculine chin
            $svg[] = "  <path d=\"M29 40 C29 24 71 24 71 40 C71 60 59 70 50 70 C41 70 29 60 29 40 Z\" fill=\"url(#{$idPrefix}_skin)\" filter=\"url(#{$idPrefix}_shadow)\" />";
        }

        // Ears
        $svg[] = "  <circle cx=\"28\" cy=\"46\" r=\"4.5\" fill=\"url(#{$idPrefix}_skin)\" />";
        $svg[] = "  <circle cx=\"72\" cy=\"46\" r=\"4.5\" fill=\"url(#{$idPrefix}_skin)\" />";

        // Cheeks / Blush
        if ($isFemale || $ageGroup === 'youth') {
            $svg[] = '  <circle cx="37" cy="52" r="4" fill="#f43f5e" opacity="0.22" />';
            $svg[] = '  <circle cx="63" cy="52" r="4" fill="#f43f5e" opacity="0.22" />';
        }

        // Senior Expression Lines
        if ($ageGroup === 'senior') {
            $svg[] = "  <path d=\"M33 46 Q30 47 33 49\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M67 46 Q70 47 67 49\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M41 57 Q43 61 46 62\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M59 57 Q57 61 54 62\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
        } elseif ($ageGroup === 'mature') {
            $svg[] = "  <path d=\"M43 59 Q45 62 47 62\" stroke=\"{$skinShadow}\" stroke-width=\"1\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M57 59 Q55 62 53 62\" stroke=\"{$skinShadow}\" stroke-width=\"1\" fill=\"none\" stroke-linecap=\"round\" />";
        }

        // Eyes
        $eyeY = $isFemale ? 45 : 46;
        $svg[] = "  <ellipse cx=\"40\" cy=\"{$eyeY}\" rx=\"3.2\" ry=\"3.5\" fill=\"#0f172a\" />";
        $svg[] = '  <circle cx="41.2" cy="'.($eyeY - 1.2).'" r="1.2" fill="#ffffff" />';
        $svg[] = "  <ellipse cx=\"60\" cy=\"{$eyeY}\" rx=\"3.2\" ry=\"3.5\" fill=\"#0f172a\" />";
        $svg[] = '  <circle cx="61.2" cy="'.($eyeY - 1.2).'" r="1.2" fill="#ffffff" />';

        // Female Eyelashes
        if ($isFemale) {
            $svg[] = '  <path d="M36 43 Q40 40 44 43" stroke="#0f172a" stroke-width="1.5" fill="none" stroke-linecap="round" />';
            $svg[] = '  <path d="M56 43 Q60 40 64 43" stroke="#0f172a" stroke-width="1.5" fill="none" stroke-linecap="round" />';
        }

        // Eyebrows
        $browColor = ($ageGroup === 'senior') ? '#94a3b8' : (($ageGroup === 'mature') ? '#475569' : '#1e1b4b');
        $browWidth = $isFemale ? '1.5' : '2.4';
        $svg[] = "  <path d=\"M36 39 Q41 37 45 40\" stroke=\"{$browColor}\" stroke-width=\"{$browWidth}\" fill=\"none\" stroke-linecap=\"round\" />";
        $svg[] = "  <path d=\"M64 39 Q59 37 55 40\" stroke=\"{$browColor}\" stroke-width=\"{$browWidth}\" fill=\"none\" stroke-linecap=\"round\" />";

        // Nose
        $svg[] = "  <path d=\"M50 46 L48.5 52 L51.5 52\" stroke=\"{$skinShadow}\" stroke-width=\"1.4\" fill=\"none\" stroke-linecap=\"round\" stroke-linejoin=\"round\" />";

        // Mouth (Pleasant warm smile)
        $mouthColor = $isFemale ? '#e11d48' : '#991b1b';
        $mouthWidth = $isFemale ? '2.2' : '1.8';
        $svg[] = "  <path d=\"M44 58 Q50 63 56 58\" stroke=\"{$mouthColor}\" stroke-width=\"{$mouthWidth}\" fill=\"none\" stroke-linecap=\"round\" />";

        // Glasses for Senior or IT lead (Hiroshi)
        $hasGlasses = ($ageGroup === 'senior') || (strtolower((string) $role) === 'salaryman' && $age > 33);
        if ($hasGlasses) {
            $glassesFrame = ($ageGroup === 'senior') ? '#d97706' : '#94a3b8'; // Gold or silver frame
            $svg[] = "  <circle cx=\"40\" cy=\"{$eyeY}\" r=\"6.5\" fill=\"none\" stroke=\"{$glassesFrame}\" stroke-width=\"1.4\" />";
            $svg[] = "  <circle cx=\"60\" cy=\"{$eyeY}\" r=\"6.5\" fill=\"none\" stroke=\"{$glassesFrame}\" stroke-width=\"1.4\" />";
            $svg[] = "  <line x1=\"46.5\" y1=\"{$eyeY}\" x2=\"53.5\" y2=\"{$eyeY}\" stroke=\"{$glassesFrame}\" stroke-width=\"1.4\" />";
            $svg[] = "  <path d=\"M33.5 {$eyeY} L28 ".($eyeY - 1)."\" stroke=\"{$glassesFrame}\" stroke-width=\"1.2\" />";
            $svg[] = "  <path d=\"M66.5 {$eyeY} L72 ".($eyeY - 1)."\" stroke=\"{$glassesFrame}\" stroke-width=\"1.2\" />";
            // Subtle glass shine
            $svg[] = '  <path d="M38 '.($eyeY - 3).' L43 '.($eyeY + 2).'" stroke="#ffffff" stroke-width="1" stroke-linecap="round" opacity="0.6" />';
            $svg[] = '  <path d="M58 '.($eyeY - 3).' L63 '.($eyeY + 2).'" stroke="#ffffff" stroke-width="1" stroke-linecap="round" opacity="0.6" />';
        }

        // Front Hair
        if ($isFemale) {
            if ($ageGroup === 'senior') {
                // Soft wavy silver fringe
                $svg[] = "  <path d=\"M29 36 C34 22 66 22 71 36 C64 28 56 26 50 30 C44 26 36 28 29 36 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } elseif ($ageGroup === 'youth') {
                // Trendy modern bangs & side locks
                $svg[] = "  <path d=\"M28 42 C26 30 36 22 50 22 C64 22 74 30 72 42 C68 34 62 30 54 32 C48 30 42 32 36 34 C32 36 29 40 28 42 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M28 40 L27 52 Q30 50 31 42 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M72 40 L73 52 Q70 50 69 42 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } else {
                // Shoulder-length stylish side parted bob
                $svg[] = "  <path d=\"M28 42 C26 28 36 21 50 21 C64 21 74 28 72 42 C68 32 60 27 50 28 C40 27 32 32 28 42 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M27 40 C25 48 26 60 30 64 C33 58 32 46 32 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M73 40 C75 48 74 60 70 64 C67 58 68 46 68 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            }
        } else {
            // Male hair
            if ($ageGroup === 'senior') {
                // Distinguished silver thinning side hair
                $svg[] = "  <path d=\"M28 40 C28 30 33 28 36 32 C34 36 33 42 34 46 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M72 40 C72 30 67 28 64 32 C66 36 67 42 66 46 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M36 26 C42 24 58 24 64 26 C60 25 50 25 40 26 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } elseif ($ageGroup === 'youth') {
                // Modern textured/spiky student hair
                $svg[] = "  <path d=\"M27 40 C26 28 34 20 50 20 C66 20 74 28 73 40 C70 30 64 25 56 26 C53 23 48 22 44 26 C38 24 32 28 27 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M46 17 L52 23 L44 24 Z\" fill=\"url(#{$idPrefix}_hair)\" />"; // Dynamic spike
                $svg[] = "  <path d=\"M54 18 L58 24 L52 25 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } else {
                // Crisp side-parted salaryman/professional hair
                $svg[] = "  <path d=\"M27 40 C26 26 36 21 50 21 C65 21 74 26 73 40 C69 30 61 26 50 27 C42 25 34 28 27 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M35 24 Q48 22 62 26\" stroke=\"{$hairHighlight}\" stroke-width=\"1.5\" fill=\"none\" />";
            }
        }

        // Integrated Badges (if includeBadges is true)
        if ($includeBadges) {
            // Flag Badge Top-Right
            $svg[] = '  <g transform="translate(70, 6)">';
            $svg[] = '    <circle cx=\"12\" cy=\"12\" r=\"11\" fill=\"#0f172a\" stroke=\"#334155\" stroke-width=\"1.5\" />';
            $svg[] = "    <text x=\"12\" y=\"16\" font-size=\"13\" text-anchor=\"middle\">{$flag}</text>";
            $svg[] = '  </g>';

            // Gender & Age Pill Bottom-Center
            $svg[] = '  <g transform="translate(18, 79)">';
            $svg[] = '    <rect x=\"0\" y=\"0\" width=\"64\" height=\"17\" rx=\"8.5\" fill=\"#0f172a\" fill-opacity=\"0.95\" stroke=\"#334155\" stroke-width=\"1.2\" />';
            $badgeColor = $isFemale ? '#f472b6' : '#60a5fa';
            $svg[] = '    <text x="32" y="12.5" font-size="10" font-family="ui-sans-serif, system-ui, sans-serif" font-weight="bold" fill="#f8fafc" text-anchor="middle">';
            $svg[] = "      <tspan fill=\"{$badgeColor}\">{$genderSymbol}</tspan> {$age}y";
            $svg[] = '    </text>';
            $svg[] = '  </g>';
        }

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Generates a base64 data URI string for the avatar SVG.
     */
    public function generateDataUri(
        string $gender = 'male',
        int $age = 30,
        string $nationality = 'Japanese',
        ?string $role = null,
        ?string $status = null,
        int $pixelSize = 64,
        bool $includeBadges = false,
        ?string $seed = null
    ): string {
        $svg = $this->generateSvg($gender, $age, $nationality, $role, $status, $pixelSize, $includeBadges, $seed);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Helper to render avatar for an Npc or User model.
     */
    public function forPerson(Npc|User $person, int $pixelSize = 64, bool $includeBadges = false): string
    {
        $gender = $person->gender ?? 'male';
        $age = $person->age ?? 30;
        $nationality = $person->nationality ?? 'Japanese';
        $role = $person instanceof Npc ? ($person->role_archetype?->value ?? null) : ($person->colaborer_archetype ?? 'missionary');
        $status = $person instanceof Npc ? ($person->status?->value ?? null) : 'green';
        $seed = $person->name ?? (string) $person->id;

        return $this->generateSvg($gender, $age, $nationality, $role, $status, $pixelSize, $includeBadges, $seed);
    }
}
