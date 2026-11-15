<?php

declare(strict_types=1);

use App\Enums\LeadershipTier;
use App\Enums\MinistryActionType;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\User;
use App\Models\Ward;
use Livewire\Livewire;

test('step 1 renders interactive clickable ward map and step 2 groups residents in required order with counts and selected action indicator', function (): void {
    $session = GameSession::factory()->create(['is_active' => true]);
    $wardA = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'spiritual_openness' => 45,
        'center_lat' => 35.1681,
        'center_lng' => 136.9066,
    ]);
    $wardB = Ward::create([
        'name' => 'Chikusa-ku',
        'name_ja' => '千種区',
        'slug' => 'chikusa-ku',
        'spiritual_openness' => 30,
        'center_lat' => 35.1667,
        'center_lng' => 136.9667,
    ]);

    $player = User::factory()->create([
        'current_game_session_id' => $session->id,
        'home_ward_id' => $wardA->id,
        'action_points' => 20,
        'training_level' => 2,
    ]);

    $church = Church::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'name' => 'Sakae House Church',
        'generation' => 1,
        'elements_json' => ['worship', 'word'],
        'is_healthy_church' => true,
        'health_score' => 85,
        'attenders_count' => 8,
        'believers_count' => 6,
        'baptized_count' => 4,
        'lat' => 35.1681,
        'lng' => 136.9066,
        'founded_game_month' => 1,
    ]);

    // 1. Leading Group(s)
    $leaderNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'church_id' => $church->id,
        'name' => 'Leader Kenji',
        'name_ja' => 'リーダー健二',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'leadership_tier' => LeadershipTier::L2_ChurchPlanter,
    ]);
    $church->update(['leader_npc_id' => $leaderNpc->id]);

    // 2. In a Group
    $memberNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'church_id' => $church->id,
        'name' => 'Member Yumi',
        'name_ja' => 'メンバー由美',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'leadership_tier' => LeadershipTier::L1_SeedSower,
    ]);

    // 3. Accepted Jesus (not in a group yet)
    $savedNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'name' => 'Saved Haruto',
        'name_ja' => '救われた陽翔',
        'role_archetype' => NpcRoleArchetype::Shopkeeper,
        'status' => ReceptivityStatus::Green,
        'prayed_salvation' => true,
        'leadership_tier' => LeadershipTier::None,
    ]);

    // 4. Yellow (Seeking)
    $yellowNpc = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'name' => 'Seeking Aoi',
        'name_ja' => '求道者葵',
        'role_archetype' => NpcRoleArchetype::UniversityStudent,
        'status' => ReceptivityStatus::Yellow,
        'prayed_salvation' => false,
        'leadership_tier' => LeadershipTier::None,
    ]);

    // 5. Red (Not Yet Seeking)
    $redNpc1 = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'name' => 'Red Takeshi',
        'name_ja' => '未信者武',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'status' => ReceptivityStatus::Red,
        'prayed_salvation' => false,
        'leadership_tier' => LeadershipTier::None,
    ]);
    $redNpc2 = Npc::create([
        'game_session_id' => $session->id,
        'ward_id' => $wardA->id,
        'name' => 'Red Daiki',
        'name_ja' => '未信者大輝',
        'role_archetype' => NpcRoleArchetype::Salaryman,
        'status' => ReceptivityStatus::Red,
        'prayed_salvation' => false,
        'leadership_tier' => LeadershipTier::None,
    ]);

    $this->actingAs($player);

    $component = Livewire::test(ActionQueuePlanner::class)
        ->call('selectWard', $wardA->id)
        ->assertSeeHtml('aria-label="Interactive Nagoya 16 Wards District Picker Map"')
        ->assertSeeHtml("wire:click=\"selectWard({$wardB->id})\"")
        ->assertSeeInOrder([
            'Leading Group(s)',
            '1 person',
            'Leader Kenji',
            'In a Group',
            '1 person',
            'Member Yumi',
            'Accepted Jesus',
            '1 person',
            'Saved Haruto',
            'Yellow (Seeking)',
            '1 person',
            'Seeking Aoi',
            'Red (Not Yet Seeking)',
            '2 people',
            'Red Takeshi',
            'Red Daiki',
        ])
        ->assertSee('Expand All')
        ->assertSee('Collapse All');

    // Select an NPC and an action, and verify it is indicated under the person's card
    $component
        ->call('selectNpc', $yellowNpc->id)
        ->set('selectedActionType', MinistryActionType::Share3Circles->value)
        ->assertSee('⚡ Selected Action:')
        ->assertSee(MinistryActionType::Share3Circles->label());

    // Queue the action and verify the queued indicator appears under the person's card
    $component
        ->call('queueAction')
        ->assertSee('📋 Queued (W1):')
        ->assertSee(MinistryActionType::Share3Circles->label());
});
