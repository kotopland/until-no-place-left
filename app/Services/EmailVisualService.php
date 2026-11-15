<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\HtmlString;

class EmailVisualService
{
    /**
     * Generate a public URL for a person's PNG avatar (for email <img> tags).
     */
    public function avatarUrlForPerson(Npc|User $person, int $size = 128): string
    {
        $gender = $person->gender ?: 'male';
        $age = (int) ($person->age ?: 30);
        $nationality = $person->nationality ?: 'Japanese';
        $role = $person instanceof Npc
            ? $person->role_archetype->value
            : ($person->colaborer_archetype ?? 'missionary');
        $status = $person instanceof Npc ? $person->status->value : 'green';
        $seed = $person->name ?: (string) $person->id;

        return $this->avatarUrl($gender, $age, $nationality, $role, $status, $size, $seed);
    }

    /**
     * Generate a public URL for a PNG avatar from explicit attributes.
     */
    public function avatarUrl(
        string $gender = 'male',
        int $age = 30,
        string $nationality = 'Japanese',
        ?string $role = null,
        ?string $status = null,
        int $size = 128,
        ?string $seed = null,
    ): string {
        return route('media.avatar', array_filter([
            'gender' => $gender,
            'age' => $age,
            'nationality' => $nationality,
            'role' => $role,
            'status' => $status,
            'size' => $size,
            'seed' => $seed,
        ], fn (mixed $v): bool => $v !== null && $v !== ''));
    }

    /**
     * Generate a public URL for a Nagoya ward PNG map (for email <img> tags).
     *
     * @param  array<int, array{lat: float, lng: float, label?: string, type?: string}>  $markers
     * @param  array<int, int>  $highlightWardIds
     */
    public function wardMapUrl(
        ?Ward $focusWard = null,
        array $markers = [],
        array $highlightWardIds = [],
        string $accentHex = '#10b981',
        ?string $title = null,
    ): string {
        $params = [
            'accent' => ltrim($accentHex, '#'),
        ];

        if ($focusWard) {
            $params['ward_id'] = $focusWard->id;
        }

        if ($highlightWardIds !== []) {
            $params['highlight'] = implode(',', array_unique($highlightWardIds));
        }

        if ($markers !== []) {
            $params['m'] = base64_encode((string) json_encode(array_slice($markers, 0, 8)));
        }

        if ($title !== null && $title !== '') {
            $params['title'] = $title;
        }

        return route('media.ward-map', $params);
    }

    /**
     * Render an email-safe HTML card showing big avatars of people involved.
     *
     * @param  array<int, array{name: string, subtitle?: string, badge?: string, avatarUrl: string}>  $people
     */
    public function renderPeopleAvatarsHtml(array $people, string $heading = 'People & Laborers Involved'): HtmlString
    {
        if ($people === []) {
            return new HtmlString('');
        }

        $cells = [];
        foreach (array_slice($people, 0, 4) as $person) {
            $name = e($person['name']);
            $subtitle = ! empty($person['subtitle']) ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">'.e($person['subtitle']).'</div>' : '';
            $badge = ! empty($person['badge']) ? '<div style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:10px;font-weight:700;">'.e($person['badge']).'</div>' : '';
            $url = e($person['avatarUrl']);

            $cells[] = '<td align="center" valign="top" style="padding:8px 10px;width:130px;">'
                .'<img src="'.$url.'" alt="'.$name.'" width="88" height="88" style="display:block;width:88px;height:88px;border-radius:18px;border:3px solid #6366f1;background:#0f172a;margin:0 auto 8px auto;" />'
                .'<div style="font-size:13px;font-weight:800;color:#0f172a;line-height:1.25;">'.$name.'</div>'
                .$subtitle
                .$badge
                .'</td>';
        }

        $cellsHtml = implode('', $cells);
        $safeHeading = e($heading);

        $html = '<div style="margin:18px 0;padding:14px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;">'
            .'<div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#475569;text-align:center;margin-bottom:8px;">'
            .'👥 '.$safeHeading
            .'</div>'
            .'<table align="center" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;border-collapse:collapse;">'
            .'<tr>'.$cellsHtml.'</tr>'
            .'</table>'
            .'</div>';

        return new HtmlString($html);
    }

    /**
     * Render an email-safe HTML card embedding a Nagoya location map PNG.
     *
     * @param  array<int, array{lat: float, lng: float, label?: string, type?: string}>  $markers
     * @param  array<int, int>  $highlightWardIds
     */
    public function renderWardMapHtml(
        ?Ward $focusWard = null,
        array $markers = [],
        array $highlightWardIds = [],
        string $accentHex = '#10b981',
        ?string $title = null,
        ?string $caption = null,
    ): HtmlString {
        $mapUrl = e($this->wardMapUrl($focusWard, $markers, $highlightWardIds, $accentHex, $title));
        $alt = e($title ?: ($focusWard ? "Nagoya Map — {$focusWard->name}" : 'Nagoya Movement Map'));
        $captionHtml = $caption
            ? '<div style="font-size:12px;color:#cbd5e1;padding:8px 12px 10px 12px;text-align:center;">'.e($caption).'</div>'
            : '';

        $html = '<div style="margin:18px 0;background:#0f172a;border:1px solid #334155;border-radius:14px;overflow:hidden;text-align:center;">'
            .'<img src="'.$mapUrl.'" alt="'.$alt.'" width="520" style="display:block;width:100%;max-width:520px;height:auto;margin:0 auto;" />'
            .$captionHtml
            .'</div>';

        return new HtmlString($html);
    }

    /**
     * Render a crisp illustrated PNG portrait avatar using PHP GD.
     */
    public function renderAvatarPng(
        string $gender = 'male',
        int $age = 30,
        string $nationality = 'Japanese',
        ?string $role = null,
        ?string $status = null,
        int $size = 128,
        ?string $seed = null,
    ): string {
        $size = max(48, min(256, $size));
        $hash = crc32(($seed ?: $nationality.$gender.$age).'|'.$role);

        $im = imagecreatetruecolor($size, $size);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        if (function_exists('imageantialias')) {
            imageantialias($im, true);
        }

        // Background color by status
        $bgRgb = match ($status) {
            'green' => [16, 62, 52],
            'yellow' => [72, 53, 20],
            'orange' => [78, 36, 18],
            'red' => [74, 24, 34],
            default => [30, 41, 59],
        };
        $ringRgb = match ($status) {
            'green' => [52, 211, 153],
            'yellow' => [251, 191, 36],
            'orange' => [251, 146, 60],
            'red' => [248, 113, 113],
            default => [129, 140, 248],
        };

        $bg = $this->allocateColor($im, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
        $ring = $this->allocateColor($im, $ringRgb[0], $ringRgb[1], $ringRgb[2]);
        $halo = $this->allocateColor($im, $ringRgb[0], $ringRgb[1], $ringRgb[2], 96);

        imagefilledrectangle($im, 0, 0, $size, $size, $bg);

        $cx = (int) round($size / 2);

        // Soft halo behind head
        imagefilledellipse($im, $cx, (int) round($size * 0.46), (int) round($size * 0.78), (int) round($size * 0.78), $halo);

        // Skin tone by nationality
        $skinRgb = $this->skinToneForNationality($nationality);
        $skin = $this->allocateColor($im, $skinRgb[0], $skinRgb[1], $skinRgb[2]);
        $skinShadow = $this->allocateColor($im, $skinRgb[0] - 24, $skinRgb[1] - 24, $skinRgb[2] - 24);

        // Outfit color by role
        $outfitRgb = match ($role) {
            'missionary', 'catalyst' => [79, 70, 229],
            'elder', 'pastor' => [15, 118, 110],
            'student' => [2, 132, 199],
            'salaryman' => [51, 65, 85],
            'homemaker' => [217, 119, 6],
            default => [99, 102, 241],
        };
        $outfit = $this->allocateColor($im, $outfitRgb[0], $outfitRgb[1], $outfitRgb[2]);
        $collar = $this->allocateColor($im, 241, 245, 249);

        // Shoulders
        imagefilledellipse($im, $cx, (int) round($size * 0.96), (int) round($size * 0.78), (int) round($size * 0.46), $outfit);
        // Collar V
        $collarPts = [
            (int) round($size * 0.38), (int) round($size * 0.76),
            (int) round($size * 0.62), (int) round($size * 0.76),
            $cx, (int) round($size * 0.90),
        ];
        imagefilledpolygon($im, $collarPts, $collar);

        // Neck
        imagefilledrectangle(
            $im,
            (int) round($size * 0.42),
            (int) round($size * 0.62),
            (int) round($size * 0.58),
            (int) round($size * 0.80),
            $skinShadow
        );

        // Hair color
        $hairRgb = $age >= 60
            ? [203, 213, 225]
            : (in_array(strtolower($nationality), ['japanese', 'korean', 'chinese', 'vietnamese', 'filipino', 'indonesian', 'thai'], true)
                ? [24, 24, 32]
                : match ($hash % 3) {
                    0 => [66, 40, 24],
                    1 => [146, 98, 57],
                    default => [30, 30, 38],
                });
        $hair = $this->allocateColor($im, $hairRgb[0], $hairRgb[1], $hairRgb[2]);

        $isFemale = strtolower($gender) === 'female';
        if ($isFemale) {
            // Back hair
            imagefilledellipse($im, $cx, (int) round($size * 0.48), (int) round($size * 0.56), (int) round($size * 0.60), $hair);
        }

        // Ears
        imagefilledellipse($im, (int) round($size * 0.27), (int) round($size * 0.48), (int) round($size * 0.08), (int) round($size * 0.12), $skinShadow);
        imagefilledellipse($im, (int) round($size * 0.73), (int) round($size * 0.48), (int) round($size * 0.08), (int) round($size * 0.12), $skinShadow);

        // Head
        imagefilledellipse($im, $cx, (int) round($size * 0.45), (int) round($size * 0.44), (int) round($size * 0.50), $skin);

        // Top hair
        imagefilledarc(
            $im,
            $cx,
            (int) round($size * 0.41),
            (int) round($size * 0.48),
            (int) round($size * 0.44),
            185,
            355,
            $hair,
            IMG_ARC_PIE
        );

        // Eyes
        $eyeColor = $this->allocateColor($im, 15, 23, 42);
        $eyeWhite = $this->allocateColor($im, 255, 255, 255);
        $eyeY = (int) round($size * 0.46);
        $leftEyeX = (int) round($size * 0.41);
        $rightEyeX = (int) round($size * 0.59);
        $eyeR = max(3, (int) round($size * 0.045));

        imagefilledellipse($im, $leftEyeX, $eyeY, $eyeR * 2, $eyeR * 2, $eyeWhite);
        imagefilledellipse($im, $rightEyeX, $eyeY, $eyeR * 2, $eyeR * 2, $eyeWhite);
        imagefilledellipse($im, $leftEyeX, $eyeY, $eyeR + 1, $eyeR + 1, $eyeColor);
        imagefilledellipse($im, $rightEyeX, $eyeY, $eyeR + 1, $eyeR + 1, $eyeColor);

        // Warm smile
        $mouthColor = $this->allocateColor($im, 180, 83, 83);
        imagearc(
            $im,
            $cx,
            (int) round($size * 0.54),
            (int) round($size * 0.16),
            (int) round($size * 0.10),
            15,
            165,
            $mouthColor
        );

        // Crisp border ring
        imagesetthickness($im, max(2, (int) round($size * 0.03)));
        imagerectangle($im, 1, 1, $size - 2, $size - 2, $ring);
        imagesetthickness($im, 1);

        ob_start();
        imagepng($im);
        $bytes = (string) ob_get_clean();
        imagedestroy($im);

        return $bytes;
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
    protected function skinToneForNationality(string $nationality): array
    {
        return match (strtolower(trim($nationality))) {
            'nigerian', 'kenyan', 'ghanaian', 'ethiopian', 'african' => [141, 85, 52],
            'indian', 'pakistani', 'bangladeshi', 'nepali', 'sri lankan' => [198, 134, 92],
            'brazilian', 'mexican', 'filipino', 'indonesian', 'thai', 'vietnamese' => [224, 172, 132],
            'american', 'british', 'canadian', 'australian', 'german', 'french' => [245, 208, 185],
            default => [242, 206, 178], // Japanese / East Asian warm tone
        };
    }
}
