<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('php artisan game status outputs active session overview', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 2,
        'current_game_day' => 10,
        'calendar_date' => '2026-05-10',
        'is_active' => true,
    ]);

    Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1691,
        'longitude' => 136.9079,
        'population' => 90000,
        'spiritual_openness' => 25,
    ]);

    $this->artisan('game', ['action' => 'status'])
        ->expectsOutputToContain('Nagoya Test Session')
        ->expectsOutputToContain('Current Game Month')
        ->assertSuccessful();
});

test('php artisan game mode sets solo and coop modes directly', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'is_active' => true,
        'settings' => ['coop_team_mode' => true],
    ]);

    Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1667,
        'longitude' => 136.9667,
        'population' => 165000,
    ]);

    // Switch to solo
    $this->artisan('game', ['action' => 'mode', 'param' => 'solo'])
        ->expectsOutputToContain('100% Solo Pioneer Mode')
        ->assertSuccessful();

    $session->refresh();
    expect($session->isCoopMode)->toBeFalse();

    // Switch to coop
    $this->artisan('game', ['action' => 'mode', 'param' => 'coop'])
        ->expectsOutputToContain('Team Co-op Mode')
        ->assertSuccessful();

    $session->refresh();
    expect($session->isCoopMode)->toBeTrue();
});

test('php artisan game advance-day advances the game day when forced', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'current_game_day' => 3,
        'days_processed_this_month' => 2,
        'calendar_date' => '2026-04-03',
        'is_active' => true,
    ]);

    $this->artisan('game', ['action' => 'advance-day', '--force' => true])
        ->expectsOutputToContain('Successfully processed Day 3')
        ->assertSuccessful();

    $session->refresh();
    expect($session->current_game_day)->toBe(4);
});

test('php artisan game migrate-fresh displays critical warning and executes with force flag', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'code' => 'nagoya-2026',
        'current_game_month' => 1,
        'is_active' => true,
    ]);

    $this->artisan('game', ['action' => 'migrate-fresh', '--force' => true])
        ->expectsOutputToContain('CRITICAL WARNING: PRODUCTION DATABASE WIPE')
        ->assertSuccessful();
});
