<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $knownNpcProfiles = [
            // Core Nagoya Oikos Archetypes & Seeded Characters
            'Kenji Tanaka' => ['gender' => 'male', 'age' => 48, 'nationality' => 'Japanese'],
            'Tomoko Tanaka' => ['gender' => 'female', 'age' => 45, 'nationality' => 'Japanese'],
            'Hiroshi Sato' => ['gender' => 'male', 'age' => 34, 'nationality' => 'Japanese'],
            'Keiko Yamamoto' => ['gender' => 'female', 'age' => 64, 'nationality' => 'Japanese'],
            'Aoi Yamamoto' => ['gender' => 'female', 'age' => 32, 'nationality' => 'Japanese'],
            'Masao Suzuki' => ['gender' => 'male', 'age' => 58, 'nationality' => 'Japanese'],
            'Goro Kondo' => ['gender' => 'male', 'age' => 55, 'nationality' => 'Japanese'],
            'Linh Nguyen' => ['gender' => 'female', 'age' => 21, 'nationality' => 'Vietnamese'],
            'Yuki Kobayashi' => ['gender' => 'female', 'age' => 21, 'nationality' => 'Japanese'],
            'Sayaka Ito' => ['gender' => 'female', 'age' => 26, 'nationality' => 'Japanese'],
            'Carlos Santos' => ['gender' => 'male', 'age' => 32, 'nationality' => 'Brazilian'],
            'Kenichi Kato' => ['gender' => 'male', 'age' => 30, 'nationality' => 'Japanese'],
            'Daisuke Kato' => ['gender' => 'male', 'age' => 32, 'nationality' => 'Japanese'],

            // Dynamically Spawned Persons of Peace & Crisis Archetypes
            'Satoshi Yamada' => ['gender' => 'male', 'age' => 44, 'nationality' => 'Japanese'],
            'Yuka Shibata' => ['gender' => 'female', 'age' => 29, 'nationality' => 'Japanese'],
            'Kazuo Kondo' => ['gender' => 'male', 'age' => 72, 'nationality' => 'Japanese'],
            'Mari Ishikawa' => ['gender' => 'female', 'age' => 36, 'nationality' => 'Japanese'],
            'Takumi Ogawa' => ['gender' => 'male', 'age' => 20, 'nationality' => 'Japanese'],
            'Emi Suzuki' => ['gender' => 'female', 'age' => 28, 'nationality' => 'Japanese'],

            // Church Movement Leaders & Test Associates
            'Kenzo Takahashi' => ['gender' => 'male', 'age' => 38, 'nationality' => 'Japanese'],
            'Kenjiro Takahashi' => ['gender' => 'male', 'age' => 52, 'nationality' => 'Japanese'],
            'Aoi Sato' => ['gender' => 'female', 'age' => 22, 'nationality' => 'Japanese'],
            'Daiki Ito' => ['gender' => 'male', 'age' => 24, 'nationality' => 'Japanese'],

            // Youth & University Students (Chikusa, Tempaku, Showa, Meito)
            'Sakura Inoue' => ['gender' => 'female', 'age' => 19, 'nationality' => 'Japanese'],
            'Hina Watanabe' => ['gender' => 'female', 'age' => 20, 'nationality' => 'Japanese'],
            'Mei Yoshida' => ['gender' => 'female', 'age' => 22, 'nationality' => 'Japanese'],
            'Yui Nakamura' => ['gender' => 'female', 'age' => 21, 'nationality' => 'Japanese'],
            'Rin Matsuoka' => ['gender' => 'female', 'age' => 23, 'nationality' => 'Japanese'],
            'Nana Mori' => ['gender' => 'female', 'age' => 20, 'nationality' => 'Japanese'],
            'Kana Hayashi' => ['gender' => 'female', 'age' => 22, 'nationality' => 'Japanese'],
            'Rina Shimizu' => ['gender' => 'female', 'age' => 24, 'nationality' => 'Japanese'],
            'Akari Hasegawa' => ['gender' => 'female', 'age' => 21, 'nationality' => 'Japanese'],
            'Saki Fujiwara' => ['gender' => 'female', 'age' => 23, 'nationality' => 'Japanese'],
            'Honoka Goto' => ['gender' => 'female', 'age' => 22, 'nationality' => 'Japanese'],
            'Mio Hashimoto' => ['gender' => 'female', 'age' => 19, 'nationality' => 'Japanese'],
            'Koharu Ishida' => ['gender' => 'female', 'age' => 20, 'nationality' => 'Japanese'],
            'Riko Murata' => ['gender' => 'female', 'age' => 21, 'nationality' => 'Japanese'],
            'Ren Suzuki' => ['gender' => 'male', 'age' => 21, 'nationality' => 'Japanese'],
            'Sho Tanaka' => ['gender' => 'male', 'age' => 23, 'nationality' => 'Japanese'],
            'Kenta Sato' => ['gender' => 'male', 'age' => 22, 'nationality' => 'Japanese'],
            'Haruto Ito' => ['gender' => 'male', 'age' => 24, 'nationality' => 'Japanese'],
            'Kaito Nakamura' => ['gender' => 'male', 'age' => 20, 'nationality' => 'Japanese'],
            'Sota Takahashi' => ['gender' => 'male', 'age' => 23, 'nationality' => 'Japanese'],

            // Working Professionals & Single Parents (Naka, Nakamura, Higashi, Nishi)
            'Haruka Saito' => ['gender' => 'female', 'age' => 27, 'nationality' => 'Japanese'],
            'Ayaka Kimura' => ['gender' => 'female', 'age' => 29, 'nationality' => 'Japanese'],
            'Asuka Takahashi' => ['gender' => 'female', 'age' => 31, 'nationality' => 'Japanese'],
            'Erika Maeda' => ['gender' => 'female', 'age' => 33, 'nationality' => 'Japanese'],
            'Rika Okada' => ['gender' => 'female', 'age' => 35, 'nationality' => 'Japanese'],
            'Mina Endo' => ['gender' => 'female', 'age' => 38, 'nationality' => 'Japanese'],
            'Hitomi Aoki' => ['gender' => 'female', 'age' => 41, 'nationality' => 'Japanese'],
            'Naomi Sakai' => ['gender' => 'female', 'age' => 43, 'nationality' => 'Japanese'],
            'Megumi Shibata' => ['gender' => 'female', 'age' => 46, 'nationality' => 'Japanese'],
            'Yumi Takeuchi' => ['gender' => 'female', 'age' => 39, 'nationality' => 'Japanese'],
            'Sanae Fujii' => ['gender' => 'female', 'age' => 37, 'nationality' => 'Japanese'],
            'Hanako Sano' => ['gender' => 'female', 'age' => 34, 'nationality' => 'Japanese'],
            'Yuto Watanabe' => ['gender' => 'male', 'age' => 28, 'nationality' => 'Japanese'],
            'Tatsuya Kobayashi' => ['gender' => 'male', 'age' => 31, 'nationality' => 'Japanese'],
            'Shota Kato' => ['gender' => 'male', 'age' => 33, 'nationality' => 'Japanese'],
            'Ryota Yoshida' => ['gender' => 'male', 'age' => 36, 'nationality' => 'Japanese'],
            'Makoto Yamada' => ['gender' => 'male', 'age' => 39, 'nationality' => 'Japanese'],
            'Takeshi Sasaki' => ['gender' => 'male', 'age' => 42, 'nationality' => 'Japanese'],

            // Mature Neighborhood Heads & Shop Owners (Mizuho, Atsuta, Nakagawa, Midori)
            'Sachiko Ogawa' => ['gender' => 'female', 'age' => 51, 'nationality' => 'Japanese'],
            'Ryoko Nishimura' => ['gender' => 'female', 'age' => 54, 'nationality' => 'Japanese'],
            'Kyoko Miura' => ['gender' => 'female', 'age' => 56, 'nationality' => 'Japanese'],
            'Chiyo Okamoto' => ['gender' => 'female', 'age' => 59, 'nationality' => 'Japanese'],
            'Kayo Nakagawa' => ['gender' => 'female', 'age' => 52, 'nationality' => 'Japanese'],
            'Yoko Matsushita' => ['gender' => 'female', 'age' => 57, 'nationality' => 'Japanese'],
            'Yumiko Ono' => ['gender' => 'female', 'age' => 54, 'nationality' => 'Japanese'],
            'Kazumi Sugiyama' => ['gender' => 'female', 'age' => 58, 'nationality' => 'Japanese'],
            'Osamu Yamaguchi' => ['gender' => 'male', 'age' => 46, 'nationality' => 'Japanese'],
            'Shinji Matsumoto' => ['gender' => 'male', 'age' => 50, 'nationality' => 'Japanese'],
            'Taro Inoue' => ['gender' => 'male', 'age' => 53, 'nationality' => 'Japanese'],
            'Ichiro Kimura' => ['gender' => 'male', 'age' => 56, 'nationality' => 'Japanese'],
            'Jiro Hayashi' => ['gender' => 'male', 'age' => 61, 'nationality' => 'Japanese'],

            // Senior Elders & Retirees (Kita, Minami, Moriyama)
            'Chiyo Tamura' => ['gender' => 'female', 'age' => 66, 'nationality' => 'Japanese'],
            'Setsuko Kojima' => ['gender' => 'female', 'age' => 68, 'nationality' => 'Japanese'],
            'Michiko Wada' => ['gender' => 'female', 'age' => 71, 'nationality' => 'Japanese'],
            'Fumiko Ueda' => ['gender' => 'female', 'age' => 74, 'nationality' => 'Japanese'],
            'Kazuko Morita' => ['gender' => 'female', 'age' => 78, 'nationality' => 'Japanese'],
            'Shizuko Harada' => ['gender' => 'female', 'age' => 69, 'nationality' => 'Japanese'],
            'Saburo Shimizu' => ['gender' => 'male', 'age' => 68, 'nationality' => 'Japanese'],
            'Minoru Yamazaki' => ['gender' => 'male', 'age' => 73, 'nationality' => 'Japanese'],
            'Tomeo Mori' => ['gender' => 'male', 'age' => 76, 'nationality' => 'Japanese'],

            // International Communities in Nagoya
            'Maria Santos' => ['gender' => 'female', 'age' => 28, 'nationality' => 'Brazilian'],
            'Ana Paula Silva' => ['gender' => 'female', 'age' => 34, 'nationality' => 'Brazilian'],
            'Fernanda Lima' => ['gender' => 'female', 'age' => 31, 'nationality' => 'Brazilian'],
            'Lucas Oliveira' => ['gender' => 'male', 'age' => 27, 'nationality' => 'Brazilian'],
            'Gabriel Souza' => ['gender' => 'male', 'age' => 33, 'nationality' => 'Brazilian'],
            'Mai Tran' => ['gender' => 'female', 'age' => 23, 'nationality' => 'Vietnamese'],
            'Huong Le' => ['gender' => 'female', 'age' => 25, 'nationality' => 'Vietnamese'],
            'Thao Pham' => ['gender' => 'female', 'age' => 22, 'nationality' => 'Vietnamese'],
            'Duc Nguyen' => ['gender' => 'male', 'age' => 23, 'nationality' => 'Vietnamese'],
            'Tuan Tran' => ['gender' => 'male', 'age' => 26, 'nationality' => 'Vietnamese'],
            'Joy Reyes' => ['gender' => 'female', 'age' => 26, 'nationality' => 'Filipino'],
            'Maria Clara Bautista' => ['gender' => 'female', 'age' => 33, 'nationality' => 'Filipino'],
            'Grace Mendoza' => ['gender' => 'female', 'age' => 42, 'nationality' => 'Filipino'],
            'Mark Mendoza' => ['gender' => 'male', 'age' => 30, 'nationality' => 'Filipino'],
            'John Cruz' => ['gender' => 'male', 'age' => 35, 'nationality' => 'Filipino'],
            'Li Wei' => ['gender' => 'female', 'age' => 24, 'nationality' => 'Chinese'],
            'Chen Xi' => ['gender' => 'female', 'age' => 30, 'nationality' => 'Chinese'],
            'Wang Fang' => ['gender' => 'female', 'age' => 38, 'nationality' => 'Chinese'],
            'Zhang Wei' => ['gender' => 'male', 'age' => 28, 'nationality' => 'Chinese'],
            'Liu Yang' => ['gender' => 'male', 'age' => 32, 'nationality' => 'Chinese'],
            'Kim Min-seo' => ['gender' => 'female', 'age' => 22, 'nationality' => 'Korean'],
            'Park Ji-hye' => ['gender' => 'female', 'age' => 29, 'nationality' => 'Korean'],
            'Lee Min-ho' => ['gender' => 'male', 'age' => 24, 'nationality' => 'Korean'],
            'Jung Woo-sung' => ['gender' => 'male', 'age' => 31, 'nationality' => 'Korean'],
            'Chloe Bennett' => ['gender' => 'female', 'age' => 25, 'nationality' => 'American'],
            'David Miller' => ['gender' => 'male', 'age' => 34, 'nationality' => 'American'],
            'Emily Watson' => ['gender' => 'female', 'age' => 28, 'nationality' => 'Canadian'],
            'Hannah Evans' => ['gender' => 'female', 'age' => 27, 'nationality' => 'British'],
            'Michael Clark' => ['gender' => 'male', 'age' => 29, 'nationality' => 'Australian'],
            'Rosa Quispe' => ['gender' => 'female', 'age' => 35, 'nationality' => 'Peruvian'],
            'Jorge Flores' => ['gender' => 'male', 'age' => 38, 'nationality' => 'Peruvian'],
        ];

        foreach ($knownNpcProfiles as $name => $data) {
            DB::table('npcs')
                ->where('name', $name)
                ->orWhere('name', 'LIKE', "{$name}%")
                ->update($data);
        }

        // Also fix AI co-laborer users
        DB::table('users')->where('email', 'sarah.jenkins@nagoyaharvest.jp')->update([
            'gender' => 'female',
            'age' => 27,
            'nationality' => 'American',
        ]);

        DB::table('users')->where('email', 'marcus.silva@nagoyaharvest.jp')->update([
            'gender' => 'male',
            'age' => 35,
            'nationality' => 'Brazilian',
        ]);

        DB::table('users')->where('email', 'kenji.yamada@nagoyaharvest.jp')->update([
            'gender' => 'male',
            'age' => 42,
            'nationality' => 'Japanese',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal necessary for data normalization
    }
};
