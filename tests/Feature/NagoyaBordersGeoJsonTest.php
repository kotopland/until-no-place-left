<?php

use App\Livewire\NagoyaMapCanvas;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use App\Services\NagoyaGeoJsonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('nagoya geojson service loads and maps authentic 16 ward boundaries with names and slugs', function () {
    $service = app(NagoyaGeoJsonService::class);

    $session = GameSession::create([
        'name' => 'Borders Test Session',
        'is_active' => true,
    ]);

    $wardA = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.1700,
        'center_lng' => 136.9066,
        'population' => 90000,
        'spiritual_openness' => 40,
    ]);

    $wardB = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'center_lat' => 35.1666,
        'center_lng' => 136.9500,
        'population' => 165000,
        'spiritual_openness' => 45,
    ]);

    $wards = Ward::all();
    $geoJson = $service->getWardsGeoJson($wards);

    expect($geoJson)->toHaveKey('type', 'FeatureCollection');
    expect($geoJson)->toHaveKey('features');
    expect(count($geoJson['features']))->toBeGreaterThanOrEqual(2);

    // Verify Naka-ku feature properties
    $nakaFeature = collect($geoJson['features'])->first(function ($f) {
        return ($f['properties']['slug'] ?? '') === 'naka-ku'
            || ($f['properties']['name_ja'] ?? '') === '中区';
    });

    expect($nakaFeature)->not->toBeNull();
    expect($nakaFeature['properties']['name'])->toBe('Naka-ku');
    expect($nakaFeature['properties']['name_ja'])->toBe('中区');
    expect($nakaFeature['properties']['openness'])->toBe(40);
    expect($nakaFeature['geometry'])->toHaveKey('type');
    expect($nakaFeature['geometry'])->toHaveKey('coordinates');
});

test('nagoya map canvas receives authentic geojson features for wards', function () {
    $session = GameSession::create([
        'name' => 'Map Canvas Borders Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'center_lat' => 35.1700,
        'center_lng' => 136.9066,
        'population' => 90000,
        'spiritual_openness' => 40,
    ]);

    $player = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'name' => 'Test Missionary',
    ]);

    $this->actingAs($player);

    Livewire::test(NagoyaMapCanvas::class)
        ->assertViewHas('wardsGeoJson', function ($wardsGeoJson) {
            expect($wardsGeoJson)->toBeArray();
            expect($wardsGeoJson['type'])->toBe('FeatureCollection');
            expect(count($wardsGeoJson['features']))->toBeGreaterThanOrEqual(1);

            $feature = $wardsGeoJson['features'][0];
            expect($feature['properties'])->toHaveKey('name');
            expect($feature['properties'])->toHaveKey('name_ja');
            expect($feature['properties'])->toHaveKey('openness');
            expect($feature['geometry'])->toHaveKey('coordinates');

            return true;
        });
});
