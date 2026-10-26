<?php

namespace Database\Seeders;

use App\Enums\BaptismStatus;
use App\Enums\ChurchElement;
use App\Enums\LeadershipTier;
use App\Enums\NpcNeedState;
use App\Enums\NpcRoleArchetype;
use App\Enums\ReceptivityStatus;
use App\Enums\RelationshipType;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\OikosRelationship;
use App\Models\User;
use App\Models\Ward;
use App\Services\AiColaborerService;
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
                'calendar_date' => '2026-04-01',
                'month_duration_minutes' => 720, // 12-hour turn cycle
                'next_tick_at' => now()->addHours(12),
                'is_active' => true,
                'settings' => [
                    'city' => 'Nagoya',
                    'target_generations' => 4,
                    'ap_per_month' => 12,
                ],
            ]
        );

        // 3. Create or Update Default Solo Player (Missionary Coach)
        $player = User::firstOrCreate(
            ['email' => 'coach@nagoyaharvest.jp'],
            [
                'name' => 'Missionary Coach',
                'password' => Hash::make('password123'),
                'current_game_session_id' => $session->id,
                'home_ward_id' => null, // 100% Solo Pioneer: start unassigned to choose in onboarding!
                'action_points' => 12,
                'max_action_points' => 12,
                'daily_action_points' => 12,
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
        if ($player->daily_action_points < 12) {
            $playerUpdates['daily_action_points'] = 12;
            $playerUpdates['max_action_points'] = 12;
        }
        if (! empty($playerUpdates)) {
            $player->update($playerUpdates);
        }

        // 4. Seed Archetypal Nagoya NPCs
        $npcsData = [
            // --- EXISTING BELIEVER (Field 3/5 Multiplication Catalyst) ---
            'daisuke_kato' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['meito-ku']->id,
                'name' => 'Daisuke Kato',
                'name_ja' => '加藤 大輔',
                'role_archetype' => NpcRoleArchetype::ExistingBeliever,
                'need_state' => NpcNeedState::None,
                'status' => ReceptivityStatus::Green,
                'prayed_salvation' => true,
                'trust_level' => 65,
                'is_person_of_peace' => false,
                'is_baptized' => true,
                'baptism_status' => BaptismStatus::Baptized,
                'trained_411' => false, // Needs Field 3 411 Training to activate!
                'leadership_tier' => LeadershipTier::Apprentice,
                'generation' => 1,
                'discipler_user_id' => $player->id,
                'lat' => 35.1680,
                'lng' => 136.9980,
                'backstory' => 'High school English teacher in Meito-ku. Attends a traditional Sunday service passively but has never been trained in simple, reproducible disciple-making. Yearns to reach his students and coworkers.',
                'spiritual_journal' => [
                    ['month' => 0, 'entry' => 'Met player at Fujigaoka station cafe. Shared that he wants to witness to fellow teachers but feels unprepared.'],
                ],
            ],

            // --- PERSON OF PEACE (Luke 10: Osu Market Merchant) ---
            'kenji_tanaka' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['naka-ku']->id,
                'name' => 'Kenji Tanaka',
                'name_ja' => '田中 健二',
                'role_archetype' => NpcRoleArchetype::Shopkeeper,
                'need_state' => NpcNeedState::None,
                'status' => ReceptivityStatus::Yellow,
                'trust_level' => 55,
                'is_person_of_peace' => true, // Classic Luke 10 PoP!
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1585,
                'lng' => 136.9045,
                'backstory' => 'Charismatic owner of a vintage clothing shop in Osu Shopping Arcade. Generous and hospitable; knows every merchant and youth in the street. Deeply interested in spiritual matters and Western morality.',
                'spiritual_journal' => [
                    ['month' => 0, 'entry' => 'Welcomed player into his shop for green tea. Shared how he loves gathering people together.'],
                ],
            ],

            // --- KENJI'S WIFE (Oikos Family Node) ---
            'tomoko_tanaka' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['naka-ku']->id,
                'name' => 'Tomoko Tanaka',
                'name_ja' => '田中 智子',
                'role_archetype' => NpcRoleArchetype::Shopkeeper,
                'need_state' => NpcNeedState::None,
                'status' => ReceptivityStatus::Red,
                'trust_level' => 40,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1588,
                'lng' => 136.9048,
                'backstory' => 'Kenji\'s devoted wife who manages their shop bookkeeping. Respects Kenji\'s decisions; initially cautious about Christianity but trusts Kenji deeply.',
                'spiritual_journal' => [],
            ],

            // --- WORKPLACE BURNOUT / EMOTIONAL CRISIS (Receptive to Deep Talk) ---
            'hiroshi_sato' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['naka-ku']->id,
                'name' => 'Hiroshi Sato',
                'name_ja' => '佐藤 浩',
                'role_archetype' => NpcRoleArchetype::Salaryman,
                'need_state' => NpcNeedState::EmotionalCrisis,
                'status' => ReceptivityStatus::Red,
                'trust_level' => 20,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1712,
                'lng' => 136.9075,
                'backstory' => 'IT Project Lead in Sakae. Works 75 hours a week, suffering chronic insomnia, panic attacks, and loneliness. Direct cold preaching will make him withdraw, but patient active listening (Deep Talk) touches his heart.',
                'spiritual_journal' => [
                    ['month' => 0, 'entry' => 'Sitting alone on a bench in Hisaya-odori Park late at night, visibly stressed.'],
                ],
            ],

            // --- SICK / PHYSICAL CRISIS (Catalyst for Healing Prayer) ---
            'keiko_yamamoto' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['showa-ku']->id,
                'name' => 'Keiko Yamamoto',
                'name_ja' => '山本 恵子',
                'role_archetype' => NpcRoleArchetype::ElderlyResident,
                'need_state' => NpcNeedState::SickNeedsHealing,
                'status' => ReceptivityStatus::Red,
                'trust_level' => 30,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1520,
                'lng' => 136.9350,
                'backstory' => '64-year-old grandmother in Gokiso struggling with acute spinal inflammation and severe mobility pain. Devoutly looks after her family. Sincere prayer in Jesus\' name offers an immediate emotional and spiritual breakthrough.',
                'spiritual_journal' => [],
            ],

            // --- KEIKO'S DAUGHTER (Family Oikos Node) ---
            'aoi_yamamoto' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['showa-ku']->id,
                'name' => 'Aoi Yamamoto',
                'name_ja' => '山本 葵',
                'role_archetype' => NpcRoleArchetype::SingleParent,
                'need_state' => NpcNeedState::EmotionalCrisis,
                'status' => ReceptivityStatus::Unreached,
                'trust_level' => 25,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1525,
                'lng' => 136.9355,
                'backstory' => 'Keiko\'s adult daughter, raising a young child alone while balancing a nursing shift. Deeply concerned about her mother\'s health.',
                'spiritual_journal' => [],
            ],

            // --- HOMELESS / MATERIAL NEED (Word + Deed Mercy Outreach) ---
            'masao_suzuki' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['naka-ku']->id,
                'name' => 'Masao Suzuki (Masa-san)',
                'name_ja' => '鈴木 正雄（マサさん）',
                'role_archetype' => NpcRoleArchetype::HomelessIndividual,
                'need_state' => NpcNeedState::HomelessOrMaterialNeed,
                'status' => ReceptivityStatus::Red,
                'trust_level' => 20,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1630,
                'lng' => 136.8990,
                'backstory' => 'Unhoused gentleman living under the canopy around Shirakawa Park. Lost his small manufacturing business 6 years ago. Hungry and accustomed to cold rejection from passersby. Responds deeply to hot meals, dignity, and personal prayers.',
                'spiritual_journal' => [],
            ],

            // --- FELLOW UNHOUSED FRIEND (Park Oikos Node) ---
            'goro_kondo' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['naka-ku']->id,
                'name' => 'Goro Kondo',
                'name_ja' => '近藤 五郎',
                'role_archetype' => NpcRoleArchetype::HomelessIndividual,
                'need_state' => NpcNeedState::HomelessOrMaterialNeed,
                'status' => ReceptivityStatus::Unreached,
                'trust_level' => 15,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1635,
                'lng' => 136.8995,
                'backstory' => 'Masa-san\'s trusted camp neighbor in Shirakawa Park. Collects aluminum cans for survival.',
                'spiritual_journal' => [],
            ],

            // --- UNIVERSITY STUDENT / SEEKER (Receptive to International Events) ---
            'linh_nguyen' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['tempaku-ku']->id,
                'name' => 'Linh Nguyen',
                'name_ja' => 'リン・グエン',
                'role_archetype' => NpcRoleArchetype::InternationalExpat,
                'need_state' => NpcNeedState::SeekingCommunity,
                'status' => ReceptivityStatus::Yellow,
                'trust_level' => 50,
                'is_person_of_peace' => true,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1380,
                'lng' => 136.9790,
                'backstory' => '21-year-old Vietnamese undergraduate at Meijo University studying computer engineering. Eager to practice Japanese and English, actively seeking authentic friendships and open to Christian faith.',
                'spiritual_journal' => [],
            ],

            // --- JAPANESE STUDENT FRIEND (Campus Oikos Node) ---
            'yuki_kobayashi' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['chikusa-ku']->id,
                'name' => 'Yuki Kobayashi',
                'name_ja' => '小林 由紀',
                'role_archetype' => NpcRoleArchetype::UniversityStudent,
                'need_state' => NpcNeedState::SeekingCommunity,
                'status' => ReceptivityStatus::Yellow,
                'trust_level' => 45,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1650,
                'lng' => 136.9550,
                'backstory' => 'Nagoya University literature junior. Studied abroad in Canada for 6 months and misses deep spiritual conversations and multicultural gatherings.',
                'spiritual_journal' => [],
            ],

            // --- MEIEKI CORPORATE COMMUTER (Nakamura-ku) ---
            'sayaka_ito' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['nakamura-ku']->id,
                'name' => 'Sayaka Ito',
                'name_ja' => '伊藤 さやか',
                'role_archetype' => NpcRoleArchetype::Salaryman,
                'need_state' => NpcNeedState::Lonely,
                'status' => ReceptivityStatus::Red,
                'trust_level' => 20,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1720,
                'lng' => 136.8840,
                'backstory' => 'Moved from Shizuoka to work in Meieki marketing firm. Has no friends in Nagoya, spending every weekend alone in her apartment.',
                'spiritual_journal' => [],
            ],

            // --- PORT WORKER (Minato-ku) ---
            'carlos_santos' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['minato-ku']->id,
                'name' => 'Carlos Santos',
                'name_ja' => 'カルロス・サントス',
                'role_archetype' => NpcRoleArchetype::InternationalExpat,
                'need_state' => NpcNeedState::SeekingCommunity,
                'status' => ReceptivityStatus::Yellow,
                'trust_level' => 40,
                'is_person_of_peace' => true,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.0920,
                'lng' => 136.8710,
                'backstory' => 'Second-generation Brazilian-Japanese forklift operator at the Port of Nagoya container terminal. Connected with hundreds of overseas workers.',
                'spiritual_journal' => [],
            ],

            // --- DAISUKE'S BROTHER (Meito-ku Oikos Family Node) ---
            'kenichi_kato' => [
                'game_session_id' => $session->id,
                'ward_id' => $wards['meito-ku']->id,
                'name' => 'Kenichi Kato',
                'name_ja' => '加藤 健一',
                'role_archetype' => NpcRoleArchetype::Salaryman,
                'need_state' => NpcNeedState::SeekingCommunity,
                'status' => ReceptivityStatus::Yellow,
                'trust_level' => 52,
                'is_person_of_peace' => false,
                'is_baptized' => false,
                'trained_411' => false,
                'leadership_tier' => LeadershipTier::None,
                'generation' => 0,
                'lat' => 35.1685,
                'lng' => 136.9995,
                'backstory' => 'Daisuke\'s younger brother living in Meito-ku. Works in automotive logistics. Respects Daisuke deeply and is open to spiritual conversations with family.',
                'spiritual_journal' => [],
            ],
        ];

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
        Church::updateOrCreate(
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
                'health_score' => 45,
                'lat' => 35.1675,
                'lng' => 136.9990,
                'founded_game_month' => 1,
            ]
        );

        // 7. Seed/Ensure Automated Missionary Co-Laborers (for Team Co-op Mode)
        app(AiColaborerService::class)->ensureAiColaborers($session);
    }
}
