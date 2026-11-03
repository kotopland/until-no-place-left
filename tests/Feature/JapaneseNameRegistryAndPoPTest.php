<?php

declare(strict_types=1);

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use App\Services\JapaneseNameRegistry;

test('seed wards data covers all 16 wards with 100% unique names and balanced PoP count', function (): void {
    $session = GameSession::factory()->create();
    $user = User::factory()->create();

    $wards = [];
    foreach ([
        'chikusa-ku', 'higashi-ku', 'kita-ku', 'nishi-ku', 'nakamura-ku', 'naka-ku',
        'showa-ku', 'mizuho-ku', 'atsuta-ku', 'nakagawa-ku', 'minato-ku', 'minami-ku',
        'moriyama-ku', 'midori-ku', 'meito-ku', 'tempaku-ku',
    ] as $idx => $slug) {
        $wards[$slug] = Ward::create([
            'name' => ucfirst($slug),
            'name_ja' => $slug,
            'slug' => $slug,
            'center_lat' => 35.1500 + ($idx * 0.01),
            'center_lng' => 136.9000 + ($idx * 0.01),
        ]);
    }

    $registry = app(JapaneseNameRegistry::class);
    $seedData = $registry->getSeedWardsData($session, $wards, $user);

    expect(count($seedData))->toBeGreaterThanOrEqual(50);

    // Verify all names and Japanese names are completely unique
    $names = array_column($seedData, 'name');
    $namesJa = array_column($seedData, 'name_ja');

    expect(count($names))->toBe(count(array_unique($names)))
        ->and(count($namesJa))->toBe(count(array_unique($namesJa)));

    // Verify PoP distribution is balanced across wards (some PoPs per ward, ~25-45%)
    $popCount = count(array_filter($seedData, fn ($d) => $d['is_person_of_peace'] === true));
    $popRatio = $popCount / count($seedData);

    expect($popCount)->toBeGreaterThanOrEqual(14)
        ->and($popCount)->toBeLessThanOrEqual(25)
        ->and($popRatio)->toBeGreaterThan(0.20)
        ->and($popRatio)->toBeLessThan(0.50);

    // Verify Meito-ku (home ward) has some PoP, but not 100% PoP
    $meitoWards = array_filter($seedData, fn ($d) => $d['ward_id'] === $wards['meito-ku']->id);
    $meitoPops = array_filter($meitoWards, fn ($d) => $d['is_person_of_peace'] === true);

    expect(count($meitoWards))->toBeGreaterThanOrEqual(3)
        ->and(count($meitoPops))->toBeGreaterThanOrEqual(1)
        ->and(count($meitoPops))->toBeLessThan(count($meitoWards));
});

test('dynamic resident generation avoids duplicate names in the game session', function (): void {
    $session = GameSession::factory()->create();
    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'center_lat' => 35.17,
        'center_lng' => 137.0,
    ]);

    // Create a resident with a known name
    Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Keisuke Hoshino',
        'name_ja' => '星野 圭介',
        'gender' => 'male',
        'age' => 33,
        'nationality' => 'Japanese',
        'status' => ReceptivityStatus::Yellow,
    ]);

    $registry = app(JapaneseNameRegistry::class);

    // Generating 10 new residents must produce 10 unique names, none matching existing
    $generatedNames = [];
    for ($i = 0; $i < 10; $i++) {
        $profile = $registry->generateUniqueResidentProfile((int) $session->id, $ward);
        expect($profile['name'])->not->toBe('Keisuke Hoshino');
        expect(in_array($profile['name'], $generatedNames))->toBeFalse();

        $generatedNames[] = $profile['name'];

        Npc::create([
            'game_session_id' => $session->id,
            'ward_id' => $ward->id,
            'name' => $profile['name'],
            'name_ja' => $profile['name_ja'],
            'gender' => $profile['gender'],
            'age' => $profile['age'],
            'nationality' => 'Japanese',
            'status' => ReceptivityStatus::Yellow,
        ]);
    }

    expect(count($generatedNames))->toBe(10);
});

test('prayer walk in a ward that already has a PoP does not create or convert other residents to PoP', function (): void {
    $session = GameSession::create([
        'name' => 'Nagoya PoP Guard Session',
        'code' => 'pop-guard-2026',
        'current_game_month' => 1,
        'current_game_day' => 1,
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'name' => 'Minato-ku',
        'name_ja' => '港区',
        'slug' => 'minato-ku',
        'center_lat' => 35.0833,
        'center_lng' => 136.8667,
        'spiritual_openness' => 20,
    ]);

    $user = User::factory()->create([
        'current_game_session_id' => $session->id,
        'action_points' => 10,
    ]);

    // Pre-existing PoP
    $existingPoP = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Carlos Santos',
        'name_ja' => 'カルロス・サントス',
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 45,
        'is_person_of_peace' => true,
    ]);

    // Pre-existing non-PoP unreached resident
    $normalNeighbor = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Mai Ishida',
        'name_ja' => '石田 舞',
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 15,
        'is_person_of_peace' => false,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'ward_id' => $ward->id,
        'target_npc_id' => null,
        'action_type' => MinistryActionType::PrayerWalk,
        'status' => QueuedActionStatus::Pending,
        'game_month' => 1,
        'game_day' => 1,
        'ap_cost' => 1,
    ]);

    $engine = new FourFieldsEngine;
    $outcome = $engine->resolveSingleAction($action, 1);

    $normalNeighbor->refresh();
    $existingPoP->refresh();

    // The normal neighbor's trust increased and status softened, but NOT made PoP!
    expect($normalNeighbor->is_person_of_peace)->toBeFalse()
        ->and($normalNeighbor->trust_level)->toBeGreaterThan(15);

    // Total PoP count in ward remains exactly 1
    $totalPops = Npc::where('game_session_id', $session->id)
        ->where('ward_id', $ward->id)
        ->where('is_person_of_peace', true)
        ->count();

    expect($totalPops)->toBe(1);
});
