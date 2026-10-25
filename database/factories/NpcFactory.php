<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BaptismStatus;
use App\Enums\LeadershipTier;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Npc>
 */
class NpcFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'ward_id' => Ward::first() ? Ward::first()->id : 1,
            'name' => fake()->name(),
            'name_ja' => '佐藤 太郎',
            'role_archetype' => NpcRoleArchetype::Salaryman,
            'need_state' => NpcNeedState::None,
            'status' => ReceptivityStatus::Yellow,
            'prayed_salvation' => false,
            'trust_level' => 45,
            'is_person_of_peace' => false,
            'is_baptized' => false,
            'baptism_status' => BaptismStatus::Uncommitted,
            'trained_411' => false,
            'leadership_tier' => LeadershipTier::None,
            'generation' => 0,
            'lat' => 35.1700,
            'lng' => 136.9000,
            'backstory' => 'Resident of Nagoya ward exploring faith.',
            'spiritual_journal' => [],
        ];
    }
}
