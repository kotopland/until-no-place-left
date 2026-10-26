<?php

declare(strict_types=1);

use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use App\Services\PersonAvatarService;
use Database\Seeders\NagoyaOikosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('npc model supports nationality, gender, and age with helper property hooks', function () {
    $session = GameSession::factory()->create();
    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'latitude' => 35.1667,
        'longitude' => 136.9667,
        'population' => 165000,
        'spiritual_openness' => 35,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Linh Nguyen',
        'name_ja' => 'リン・グエン',
        'gender' => 'female',
        'age' => 21,
        'nationality' => 'Vietnamese',
        'role_archetype' => NpcRoleArchetype::InternationalExpat,
        'status' => ReceptivityStatus::Yellow,
    ]);

    expect($npc->gender)->toBe('female')
        ->and($npc->age)->toBe(21)
        ->and($npc->nationality)->toBe('Vietnamese')
        ->and($npc->genderIcon)->toBe('♀')
        ->and($npc->genderLabel)->toBe('Female')
        ->and($npc->nationalityFlag)->toBe('🇻🇳')
        ->and($npc->ageGroup)->toBe('youth')
        ->and($npc->ageGroupLabel)->toBe('Youth (18-24)')
        ->and($npc->demographicSummary)->toContain('🇻🇳 Vietnamese', '♀ 21 yrs');

    $seniorNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Keiko Yamamoto',
        'name_ja' => '山本 恵子',
        'gender' => 'female',
        'age' => 64,
        'nationality' => 'Japanese',
        'role_archetype' => NpcRoleArchetype::ElderlyResident,
    ]);

    expect($seniorNpc->ageGroup)->toBe('mature')
        ->and($seniorNpc->nationalityFlag)->toBe('🇯🇵');
});

test('person avatar service generates rich svg with demographic layers', function () {
    $service = app(PersonAvatarService::class);

    // Test youth Vietnamese female
    $svgYouth = $service->generateSvg('female', 21, 'Vietnamese', 'universitystudent', 'yellow', 64, true);
    expect($svgYouth)->toContain('<svg', '🇻🇳', '♀', '21y');

    // Test senior Japanese male
    $svgSenior = $service->generateSvg('male', 72, 'Japanese', 'elderly', 'green', 80, true);
    expect($svgSenior)->toContain('<svg', '🇯🇵', '♂', '72y');

    // Test Brazilian male
    $svgBrazilian = $service->generateSvg('male', 32, 'Brazilian', 'internationalexpat', 'yellow', 48, true);
    expect($svgBrazilian)->toContain('<svg', '🇧🇷', '♂', '32y');
});

test('person avatar blade component renders correctly for npc and user', function () {
    $session = GameSession::factory()->create();
    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
        'latitude' => 35.1667,
        'longitude' => 137.0000,
        'population' => 165000,
        'spiritual_openness' => 50,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Daisuke Kato',
        'name_ja' => '加藤 大輔',
        'gender' => 'male',
        'age' => 32,
        'nationality' => 'Japanese',
        'role_archetype' => NpcRoleArchetype::ExistingBeliever,
        'status' => ReceptivityStatus::Green,
    ]);

    $view = $this->blade('<x-person-avatar :person="$npc" size="md" :showBadges="true" />', ['npc' => $npc]);
    $view->assertSee('🇯🇵');
    $view->assertSee('♂');
    $view->assertSee('32y');

    $sarah = User::factory()->create([
        'name' => 'Sarah Jenkins',
        'gender' => 'female',
        'age' => 27,
        'nationality' => 'American',
        'is_ai_colaborer' => true,
    ]);

    $viewSarah = $this->blade('<x-person-avatar :person="$sarah" size="sm" :showBadges="true" :showAgePill="true" />', ['sarah' => $sarah]);
    $viewSarah->assertSee('🇺🇸');
    $viewSarah->assertSee('♀');
    $viewSarah->assertSee('27y');
});

test('action queue planner displays demographic avatars for ward residents and selected person', function () {
    $session = GameSession::create([
        'name' => 'Nagoya Test Session',
        'is_active' => true,
    ]);

    $ward = Ward::create([
        'game_session_id' => $session->id,
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'latitude' => 35.1709,
        'longitude' => 136.9080,
        'population' => 90000,
        'spiritual_openness' => 30,
    ]);

    $player = User::factory()->create([
        'name' => 'Missionary Pioneer',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'action_points' => 12,
        'current_game_month' => 1,
        'current_game_day' => 1,
    ]);

    $kenji = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Kenji Tanaka',
        'name_ja' => '田中 健二',
        'gender' => 'male',
        'age' => 48,
        'nationality' => 'Japanese',
        'role_archetype' => NpcRoleArchetype::Shopkeeper,
        'status' => ReceptivityStatus::Yellow,
        'trust_level' => 55,
        'is_person_of_peace' => true,
    ]);

    $this->actingAs($player);

    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $ward->id)
        ->assertSee('Kenji Tanaka')
        ->assertSee('🇯🇵 Japanese')
        ->assertSee('♂ 48 yrs')
        ->call('selectNpc', $kenji->id)
        ->assertSee('Kenji Tanaka (田中 健二)')
        ->assertSee('♂ 48y');
});

test('nagoya oikos seeder seeds accurate demographics for archetypal citizens', function () {
    $this->seed(NagoyaOikosSeeder::class);

    $linh = Npc::where('name', 'Linh Nguyen')->first();
    expect($linh)->not->toBeNull()
        ->and($linh->nationality)->toBe('Vietnamese')
        ->and($linh->gender)->toBe('female')
        ->and($linh->age)->toBe(21)
        ->and($linh->nationalityFlag)->toBe('🇻🇳');

    $carlos = Npc::where('name', 'Carlos Santos')->first();
    expect($carlos)->not->toBeNull()
        ->and($carlos->nationality)->toBe('Brazilian')
        ->and($carlos->gender)->toBe('male')
        ->and($carlos->age)->toBe(32)
        ->and($carlos->nationalityFlag)->toBe('🇧🇷');

    $keiko = Npc::where('name', 'Keiko Yamamoto')->first();
    expect($keiko)->not->toBeNull()
        ->and($keiko->nationality)->toBe('Japanese')
        ->and($keiko->gender)->toBe('female')
        ->and($keiko->age)->toBe(64)
        ->and($keiko->nationalityFlag)->toBe('🇯🇵');
});
