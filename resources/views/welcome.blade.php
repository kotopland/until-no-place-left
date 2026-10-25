<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nagoya Oikos: The Four Fields • Movement Simulation Story & Rules</title>
    
    <link rel="icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}">
    @fonts

    <!-- Leaflet CSS for preview map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 font-sans selection:bg-indigo-500 selection:text-white antialiased overflow-x-hidden">

    <!-- Top Glow / Ambient Background Gradient -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[500px] bg-gradient-to-b from-indigo-900/20 via-emerald-900/10 to-transparent blur-3xl pointer-events-none -z-10"></div>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.jpeg') }}" 
                    alt="Nagoya Oikos: The Four Fields Logo" 
                    class="w-11 h-11 object-contain rounded-2xl shadow-xl shadow-indigo-500/25 border border-indigo-500/40 group-hover:scale-105 transition duration-300"
                >
                <div>
                    <span class="text-xs font-black text-white tracking-wider uppercase flex items-center gap-1.5">
                        <span>Nagoya Oikos</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-indigo-950 text-indigo-400 border border-indigo-800">Four Fields</span>
                    </span>
                    <span class="text-[11px] text-slate-400 block font-medium">Until No Place Left • Japan</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-6 text-xs font-semibold text-slate-300">
                <a href="#story" class="hover:text-indigo-400 transition">The Story</a>
                <a href="#four-fields" class="hover:text-emerald-400 transition">The Four Fields</a>
                <a href="#rules" class="hover:text-amber-400 transition">Game Rules</a>
                <a href="#wards" class="hover:text-purple-400 transition">16 Wards</a>
            </nav>

            <div class="flex items-center gap-3">
                <!-- Ambient Music Toggle Button -->
                <button 
                    type="button" 
                    @click="$store.audio?.toggle()" 
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                    :class="$store.audio?.isPlaying ? 'bg-indigo-950/90 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500/40' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                    :title="$store.audio?.isPlaying ? 'Turn Background Music OFF' : 'Turn Background Music ON (Uplifting Japanese Ambient)'"
                >
                    <span x-show="!$store.audio?.isPlaying">🔇</span>
                    <span x-show="$store.audio?.isPlaying" class="flex items-center gap-1">
                        <span class="text-xs">🎵</span>
                        <span class="inline-flex gap-0.5 items-end h-3">
                            <span class="w-0.5 h-2 bg-indigo-400 rounded-full animate-bounce"></span>
                            <span class="w-0.5 h-3 bg-emerald-400 rounded-full animate-bounce delay-75"></span>
                            <span class="w-0.5 h-1.5 bg-indigo-400 rounded-full animate-bounce delay-150"></span>
                        </span>
                    </span>
                    <span class="hidden sm:inline text-[11px]" x-text="$store.audio?.isPlaying ? 'Music ON' : 'Music OFF'"></span>
                </button>

                @auth
                    <a 
                        href="{{ route('home') }}" 
                        class="hidden sm:flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    <a 
                        href="{{ route('home') }}" 
                        class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-xl shadow-indigo-600/25 hover:shadow-indigo-600/40 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>⚡</span>
                        <span>Continue Mission</span>
                    </a>
                @else
                    <a 
                        href="{{ route('login') }}" 
                        class="text-xs font-bold text-slate-300 hover:text-white px-3.5 py-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 border border-slate-800 transition"
                    >
                        Sign In
                    </a>
                    <a 
                        href="{{ route('login') }}" 
                        class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-xl shadow-indigo-600/25 hover:shadow-indigo-600/40 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>⚡</span>
                        <span>Create Player & Join</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-24">

        <!-- HERO SECTION: The Big Visual Banner & Hook -->
        <section class="relative flex flex-col lg:flex-row items-center gap-12 pt-6 lg:pt-12">
            <div class="flex-1 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900/90 border border-slate-800 text-xs text-indigo-300 font-semibold shadow-inner">
                    <span>🌸</span>
                    <span>Nagoya, Aichi Prefecture • 2.3 Million Souls</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.1]">
                    Relational Church <br/>
                    Planting in the <br/>
                    <span class="bg-gradient-to-r from-amber-400 via-emerald-400 to-indigo-400 bg-clip-text text-transparent">
                        Heart of Japan
                    </span>
                </h1>

                <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Step into the shoes of a missionary coach in Nagoya. Form friendships, listen deeply to the brokenhearted, sow the Gospel, and equip Japanese disciples to reach their own <strong class="text-white">Oikos (relational webs)</strong> until there is <strong class="text-amber-400">No Place Left</strong> without a healthy Acts 2 church.
                </p>

                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                    <a 
                        href="{{ route('home') }}" 
                        class="px-6 py-3 rounded-2xl text-sm font-black bg-gradient-to-r from-indigo-600 to-emerald-500 text-white shadow-xl shadow-indigo-600/30 hover:scale-[1.03] active:scale-[0.98] transition flex items-center gap-2"
                    >
                        <span>🎮</span>
                        <span>Play Now (Action HQ)</span>
                    </a>
                    <a 
                        href="#story" 
                        class="px-6 py-3 rounded-2xl text-sm font-bold bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-2"
                    >
                        <span>📜</span>
                        <span>Read the Story & Rules</span>
                    </a>
                </div>

                <!-- Live Quick Stats Strip -->
                <div class="grid grid-cols-3 gap-3 pt-6 border-t border-slate-800/80 max-w-md mx-auto lg:mx-0 text-left">
                    <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                        <span class="text-xl font-black text-indigo-400">16</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Nagoya Wards</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                        <span class="text-xl font-black text-emerald-400">&lt; 0.5%</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Believers in Japan</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                        <span class="text-xl font-black text-amber-400">G1–G4+</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Church Streams</span>
                    </div>
                </div>
            </div>

            <!-- Logo Art Card Feature -->
            <div class="flex-1 w-full max-w-xl relative group">
                <div class="absolute -inset-1 rounded-3xl bg-gradient-to-tr from-amber-500/30 via-emerald-500/30 to-indigo-600/40 blur-xl opacity-75 group-hover:opacity-100 transition duration-700"></div>
                <div class="relative rounded-3xl overflow-hidden border border-slate-800 shadow-2xl bg-slate-900/90 backdrop-blur-md p-3">
                    <img 
                        src="{{ asset('images/until-no-place-left-logo.jpeg') }}" 
                        alt="Nagoya Oikos: The Four Fields Official Logo Artwork" 
                        class="w-full h-auto rounded-2xl object-cover shadow-inner"
                    >
                   
                </div>
            </div>
        </section>

        <!-- SECTION 1: THE STORY & SPIRITUAL CONTEXT -->
        <section id="story" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>📖</span>
                    <span>The Background Lore & Narrative</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">The Cry of Nagoya & The Apostolic Vision</h2>
                <p class="text-sm text-slate-400">
                    Why Nagoya? Why Relational Movements? Understanding the biblical foundation of No Place Left.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Story Card 1 -->
                <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4 relative overflow-hidden">
                    <div class="w-12 h-12 rounded-2xl bg-rose-950/60 border border-rose-800/60 flex items-center justify-center text-2xl text-rose-400">
                        ⛩️
                    </div>
                    <h3 class="text-lg font-bold text-white">The Missionary "Graveyard"?</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Japan is home to 125 million people, yet less than half of one percent know Jesus Christ. In Nagoya, corporate burnout, intense loneliness, ancestral Buddhist household obligations, and ancient Shinto shrines (like Atsuta Jingu) often make cold, Western-style pulpit preaching hit a brick wall.
                    </p>
                    <div class="pt-2 text-[11px] text-rose-300 font-semibold">
                        💡 Key Insight: Preaching without deep listening causes Japanese seekers to politely withdraw.
                    </div>
                </div>

                <!-- Story Card 2 -->
                <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4 relative overflow-hidden">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-950/60 border border-indigo-800/60 flex items-center justify-center text-2xl text-indigo-400">
                        🌐
                    </div>
                    <h3 class="text-lg font-bold text-white">The Oikos Relational Web</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        In Greek, <strong class="text-indigo-400">Oikos</strong> means household or relational sphere: family, close friends, coworkers, and neighbors. In Japan, trust is everything. The Gospel spreads virally not through strangers, but through natural networks of trust once a Japanese person accepts Christ and shares with their family.
                    </p>
                    <div class="pt-2 text-[11px] text-indigo-300 font-semibold">
                        💡 Key Insight: Disciples multiply through existing webs of trust, not isolated steeples.
                    </div>
                </div>

                <!-- Story Card 3 -->
                <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4 relative overflow-hidden">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 flex items-center justify-center text-2xl text-emerald-400">
                        🔥
                    </div>
                    <h3 class="text-lg font-bold text-white">Romans 15:23 & No Place Left</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        The Apostle Paul wrote in Romans 15:23: <em>"Having no place left in these parts..."</em> How could Paul say that in major provinces? Because he didn't convert everyone himself — he planted reproducing, indigenous church movements that filled every neighborhood. This is your assignment in Nagoya.
                    </p>
                    <div class="pt-2 text-[11px] text-emerald-300 font-semibold">
                        💡 Victory Condition: Indigenous 4th generation (G4+) church streams in all 16 wards!
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: THE FOUR FIELDS FRAMEWORK (Mark 4:26-29) -->
        <section id="four-fields" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🌱</span>
                    <span>The Ministry Engine</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">The 5 Fields of Kingdom Multiplication</h2>
                <p class="text-sm text-slate-400">
                    Based on Jesus' parable of the growing seed in Mark 4:26-29, every healthy movement moves systematically through five distinct phases.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Field 1 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4 hover:border-blue-500/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-950 text-blue-400 border border-blue-800 uppercase">Field 1</span>
                            <span class="text-xl">🤝</span>
                        </div>
                        <h4 class="text-base font-bold text-white">Empty Field (Entry Strategy)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Entering new territory. Identifying <strong class="text-blue-300">Persons of Peace (Luke 10)</strong> through Casual Talk, active listening to grief/insomnia, practical mercy (Shirakawa Park soup kitchens), and International Cafes.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-blue-300">
                        ⚡ Actions: Casual Talk, Deep Listening, Healing Prayer, Mercy Outreach.
                    </div>
                </div>

                <!-- Field 2 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4 hover:border-emerald-500/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800 uppercase">Field 2</span>
                            <span class="text-xl">🌾</span>
                        </div>
                        <h4 class="text-base font-bold text-white">Seeded Field (Gospel Sowing)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Proclaiming the Good News simply and reproducibly. Sharing your <strong class="text-emerald-300">15-Second Testimony</strong> and the <strong class="text-emerald-300">3 Circles Gospel Presentation</strong>. Leading seekers into the Prayer of Salvation.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-emerald-300">
                        ⚡ Actions: 15-Sec Testimony, 3 Circles, Prayer of Salvation (Sinner's Prayer).
                    </div>
                </div>

                <!-- Field 3 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4 hover:border-amber-500/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800 uppercase">Field 3</span>
                            <span class="text-xl">🌿</span>
                        </div>
                        <h4 class="text-base font-bold text-white">Growing Field (Discipleship)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Navigating the cultural cost of baptism with Japanese families. Immediately equipping new disciples with the <strong class="text-amber-300">411 Tool</strong> (4 Questions, 1 Sheet, 1 Hour) so they share with their own Oikos from week one.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-amber-300">
                        ⚡ Actions: Counting Cost & Baptism, 411 Discipleship Training.
                    </div>
                </div>

                <!-- Field 4 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4 hover:border-purple-500/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-950 text-purple-400 border border-purple-800 uppercase">Field 4</span>
                            <span class="text-xl">⛪</span>
                        </div>
                        <h4 class="text-base font-bold text-white">Harvested Field (Acts 2 Church Circles)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Gathering disciples into simple 3/3rds groups practicing the <strong class="text-purple-300">11 Acts 2 rhythms</strong>: Word, Prayer, Fellowship, Communion, Baptism, Giving, Worship, Local Leaders, and Multiplying Groups.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-purple-300">
                        ⚡ Rule: Developing dashed circle turns solid when all 11 rhythms are active!
                    </div>
                </div>

                <!-- Field 5 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4 hover:border-rose-500/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800 uppercase">Field 5</span>
                            <span class="text-xl">👑</span>
                        </div>
                        <h4 class="text-base font-bold text-white">Multiplication (Timothy Leaders)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Multiplying leadership. Mentoring emerging Japanese elders and planters (2 Tim 2:2). When a disciple has a new believer or becomes a Timothy, they autonomously birth <strong class="text-rose-300">G2, G3, and G4+ daughter churches</strong>!
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-rose-300">
                        ⚡ Actions: Mentor Timothy, Autonomously Plant G2/G3/G4+ Streams.
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 3: GAME RULES & SIMULATION MECHANICS -->
        <section id="rules" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>⚡</span>
                    <span>Game Mechanics</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">How the Game Operates & How to Win</h2>
                <p class="text-sm text-slate-400">
                    Mastering Action Points, daily relational ripples, and multiplayer cooperative synergy.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Mechanic 1: Action Points -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🔋</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Daily Action Points (12 AP)</h3>
                            <span class="text-xs text-slate-400">Time Stewardship Simulation</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Missionary energy is finite. Each day you receive <strong class="text-indigo-400">12 Action Points (AP)</strong>. Different actions consume different amounts of AP:
                    </p>
                    <ul class="text-xs space-y-2 text-slate-300">
                        <li class="flex items-center justify-between p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span>Casual Talk & Friendship</span>
                            <span class="font-bold text-emerald-400">1 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span>Deep Talk & Active Listening</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span>Share 3 Circles Gospel</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span>Equip with 411 Discipleship</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span>Host International Cultural Cafe</span>
                            <span class="font-bold text-rose-400">4 AP</span>
                        </li>
                    </ul>
                </div>

                <!-- Mechanic 2: Daily & Monthly Ticks -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⏱️</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Daily & Monthly Ticks</h3>
                            <span class="text-xs text-slate-400">Relational Ripple Progression</span>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300 leading-relaxed">
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white block font-semibold">🕒 Daily Ticks (Every 10 Minutes Online)</strong>
                            <span>Days advance while you interact with the game. Relational trust deepens and answered prayer ripples through family and friend networks (capped at 30 game days per real day).</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white block font-semibold">📅 Monthly Tick (Every 30 Game Days)</strong>
                            <span>When the 30-day month concludes, all queued actions are resolved, 411-trained believers step out into the harvest, daughter churches plant, and you receive an in-depth Monthly Movement Digest report.</span>
                        </div>
                    </div>
                </div>

                <!-- Mechanic 3: 1 Cor 3:6 Co-op Synergy -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🤝</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Cooperative Synergy (1 Cor 3:6-8)</h3>
                            <span class="text-xs text-slate-400">No Solo Heroes • Body Ministry</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        <em>"Paul planted, Apollos watered, but God gave the growth."</em> In Nagoya Oikos, players work the same city grid together.
                    </p>
                    <div class="p-3 rounded-xl bg-indigo-950/50 border border-indigo-800/60 text-xs text-indigo-300 leading-relaxed">
                        💡 <strong>The Synergy Harvest Rule:</strong> If Missionary A pioneers an unreached resident, Missionary B waters through prayer, and Missionary C shares the Gospel, <strong class="text-white">+50% bonus points</strong> are pooled and distributed to all co-laborers!
                    </div>
                </div>

                <!-- Mechanic 4: Movement Victory -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🏆</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Generational Scoring & Victory</h3>
                            <span class="text-xs text-slate-400">The 4th Generation Milestone</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Points are weighted exponentially towards generational reproduction (2 Tim 2:2):
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 rounded-lg bg-slate-950 border border-slate-800">
                            <span class="text-slate-400 block text-[11px]">G1 Believer / Church</span>
                            <span class="font-bold text-blue-400">+10 pts</span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-950 border border-slate-800">
                            <span class="text-slate-400 block text-[11px]">G2 Daughter Church</span>
                            <span class="font-bold text-purple-400">+250 pts</span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-950 border border-slate-800">
                            <span class="text-slate-400 block text-[11px]">G3 Granddaughter</span>
                            <span class="font-bold text-pink-400">+1,000 pts</span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-950 border border-slate-800">
                            <span class="text-slate-400 block text-[11px]">G4+ Movement Stream</span>
                            <span class="font-bold text-amber-400">+5,000 pts!</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 4: 16 WARDS OF NAGOYA OVERVIEW -->
        <section id="wards" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-purple-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🗾</span>
                    <span>The Mission Field</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Nagoya's 16 Diverse Wards</h2>
                <p class="text-sm text-slate-400">
                    Each ward has its own distinct demographics, spiritual openness index, and poverty/need dynamics.
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-center">
                @php
                    $wardPills = [
                        ['name' => 'Naka-ku', 'ja' => '中区', 'theme' => 'Sakae & Osu Arcade', 'open' => '30%'],
                        ['name' => 'Nakamura-ku', 'ja' => '中村区', 'theme' => 'Meieki Station Hub', 'open' => '25%'],
                        ['name' => 'Meito-ku', 'ja' => '名東区', 'theme' => 'Fujigaoka & Teachers', 'open' => '40%'],
                        ['name' => 'Chikusa-ku', 'ja' => '千種区', 'theme' => 'Nagoya Univ Campuses', 'open' => '35%'],
                        ['name' => 'Showa-ku', 'ja' => '昭和区', 'theme' => 'Nanzan & Leafy Families', 'open' => '35%'],
                        ['name' => 'Tempaku-ku', 'ja' => '天白区', 'theme' => 'International Students', 'open' => '45%'],
                        ['name' => 'Minato-ku', 'ja' => '港区', 'theme' => 'Port & Factory Expats', 'open' => '38%'],
                        ['name' => 'Atsuta-ku', 'ja' => '熱田区', 'theme' => 'Atsuta Jingu Shrine', 'open' => '18%'],
                        ['name' => 'Higashi-ku', 'ja' => '東区', 'theme' => 'Nagoya Dome & Heritage', 'open' => '22%'],
                        ['name' => 'Kita-ku', 'ja' => '北区', 'theme' => 'Suburban Commuters', 'open' => '20%'],
                        ['name' => 'Nishi-ku', 'ja' => '西区', 'theme' => 'Mozo & Craftsmen', 'open' => '24%'],
                        ['name' => 'Mizuho-ku', 'ja' => '瑞穂区', 'theme' => 'Yamazaki River', 'open' => '22%'],
                        ['name' => 'Nakagawa-ku', 'ja' => '中川区', 'theme' => 'Canals & Industry', 'open' => '20%'],
                        ['name' => 'Minami-ku', 'ja' => '南区', 'theme' => 'Nippon Gaishi Hall', 'open' => '20%'],
                        ['name' => 'Moriyama-ku', 'ja' => '守山区', 'theme' => 'Green Foothills', 'open' => '25%'],
                        ['name' => 'Midori-ku', 'ja' => '緑区', 'theme' => 'Odaka Castle Park', 'open' => '22%'],
                    ];
                @endphp

                @foreach($wardPills as $w)
                    <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-1 hover:border-indigo-500/50 transition">
                        <span class="text-xs font-bold text-white block">{{ $w['name'] }}</span>
                        <span class="text-[10px] text-slate-500 block">{{ $w['ja'] }}</span>
                        <span class="text-[10px] text-indigo-400 block font-semibold truncate">{{ $w['theme'] }}</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-950 text-emerald-400 border border-slate-800 block">
                            {{ $w['open'] }} Open
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- SECTION 5: QUICK START CHECKLIST FOR NEW MISSIONARIES -->
        <section class="p-8 sm:p-12 rounded-3xl bg-gradient-to-tr from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-2xl space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🚀</span>
                    <span>Quick-Start Walkthrough</span>
                </span>
                <h3 class="text-2xl sm:text-3xl font-black text-white">Your First 5 Steps in Nagoya</h3>
                <p class="text-xs sm:text-sm text-slate-400">Follow this simple roadmap to plant your first disciple and daughter church.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-left text-xs">
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">1</span>
                    <strong class="text-white block font-semibold">Enter Action HQ</strong>
                    <span class="text-slate-400 block leading-relaxed">Select a resident in Nagoya. Look for Persons of Peace with Yellow status (like Kenji in Osu or Linh in Tempaku).</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">2</span>
                    <strong class="text-white block font-semibold">Build Relational Trust</strong>
                    <span class="text-slate-400 block leading-relaxed">Execute Casual Talk or Deep Listening. Pray for healing if they are in crisis until trust rises to 60%+.</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">3</span>
                    <strong class="text-white block font-semibold">Share the Gospel</strong>
                    <span class="text-slate-400 block leading-relaxed">Share the 3 Circles. Lead them in the Prayer of Salvation. Their status will turn Green!</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">4</span>
                    <strong class="text-white block font-semibold">Train 411 Discipleship</strong>
                    <span class="text-slate-400 block leading-relaxed">Equip your new believer in 411. They become an Apprentice and begin sharing with their own family.</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-[11px]">5</span>
                    <strong class="text-white block font-semibold">Birth G2 Discovery Church</strong>
                    <span class="text-slate-400 block leading-relaxed">When their family accepts Christ, an autonomous G2 Church Circle is birthed! Watch reproduction explode!</span>
                </div>
            </div>

            <div class="pt-4 text-center">
                <a 
                    href="{{ route('home') }}" 
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl text-sm font-black bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-2xl shadow-indigo-600/30 hover:scale-105 active:scale-[0.98] transition cursor-pointer"
                >
                    <span>⚡</span>
                    <span>Start Playing: Enter Action HQ</span>
                </a>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/90 py-10 mt-16 text-center text-xs text-slate-500 space-y-3">
        <div class="flex items-center justify-center gap-2">
            <img src="{{ asset('images/until-no-place-left-logo-small.png') }}" alt="Logo" class="w-6 h-6 rounded-lg object-contain">
            <span class="text-slate-300 font-bold">Nagoya Oikos: The Four Fields</span>
        </div>
        <p>A multi-player movement simulation based on the Four Fields framework and Acts 2 Church Circles.</p>
        <p class="text-[11px] text-slate-600">Romans 15:23 • Mark 4:26-29 • 2 Timothy 2:2 • Matthew 28:18-20</p>
    </footer>

</body>
</html>
