<?php

use App\Livewire\NagoyaMapCanvas;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('nagoya map canvas initializes with default base map and allows changing zero api key base maps', function () {
    $session = GameSession::create([
        'name' => 'Base Map Test Session',
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
        'name' => 'Test Explorer',
    ]);

    $this->actingAs($player);

    $component = Livewire::test(NagoyaMapCanvas::class)
        ->assertSet('baseMap', 'streets')
        ->assertSee('OSM Streets')
        ->assertSee('Dark Canvas')
        ->assertSee('Satellite')
        ->assertSee('Topographic')
        ->assertSee('World Streets')
        ->assertSee('No API Key');

    // Switch to dark
    $component->call('setBaseMap', 'dark')
        ->assertSet('baseMap', 'dark');

    // Switch to satellite
    $component->call('setBaseMap', 'satellite')
        ->assertSet('baseMap', 'satellite');

    // Switch to topo
    $component->call('setBaseMap', 'topo')
        ->assertSet('baseMap', 'topo');

    // Switch to esri_streets
    $component->call('setBaseMap', 'esri_streets')
        ->assertSet('baseMap', 'esri_streets');

    // Switch back to streets
    $component->call('setBaseMap', 'streets')
        ->assertSet('baseMap', 'streets');

    // Dispatches map-basemap-changed event listener
    $component->dispatch('map-basemap-changed', baseMap: 'dark')
        ->assertSet('baseMap', 'dark');

    // Invalid base map is rejected and retains current baseMap
    $component->call('setBaseMap', 'invalid_map_layer')
        ->assertSet('baseMap', 'dark');
});

test('nagoya map canvas provides available base maps list with required keys and zero api keys', function () {
    $canvas = new NagoyaMapCanvas;
    $baseMaps = $canvas->getAvailableBaseMaps();

    expect($baseMaps)->toBeArray();
    expect(count($baseMaps))->toBe(5);

    $ids = collect($baseMaps)->pluck('id')->all();
    expect($ids)->toEqual(['streets', 'dark', 'satellite', 'topo', 'esri_streets']);

    foreach ($baseMaps as $bm) {
        expect($bm)->toHaveKeys(['id', 'name', 'name_ja', 'icon', 'subtitle']);
        expect($bm['subtitle'])->toContain('No API Key');
    }
});
