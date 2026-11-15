<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ward;
use App\Services\EmailVisualService;
use App\Services\NagoyaMiniMapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaVisualController extends Controller
{
    /**
     * Serve a lightweight, email-optimized PNG of the webapp logo.
     */
    public function logo(): Response
    {
        $sourcePath = public_path('images/until-no-place-left-logo-small.png');

        if (is_file($sourcePath) && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($sourcePath);
            if ($src !== false) {
                $targetSize = 128;
                $dst = imagecreatetruecolor($targetSize, $targetSize);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                if ($transparent !== false) {
                    imagefilledrectangle($dst, 0, 0, $targetSize, $targetSize, $transparent);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetSize, $targetSize, imagesx($src), imagesy($src));

                ob_start();
                imagepng($dst, null, 6);
                $png = (string) ob_get_clean();

                imagedestroy($src);
                imagedestroy($dst);

                return response($png, 200, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'public, max-age=604800',
                ]);
            }
        }

        abort(404);
    }

    /**
     * Serve a deterministic PNG portrait avatar for emails.
     */
    public function avatar(Request $request, EmailVisualService $visuals): Response
    {
        $gender = (string) $request->query('gender', 'male');
        $age = max(5, min(99, (int) $request->query('age', 30)));
        $nationality = (string) $request->query('nationality', 'Japanese');
        $role = $request->query('role') ? (string) $request->query('role') : null;
        $status = $request->query('status') ? (string) $request->query('status') : null;
        $size = max(48, min(256, (int) $request->query('size', 128)));
        $seed = $request->query('seed') ? (string) $request->query('seed') : null;

        $png = $visuals->renderAvatarPng($gender, $age, $nationality, $role, $status, $size, $seed);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Serve a PNG Nagoya ward location map for emails.
     */
    public function wardMap(Request $request, NagoyaMiniMapService $miniMap): Response
    {
        $wardId = $request->query('ward_id') ? (int) $request->query('ward_id') : null;
        $focusWard = $wardId ? Ward::find($wardId) : null;

        $highlightRaw = (string) $request->query('highlight', '');
        $highlightWardIds = array_values(array_filter(
            array_map('intval', explode(',', $highlightRaw)),
            fn (int $id): bool => $id > 0
        ));

        $markers = [];
        $encodedMarkers = $request->query('m');
        if (is_string($encodedMarkers) && $encodedMarkers !== '') {
            $decoded = json_decode((string) base64_decode($encodedMarkers, true), true);
            if (is_array($decoded)) {
                foreach (array_slice($decoded, 0, 8) as $m) {
                    if (is_array($m) && isset($m['lat'], $m['lng'])) {
                        $markers[] = [
                            'lat' => (float) $m['lat'],
                            'lng' => (float) $m['lng'],
                            'label' => isset($m['label']) ? (string) $m['label'] : '',
                            'type' => isset($m['type']) ? (string) $m['type'] : 'ward',
                        ];
                    }
                }
            }
        }

        $accentRaw = preg_replace('/[^0-9a-fA-F]/', '', (string) $request->query('accent', '10b981'));
        $accentHex = strlen((string) $accentRaw) === 6 ? '#'.$accentRaw : '#10b981';
        $title = $request->query('title') ? (string) $request->query('title') : null;

        $png = $miniMap->renderPng(
            focusWard: $focusWard,
            markers: $markers,
            highlightWardIds: $highlightWardIds,
            accentHex: $accentHex,
            width: 560,
            height: 300,
            title: $title,
        );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
