<?php

declare(strict_types=1);

use App\Livewire\ChurchGenerationMap;
use App\Livewire\MultiplayerLeaderboard;
use App\Livewire\NagoyaMapCanvas;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\User;
use App\Services\RealLifeSimulationService;
use App\Services\ShowcaseSandboxService;
use Database\Seeders\NagoyaOikosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    $sandbox = app(ShowcaseSandboxService::class);
    $sandbox->deactivate();
    $guestPath = $sandbox->getSandboxDatabasePath(userId: null, isGuest: true);
    @unlink($guestPath);
    @unlink("{$guestPath}-wal");
    @unlink("{$guestPath}-shm");
});

test('real life simulation across 3m, 6m, 1y, 2y, 5y, 10y, and 15y reflects authentic Japanese ministry progression', function (): void {
    (new NagoyaOikosSeeder)->run();

    $session = GameSession::where('code', 'nagoya-2026')->firstOrFail();
    $simulator = app(RealLifeSimulationService::class);

    $result = $simulator->simulate(
        session: $session,
        targetMonths: 180,
        mode: 'coop',
        resetFirst: true,
        rngSeed: 20260401,
    );

    $milestones = $result['milestones'];

    // Verify all 7 canonical horizons are captured
    expect(array_keys($milestones))->toBe(['3m', '6m', '1y', '2y', '5y', '10y', '15y']);

    $m3 = $milestones['3m'];
    $m6 = $milestones['6m'];
    $y1 = $milestones['1y'];
    $y2 = $milestones['2y'];
    $y5 = $milestones['5y'];
    $y10 = $milestones['10y'];
    $y15 = $milestones['15y'];

    // 1. 3 Months (M3): Plowing & Language School — 1 initial ward, G1 only, 1 baptized (Daisuke Kato), seekers still studying
    expect($m3['months'])->toBe(3)
        ->and($m3['wards_reached'])->toBe(1)
        ->and($m3['deepest_generation'])->toBe(1)
        ->and($m3['baptized_count'])->toBe(1)
        ->and($m3['green_believers_count'])->toBeBetween(1, 3)
        ->and($m3['yellow_seekers_count'])->toBeGreaterThanOrEqual(4);

    // 2. 6 Months (M6): Foundation graduation, first conversions counting the cost of baptism, first G2 household circle
    expect($m6['months'])->toBe(6)
        ->and($m6['green_believers_count'])->toBeGreaterThan($m3['green_believers_count'])
        ->and($m6['counting_cost_count'])->toBeGreaterThanOrEqual(2)
        ->and($m6['active_churches_count'])->toBeGreaterThanOrEqual(2)
        ->and($m6['deepest_generation'])->toBe(2);

    // 3. 1 Year (M12): First Japanese water baptisms after 6+ months of family preparation & G2 churches
    expect($y1['months'])->toBe(12)
        ->and($y1['baptized_count'])->toBeGreaterThan($m6['baptized_count'])
        ->and($y1['green_believers_count'])->toBeGreaterThan($m6['green_believers_count'])
        ->and($y1['wards_reached'])->toBeBetween(2, 5)
        ->and($y1['deepest_generation'])->toBe(2);

    // 4. 2 Years (M24): Emerging L2/L3 Timothy leaders, G3 simple churches, and real-life attrition weathered
    expect($y2['months'])->toBe(24)
        ->and($y2['green_believers_count'])->toBeGreaterThan($y1['green_believers_count'])
        ->and($y2['baptized_count'])->toBeGreaterThan($y1['baptized_count'])
        ->and($y2['deepest_generation'])->toBe(3)
        ->and($y2['leaders_by_tier']['L2'])->toBeGreaterThanOrEqual(1)
        ->and($y2['leaders_by_tier']['L3'])->toBeGreaterThanOrEqual(1)
        ->and($y2['cumulative_attrition_events'])->toBeGreaterThanOrEqual(1);

    // 5. 5 Years (M60): Full missionary term — G4 movement stream, L4 Movement Trainer, 8–12 wards reached
    expect($y5['months'])->toBe(60)
        ->and($y5['green_believers_count'])->toBeGreaterThan($y2['green_believers_count'])
        ->and($y5['deepest_generation'])->toBe(4)
        ->and($y5['wards_reached'])->toBeBetween(7, 12)
        ->and($y5['leaders_by_tier']['L4'])->toBeGreaterThanOrEqual(1)
        ->and($y5['dissolved_churches_count'] + $y5['cumulative_revitalizations'])->toBeGreaterThanOrEqual(1);

    // 6. 10 Years (M120): City-wide indigenous movement — G5–G6 streams, L5 Strategy Coordinator, 13–15 wards reached
    expect($y10['months'])->toBe(120)
        ->and($y10['green_believers_count'])->toBeGreaterThan($y5['green_believers_count'])
        ->and($y10['deepest_generation'])->toBeGreaterThanOrEqual(5)
        ->and($y10['wards_reached'])->toBeBetween(12, 15)
        ->and($y10['leaders_by_tier']['L5'])->toBeGreaterThanOrEqual(1)
        ->and($y10['dissolvement_percent'])->toBeBetween(35, 45)
        ->and($y10['merged_groups_count'])->toBeGreaterThanOrEqual(15)
        ->and($y10['bible_study_groups_count'])->toBeGreaterThan($y10['healthy_churches_count'])
        ->and($y10['bible_study_percent'])->toBeBetween(40, 60);

    // 7. 15 Years (M180): "Until No Place Left" — all 16 wards of Nagoya reached (100% saturation)
    expect($y15['months'])->toBe(180)
        ->and($y15['wards_reached'])->toBe(16)
        ->and($y15['saturation_percent'])->toBe(100)
        ->and($y15['deepest_generation'])->toBeGreaterThanOrEqual(6)
        ->and($y15['baptized_count'])->toBeGreaterThan($y10['baptized_count'])
        ->and($y15['active_churches_count'])->toBeGreaterThan($y10['active_churches_count'])
        ->and($y15['dissolvement_percent'])->toBeBetween(35, 45)
        ->and($y15['bible_study_groups_count'])->toBeGreaterThan($y15['healthy_churches_count'])
        ->and($y15['bible_study_percent'])->toBeBetween(40, 60);

    // Verify in each active generation across 2y, 5y, 10y, and 15y there are more Bible study groups than churches (40-60%)
    foreach ([$y2, $y5, $y10, $y15] as $milestoneSnap) {
        foreach ($milestoneSnap['generations_breakdown'] as $genBucket) {
            if ($genBucket['total'] === 0) {
                continue;
            }

            expect($genBucket['bible_studies'])->toBeGreaterThan($genBucket['churches'])
                ->and($genBucket['bible_study_percent'])->toBeBetween(40, 60);
        }
    }

    // Verify ONLY computer players exist in the simulation database and live DB is untouched
    expect(User::where('is_ai_colaborer', false)->count())->toBe(0)
        ->and(User::where('is_ai_colaborer', true)->count())->toBeGreaterThanOrEqual(4);

    $pioneer = User::where('email', 'kenji.yamada@nagoyaharvest.jp')->firstOrFail();
    expect($pioneer->is_ai_colaborer)->toBeTrue()
        ->and($pioneer->language_training_completed)->toBeTrue()
        ->and($pioneer->badges)->toContain('Language & L1 Certified')
        ->and($pioneer->badges)->toContain('No Place Left Nagoya');

    // Verify primary live game database ('sqlite') was never modified
    expect(GameSession::on('sqlite')->where('code', 'nagoya-2026')->value('current_game_month'))->toBe(1)
        ->and(Church::on('sqlite')->count())->toBe(1)
        ->and(User::on('sqlite')->where('email', 'coach@nagoyaharvest.jp')->exists())->toBeTrue();
});

test('php artisan game:simulate command supports individual horizons and all milestones', function (string $horizon, int $expectedNextMonth, string $expectedLabel): void {
    (new NagoyaOikosSeeder)->run();

    $this->artisan('game:simulate', [
        'duration' => $horizon,
        '--force' => true,
    ])
        ->expectsOutputToContain('NAGOYA OIKOS — REAL-LIFE LONGITUDINAL MOVEMENT SIMULATOR')
        ->expectsOutputToContain("MILESTONE: {$expectedLabel}")
        ->assertSuccessful();

    $session = GameSession::where('code', 'nagoya-2026')->firstOrFail();
    expect($session->current_game_month)->toBe($expectedNextMonth)
        ->and(GameSession::on('sqlite')->where('code', 'nagoya-2026')->value('current_game_month'))->toBe(1);
})->with([
    '3 months' => ['3m', 4, '3 Months'],
    '6 months' => ['6m', 7, '6 Months'],
    '1 year' => ['1y', 13, '1 Year'],
    '2 years' => ['2y', 25, '2 Years'],
    '5 years' => ['5y', 61, '5 Years'],
    '10 years' => ['10y', 121, '10 Years'],
    '15 years' => ['15y', 181, '15 Years'],
]);

test('php artisan game:simulate rejects invalid duration argument', function (): void {
    (new NagoyaOikosSeeder)->run();

    $this->artisan('game:simulate', [
        'duration' => '99y',
        '--force' => true,
    ])
        ->expectsOutputToContain("Invalid simulation duration '99y'")
        ->assertFailed();
});

test('php artisan game simulate delegates to game:simulate from operator command', function (): void {
    (new NagoyaOikosSeeder)->run();

    $this->artisan('game', [
        'action' => 'simulate',
        'param' => '6m',
        '--force' => true,
    ])
        ->expectsOutputToContain('MILESTONE: 6 Months')
        ->assertSuccessful();
});

test('interactive browser time-stage showcase runs in isolated per-player sqlite database with computer players only and renders Map, Gen Map, and Leaderboard', function (): void {
    (new NagoyaOikosSeeder)->run();

    $liveUser = User::on('sqlite')->where('email', 'coach@nagoyaharvest.jp')->firstOrFail();
    $initialLiveNpcCount = Npc::on('sqlite')->count();
    $initialLiveChurchCount = Church::on('sqlite')->count();

    $component = Livewire::actingAs($liveUser)
        ->test(NagoyaMovementDashboard::class)
        ->call('toggleShowcaseBar')
        ->assertSet('showShowcaseBar', true)
        ->assertSet('currentTab', 'map')
        ->assertSet('showOnboardingModal', false)
        ->assertSee('Isolated Temp SQLite DB • Computer Players Only');

    // Load 3 Months stage in isolated showcase DB
    $component->call('loadShowcaseStage', '3m')
        ->assertSet('activeShowcaseStage', '3m')
        ->assertSee('Phase 1: Cultural Entry, Language Foundation & Soil Plowing');

    expect(GameSession::where('code', 'nagoya-2026')->value('current_game_month'))->toBe(4)
        ->and(User::where('is_ai_colaborer', false)->count())->toBe(0)
        ->and(GameSession::on('sqlite')->where('code', 'nagoya-2026')->value('current_game_month'))->toBe(1);

    // Load 15 Years stage and open 7-stage comparison modal
    $component->call('openShowcaseComparison', true)
        ->assertSet('activeShowcaseStage', '15y')
        ->assertSet('showShowcaseComparisonModal', true)
        ->assertSee('Phase 7: Long-Obedience Maturity')
        ->assertSee('Nagoya Movement Trajectory Across All 7 Real-Life Milestones');

    expect(GameSession::where('code', 'nagoya-2026')->value('current_game_month'))->toBe(181)
        ->and($component->get('showcaseSnapshot.wards_reached'))->toBe(16);

    // Verify Map, Circles & Gen Map, and Leaderboard render the simulated computer-only data
    Livewire::actingAs($liveUser)
        ->test(NagoyaMapCanvas::class)
        ->assertSee('Nagoya Movement Canvas');

    Livewire::actingAs($liveUser)
        ->test(ChurchGenerationMap::class)
        ->assertSee('Acts 2 Circle');

    Livewire::actingAs($liveUser)
        ->test(MultiplayerLeaderboard::class)
        ->assertSee('Kenji Yamada')
        ->assertSee('Sarah Jenkins')
        ->assertSee('Marcus Silva')
        ->assertSee('Yuki Takahashi')
        ->assertDontSee('Missionary Coach');

    // Verify live database is STILL 100% untouched at Month 1
    expect(GameSession::on('sqlite')->where('code', 'nagoya-2026')->value('current_game_month'))->toBe(1)
        ->and(Npc::on('sqlite')->count())->toBe($initialLiveNpcCount)
        ->and(Church::on('sqlite')->count())->toBe($initialLiveChurchCount);

    // Exit Showcase Mode and verify connection returns to primary 'sqlite'
    $component->call('exitShowcaseSandbox')
        ->assertSet('showShowcaseBar', false);

    expect(app(ShowcaseSandboxService::class)->isActive())->toBeFalse();
});

test('non-registered guest players can access simulation via shared sqlite database that can only be regenerated once per real life day', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(10));
    $todayDate = now()->toDateString();

    (new NagoyaOikosSeeder)->run();

    // 1. Unauthenticated guest HTTP request to /simulate succeeds (200 OK) and activates the shared guest SQLite DB
    $response = $this->get(route('simulate'));
    $response->assertSuccessful()
        ->assertSee('Shared Guest SQLite DB • Regenerated Once Daily')
        ->assertSee($todayDate);

    $sandbox = app(ShowcaseSandboxService::class);
    $guestDbPath = $sandbox->getSandboxDatabasePath(userId: null, isGuest: true);

    expect($guestDbPath)->toEndWith('showcase_guest.sqlite')
        ->and(file_exists($guestDbPath))->toBeTrue()
        ->and($sandbox->isGuestDatabaseGeneratedToday())->toBeTrue()
        ->and($sandbox->getGuestGeneratedDate())->toBe($todayDate);

    $generatedAtFirst = GameSession::where('code', 'nagoya-2026')->first()?->settings['guest_generated_at'] ?? null;
    expect($generatedAtFirst)->not->toBeNull();

    // 2. Guest Livewire interaction uses the same shared SQLite DB, can switch milestone views from cached snapshots,
    //    and blocks regenerating the database a second time on the same real-life day
    $this->travel(2)->hours();

    $guestComponent = Livewire::test(NagoyaMovementDashboard::class)
        ->assertSet('isGuestShowcase', true)
        ->assertSet('showShowcaseBar', true)
        ->assertSet('guestDatabaseGeneratedDate', $todayDate)
        ->call('loadShowcaseStage', '10y')
        ->assertSet('activeShowcaseStage', '10y')
        ->assertSee('Phase 6: Decade of Faithful Presence');

    // Attempt to regenerate on the same real-life day is blocked
    $regenAttemptSameDay = $sandbox->regenerateGuestDatabaseIfAllowed();
    expect($regenAttemptSameDay['regenerated'])->toBeFalse()
        ->and($regenAttemptSameDay['generated_date'])->toBe($todayDate);

    $guestComponent->call('regenerateGuestSimulation')
        ->assertDispatched('notify');

    $generatedAtSameDay = GameSession::where('code', 'nagoya-2026')->first()?->settings['guest_generated_at'] ?? null;
    expect($generatedAtSameDay)->toBe($generatedAtFirst);

    // 3. Advance to the next real-life calendar day: regeneration is now allowed once for the new day
    $this->travel(1)->days();
    $tomorrowDate = now()->toDateString();

    expect($sandbox->isGuestDatabaseGeneratedToday())->toBeFalse();

    $guestComponent->call('regenerateGuestSimulation')
        ->assertSet('guestDatabaseGeneratedDate', $tomorrowDate);

    expect($sandbox->isGuestDatabaseGeneratedToday())->toBeTrue()
        ->and($sandbox->getGuestGeneratedDate())->toBe($tomorrowDate);

    $generatedAtNextDay = GameSession::where('code', 'nagoya-2026')->first()?->settings['guest_generated_at'] ?? null;
    expect($generatedAtNextDay)->not->toBe($generatedAtFirst);

    // Primary live database remains completely untouched
    expect(GameSession::on('sqlite')->where('code', 'nagoya-2026')->value('current_game_month'))->toBe(1);
});
