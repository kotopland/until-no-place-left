<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nagoya Oikos: The Four Fields • Movement Simulation Story & Rules</title>
    
    <link rel="icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}">
    @fonts

    <!-- Leaflet CSS for preview map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <script>
        (function() {
            var lastToggleTime = 0;
            window.__getTheme = function() {
                var stored = localStorage.getItem('theme');
                if (stored) return stored;
                return 'dark';
            };
            window.__applyTheme = function(mode) {
                var isDark = mode === 'dark';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                document.querySelectorAll('[data-theme-icon]').forEach(function(el) {
                    el.textContent = isDark ? '☀️' : '🌙';
                });
                document.querySelectorAll('[data-theme-label]').forEach(function(el) {
                    el.textContent = isDark ? 'Light' : 'Dark';
                });
            };
            window.__toggleTheme = function() {
                var now = Date.now();
                if (now - lastToggleTime < 150) {
                    return;
                }
                lastToggleTime = now;

                var current = window.__getTheme();
                var next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', next);
                window.__applyTheme(next);
                if (window.Alpine && window.Alpine.store && window.Alpine.store('theme')) {
                    window.Alpine.store('theme').mode = next;
                }
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { mode: next } }));
            };
            window.__applyTheme(window.__getTheme());
            document.addEventListener('DOMContentLoaded', function() {
                window.__applyTheme(window.__getTheme());
            });
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans selection:bg-indigo-500 selection:text-white antialiased overflow-x-hidden transition-colors duration-200" x-data>

    <!-- Top Glow / Ambient Background Gradient -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[500px] bg-gradient-to-b from-indigo-900/20 via-emerald-900/10 to-transparent blur-3xl pointer-events-none -z-10"></div>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
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
                <a href="#toolbox" class="hover:text-cyan-400 transition">5 Levels Toolbox</a>
                <a href="#rules" class="hover:text-amber-400 transition">Game Rules</a>
                <a href="#wards" class="hover:text-purple-400 transition">16 Wards</a>
                <a href="#quickstart" class="hover:text-emerald-400 transition">Quick Start</a>
            </nav>

            <div class="flex items-center gap-2.5">
                <!-- Theme Mode Toggle Button (Light/Dark) -->
                <button 
                    type="button" 
                    onclick="window.__toggleTheme ? window.__toggleTheme() : null"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 border-slate-300 dark:border-slate-700/80 text-amber-600 dark:text-amber-300 hover:text-amber-700 dark:hover:text-white"
                    title="Switch Light/Dark Mode"
                >
                    <span data-theme-icon>☀️</span>
                    <span data-theme-label class="hidden sm:inline text-[11px]">Light</span>
                </button>

                <!-- Ambient Music Toggle Button with Hover Popover -->
                <div class="relative group">
                    <button 
                        type="button" 
                        @click="$store.audio?.toggle()" 
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                        :class="$store.audio?.isPlaying ? 'bg-indigo-950/90 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500/40' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
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

                    <!-- Hover Popover: Song Title & Controls -->
                    <div 
                        class="absolute right-0 top-full mt-2 w-64 p-3.5 rounded-2xl bg-slate-900/95 backdrop-blur-xl border border-indigo-500/30 shadow-2xl shadow-black/80 z-50 pointer-events-none opacity-0 group-hover:opacity-100 scale-95 group-hover:scale-100 transition-all duration-200 origin-top-right space-y-2 text-left"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-800 pb-2">
                            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" :class="$store.audio?.isPlaying ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                                <span x-text="$store.audio?.isPlaying ? 'Now Playing' : 'Background Music'"></span>
                            </span>
                            <span class="text-[9px] font-semibold text-slate-300 px-1.5 py-0.5 rounded bg-slate-800/90 border border-slate-700/60" x-text="$store.audio?.isPlaying ? ($store.audio?.currentSongCategory || 'Traditional & Folk') : 'Paused'"></span>
                        </div>

                        <div x-show="$store.audio?.isPlaying" class="space-y-1">
                            <div class="text-xs font-bold text-white leading-snug flex items-center gap-1.5">
                                <span>🎵</span>
                                <span x-text="$store.audio?.currentSongEnglish || 'Traditional Melody'"></span>
                            </div>
                            <div class="text-[11px] text-indigo-300/90 font-medium pl-5" x-text="$store.audio?.currentSongJapanese || ''"></div>
                            <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-800/60 flex items-center gap-1">
                                <span>💡</span>
                                <span>Click to mute • Shuffles on next play</span>
                            </div>
                        </div>

                        <div x-show="!$store.audio?.isPlaying" class="space-y-1">
                            <div class="text-xs font-semibold text-slate-300">Music is muted</div>
                            <div class="text-[10px] text-slate-400">Click to play famous Japanese melodies (Sakura, Sukiyaki, Furusato, etc.)</div>
                        </div>
                    </div>
                </div>

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
                            Entering new territory. Softening spiritual soil and discovering <strong class="text-blue-300">Persons of Peace (Luke 10)</strong> through solo street prayer walks or <strong class="text-emerald-400">Joint MAWL Prayer Walks</strong> with residents, Casual Talk, listening to corporate burnout, healing prayer, mercy care, and International Cafes.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-blue-300">
                        ⚡ Actions: Prayer Walking (Solo/Joint), Casual Talk, Deep Listening, Healing Prayer, Mercy Outreach, International Cafe.
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
                            Proclaiming the Good News simply and reproducibly. Sharing your <strong class="text-emerald-300">15-Second Testimony</strong> (before, how Christ met me, life now) and the visual <strong class="text-emerald-300">3 Circles Gospel Presentation</strong>. Guiding seekers through the Prayer of Salvation.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-emerald-300">
                        ⚡ Actions: 15-Sec Testimony, 3 Circles Gospel, Prayer of Salvation.
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
                            Grounding believers in immediate loving obedience through the <strong class="text-amber-300">Commands of Christ</strong> (with hand motions) and <strong class="text-amber-300">Sword Bible Studies</strong>. Equipping them with the <strong class="text-amber-300">411 Tool</strong> to share with their own Oikos and count the cost of baptism.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-amber-300">
                        ⚡ Actions: Commands of Christ, Sword Bible Study, Counting Cost & Baptism, 411 Discipleship.
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
                            Gathering baptized disciples into self-governing 3/3rds groups practicing the <strong class="text-purple-300">11 Acts 2 rhythms</strong>: Word, Prayer, Fellowship, Communion, Baptism, Giving, Worship, Local Leaders, and Multiplying Groups.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-purple-300">
                        ⚡ Actions: Acts 2 Church Circles (3/3rds: Look Back, Look Up, Look Ahead).
                    </div>
                </div>

                <!-- Field 5 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 flex flex-col justify-between space-y-4 hover:border-rose-500/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800 uppercase">Field 5</span>
                            <span class="text-xl">👑</span>
                        </div>
                        <h4 class="text-base font-bold text-white">Multiplication (5 Levels of Leadership)</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Multiplying leadership beyond missionary bottlenecks. Mentoring Timothys (L1 to L2), conducting quarterly <strong class="text-rose-300">Iron-on-Iron peer coaching</strong> with generational mapping, and deploying regional <strong class="text-rose-300">Swarm Training teams</strong>.
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-[11px] text-rose-300">
                        ⚡ Actions: Mentor Timothy, Iron-on-Iron Coaching, Swarm Training & Movement Catalyzing.
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2.5: THE 5 LEVELS OF LEADERSHIP TRAINING & COACHING TOOLBOX -->
        <section id="toolbox" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🧰</span>
                    <span>Movement Leadership Matrix</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">5 Levels of Leadership Training & Coaching Toolbox</h2>
                <p class="text-sm text-slate-400">
                    A comprehensive framework to identify, train, coach, and multiply Japanese Christian workers from first-time seed sowers to regional strategy coordinators.
                </p>
            </div>

            <!-- Guiding Principle Banner -->
            <div class="p-6 rounded-3xl bg-gradient-to-r from-cyan-950/80 via-indigo-950/70 to-slate-900 border border-cyan-800/60 shadow-xl space-y-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">💡</span>
                    <h3 class="text-base font-bold text-cyan-200">The Guiding Principle of Movement Coaching</h3>
                </div>
                <blockquote class="text-sm text-cyan-100 font-medium italic border-l-4 border-cyan-500 pl-4 py-1 leading-relaxed">
                    "Lead from one level ahead of wherever the leader is who you are with! Once your leaders are confident and competent with the tools for their current level, begin equipping them with tools from 1 level above!"
                </blockquote>
                <p class="text-xs text-slate-300 leading-relaxed">
                    In the simulation, every resident's spiritual readiness and leadership tier are tracked in real-time. The system strictly guards against pedagogical mismatches: <em>people who have never heard about Jesus cannot be assigned Timothy Mentoring or Swarm Training</em>.
                </p>
            </div>

            <!-- 5 Levels Cards Matrix -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Level 1 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-cyan-800/40 flex flex-col justify-between space-y-4 hover:border-cyan-500/60 transition group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-950 text-cyan-400 border border-cyan-800">LEVEL 1</span>
                            <span class="text-xl">🌱</span>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-white group-hover:text-cyan-300 transition">Seed Sower</h4>
                            <span class="text-[11px] text-cyan-400 font-medium block">種まく人 (Personal Sower)</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <strong>Core Focus:</strong> Personal evangelism, entry into new relational spheres, and basic discipleship tools.
                        </p>
                        <div class="space-y-1.5 text-[11px] text-slate-400">
                            <div><strong class="text-slate-200">Tools:</strong> 411, 15-Sec Testimony, 3 Circles, 3-Touch Entry, Oikos Mapping.</div>
                            <div><strong class="text-slate-200">Studies:</strong> Commands of Christ, My Story / Jesus Story.</div>
                            <div><strong class="text-slate-200">Progression:</strong> Overcome fear of man; witness consistently to family & friends.</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-800 text-[10px] font-bold text-cyan-300 flex items-center justify-between">
                        <span>Residential: 3-Touch Entry</span>
                        <span>Multiplication: 1x</span>
                    </div>
                </div>

                <!-- Level 2 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-emerald-800/40 flex flex-col justify-between space-y-4 hover:border-emerald-500/60 transition group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">LEVEL 2</span>
                            <span class="text-xl">⛪</span>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-white group-hover:text-emerald-300 transition">Church Planter</h4>
                            <span class="text-[11px] text-emerald-400 font-medium block">開拓者 (Simple Church Planter)</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <strong>Core Focus:</strong> Gathering disciples, forming simple reproducing churches, and pastoral care.
                        </p>
                        <div class="space-y-1.5 text-[11px] text-slate-400">
                            <div><strong class="text-slate-200">Tools:</strong> 3/3rds Church Circles, Commands of Christ, Sword DBS, Baptism practice.</div>
                            <div><strong class="text-slate-200">Studies:</strong> 3/3rds Meeting Pattern, Lord's Supper, Acts 2 Church Health.</div>
                            <div><strong class="text-slate-200">Progression:</strong> Lead a 3/3rds church; practice loving accountability.</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-800 text-[10px] font-bold text-emerald-300 flex items-center justify-between">
                        <span>Residential: Church Formation</span>
                        <span>Multiplication: 2x–3x</span>
                    </div>
                </div>

                <!-- Level 3 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-amber-800/40 flex flex-col justify-between space-y-4 hover:border-amber-500/60 transition group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800">LEVEL 3</span>
                            <span class="text-xl">🌿</span>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-white group-hover:text-amber-300 transition">CPMer / Multiplier</h4>
                            <span class="text-[11px] text-amber-400 font-medium block">増殖者 (Generational Multiplier)</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <strong>Core Focus:</strong> Multiplying churches to 2nd, 3rd, and 4th generations (G2–G4+).
                        </p>
                        <div class="space-y-1.5 text-[11px] text-slate-400">
                            <div><strong class="text-slate-200">Tools:</strong> Iron-on-Iron Coaching, Generational Mapping, 4 Fields Diagnostics.</div>
                            <div><strong class="text-slate-200">Studies:</strong> 4 Fields Diagnostics, 12 Diagnostic Questions, 5 Levels Matrix.</div>
                            <div><strong class="text-slate-200">Progression:</strong> Solve generational bottlenecks; coach daughter churches.</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-800 text-[10px] font-bold text-amber-300 flex items-center justify-between">
                        <span>Residential: Left/Right Hand</span>
                        <span>Multiplication: 4x–8x</span>
                    </div>
                </div>

                <!-- Level 4 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-purple-800/40 flex flex-col justify-between space-y-4 hover:border-purple-500/60 transition group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-950 text-purple-400 border border-purple-800">LEVEL 4</span>
                            <span class="text-xl">🐝</span>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-white group-hover:text-purple-300 transition">Movement Trainer</h4>
                            <span class="text-[11px] text-purple-400 font-medium block">運動トレーナー (Regional Trainer)</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <strong>Core Focus:</strong> Equipping and multiplying trainers across multiple wards and networks.
                        </p>
                        <div class="space-y-1.5 text-[11px] text-slate-400">
                            <div><strong class="text-slate-200">Tools:</strong> Swarm Training, Mid-Level Leadership Training, Strategy Evaluation.</div>
                            <div><strong class="text-slate-200">Studies:</strong> CPM Case Studies, Training Trainers (T4T), Catalyst Multiplication.</div>
                            <div><strong class="text-slate-200">Progression:</strong> Lead multi-team field saturation; establish regional cohorts.</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-800 text-[10px] font-bold text-purple-300 flex items-center justify-between">
                        <span>Residential: Mid-Level Training</span>
                        <span>Multiplication: 10x–20x</span>
                    </div>
                </div>

                <!-- Level 5 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-rose-800/40 flex flex-col justify-between space-y-4 hover:border-rose-500/60 transition group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">LEVEL 5</span>
                            <span class="text-xl">👑</span>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-white group-hover:text-rose-300 transition">Strategy Coordinator</h4>
                            <span class="text-[11px] text-rose-400 font-medium block">戦略コーディネーター (Regional SC)</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <strong>Core Focus:</strong> City-wide and regional saturation strategy across all 16 Nagoya wards.
                        </p>
                        <div class="space-y-1.5 text-[11px] text-slate-400">
                            <div><strong class="text-slate-200">Tools:</strong> Master Ward Mapping, Unreached Group Mapping, Movement Labs, CPM Summits.</div>
                            <div><strong class="text-slate-200">Studies:</strong> Aichi Demographics, Luke 10 Macro Strategy, G4 Movements.</div>
                            <div><strong class="text-slate-200">Progression:</strong> Cast city-wide vision and deploy trainers Until No Place Left.</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-slate-800 text-[10px] font-bold text-rose-300 flex items-center justify-between">
                        <span>Residential: Movement Summit</span>
                        <span>Multiplication: 50x+</span>
                    </div>
                </div>
            </div>

            <!-- Option B Callout: Dual-Mode Prayer Walking (Solo & Joint MAWL) -->
            <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">👣</span>
                    <div>
                        <h3 class="text-base font-bold text-white">Dual-Mode Prayer Walking & Spiritual Mapping (Option B)</h3>
                        <span class="text-xs text-emerald-400 font-semibold">Solo Ward Intercession vs. Joint MAWL Walk with a Resident</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-300 leading-relaxed">
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                        <strong class="text-white block font-semibold flex items-center gap-1.5">
                            <span>🚶</span>
                            <span>Solo Ward-Wide Intercession (No Target NPC)</span>
                        </strong>
                        <p>
                            Walk through unknown neighborhoods, transit stations, and shrine districts to soften spiritual soil (+2% ward openness). Each walk increases the cumulative probability (base 35% + 15% per walk, up to 90%) of discovering a <strong class="text-blue-300">Luke 10 Person of Peace</strong>.
                        </p>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                        <strong class="text-white block font-semibold flex items-center gap-1.5">
                            <span>👥</span>
                            <span>Joint MAWL Prayer Walk (With Target Resident)</span>
                        </strong>
                        <p>
                            Invite an open contact, seeker, or believer to walk together using <strong class="text-emerald-400">MAWL</strong> (<em>Model, Assist, Watch, Leave</em>). Walking their neighborhood models prayer evangelism directly in their world, surges their personal trust by <strong class="text-emerald-300">+22% to +32%</strong>, and covers their immediate family & coworker <strong class="text-indigo-300">Oikos network (+15% trust)</strong> in intercession!
                        </p>
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                        Missionary energy and time are finite. Each day you receive <strong class="text-indigo-400">12 Action Points (AP)</strong>. Actions consume base AP, plus an additional <strong class="text-amber-400">+1 to +2 AP transit surcharge</strong> when commuting to distant wards from your Home Base:
                    </p>
                    <ul class="text-xs space-y-1.5 text-slate-300 max-h-80 overflow-y-auto pr-1">
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🚶 Solo Prayer Walk (1 AP) / 👣 Joint Prayer Walk (2 AP, max 1/day & 3/wk)</span>
                            <span class="font-bold text-emerald-400">1 – 2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>💬 Casual Talk & Friendship</span>
                            <span class="font-bold text-emerald-400">1 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>👂 Deep Listening & Burdens</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🙏 Prayer for Healing & Peace</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>⏱️ 15-Second Testimony</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>⭕ Share 3 Circles Gospel</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🕊️ Guide Prayer of Salvation</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>📜 Commands of Christ (L2 Curriculum)</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🗡️ Sword Discovery Bible Study</span>
                            <span class="font-bold text-emerald-400">2 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🍲 Mercy Outreach & Practical Care</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🌊 Count Cost & Baptism</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>📋 Equip with 411 Discipleship</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>⛪ Form Acts 2 Church Circles</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🌱 Mentor Timothy Leader (L1 to L2)</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>⚔️ Iron-on-Iron Peer Coaching (L2 to L3)</span>
                            <span class="font-bold text-amber-400">3 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🍱 Host International Cultural Cafe</span>
                            <span class="font-bold text-rose-400">4 AP</span>
                        </li>
                        <li class="flex items-center justify-between p-1.5 rounded-lg bg-slate-950 border border-slate-800">
                            <span>🐝 Swarm Training & Movement Catalyzing</span>
                            <span class="font-bold text-rose-400">4 AP</span>
                        </li>
                    </ul>
                    <div class="p-2.5 rounded-xl bg-indigo-950/40 border border-indigo-800/60 text-[11px] text-indigo-300">
                        ⚡ <em>Action HQ Flow:</em> Scheduling actions follows an intuitive 3-step path: (1) Pick District ➔ (2) Pick Resident ➔ (3) Choose an Action from the list. The planner smoothly scrolls down to the <strong>Ready to Queue for Day X</strong> box at the bottom where you can add personal prayer notes and queue the action with one click (or use the persistent confirmation dock on mobile devices).
                    </div>
                </div>

                <!-- Mechanic 2: Home Base, Commuting & Proximity -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🏡</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Home Base & Commuting Friction</h3>
                            <span class="text-xs text-slate-400">Incarnational Ministry & Proximity</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Jesus ministered incarnationally where He dwelt. In Nagoya Oikos, you establish a <strong class="text-emerald-400">Home Ward</strong> where you live, and you can relocate your residence anytime:
                    </p>
                    <div class="space-y-2 text-xs text-slate-300">
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-emerald-400 font-bold block flex items-center gap-1">
                                <span>🏡</span>
                                <span>Home Ward (0 km): Neighbor Advantage</span>
                            </span>
                            <span class="text-slate-400 text-[11px]">
                                <strong>0 AP transit penalty</strong>, effortless casual bumping, and a <strong>+25% Trust & Rapid Follow-Up Bonus</strong> when cultivating relationships or walking with neighbors.
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-amber-400 font-bold block flex items-center gap-1">
                                <span>🚇</span>
                                <span>Cross-City Travel (5–10+ km)</span>
                            </span>
                            <span class="text-slate-400 text-[11px]">
                                Subways take valuable ministry hours. Long distances add <strong>+1 to +2 AP transit surcharges</strong> per action and introduce <strong>-15% to -30% follow-up friction</strong>.
                            </span>
                        </div>
                        <div class="p-2 rounded-xl bg-indigo-950/40 border border-indigo-800/60 text-[11px] text-indigo-300">
                            💡 <em>Pioneer Tip:</em> Click <strong>"🏡 Move Here"</strong> in the Action Queue Planner to relocate your missionary base whenever your ministry focus shifts to a new ward! You can also view your house (🏡) and fellow co-laborers' homes (🏠) on the GIS map to coordinate team deployments across the 16 wards.
                        </div>
                    </div>
                </div>

                <!-- Mechanic 3: Independent Player Pacing & Progression -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⏱️</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Independent Player Pacing</h3>
                            <span class="text-xs text-slate-400">Personal Clock & Self-Advancement</span>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300 leading-relaxed">
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white block font-semibold">⚡ Personal Daily Progression</strong>
                            <span>Clicking <em>Next Day</em> advances <strong>only your</strong> game day and replenishes your 12 AP instantly. You progress at your own pace without waiting for or disrupting other players.</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white block font-semibold">📅 Self-Paced Month Advance & Review</strong>
                            <span>Upon completing 30 game days in a month, manual month advancement unlocks. Review your rich <strong>Month in Review Digest</strong> celebrating answered prayers, church health, and coach insights.</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white block font-semibold">🛡️ Automated Nightly Safeguards</strong>
                            <span>A nightly automated tick advances inactive players, while active players who already advanced are safely guarded against double-advancement.</span>
                        </div>
                    </div>
                </div>

                <!-- Mechanic 3: 1 Cor 3:6 Co-op Synergy & Automated Missionary Co-Laborers -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🤝</span>
                            <div>
                                <h3 class="text-base font-bold text-white">Cooperative Synergy & Missionary Co-Laborers</h3>
                                <span class="text-xs text-slate-400">1 Cor 3:6-8 • Solo Pioneer or Team Co-op</span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-950 text-emerald-300 border border-emerald-800">
                            Command Configured
                        </span>
                    </div>

                    <p class="text-xs text-slate-300 leading-relaxed">
                        <em>"Paul planted, Apollos watered, but God gave the growth."</em> Nagoya Oikos supports two dedicated gameplay modes configured by administrators via the <code class="px-1.5 py-0.5 rounded bg-slate-800 text-indigo-300 font-mono text-[10px]">php artisan game</code> operator command center (players cannot alter the mode in-game, ensuring consistent competitive and cooperative fairness):
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white block font-semibold flex items-center gap-1.5">
                                <span>🏔️</span>
                                <span>Solo Pioneer Mode</span>
                            </strong>
                            <p class="text-slate-400 text-[11px] leading-relaxed">
                                You are the sole catalytic missionary in Nagoya. All 16 wards and thousands of oikos connections depend on your pioneering faithfulness without computer aid.
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-indigo-900/50 space-y-1">
                            <strong class="text-indigo-300 block font-semibold flex items-center gap-1.5">
                                <span>👥</span>
                                <span>Team Co-op Mode (AI Co-Laborers)</span>
                            </strong>
                            <p class="text-slate-400 text-[11px] leading-relaxed">
                                Three specialized automated missionary co-laborers sow, pray, and disciple alongside you on daily ticks, modeling biblical teamwork:
                            </p>
                        </div>
                    </div>

                    <!-- AI Missionary Archetypes -->
                    <div class="space-y-2 pt-1">
                        <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div>
                                <div class="font-bold text-white flex items-center gap-1.5">
                                    <span>🎓</span>
                                    <span>Sarah Jenkins (Chikusa-ku)</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-indigo-900 text-indigo-300 border border-indigo-700">Campus Sower</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-0.5">Focuses on student hubs near Nagoya University. Uses rapid 15-second testimonies and 3 Circles Gospel shares.</p>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div>
                                <div class="font-bold text-white flex items-center gap-1.5">
                                    <span>🍲</span>
                                    <span>Marcus Silva (Minato-ku)</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-900 text-amber-300 border border-amber-700">Mercy Catalyst</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-0.5">Engages crisis needs, homeless populations, and port laborers. Melts hard ground through physical relief and healing prayer.</p>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div>
                                <div class="font-bold text-white flex items-center gap-1.5">
                                    <span>📜</span>
                                    <span>Kenji Yamada (Meito-ku)</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-emerald-900 text-emerald-300 border border-emerald-700">Discipleship Trainer</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-0.5">Deeply trains Green believers with 411 and Commands of Christ, organizing multiple disciples into reproducing Church Circles.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-indigo-950/50 border border-indigo-800/60 text-xs text-indigo-300 leading-relaxed space-y-1">
                        <div>💡 <strong>Collaborative Co-op Synergy Mechanics:</strong></div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-300">
                            <li><strong>Joint MAWL Prayer Walk:</strong> Partner 2-by-2 in a ward with a co-laborer to soften spiritual openness (+50% bonus) and earn +15 Co-op Synergy points. <em>(Limited to once per day and 3 times per week to maintain a balanced ministry rhythm).</em></li>
                            <li><strong>Iron-on-Iron Sharpening:</strong> Meet for peer coaching to receive +2 bonus AP, +25 Co-op points, and contextual Four Fields advice.</li>
                            <li><strong>Shared Harvest:</strong> Sowing, watering, and harvesting the same resident pools +50% bonus points to all participating co-laborers!</li>
                        </ul>
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

                <!-- Mechanic 5: Interactive GIS Movement Map -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🗺️</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Interactive GIS Movement Map</h3>
                            <span class="text-xs text-slate-400">Real-Time Spiritual Intelligence</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        The Action HQ map provides live tactical overlays across Nagoya:
                    </p>
                    <ul class="text-xs space-y-1.5 text-slate-300">
                        <li class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-950 border border-slate-800/80">
                            <span>🔥</span>
                            <span><strong>Authentic 16-Ward GIS Boundaries & Heatmap</strong>: High-resolution official municipal borders with live spiritual openness choropleth shading</span>
                        </li>
                        <li class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-950 border border-slate-800/80">
                            <span>⭕</span>
                            <span><strong>Acts 2 Church Circles</strong> (dashed developing vs solid multiplying)</span>
                        </li>
                        <li class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-950 border border-slate-800/80">
                            <span>🌐</span>
                            <span><strong>Oikos Relational Arcs</strong> connecting families & coworkers</span>
                        </li>
                        <li class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-950 border border-slate-800/80">
                            <span>👣</span>
                            <span><strong>Historical Prayer Walks</strong> & discovered Persons of Peace</span>
                        </li>
                        <li class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-950 border border-slate-800/80">
                            <span>🏡</span>
                            <span><strong>Missionary Residences & Team Bases</strong>: View your home (🏡) and fellow co-laborers' houses (🏠) with dedicated map toggles and status tooltips</span>
                        </li>
                    </ul>
                </div>

                <!-- Mechanic 6: Live Multiplayer Leaderboard -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">📊</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Live Multiplayer Leaderboard</h3>
                            <span class="text-xs text-slate-400">Movement Metrics & City-Wide Impact</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Track progress across the whole body of Christ in Nagoya:
                    </p>
                    <div class="space-y-2 text-xs text-slate-300">
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-indigo-400 font-bold block">🏅 Generational Depth Tracking</span>
                            <span class="text-slate-400 text-[11px]">Compare individual and team contributions to G1–G4 church reproduction.</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-emerald-400 font-bold block">✨ Shared Victory Condition</span>
                            <span class="text-slate-400 text-[11px]">Win together when every ward has healthy, reproducing indigenous churches.</span>
                        </div>
                    </div>
                </div>

                <!-- Mechanic 7: Missionary Candidate Capability Screening -->
                <div class="p-6 rounded-3xl bg-slate-900/80 border border-indigo-500/40 shadow-xl space-y-4 md:col-span-2 lg:col-span-3">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">📋</span>
                        <div>
                            <h3 class="text-base font-bold text-white">Missionary Candidate Capability Screening</h3>
                            <span class="text-xs text-indigo-400">7 Dimensions • Gated Field Clearance Prior to Deployment</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Cross-cultural pioneering in Japan requires deep spiritual, emotional, and cultural fortitude. Before establishing a home base, new players undergo a 14-question evaluation across <strong class="text-white">7 core missionary dimensions</strong>:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-2.5 text-xs text-slate-300">
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>🍱</span>
                                <span>Cultural & Food</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Honoring host hospitality (natto, cuisine) & reading the air (wa).</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>🔥</span>
                                <span>Calling & Ministry</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Rom 15:20 apostolic vision & persistence through dry seasons.</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>🗣️</span>
                                <span>Language Learning</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Daily immersion & childlike humility to sound clumsy while learning.</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>🤝</span>
                                <span>Team & Authority</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Matt 18 peacemaking, honoring leadership & preserving team unity.</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>⚓</span>
                                <span>Emotional Resilience</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Abiding deeply in Christ, healthy Sabbath & conquering urban isolation.</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>🥗</span>
                                <span>Dietary & Health</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Food allergy communication, sleep rhythms & physical mobility stamina.</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="flex items-center gap-1.5 font-bold text-white text-[11px]">
                                <span>💼</span>
                                <span>Practical & Exit</span>
                            </span>
                            <p class="text-[11px] text-slate-400 leading-snug">Financial reserves, emergency buffers & intentional MAWL exit criteria.</p>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-indigo-950/50 border border-indigo-800/60 text-xs text-indigo-300 leading-relaxed flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span><strong>Qualification Gate:</strong> Candidates must score ≥ 70% overall with no critical gaps (≥ 40% in each dimension) to earn Nagoya Deployment Clearance. Candidates can review tailored coaching notes and retake the screening anytime.</span>
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

            <!-- Home Base & Incarnational Relocation Banner -->
            <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-indigo-950/60 border border-emerald-800/60 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
                <div class="flex items-center gap-3.5">
                    <span class="text-3xl">🏡</span>
                    <div>
                        <h4 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>Choose Where You Live & Work</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-900/80 text-emerald-300 border border-emerald-700">Incarnational Presence</span>
                        </h4>
                        <p class="text-xs text-slate-300 mt-0.5 max-w-2xl leading-relaxed">
                            Every missionary needs a physical home base in the city. On your <strong>first visit</strong>, an interactive prompt guides you to choose your starting district, highlighting <strong>Pioneering Frontiers where 0 other co-laborers reside</strong> (Romans 15:20) to blanket the city. Living in your <strong>Home Ward</strong> grants effortless local access: <strong>0 AP transit penalty</strong>, <strong>+25% rapid follow-up trust</strong>, and enhanced prayer walk openness. Traveling across town adds <strong>+1 to +2 AP transit costs</strong>. You can view or change your home base anytime from the top navigation bar or the Action Queue Planner!
                        </p>
                    </div>
                </div>
                <a 
                    href="{{ route('home') }}" 
                    class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-1.5 cursor-pointer"
                >
                    <span>🧭</span>
                    <span>Set Home Ward in HQ</span>
                </a>
            </div>
        </section>

        <!-- SECTION 5: QUICK START CHECKLIST FOR NEW MISSIONARIES -->
        <section id="quickstart" class="scroll-mt-24 p-8 sm:p-12 rounded-3xl bg-gradient-to-tr from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-2xl space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🚀</span>
                    <span>Quick-Start Walkthrough</span>
                </span>
                <h3 class="text-2xl sm:text-3xl font-black text-white">Your First 6 Steps in Nagoya</h3>
                <p class="text-xs sm:text-sm text-slate-400">Follow this simple roadmap to establish your base and multiply indigenous churches.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5 text-left text-xs">
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-indigo-500/50 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">1</span>
                    <strong class="text-white block font-semibold">Candidate Screening Gate</strong>
                    <span class="text-slate-400 block leading-relaxed">Complete the 14-question evaluation across <strong class="text-indigo-400">7 missionary dimensions</strong> (Food & Culture, Calling, Language, Authority, Resilience, Dietary/Health, Financial/Exit). Achieve ≥ 70% to gain <strong>Nagoya Deployment Clearance</strong>!</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">2</span>
                    <strong class="text-white block font-semibold">Choose Home Base on Arrival</strong>
                    <span class="text-slate-400 block leading-relaxed">Select your home base from the 16 wards. Accept the smart suggestion for an unreached <strong>Pioneering Frontier (0 Co-laborers)</strong> to gain <strong class="text-emerald-400">+25% trust & 0 AP commute</strong>.</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">3</span>
                    <strong class="text-white block font-semibold">Prayer Walk (Solo/MAWL)</strong>
                    <span class="text-slate-400 block leading-relaxed">Intercede on ward streets solo or conduct a <strong class="text-emerald-400">Joint MAWL Prayer Walk</strong> with a local resident to soften soil, discover Persons of Peace, and cover their Oikos.</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">4</span>
                    <strong class="text-white block font-semibold">Build Relational Trust</strong>
                    <span class="text-slate-400 block leading-relaxed">Engage in Casual Talk, active listening to corporate burnout, and healing prayer. Elevate trust to 60%+ to open hearts to the Gospel.</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-[11px]">5</span>
                    <strong class="text-white block font-semibold">Proclaim & Harvest</strong>
                    <span class="text-slate-400 block leading-relaxed">Share your 15-second testimony and 3 Circles Gospel. Guide seekers through the Prayer of Salvation to become new reproducing believers.</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-emerald-600 text-white space-y-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-[11px]">6</span>
                    <strong class="text-white block font-semibold">Multiply L1–L5 Movements</strong>
                    <span class="text-slate-400 block leading-relaxed">Equip with Commands of Christ & 411, form Acts 2 Church Circles, mentor Timothys (L2), sharpen peers via Iron-on-Iron (L3), and launch Swarms (L4/L5) to G4+ victory!</span>
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

            <div class="text-center text-[11px] text-slate-500 flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 pt-2 border-t border-slate-800/60">
                <span class="flex items-center gap-1.5">
                    <span>🌓</span>
                    <span>Visual Preference: Toggle between <strong>Dark Mode</strong> and <strong>Light Mode</strong> anytime using the ☀️/🌙 button in the top navigation bar.</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span>🎵</span>
                    <span>Traditional Soundscape: Plays 17 iconic Japanese melodies (Traditional & Folk: Sakura Sakura, Haru no Umi, Yuyake Koyake, Akatombo, Furusato, Kojo no Tsuki, Takeda Lullaby, Edo Lullaby, Zuizuizukkorobashi; Modern & Cinematic: Sukiyaki, Merry Christmas Mr. Lawrence, Summer, Always With Me, Path of the Wind, Stay With Me, Plastic Love, First Love) by default. Hover the 🎵 button to view the current song popover, or click to toggle/shuffle.</span>
                </span>
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

    @livewireScripts
</body>
</html>
