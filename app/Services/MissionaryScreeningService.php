<?php

declare(strict_types=1);

namespace App\Services;

class MissionaryScreeningService
{
    /**
     * Passing threshold percentage overall.
     */
    public const int PASSING_SCORE_THRESHOLD = 70;

    /**
     * Minimum passing percentage for each individual dimension (no critical red flags allowed).
     */
    public const int MIN_DIMENSION_THRESHOLD = 40;

    /**
     * The 5 core missionary screening dimensions.
     *
     * @return array<string, array{name: string, name_ja: string, icon: string, description: string}>
     */
    public function getDimensions(): array
    {
        return [
            'cultural_connections' => [
                'name' => 'Cultural Connections & Food Adaptability',
                'name_ja' => '食文化・日本社会への親和性と適応力',
                'icon' => '🍱',
                'description' => 'Eagerness to embrace Japanese culinary heritage (natto, sushi, regional cuisine), cultural humility, and sensitivity to social harmony (wa).',
            ],
            'calling_background' => [
                'name' => 'Calling & Ministry Background',
                'name_ja' => '開拓への召命・宣教の動機と忍耐力',
                'icon' => '🔥',
                'description' => 'Unshakable conviction in pioneer church planting (Romans 15:20), pastoral experience, and endurance through prolonged unfruitful seasons.',
            ],
            'language_learning' => [
                'name' => 'Language Acquisition & Cultural Learning',
                'name_ja' => '日本語習得への専念・謙虚な学びの姿勢',
                'icon' => '🗣️',
                'description' => 'Commitment to disciplined language immersion (hiragana, kanji, keigo) and humility to make mistakes like a lifelong child learner.',
            ],
            'interpersonal_dynamics' => [
                'name' => 'Interpersonal Dynamics, Conflict & Authority',
                'name_ja' => '人間関係・紛争解決と霊的権威への敬意',
                'icon' => '🤝',
                'description' => 'Christ-centered peacemaking, vulnerability in team dynamics, and honoring spiritual authority and local leadership without division.',
            ],
            'emotional_resilience' => [
                'name' => 'Emotional Resilience, Mental Health & Loneliness',
                'name_ja' => '情緒的レジリエンス・孤独への対処とメンタルヘルス',
                'icon' => '⚓',
                'description' => 'Deep abiding in Christ, healthy self-regulation under spiritual warfare and culture fatigue, and avoiding destructive isolation.',
            ],
            'dietary_health' => [
                'name' => 'Dietary Needs, Allergies & Daily Health',
                'name_ja' => '食事制限・アレルギー・日常の健康管理',
                'icon' => '🥗',
                'description' => 'Proactive communication of medical dietary needs, allergy safety in food fellowship, and physical health rhythms for urban mobility.',
            ],
            'practical_financial_exit' => [
                'name' => 'Practical, Financial & Exit Considerations',
                'name_ja' => '現実的基盤・財務管理・明確な撤退基準',
                'icon' => '💼',
                'description' => 'Sustainable financial support, emergency contingency reserves, tax/visa stewardship, and clear succession and MAWL exit benchmarks.',
            ],
        ];
    }

    /**
     * List of all 10 scenario-based screening questions across the 5 dimensions.
     *
     * @return array<string, array{
     *     id: string,
     *     dimension: string,
     *     category_label: string,
     *     title: string,
     *     scenario: string,
     *     options: array<int, array{key: string, text: string, points: int, explanation: string}>
     * }>
     */
    public function getQuestions(): array
    {
        return [
            // Dimension 1: Cultural Connections & Food Adaptability
            'q1' => [
                'id' => 'q1',
                'dimension' => 'cultural_connections',
                'category_label' => 'Cultural Connections (Food & Cuisine)',
                'title' => 'When invited to dinner by a Japanese host family and served unfamiliar traditional delicacies (such as fermented natto, raw seafood, or beef tongue):',
                'scenario' => 'Food fellowship is sacred in Japan. Turning away dishes can unintentionally signal rejection of the host’s hospitality.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I politely try a small respectful portion to express gratitude, asking about its preparation even if it feels unfamiliar.',
                        'points' => 15,
                        'explanation' => 'Healthy, respectful willingness to adapt while honestly managing personal comfort.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I partake with gratitude, curiosity, and warmth, rejoicing to honor their hospitality and share their daily culinary life.',
                        'points' => 25,
                        'explanation' => 'Exemplary cultural identification in the spirit of 1 Corinthians 9:22.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I decline the food or bring my own familiar Western meals, adhering strictly to my familiar diet.',
                        'points' => 0,
                        'explanation' => 'Critical gap: In Japanese culture, refusing food fellowship erects an immediate barrier to trust.',
                    ],
                ],
            ],
            'q2' => [
                'id' => 'q2',
                'dimension' => 'cultural_connections',
                'category_label' => 'Cultural Connections (Social Customs & Wa)',
                'title' => 'Navigating Japanese indirect communication (honne vs. tatemae) and nuanced social harmony (wa):',
                'scenario' => 'Japanese communication values preserving relational peace over direct confrontational candor.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I believe direct confrontation is always superior and expect local neighbors to adapt to my blunt communication style.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Imposing outside communicative styles will cause offense and close neighborhood doors.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I acknowledge that indirect communication is different from my own style and actively seek coaching from local believers.',
                        'points' => 15,
                        'explanation' => 'Good teachable posture willing to learn with guidance.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I approach Japanese culture as a humble observer, learning to read the air (kuuki wo yomu) and valuing relational harmony above assertiveness.',
                        'points' => 25,
                        'explanation' => 'Exceptional cultural intelligence essential for fruitful long-term ministry.',
                    ],
                ],
            ],

            // Dimension 2: Calling & Ministry Background
            'q3' => [
                'id' => 'q3',
                'dimension' => 'calling_background',
                'category_label' => 'Calling & Ministry (Pioneering Conviction)',
                'title' => 'What is your core spiritual motivation for entering the Nagoya urban harvest field?',
                'scenario' => 'Nagoya is Japan’s fourth-largest metropolis with less than 0.3% Christian presence and deep unreached hubs.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'A clear God-given burden for Jesus’ name to be magnified where Christ is not yet named (Romans 15:20) and sacrificial love for the Japanese people.',
                        'points' => 25,
                        'explanation' => 'Rooted in apostolic pioneer vision that withstands hardship.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'A personal fascination with anime, Japanese pop culture, or seeking an international travel adventure.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Cultural fandom without a deep spiritual calling collapses when culture shock arrives.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'A tested heart for cross-cultural missions and a desire to be used by the Holy Spirit in a city of great need.',
                        'points' => 15,
                        'explanation' => 'Genuine missionary desire with healthy willingness to grow on the field.',
                    ],
                ],
            ],
            'q4' => [
                'id' => 'q4',
                'dimension' => 'calling_background',
                'category_label' => 'Calling & Ministry (Endurance in Dry Soil)',
                'title' => 'If you faithfully sow seed, prayer walk, and build relationships for 12 months without seeing a single baptism:',
                'scenario' => 'Pioneering in Japan is often spiritual trench warfare before breakthrough generational movements emerge.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I reach out to seasoned mentors and prayer partners to evaluate bottlenecks, praying through discouragement without quitting.',
                        'points' => 15,
                        'explanation' => 'Healthy resilience supported by community.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I become deeply cynical, blame the Japanese people for being spiritually hardhearted, and prepare to resign.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Bitterness against the harvest field poisons gospel sowing.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I remain steadfast, knowing fruitfulness belongs to God, intensifying persistent prayer while examining my sowing strategies with humility.',
                        'points' => 25,
                        'explanation' => 'Biblical perseverance (Galatians 6:9) needed for catalytic movement.',
                    ],
                ],
            ],

            // Dimension 3: Language Acquisition & Cultural Learning
            'q5' => [
                'id' => 'q5',
                'dimension' => 'language_learning',
                'category_label' => 'Language & Learning (Language Dedication)',
                'title' => 'Regarding the arduous investment needed to learn Japanese (hiragana, katakana, kanji, and keigo):',
                'scenario' => 'Language is the primary key to the Japanese heart; without it, discipleship depth is severely hindered.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I expect to rely on automated AI translation apps and conduct ministry almost exclusively in English.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Avoiding language acquisition limits gospel access to a tiny English-speaking elite.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I commit to disciplined daily language immersion, eager to practice on the streets, markets, and cafes despite sounding clumsy or imperfect.',
                        'points' => 25,
                        'explanation' => 'The humble posture of an effective language learner who prioritizes heart connection.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I prioritize structured classroom study and language exchange partners to steadily build communicative capability.',
                        'points' => 15,
                        'explanation' => 'Steady, practical dedication to language acquisition.',
                    ],
                ],
            ],
            'q6' => [
                'id' => 'q6',
                'dimension' => 'language_learning',
                'category_label' => 'Language & Learning (Teachable Student Posture)',
                'title' => 'When a local Japanese resident, clerk, or friend points out an error in your speech or social etiquette:',
                'scenario' => 'A missionary must transition from being an expert teacher to becoming an infant learner.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I thank them sincerely with a smile; every gentle correction is an invaluable gift bridging me closer to the heart of the community.',
                        'points' => 25,
                        'explanation' => 'Unfeigned humility that delights Japanese neighbors and builds immense rapport.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I feel a brief sting of embarrassment, but write down the correct phrasing and thank them for the tip.',
                        'points' => 15,
                        'explanation' => 'Normal human response followed by constructive learning.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I feel offended or embarrassed, become self-conscious, and avoid conversing with them in Japanese again.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Fragile ego prevents the language breakthroughs necessary for fruitful pioneering.',
                    ],
                ],
            ],

            // Dimension 4: Interpersonal Dynamics, Conflict & Authority
            'q7' => [
                'id' => 'q7',
                'dimension' => 'interpersonal_dynamics',
                'category_label' => 'Interpersonal Dynamics (Conflict Resolution)',
                'title' => 'When sharp interpersonal tension or disagreements arise with a fellow co-laborer on your team:',
                'scenario' => 'Cross-cultural team conflict is historically the #1 reason missionaries exit the field prematurely.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I bring the situation to our team leader or pastoral coach to facilitate a mediated conversation.',
                        'points' => 15,
                        'explanation' => 'Healthy willingness to resolve conflict through proper accountability.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I promptly seek face-to-face grace-filled reconciliation in the spirit of Matthew 18, listening deeply to understand their perspective before defending my own.',
                        'points' => 25,
                        'explanation' => 'A peacemaker who protects the unity of the Spirit (Ephesians 4:3).',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I nurse silent resentment, vent to outside supporters, or build factions within the team.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Unresolved bitterness fractures teams and sabotages church planting movements.',
                    ],
                ],
            ],
            'q8' => [
                'id' => 'q8',
                'dimension' => 'interpersonal_dynamics',
                'category_label' => 'Interpersonal Dynamics (Spiritual Authority & Unity)',
                'title' => 'When your movement leader or local national pastor directs a strategy or team boundary that you personally dislike:',
                'scenario' => 'Respect for senpai/kohai leadership structures and spiritual unity is vital in Japanese society.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I disregard their instructions, undermine their credibility, and do whatever I think is right.',
                        'points' => 0,
                        'explanation' => 'Critical gap: A rebellious spirit incapable of submission cannot be entrusted with spiritual leadership.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I defer to their authority while continuing to pray for wisdom and clarity together.',
                        'points' => 15,
                        'explanation' => 'Respectful submission supporting organizational health.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I communicate my thoughts respectfully in private, but joyfully submit to spiritual leadership and champion team unity.',
                        'points' => 25,
                        'explanation' => 'Mature servant heart that understands spiritual authority and cooperative synergy.',
                    ],
                ],
            ],

            // Dimension 5: Emotional Resilience, Mental Health & Loneliness
            'q9' => [
                'id' => 'q9',
                'dimension' => 'emotional_resilience',
                'category_label' => 'Emotional Resilience (Overcoming Loneliness)',
                'title' => 'During solitary winter seasons in Nagoya when feelings of loneliness, homesickness, or isolation creep in:',
                'scenario' => 'Nagoya’s fast-paced urban anonymity can feel intensely isolating to newcomers.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I recognize the warning signs of isolation early and schedule calls with family, mentors, or teammate check-ins.',
                        'points' => 15,
                        'explanation' => 'Good self-awareness and proactive boundary maintenance.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I withdraw into isolated binge-watching, doomscrolling, or unhealthy escapism, concealing my struggles from others.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Isolation without accountability leaves candidates vulnerable to depression and burnout.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I cultivate daily intimate abiding in Jesus, maintain vulnerable rhythms with prayer partners, and proactively invite others into fellowship.',
                        'points' => 25,
                        'explanation' => 'Anchored deeply in the Lord with healthy relational safeguards.',
                    ],
                ],
            ],
            'q10' => [
                'id' => 'q10',
                'dimension' => 'emotional_resilience',
                'category_label' => 'Emotional Resilience (Stress & Spiritual Warfare)',
                'title' => 'When culture fatigue, language frustration, and spiritual opposition assault your mental peace at once:',
                'scenario' => 'Spiritual warfare in Japan often manifests as quiet mental oppression, cynicism, and chronic weariness.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I recognize spiritual warfare, put on the full armor of God, practice regular Sabbath rest, and rest securely in my identity in Christ.',
                        'points' => 25,
                        'explanation' => 'Spiritual maturity that battles with spiritual weapons rather than fleshly willpower.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I attempt to power through with willpower alone until physical sickness, anxiety attacks, or emotional breakdown occur.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Neglecting mental health and spiritual rest leads to severe breakdown.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I pause, step away for a day of prayer and outdoor recreation, and seek prayer from trusted intercessors.',
                        'points' => 15,
                        'explanation' => 'Healthy emotional pacing that restores soul health.',
                    ],
                ],
            ],

            // Dimension 6: Dietary Needs, Allergies & Daily Health
            'q11' => [
                'id' => 'q11',
                'dimension' => 'dietary_health',
                'category_label' => 'Dietary Needs & Allergy Safety',
                'title' => 'Managing severe food allergies or medical dietary restrictions while participating in Japanese food fellowship:',
                'scenario' => 'Key Japanese ingredients (soy, wheat/gluten, fish broth dashi, eggs) are in almost every traditional dish. Unclear communication causes relational strain or medical emergencies.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I hide severe allergies to avoid awkwardness, or demand that Japanese hosts prepare completely customized Western meals without notice.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Concealing allergies risks emergency medical crises, while demanding sudden accommodation violates Japanese social harmony.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I clearly, politely communicate my medical needs in advance using respectful Japanese, expressing deep gratitude for hospitality while taking personal responsibility for my health.',
                        'points' => 25,
                        'explanation' => 'Exemplary communication that safeguards health while honoring the host’s hospitality.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I quietly navigate food on my own, asking when unsure, though I sometimes feel uncomfortable bringing up dietary constraints.',
                        'points' => 15,
                        'explanation' => 'Cautious approach, but requires greater confidence in proactive cross-cultural communication.',
                    ],
                ],
            ],
            'q12' => [
                'id' => 'q12',
                'dimension' => 'dietary_health',
                'category_label' => 'Physical Health & Energy Rhythms',
                'title' => 'Maintaining physical stamina, consistent sleep, and daily health amidst Nagoya’s demanding walking commutes and summer humidity:',
                'scenario' => 'Nagoya missionaries average 10,000+ walking steps daily across train stations and uphill residential wards. Neglecting physical rest leads to chronic fatigue.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I maintain fair health habits, but often sacrifice sleep, hydration, or meals during intense ministry seasons.',
                        'points' => 15,
                        'explanation' => 'Common vulnerability; needs intentional boundaries to prevent cumulative burnout.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I treat physical health as stewardship: maintaining consistent sleep schedules, proper hydration, nutritious meals, and regular physical exercise for long-term endurance.',
                        'points' => 25,
                        'explanation' => 'Wise stewardship recognizing the body as the temple of the Holy Spirit (1 Cor 6:19).',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I ignore physical warning signs, live on convenience store junk food and energy drinks, and believe spiritual zeal replaces bodily care.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Physical neglect invariably causes physical breakdown, mental fog, and premature field exit.',
                    ],
                ],
            ],

            // Dimension 7: Practical, Financial & Exit Considerations
            'q13' => [
                'id' => 'q13',
                'dimension' => 'practical_financial_exit',
                'category_label' => 'Financial Stewardship & Reserves',
                'title' => 'Securing dependable financial support, managing fluctuating exchange rates, and holding emergency contingency reserves:',
                'scenario' => 'Japan has high living costs, mandatory national health insurance, municipal inhabitant taxes, and rent deposits. Underfunded workers face extreme anxiety.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I arrive on the field with heavy consumer debt or minimal funding, expecting Japanese churches or spontaneous donations to cover my bills.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Financial instability places undue burdens on local national churches and violates missionary ethics.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I have sufficient funds for basic monthly living, but limited contingency reserves and need to cultivate a stronger sending network.',
                        'points' => 15,
                        'explanation' => 'Viable foundation that needs strengthening with emergency buffer reserves.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I maintain a fully funded, transparent support structure with a 3–6 month emergency reserve, regular partner communication, and faithful financial accountability.',
                        'points' => 25,
                        'explanation' => 'Sound financial stewardship (Luke 14:28) that prevents financial crisis on the field.',
                    ],
                ],
            ],
            'q14' => [
                'id' => 'q14',
                'dimension' => 'practical_financial_exit',
                'category_label' => 'Exit Strategy & Succession Planning',
                'title' => 'Establishing clear exit milestones, healthy succession criteria, and personal transition triggers:',
                'scenario' => 'CPM principles require missionaries to Model, Assist, Watch, and Leave (MAWL), empowering indigenous Japanese believers to lead autonomously.',
                'options' => [
                    [
                        'key' => 'A',
                        'text' => 'I hold an intentional exit mindset from Day 1: defining clear generational benchmarks for indigenous leadership handover and healthy personal/family transition triggers.',
                        'points' => 25,
                        'explanation' => 'True apostolic posture (Paul in Ephesus/Corinth) that prevents missionary dependency and releases local movement.',
                    ],
                    [
                        'key' => 'B',
                        'text' => 'I aim to empower local leaders, but have not yet formalized written exit criteria or personal transition benchmarks.',
                        'points' => 15,
                        'explanation' => 'Good intentions; needs structured milestones for intentional leadership transfer.',
                    ],
                    [
                        'key' => 'C',
                        'text' => 'I plan to hold permanent control as the foreign director, believing the Japanese church cannot survive without ongoing foreign oversight.',
                        'points' => 0,
                        'explanation' => 'Critical gap: Colonial paternalism stifles indigenous movement multiplication and violates 2 Timothy 2:2.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Evaluate a candidate's answers and generate a comprehensive capability scorecard.
     *
     * @param  array<string, string|int>  $answers  Keyed by question id ('q1' => 'A', or 25)
     * @return array{
     *     passed: bool,
     *     total_score: int,
     *     total_points: int,
     *     max_points: int,
     *     dimension_scores: array<string, int>,
     *     dimension_points: array<string, int>,
     *     dimension_max: array<string, int>,
     *     profile_title: string,
     *     profile_title_ja: string,
     *     coaching_feedback: array<string, string>,
     *     critical_gaps: array<string>,
     *     summary: string,
     *     answered_count: int,
     *     total_questions: int,
     * }
     */
    public function evaluate(array $answers): array
    {
        $questions = $this->getQuestions();
        $dimensions = $this->getDimensions();

        $dimensionPoints = [];
        $dimensionMax = [];
        $criticalGaps = [];

        foreach (array_keys($dimensions) as $dimKey) {
            $dimensionPoints[$dimKey] = 0;
            $dimensionMax[$dimKey] = 0;
        }

        $totalPoints = 0;
        $maxPoints = 0;
        $answeredCount = 0;

        foreach ($questions as $qId => $qData) {
            $dimKey = $qData['dimension'];
            $maxPoints += 25;
            $dimensionMax[$dimKey] += 25;

            $userChoice = $answers[$qId] ?? null;

            if ($userChoice !== null) {
                $answeredCount++;
                $points = 0;

                // Match by option key ('A', 'B', 'C') or numeric points
                if (is_numeric($userChoice)) {
                    $points = (int) $userChoice;
                } else {
                    foreach ($qData['options'] as $option) {
                        if ($option['key'] === strtoupper((string) $userChoice)) {
                            $points = $option['points'];
                            break;
                        }
                    }
                }

                $dimensionPoints[$dimKey] += $points;
                $totalPoints += $points;

                if ($points === 0) {
                    $criticalGaps[] = $qData['category_label'];
                }
            }
        }

        // Calculate percentage scores
        $totalScore = $maxPoints > 0 ? (int) round(($totalPoints / $maxPoints) * 100) : 0;

        $dimensionScores = [];
        $coachingFeedback = [];
        $dimensionFailures = [];

        foreach ($dimensions as $dimKey => $dimMeta) {
            $dimMax = $dimensionMax[$dimKey] ?: 50;
            $dimScore = (int) round(($dimensionPoints[$dimKey] / $dimMax) * 100);
            $dimensionScores[$dimKey] = $dimScore;

            if ($dimScore < self::MIN_DIMENSION_THRESHOLD) {
                $dimensionFailures[] = $dimMeta['name'];
            }

            // Generate contextual coaching notes
            if ($dimScore < 70) {
                $coachingFeedback[$dimKey] = $this->getCoachingNote($dimKey, $dimScore);
            }
        }

        // Qualification Gate: must have overall score >= 70 AND every dimension >= 40 with no critical gaps
        $passed = $totalScore >= self::PASSING_SCORE_THRESHOLD
            && empty($dimensionFailures)
            && empty($criticalGaps)
            && $answeredCount >= count($questions);

        // Determine profile archetype title based on highest performing dimension
        [$profileTitle, $profileTitleJa] = $this->determineProfileArchetype($dimensionScores);

        $summary = $passed
            ? "Deployment Clearance Granted: You demonstrated mature readiness with an overall score of {$totalScore}%. Your profile archetype is {$profileTitle}."
            : "Deployment Clearance On Hold: Score {$totalScore}% (Passing threshold: 70%, with >= 40% across all 7 dimensions and no critical red flags). Mentorship and coaching required before deployment.";

        return [
            'passed' => $passed,
            'total_score' => $totalScore,
            'total_points' => $totalPoints,
            'max_points' => $maxPoints,
            'dimension_scores' => $dimensionScores,
            'dimension_points' => $dimensionPoints,
            'dimension_max' => $dimensionMax,
            'profile_title' => $profileTitle,
            'profile_title_ja' => $profileTitleJa,
            'coaching_feedback' => $coachingFeedback,
            'critical_gaps' => array_unique($criticalGaps),
            'summary' => $summary,
            'answered_count' => $answeredCount,
            'total_questions' => count($questions),
        ];
    }

    /**
     * Determine profile title and Japanese title from dimension strengths.
     *
     * @param  array<string, int>  $dimensionScores
     * @return array{0: string, 1: string}
     */
    protected function determineProfileArchetype(array $dimensionScores): array
    {
        arsort($dimensionScores);
        $topDimension = array_key_first($dimensionScores);

        return match ($topDimension) {
            'cultural_connections' => ['Cross-Cultural Ambassador', '文化の架け橋（異文化適応リーダー）'],
            'calling_background' => ['Resolute Pioneer Catalyzer', '不屈の開拓宣教者（使徒的リーダー）'],
            'language_learning' => ['Lifelong Cultural Learner', '謙虚な学び手（言語・文化架橋者）'],
            'interpersonal_dynamics' => ['Peacemaking Team Builder', '平和をつくり出す友（チームビルダー）'],
            'emotional_resilience' => ['Resilient Gospel Trailblazer', '情緒的開拓者（不屈の霊的開拓者）'],
            'dietary_health' => ['Wholesome Steward & Host', '全人的スチュワード（健康・生活管理リーダー）'],
            'practical_financial_exit' => ['Strategic Steward & Catalyzer', '戦略的基盤構築者（財務・継承プランナー）'],
            default => ['Missionary Disciple Pioneer', '福音宣教の弟子'],
        };
    }

    /**
     * Provide targeted coaching notes for candidate growth.
     */
    protected function getCoachingNote(string $dimension, int $score): string
    {
        return match ($dimension) {
            'cultural_connections' => 'Food fellowship and honoring relational harmony (wa) are the primary doorways to trust in Nagoya. Embrace unfamiliar foods with warmth and cultivate the art of reading social air without imposing external bluntness.',
            'calling_background' => 'Pioneer church planting in Japan is a spiritual marathon. Anchor your identity in Christ rather than immediate numbers; when breakthrough seems slow, persistent intercession and faithful sowing will yield generational fruit.',
            'language_learning' => 'Language is the highway to the Japanese heart. Embrace the humility of a child who makes mistakes daily. Daily immersion and listening to local neighbors will break barriers faster than relying on translation shortcuts.',
            'interpersonal_dynamics' => 'Team unity and mutual honor under spiritual authority are your strongest shield against enemy counterattacks. Pursue prompt face-to-face reconciliation and honor Japanese leadership structures to protect team cohesion.',
            'emotional_resilience' => 'Never battle urban isolation alone. Practice proactive Sabbath rest, confide transparently in prayer partners, and rest deeply in God’s grace to maintain mental and emotional health in Nagoya.',
            'dietary_health' => 'Physical stewardship is vital for long-term endurance. Prepare respectful Japanese communication cards for severe food allergies ahead of time, honor host hospitality, and protect regular sleep, hydration, and nutrition rhythms amidst demanding walking commutes.',
            'practical_financial_exit' => 'Financial transparency, emergency contingency reserves (3–6 months), and an intentional MAWL exit strategy ensure your ministry empowers Japanese leaders rather than creating foreign dependency. Plan for generational succession from day one.',
            default => 'Continue to seek mentorship and biblical coaching to build cross-cultural missionary endurance.',
        };
    }
}
