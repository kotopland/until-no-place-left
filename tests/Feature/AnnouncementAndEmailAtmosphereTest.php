<?php

declare(strict_types=1);

use App\Enums\LeadershipTier;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Livewire\GameChat;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\MonthlyReport;
use App\Models\MovementCelebration;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use App\Notifications\MagicLoginLinkNotification;
use App\Notifications\MonthlyMovementReportNotification;
use App\Notifications\PlayerInactivityReminderNotification;
use Livewire\Livewire;

test('announcement banner displays big avatars, church behind, and nagoya location map', function (): void {
    $session = GameSession::factory()->create(['is_active' => true]);
    $ward = Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'center_lat' => 35.1667,
        'center_lng' => 136.9667,
    ]);

    $missionary = User::factory()->create([
        'name' => 'Missionary Hannah',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'gender' => 'female',
        'age' => 29,
        'nationality' => 'American',
    ]);

    $church = Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Chikusa Living Water Circle',
        'leader_user_id' => $missionary->id,
        'generation' => 1,
        'elements_json' => ['worship', 'prayer', 'word'],
        'is_healthy_church' => true,
        'health_score' => 88,
        'attenders_count' => 14,
        'believers_count' => 10,
        'baptized_count' => 8,
        'lat' => 35.1680,
        'lng' => 136.9690,
        'founded_game_month' => 1,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'church_id' => $church->id,
        'name' => 'Kenji Sato',
        'name_ja' => '佐藤 健二',
        'gender' => 'male',
        'age' => 34,
        'nationality' => 'Japanese',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'status' => ReceptivityStatus::Green,
        'leadership_tier' => LeadershipTier::L1_SeedSower,
        'generation' => 1,
        'discipler_user_id' => $missionary->id,
        'lat' => 35.1650,
        'lng' => 136.9640,
    ]);

    MovementCelebration::recordBaptism(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Chikusa-ku (千種区)',
        personName: 'Kenji Sato (佐藤 健二)',
        personRole: 'Salaryman',
        disciplerName: 'Missionary Hannah',
        details: 'Kenji was baptized in Chikusa-ku!',
        generation: 1,
        gameMonth: 2,
        gameWeek: 3,
        userId: $missionary->id,
        npcId: $npc->id,
    );

    $this->actingAs($missionary);

    Livewire::test(NagoyaMovementDashboard::class)
        ->set('currentTab', 'map')
        ->assertSee('Kenji Sato (佐藤 健二)')
        ->assertSee('Missionary Hannah')
        ->assertSee('Chikusa Living Water Circle')
        ->assertSee('14 Attenders')
        ->assertSeeHtml('width: 112px; height: 112px;')
        ->assertSeeHtml('width: 80px; height: 80px;')
        ->assertSeeHtml('aria-label="Nagoya location map"')
        ->assertSeeHtml('<polygon');
});

test('game chat announcements channel renders avatars, church behind, and location map', function (): void {
    $session = GameSession::factory()->create(['is_active' => true]);
    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
    ]);

    $missionary = User::factory()->create([
        'name' => 'Catalyst Daniel',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
    ]);

    $church = Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Sakae Oikos Fellowship',
        'leader_user_id' => $missionary->id,
        'generation' => 1,
        'elements_json' => ['worship', 'word'],
        'is_healthy_church' => false,
        'health_score' => 65,
        'attenders_count' => 7,
        'believers_count' => 4,
        'baptized_count' => 2,
        'lat' => 35.1681,
        'lng' => 136.9066,
        'founded_game_month' => 1,
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'church_id' => $church->id,
        'name' => 'Aoi Nakamura',
        'name_ja' => '中村 葵',
        'gender' => 'female',
        'age' => 22,
        'nationality' => 'Japanese',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'status' => ReceptivityStatus::Green,
        'leadership_tier' => LeadershipTier::L1_SeedSower,
        'generation' => 1,
        'discipler_user_id' => $missionary->id,
        'lat' => 35.1695,
        'lng' => 136.9080,
    ]);

    MovementCelebration::recordChurchPlanted(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Naka-ku (中区)',
        churchName: 'Sakae Oikos Fellowship',
        leaderName: 'Aoi Nakamura (中村 葵)',
        startedByName: 'Catalyst Daniel',
        details: 'New church planted in Naka-ku!',
        generation: 1,
        gameMonth: 1,
        gameWeek: 2,
        userId: $missionary->id,
        npcId: $npc->id,
        churchId: $church->id,
    );

    $this->actingAs($missionary);

    Livewire::test(GameChat::class)
        ->call('setChannel', 'announcements')
        ->assertSee('Aoi Nakamura (中村 葵)')
        ->assertSee('Sakae Oikos Fellowship')
        ->assertSeeHtml('width: 80px; height: 80px;')
        ->assertSeeHtml('aria-label="Nagoya location map"');
});

test('emails include avatar and nagoya ward map images', function (): void {
    $session = GameSession::factory()->create(['is_active' => true]);
    $ward = Ward::create([
        'name' => 'Showa-ku',
        'name_ja' => '昭和区',
        'slug' => 'showa-ku',
    ]);

    $player = User::factory()->create([
        'name' => 'Sarah Missionary',
        'email' => 'sarah@example.com',
        'current_game_session_id' => $session->id,
        'home_ward_id' => $ward->id,
        'gender' => 'female',
        'age' => 31,
        'nationality' => 'Canadian',
    ]);

    $npc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Haruto Yamada',
        'name_ja' => '山田 陽翔',
        'gender' => 'male',
        'age' => 27,
        'nationality' => 'Japanese',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'status' => ReceptivityStatus::Green,
        'leadership_tier' => LeadershipTier::L1_SeedSower,
        'generation' => 1,
        'discipler_user_id' => $player->id,
    ]);

    MovementCelebration::recordSalvation(
        gameSessionId: $session->id,
        wardId: $ward->id,
        wardName: 'Showa-ku (昭和区)',
        personName: 'Haruto Yamada (山田 陽翔)',
        personRole: 'Student',
        disciplerName: 'Sarah Missionary',
        details: 'Haruto trusted Christ!',
        generation: 1,
        gameMonth: 3,
        gameWeek: 1,
        userId: $player->id,
        npcId: $npc->id,
    );

    $report = MonthlyReport::create([
        'game_session_id' => $session->id,
        'user_id' => $player->id,
        'game_month' => 3,
        'summary_title' => 'Month 3 Kingdom Report',
        'highlights_json' => ['Haruto Yamada prayed to receive Christ'],
        'answered_prayers_json' => [],
        'disciples_multiplied_count' => 1,
        'churches_planted_count' => 0,
        'generational_score_gained' => 25,
        'total_generational_score' => 125,
        'coach_tips_json' => [],
    ]);

    $monthlyHtml = (string) (new MonthlyMovementReportNotification($report))->toMail($player)->render();
    expect($monthlyHtml)
        ->toContain('/media/logo.png')
        ->not->toContain('laravel.com/img/notification-logo')
        ->not->toContain('&lt;div')
        ->not->toContain('&lt;img')
        ->toContain('/media/avatar.png')
        ->toContain('/media/ward-map.png')
        ->toContain('Sarah Missionary')
        ->toContain('Haruto Yamada');

    $inactivityHtml = (string) (new PlayerInactivityReminderNotification(3))->toMail($player)->render();
    expect($inactivityHtml)
        ->toContain('/media/logo.png')
        ->not->toContain('laravel.com/img/notification-logo')
        ->not->toContain('&lt;div')
        ->not->toContain('&lt;img')
        ->toContain('/media/avatar.png')
        ->toContain('/media/ward-map.png')
        ->toContain('Sarah Missionary')
        ->toContain('Haruto Yamada');

    $magicHtml = (string) (new MagicLoginLinkNotification(
        url: 'http://example.test/auth/magic-link?test=1',
        name: 'Sarah Missionary',
        isNewUser: true,
    ))->toMail($player)->render();
    expect($magicHtml)
        ->toContain('/media/logo.png')
        ->not->toContain('laravel.com/img/notification-logo')
        ->not->toContain('&lt;div')
        ->not->toContain('&lt;img')
        ->toContain('/media/avatar.png')
        ->toContain('/media/ward-map.png')
        ->toContain('Sarah Missionary');
});

test('public media endpoints return valid png images for logo, avatars, and nagoya ward maps', function (): void {
    $logoResponse = $this->get(route('media.logo'));
    $logoResponse->assertStatus(200)
        ->assertHeader('Content-Type', 'image/png');
    expect(substr($logoResponse->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");

    $ward = Ward::create([
        'name' => 'Meito-ku',
        'name_ja' => '名東区',
        'slug' => 'meito-ku',
    ]);

    $avatarResponse = $this->get(route('media.avatar', [
        'gender' => 'female',
        'age' => 28,
        'nationality' => 'Japanese',
        'role' => 'student',
        'status' => 'green',
        'size' => 128,
        'seed' => 'Yuki Takahashi',
    ]));

    $avatarResponse->assertStatus(200)
        ->assertHeader('Content-Type', 'image/png');
    expect(substr($avatarResponse->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");

    $mapResponse = $this->get(route('media.ward-map', [
        'ward_id' => $ward->id,
        'accent' => '10b981',
        'title' => 'Meito-ku Frontier',
    ]));

    $mapResponse->assertStatus(200)
        ->assertHeader('Content-Type', 'image/png');
    expect(substr($mapResponse->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

test('invitation page renders live nagoya wards status map under the facts strip', function (): void {
    $session = GameSession::factory()->create([
        'is_active' => true,
        'current_game_month' => 4,
        'current_game_week' => 2,
    ]);
    $ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'spiritual_openness' => 45,
        'center_lat' => 35.1681,
        'center_lng' => 136.9066,
    ]);
    Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $ward->id,
        'name' => 'Sakae Light Church',
        'generation' => 1,
        'elements_json' => ['worship', 'word'],
        'is_healthy_church' => true,
        'health_score' => 90,
        'attenders_count' => 12,
        'believers_count' => 9,
        'baptized_count' => 6,
        'lat' => 35.1681,
        'lng' => 136.9066,
        'founded_game_month' => 1,
    ]);

    $response = $this->get(route('invitation'));

    $response->assertStatus(200)
        ->assertSee('Empty fields to reach')
        ->assertSee('Awaiting the Good News')
        ->assertSee('Nagoya Wards — Current Status')
        ->assertSee('Month 4, Week 2')
        ->assertSee('1 Active Churches')
        ->assertSee('aria-label="Nagoya Wards Current Status Map"', false)
        ->assertSee('Sakae Light Church');
});
