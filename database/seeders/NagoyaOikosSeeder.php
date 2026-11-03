<?php

namespace Database\Seeders;

use App\Enums\ChurchElement;
use App\Enums\ReceptivityStatus;
use App\Enums\RelationshipType;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\OikosRelationship;
use App\Models\User;
use App\Models\Ward;
use App\Services\AiColaborerService;
use App\Services\JapaneseNameRegistry;
use App\Services\NagoyaGeoJsonService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class NagoyaOikosSeeder extends Seeder
{
    /**
     * Run the database seeds for Nagoya 16 Wards and Oikos network.
     */
    public function run(): void
    {
        // 0. Ensure authentic 16-ward GIS boundaries are imported and synced
        $repoFile = resource_path('data/nagoya-border.json');
        $storageFile = storage_path('app/nagoya-border.json');

        if (File::exists($storageFile) && ! File::exists($repoFile)) {
            File::ensureDirectoryExists(dirname($repoFile));
            @File::copy($storageFile, $repoFile);
        } elseif (File::exists($repoFile) && ! File::exists($storageFile)) {
            File::ensureDirectoryExists(dirname($storageFile));
            @File::copy($repoFile, $storageFile);
        }

        app(NagoyaGeoJsonService::class)->loadOrImportWardGeoJson($repoFile);

        // 1. Seed Nagoya's 16 Wards
        $wardsData = [
            [
                'name' => 'Chikusa-ku',
                'name_ja' => '千種区',
                'slug' => 'chikusa-ku',
                'center_lat' => 35.1666,
                'center_lng' => 136.9500,
                'spiritual_openness' => 45,
                'poverty_need_index' => 12,
                'population' => 165000,
                'description' => 'Academic and residential hub hosting Nagoya University. Vibrant youth, trendy cafes around Kakuozan and Motoyama, and open student networks.',
            ],
            [
                'name' => 'Higashi-ku',
                'name_ja' => '東区',
                'slug' => 'higashi-ku',
                'center_lat' => 35.1833,
                'center_lng' => 136.9333,
                'spiritual_openness' => 25,
                'poverty_need_index' => 15,
                'population' => 84000,
                'description' => 'Historic samurai district featuring the Tokugawa Art Museum, Ozone commercial corridor, and established traditional neighborhoods.',
            ],
            [
                'name' => 'Kita-ku',
                'name_ja' => '北区',
                'slug' => 'kita-ku',
                'center_lat' => 35.2000,
                'center_lng' => 136.9167,
                'spiritual_openness' => 20,
                'poverty_need_index' => 25,
                'population' => 163000,
                'description' => 'Working-class residential zone along the Meijo subway line with an aging population and deep neighborhood roots.',
            ],
            [
                'name' => 'Nishi-ku',
                'name_ja' => '西区',
                'slug' => 'nishi-ku',
                'center_lat' => 35.2000,
                'center_lng' => 136.8833,
                'spiritual_openness' => 25,
                'poverty_need_index' => 20,
                'population' => 150000,
                'description' => 'Blends industrial heritage (Toyota Commemorative Museum, Noritake Garden) with family suburbs alongside the Shonai River.',
            ],
            [
                'name' => 'Nakamura-ku',
                'name_ja' => '中村区',
                'slug' => 'nakamura-ku',
                'center_lat' => 35.1709,
                'center_lng' => 136.8816,
                'spiritual_openness' => 35,
                'poverty_need_index' => 70, // High poverty/homelessness near Meieki
                'population' => 135000,
                'description' => 'Gateway of Central Japan centered on Nagoya Station (Meieki). Sky-high commercial towers juxtaposed with high concentrations of homeless and needy citizens around station overpasses.',
            ],
            [
                'name' => 'Naka-ku',
                'name_ja' => '中区',
                'slug' => 'naka-ku',
                'center_lat' => 35.1700,
                'center_lng' => 136.9066,
                'spiritual_openness' => 40,
                'poverty_need_index' => 65, // High poverty/homelessness in Shirakawa Park & Sakae
                'population' => 90000,
                'description' => 'Heart of downtown Nagoya comprising Sakae, Fushimi, and Osu. Dense blend of exhausted corporate salarymen, energetic youth subcultures, and unhoused communities in urban parks.',
            ],
            [
                'name' => 'Showa-ku',
                'name_ja' => '昭和区',
                'slug' => 'showa-ku',
                'center_lat' => 35.1500,
                'center_lng' => 136.9333,
                'spiritual_openness' => 35,
                'poverty_need_index' => 10,
                'population' => 108000,
                'description' => 'Prestigious education and residential ward (Nanzan University, Nagoya Institute of Technology) with leafy family suburbs like Gokiso and Yagoto.',
            ],
            [
                'name' => 'Mizuho-ku',
                'name_ja' => '瑞穂区',
                'slug' => 'mizuho-ku',
                'center_lat' => 35.1333,
                'center_lng' => 136.9333,
                'spiritual_openness' => 22,
                'poverty_need_index' => 15,
                'population' => 106000,
                'description' => 'Home to Mizuho Athletics Stadium and scenic Yamazaki River cherry blossoms. Quiet, stable, multi-generational families.',
            ],
            [
                'name' => 'Atsuta-ku',
                'name_ja' => '熱田区',
                'slug' => 'atsuta-ku',
                'center_lat' => 35.1300,
                'center_lng' => 136.9000,
                'spiritual_openness' => 18,
                'poverty_need_index' => 35,
                'population' => 66000,
                'description' => 'Spiritual stronghold dominated by the ancient Atsuta Jingu shrine. Major transit junction at Kanayama connecting trains and subways.',
            ],
            [
                'name' => 'Nakagawa-ku',
                'name_ja' => '中川区',
                'slug' => 'nakagawa-ku',
                'center_lat' => 35.1333,
                'center_lng' => 136.8667,
                'spiritual_openness' => 20,
                'poverty_need_index' => 35,
                'population' => 220000,
                'description' => 'Canal-laced southwest district with a massive commuter populace, small manufacturing businesses, and working-class families.',
            ],
            [
                'name' => 'Minato-ku',
                'name_ja' => '港区',
                'slug' => 'minato-ku',
                'center_lat' => 35.0833,
                'center_lng' => 136.8667,
                'spiritual_openness' => 38,
                'poverty_need_index' => 50,
                'population' => 145000,
                'description' => 'Port of Nagoya, logistics depots, and international factory workforce. Highest density of Latin American and Southeast Asian residents in Nagoya.',
            ],
            [
                'name' => 'Minami-ku',
                'name_ja' => '南区',
                'slug' => 'minami-ku',
                'center_lat' => 35.1000,
                'center_lng' => 136.9167,
                'spiritual_openness' => 20,
                'poverty_need_index' => 40,
                'population' => 136000,
                'description' => 'Industrial heartland hosting Nippon Gaishi Hall. Characterized by tightly knit craftsmen communities and an aging blue-collar base.',
            ],
            [
                'name' => 'Moriyama-ku',
                'name_ja' => '守山区',
                'slug' => 'moriyama-ku',
                'center_lat' => 35.2000,
                'center_lng' => 136.9833,
                'spiritual_openness' => 25,
                'poverty_need_index' => 10,
                'population' => 176000,
                'description' => 'Verdant northeastern ward with Mt. Togoku. Fast-growing suburban bedroom community with numerous young families.',
            ],
            [
                'name' => 'Midori-ku',
                'name_ja' => '緑区',
                'slug' => 'midori-ku',
                'center_lat' => 35.0833,
                'center_lng' => 136.9667,
                'spiritual_openness' => 28,
                'poverty_need_index' => 12,
                'population' => 248000,
                'description' => 'Most populous ward in Nagoya. Historic Arimatsu tie-dye village, sprawling parks (Odaka), and dynamic new housing developments.',
            ],
            [
                'name' => 'Meito-ku',
                'name_ja' => '名東区',
                'slug' => 'meito-ku',
                'center_lat' => 35.1667,
                'center_lng' => 137.0000,
                'spiritual_openness' => 50,
                'poverty_need_index' => 8,
                'population' => 165000,
                'description' => 'Eastern terminal at Fujigaoka. High concentration of internationally minded families, corporate returnees, and bilingual schools.',
            ],
            [
                'name' => 'Tempaku-ku',
                'name_ja' => '天白区',
                'slug' => 'tempaku-ku',
                'center_lat' => 35.1333,
                'center_lng' => 136.9833,
                'spiritual_openness' => 42,
                'poverty_need_index' => 14,
                'population' => 162000,
                'description' => 'Youthful university ward anchored by Meijo University campus, vibrant international dormitories, and scenic river valleys.',
            ],
        ];

        $wards = [];
        foreach ($wardsData as $data) {
            $wards[$data['slug']] = Ward::updateOrCreate(['slug' => $data['slug']], $data);
        }

        // 2. Create Active Game Session
        $session = GameSession::firstOrCreate(
            ['code' => 'nagoya-2026'],
            [
                'name' => 'Nagoya Harvest 2026 (No Place Left)',
                'current_game_month' => 1,
                'current_game_week' => 1,
                'weeks_processed_this_month' => 0,
                'current_game_day' => 1,
                'days_processed_this_month' => 0,
                'calendar_date' => '2026-04-01',
                'month_duration_minutes' => 720, // 12-hour turn cycle
                'next_tick_at' => now()->addHours(12),
                'next_weekly_tick_at' => now()->addMinutes(15),
                'next_daily_tick_at' => now()->addMinutes(15),
                'is_active' => true,
                'settings' => [
                    'city' => 'Nagoya',
                    'target_generations' => 4,
                    'ap_per_month' => 80,
                ],
            ]
        );

        // 3. Create or Update Default Solo Player (Missionary Coach)
        $player = User::firstOrCreate(
            ['email' => 'coach@nagoyaharvest.jp'],
            [
                'name' => 'Missionary Coach',
                'gender' => 'male',
                'age' => 35,
                'nationality' => 'Japanese',
                'password' => Hash::make('password123'),
                'current_game_session_id' => $session->id,
                'home_ward_id' => null, // 100% Solo Pioneer: start unassigned to choose in onboarding!
                'current_game_month' => 1,
                'current_game_week' => 1,
                'weeks_processed_this_month' => 0,
                'weeks_played_today' => 0,
                'action_points' => 20,
                'max_action_points' => 26,
                'weekly_action_points' => 20,
                'daily_action_points' => 20,
                'total_generational_score' => 60,
                'badges' => ['First Step', 'Prayer Walker'],
                'notify_email' => true,
                'notify_in_app' => true,
            ]
        );

        $playerUpdates = [];
        if (! $player->current_game_session_id) {
            $playerUpdates['current_game_session_id'] = $session->id;
        }
        if ($player->weekly_action_points < 20) {
            $playerUpdates['weekly_action_points'] = 20;
            $playerUpdates['daily_action_points'] = 20;
            $playerUpdates['max_action_points'] = 26;
            $playerUpdates['action_points'] = max(20, $player->action_points);
        }
        if (! empty($playerUpdates)) {
            $player->update($playerUpdates);
        }

        // 4. Seed Archetypal Nagoya NPCs across all 16 Wards
        // Sourced from JapaneseNameRegistry ensuring 100% unique names and a realistic Luke 10 PoP ratio (~9%).
        $npcsData = app(JapaneseNameRegistry::class)->getSeedWardsData($session, $wards, $player);

        $npcs = [];
        foreach ($npcsData as $key => $data) {
            $npcs[$key] = Npc::updateOrCreate(
                [
                    'game_session_id' => $session->id,
                    'name' => $data['name'],
                ],
                $data
            );
        }

        // 5. Seed Oikos Relational Edges (The Web of Trust)
        $oikosEdges = [
            // Kenji Tanaka's Oikos (Osu Market & Friends)
            [
                'npc_id' => $npcs['kenji_tanaka']->id,
                'related_npc_id' => $npcs['tomoko_tanaka']->id,
                'relationship_type' => RelationshipType::Family,
                'trust_level' => 85,
                'notes' => 'Husband and wife partnership in Osu store.',
            ],
            [
                'npc_id' => $npcs['kenji_tanaka']->id,
                'related_npc_id' => $npcs['hiroshi_sato']->id,
                'relationship_type' => RelationshipType::Friend,
                'trust_level' => 60,
                'notes' => 'College classmates who still meet at Osu cafes occasionally.',
            ],
            // Keiko Yamamoto's Family Oikos
            [
                'npc_id' => $npcs['keiko_yamamoto']->id,
                'related_npc_id' => $npcs['aoi_yamamoto']->id,
                'relationship_type' => RelationshipType::Family,
                'trust_level' => 90,
                'notes' => 'Mother and daughter living close in Gokiso.',
            ],
            // Masao Suzuki's Park Oikos
            [
                'npc_id' => $npcs['masao_suzuki']->id,
                'related_npc_id' => $npcs['goro_kondo']->id,
                'relationship_type' => RelationshipType::Friend,
                'trust_level' => 70,
                'notes' => 'Close friends surviving together in Shirakawa Park.',
            ],
            // Campus & Student Oikos
            [
                'npc_id' => $npcs['linh_nguyen']->id,
                'related_npc_id' => $npcs['yuki_kobayashi']->id,
                'relationship_type' => RelationshipType::Friend,
                'trust_level' => 65,
                'notes' => 'Met at language exchange club; study together at library.',
            ],
            // Daisuke Kato's Network (Family, School & Community)
            [
                'npc_id' => $npcs['daisuke_kato']->id,
                'related_npc_id' => $npcs['kenichi_kato']->id,
                'relationship_type' => RelationshipType::Family,
                'trust_level' => 80,
                'notes' => 'Brothers living close in Meito-ku who share weekly dinners.',
            ],
            [
                'npc_id' => $npcs['daisuke_kato']->id,
                'related_npc_id' => $npcs['sayaka_ito']->id,
                'relationship_type' => RelationshipType::Neighbor,
                'trust_level' => 45,
                'notes' => 'Live in neighboring apartment complexes in Fujigaoka.',
            ],
            [
                'npc_id' => $npcs['daisuke_kato']->id,
                'related_npc_id' => $npcs['misaki_nonaka']->id,
                'relationship_type' => RelationshipType::Neighbor,
                'trust_level' => 50,
                'notes' => 'Fellow educators living in Fujigaoka.',
            ],
            // Tempaku-ku Campus Network
            [
                'npc_id' => $npcs['linh_nguyen']->id,
                'related_npc_id' => $npcs['soichiro_hayakawa']->id,
                'relationship_type' => RelationshipType::Friend,
                'trust_level' => 60,
                'notes' => 'Fellow university students in Tempaku-ku.',
            ],
            // Kita-ku Person of Peace Network
            [
                'npc_id' => $npcs['ren_takahashi']->id,
                'related_npc_id' => $npcs['chiyo_okada']->id,
                'relationship_type' => RelationshipType::Neighbor,
                'trust_level' => 75,
                'notes' => 'Cafe regular whom Ren checks on weekly.',
            ],
            // Minato-ku Port Worker Network
            [
                'npc_id' => $npcs['carlos_santos']->id,
                'related_npc_id' => $npcs['rosa_morales']->id,
                'relationship_type' => RelationshipType::Friend,
                'trust_level' => 70,
                'notes' => 'Connects weekly at Latin American import grocery in Minato.',
            ],
            [
                'npc_id' => $npcs['carlos_santos']->id,
                'related_npc_id' => $npcs['mai_ishida']->id,
                'relationship_type' => RelationshipType::Coworker,
                'trust_level' => 55,
                'notes' => 'Port logistics colleagues at container pier.',
            ],
            // Midori-ku Community Network
            [
                'npc_id' => $npcs['naomi_fujiwara']->id,
                'related_npc_id' => $npcs['daiki_yoshikawa']->id,
                'relationship_type' => RelationshipType::Neighbor,
                'trust_level' => 60,
                'notes' => 'Suburban neighborhood committee and school PTA connections.',
            ],
        ];

        foreach ($oikosEdges as $edge) {
            OikosRelationship::updateOrCreate(
                [
                    'game_session_id' => $session->id,
                    'npc_id' => $edge['npc_id'],
                    'related_npc_id' => $edge['related_npc_id'],
                ],
                [
                    'relationship_type' => $edge['relationship_type'],
                    'trust_level' => $edge['trust_level'],
                    'notes' => $edge['notes'],
                ]
            );

            // Also create reverse relationship for realistic reciprocal network
            OikosRelationship::updateOrCreate(
                [
                    'game_session_id' => $session->id,
                    'npc_id' => $edge['related_npc_id'],
                    'related_npc_id' => $edge['npc_id'],
                ],
                [
                    'relationship_type' => $edge['relationship_type'],
                    'trust_level' => (int) round($edge['trust_level'] * 0.95),
                    'notes' => 'Reciprocal connection: '.$edge['notes'],
                ]
            );
        }

        // 6. Seed Initial G1 3/3rds Group (Field 4 Church Circle)
        // Led by the player missionary coach; Daisuke Kato is an apprentice disciple who will plant G2!
        $g1Church = Church::updateOrCreate(
            [
                'game_session_id' => $session->id,
                'name' => 'Meito Living Seed (G1)',
            ],
            [
                'ward_id' => $wards['meito-ku']->id,
                'parent_church_id' => null,
                'leader_npc_id' => null,
                'leader_user_id' => $player->id,
                'generation' => 1,
                'elements_json' => [
                    ChurchElement::RepentanceBelief->value,
                    ChurchElement::Word->value,
                    ChurchElement::Prayer->value,
                    ChurchElement::Fellowship->value,
                ],
                'is_healthy_church' => false, // Still developing towards complete Acts 2 circle
                'health_score' => 60,
                'attenders_count' => 8,
                'believers_count' => 5,
                'baptized_count' => 4,
                'is_dissolved' => false,
                'started_by_name' => $player->name,
                'lat' => 35.1675,
                'lng' => 136.9990,
                'founded_game_month' => 1,
            ]
        );

        // Associate Green believers in Meito-ku with this initial G1 church
        Npc::where('game_session_id', $session->id)
            ->where('ward_id', $wards['meito-ku']->id)
            ->where('status', ReceptivityStatus::Green)
            ->update(['church_id' => $g1Church->id]);

        // 7. Seed/Ensure Automated Missionary Co-Laborers (for Team Co-op Mode)
        app(AiColaborerService::class)->ensureAiColaborers($session);
    }
}
