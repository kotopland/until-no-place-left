<?php

use App\Enums\MinistryActionType;
use App\Enums\QueuedActionStatus;
use App\Enums\ReceptivityStatus;
use App\Livewire\ActionQueuePlanner;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\NpcContribution;
use App\Models\QueuedMonthlyAction;
use App\Models\User;
use App\Models\Ward;
use App\Services\FourFieldsEngine;
use App\Services\RealLifeSimulationService;
use App\Services\ShowcaseSandboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ward = Ward::create([
        'name' => 'Naka-ku',
        'name_ja' => '中区',
        'slug' => 'naka-ku',
        'population' => 95000,
        'spiritual_openness' => 25,
        'center_lat' => 35.1681,
        'center_lng' => 136.9066,
    ]);

    $this->session = GameSession::create([
        'name' => 'Training Progression Test',
        'current_game_month' => 1,
        'current_game_week' => 1,
        'status' => 'active',
    ]);
});

it('locks level 1, 2, and 3 actions for an untrained player until invited and trained', function () {
    $player = User::factory()->untrained()->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 1,
        'action_points' => 20,
    ]);

    $npc = Npc::factory()->create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'status' => ReceptivityStatus::Unreached,
    ]);

    expect(MinistryActionType::CasualTalk->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::PrayerWalk->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::SabbathRest->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::OikosMapping->isUnlockedFor($player))->toBeFalse()
        ->and(MinistryActionType::Share3Circles->isUnlockedFor($player))->toBeFalse()
        ->and(MinistryActionType::CommitToBaptism->isUnlockedFor($player))->toBeFalse()
        ->and(MinistryActionType::ChurchCircles->isUnlockedFor($player))->toBeFalse()
        ->and(MinistryActionType::MentorTimothy->isUnlockedFor($player))->toBeFalse()
        ->and(MinistryActionType::MidLevelConference->isUnlockedFor($player))->toBeFalse();

    $this->actingAs($player);

    Livewire::test(ActionQueuePlanner::class)
        ->set('selectedWardId', $this->ward->id)
        ->set('selectedNpcId', $npc->id)
        ->set('selectedActionType', MinistryActionType::Share3Circles->value)
        ->call('queueAction');

    expect(QueuedMonthlyAction::where('user_id', $player->id)->count())->toBe(0);
});

it('unlocks level 1 actions including oikos mapping after accepting 411 training for 2 ap', function () {
    $player = User::factory()->untrained()->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 1,
        'action_points' => 20,
    ]);

    expect($player->nextAvailableTrainingInvite())->toBe(1);

    $this->actingAs($player);

    Livewire::test(ActionQueuePlanner::class)
        ->call('acceptTrainingInvite', 1);

    $player->refresh();

    expect($player->training_level)->toBe(1)
        ->and($player->level_1_completed_month)->toBe(1)
        ->and($player->action_points)->toBe(18)
        ->and(MinistryActionType::OikosMapping->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::Share15SecTestimony->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::Share3Circles->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::PraySalvation->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::Train411->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::CommitToBaptism->isUnlockedFor($player))->toBeFalse();
});

it('resolves oikos mapping to surface yellow seekers and grow local churches', function () {
    $player = User::factory()->withTrainingLevel(1, level1Month: 1)->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 1,
        'action_points' => 20,
    ]);

    $unreachedNpc = Npc::factory()->create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'status' => ReceptivityStatus::Unreached,
        'trust_level' => 10,
    ]);

    $church = Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'Naka House Church',
        'leader_user_id' => $player->id,
        'generation' => 1,
        'elements_json' => ['worship', 'word'],
        'is_healthy_church' => false,
        'health_score' => 60,
        'attenders_count' => 4,
        'believers_count' => 3,
        'baptized_count' => 1,
        'lat' => 35.1681,
        'lng' => 136.9066,
        'founded_game_month' => 1,
    ]);

    $action = QueuedMonthlyAction::create([
        'game_session_id' => $this->session->id,
        'user_id' => $player->id,
        'ward_id' => $this->ward->id,
        'action_type' => MinistryActionType::OikosMapping,
        'game_month' => 1,
        'game_week' => 1,
        'game_day' => 1,
        'ap_cost' => 1,
        'status' => QueuedActionStatus::Pending,
    ]);

    $engine = app(FourFieldsEngine::class);
    $outcome = $engine->resolveSingleAction($action, 1);

    $unreachedNpc->refresh();
    $church->refresh();

    expect($outcome->newStatus)->toBe(ReceptivityStatus::Yellow)
        ->and($unreachedNpc->status)->toBe(ReceptivityStatus::Yellow)
        ->and($church->attenders_count)->toBeGreaterThan(4);
});

it('requires two months after level 1 before unlocking level 2 invite for 12 ap', function () {
    $player = User::factory()->withTrainingLevel(1, level1Month: 1)->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 2,
        'action_points' => 20,
    ]);

    expect($player->isEligibleForTrainingInvite(2))->toBeFalse()
        ->and($player->nextAvailableTrainingInvite())->toBeNull();

    $player->update(['current_game_month' => 3]);
    $player->refresh();

    expect($player->isEligibleForTrainingInvite(2))->toBeTrue()
        ->and($player->nextAvailableTrainingInvite())->toBe(2);

    $this->actingAs($player);

    Livewire::test(ActionQueuePlanner::class)
        ->call('acceptTrainingInvite', 2);

    $player->refresh();

    expect($player->training_level)->toBe(2)
        ->and($player->level_2_completed_month)->toBe(3)
        ->and($player->action_points)->toBe(8)
        ->and(MinistryActionType::CommitToBaptism->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::CommandsOfChrist->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::SwordBibleStudy->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::ChurchCircles->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::MentorTimothy->isUnlockedFor($player))->toBeFalse();
});

it('requires starting a three third group before unlocking level 3 invite for 12 ap', function () {
    $player = User::factory()->withTrainingLevel(2, level1Month: 1)->create([
        'current_game_session_id' => $this->session->id,
        'home_ward_id' => $this->ward->id,
        'current_game_month' => 4,
        'action_points' => 20,
    ]);

    expect($player->hasStartedThreeThirdGroup())->toBeFalse()
        ->and($player->isEligibleForTrainingInvite(3))->toBeFalse();

    Church::create([
        'game_session_id' => $this->session->id,
        'ward_id' => $this->ward->id,
        'name' => 'Sakae 3/3rds Group',
        'leader_user_id' => $player->id,
        'generation' => 1,
        'elements_json' => ['word', 'prayer'],
        'is_healthy_church' => false,
        'health_score' => 55,
        'attenders_count' => 3,
        'believers_count' => 3,
        'baptized_count' => 1,
        'lat' => 35.1681,
        'lng' => 136.9066,
        'founded_game_month' => 4,
    ]);

    expect($player->hasStartedThreeThirdGroup())->toBeTrue()
        ->and($player->isEligibleForTrainingInvite(3))->toBeTrue()
        ->and($player->nextAvailableTrainingInvite())->toBe(3);

    $this->actingAs($player);

    Livewire::test(ActionQueuePlanner::class)
        ->call('acceptTrainingInvite', 3);

    $player->refresh();

    expect($player->training_level)->toBe(3)
        ->and($player->level_3_completed_month)->toBe(4)
        ->and($player->action_points)->toBe(8)
        ->and(MinistryActionType::MentorTimothy->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::IronOnIron->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::SwarmTraining->isUnlockedFor($player))->toBeTrue()
        ->and(MinistryActionType::MidLevelConference->isUnlockedFor($player))->toBeTrue();
});

it('advances simulated ai co-laborers through npl training levels and records oikos mapping in showcase simulation', function () {
    $sandbox = app(ShowcaseSandboxService::class);
    $session = $sandbox->activate(recreate: true);

    try {
        $simulator = app(RealLifeSimulationService::class);
        $simulator->simulate(
            session: $session,
            targetMonths: 3,
            mode: 'coop',
            resetFirst: true,
            rngSeed: 20260401,
        );

        $aiPlayers = User::where('current_game_session_id', $session->id)->get();
        expect($aiPlayers->count())->toBeGreaterThanOrEqual(4);

        foreach ($aiPlayers as $aiPlayer) {
            $expectedLevel = $aiPlayer->hasStartedThreeThirdGroup() ? 3 : 2;
            expect($aiPlayer->training_level)->toBe($expectedLevel)
                ->and($aiPlayer->level_1_completed_month)->toBe(1)
                ->and($aiPlayer->level_2_completed_month)->toBe(3);

            if ($expectedLevel === 3) {
                expect($aiPlayer->level_3_completed_month)->toBe(3);
            }
        }

        $oikosMappingCount = NpcContribution::where('game_session_id', $session->id)
            ->where('action_type', MinistryActionType::OikosMapping)
            ->count();

        expect($oikosMappingCount)->toBeGreaterThan(0);
    } finally {
        $sandbox->deactivate();
    }
});
