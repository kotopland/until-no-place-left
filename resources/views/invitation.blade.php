<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reach Nagoya with Jesus • Until There is No Place Left</title>
    
    <link rel="icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}">
    @fonts

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
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[550px] bg-gradient-to-b from-indigo-900/25 via-emerald-900/15 to-transparent blur-3xl pointer-events-none -z-10"></div>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-50 bg-slate-950/85 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-2 sm:gap-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 sm:gap-3 group min-w-0 shrink-0">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                    alt="Nagoya Oikos: The Four Fields Logo" 
                    class="w-9 h-9 sm:w-11 sm:h-11 object-contain rounded-xl sm:rounded-2xl shadow-xl shadow-indigo-500/25 border border-indigo-500/40 group-hover:scale-105 transition duration-300 shrink-0"
                >
                <div class="min-w-0">
                    <span class="text-xs font-black text-white tracking-wider uppercase flex items-center gap-1.5 whitespace-nowrap">
                        <span>Nagoya Oikos</span>
                        <span class="hidden sm:inline-block px-1.5 py-0.5 rounded text-[10px] bg-indigo-950 text-indigo-400 border border-indigo-800 font-bold">Romans 15:23</span>
                    </span>
                    <span class="hidden sm:block text-[11px] text-slate-400 font-medium truncate">Reach Nagoya with Jesus • No Place Left</span>
                </div>
            </a>

            <!-- Center Navigation Links -->
            <nav class="hidden md:flex items-center gap-3 lg:gap-5 xl:gap-7 text-xs font-semibold text-slate-300">
                <a href="#vision" class="hover:text-amber-400 transition">The Vision</a>
                <a href="#four-fields" class="hover:text-emerald-400 transition">The Movement Cycle</a>
                <a href="#wards" class="hover:text-purple-400 transition">16 Wards</a>
                <a href="#how-to-join" class="hover:text-indigo-400 transition">How to Step In</a>
                <a href="{{ route('welcome') }}" class="hover:text-cyan-400 transition flex items-center gap-1">
                    <span>📖</span>
                    <span>Game Overview &amp; Rules</span>
                </a>
            </nav>

            <!-- Right Controls: Theme, Music & Action Buttons -->
            <div class="flex items-center gap-2 lg:gap-2.5">
                <!-- Theme Mode Toggle Button (Light/Dark) -->
                <button 
                    type="button" 
                    onclick="window.__toggleTheme ? window.__toggleTheme() : null"
                    class="flex items-center gap-1.5 p-2 xl:px-3 xl:py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 border-slate-300 dark:border-slate-700/80 text-amber-600 dark:text-amber-300 hover:text-amber-700 dark:hover:text-white"
                    title="Switch Light/Dark Mode"
                >
                    <span data-theme-icon>☀️</span>
                    <span data-theme-label class="hidden xl:inline text-[11px]">Light</span>
                </button>

                <!-- Ambient Music Toggle Button with Hover Popover -->
                <div class="relative group">
                    <button 
                        type="button" 
                        @click="$store.audio?.toggle()" 
                        class="flex items-center gap-1.5 p-2 xl:px-3 xl:py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                        :class="$store.audio?.isPlaying ? 'bg-indigo-950/90 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500/40' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                        title="Japanese Background Music"
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
                        <span class="hidden xl:inline text-[11px]" x-text="$store.audio?.isPlaying ? 'Music ON' : 'Music OFF'"></span>
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
                    <!-- Authenticated Player Pill & Dashboard Link -->
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="hidden sm:flex items-center gap-2 text-xs font-bold text-slate-300 hover:text-white px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 truncate max-w-[130px]"
                        title="{{ auth()->user()->name }} ({{ auth()->user()->nationality ?? 'Japan' }})"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                        <span class="truncate">{{ auth()->user()->name }}</span>
                    </a>

                    <a 
                        href="{{ route('dashboard') }}" 
                        class="px-3.5 xl:px-5 py-2 xl:py-2.5 rounded-2xl text-xs font-black bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-xl shadow-indigo-600/25 hover:shadow-indigo-600/40 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2 cursor-pointer shrink-0"
                    >
                        <span>⚡</span>
                        <span>Action HQ Dashboard</span>
                    </a>
                @else
                    <!-- Guest Options: Simulation, Sign In, Join -->
                    <a 
                        href="{{ route('simulate') }}" 
                        class="hidden sm:flex items-center gap-1.5 text-xs font-bold text-amber-300 hover:text-amber-200 px-3 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 transition"
                        title="Explore the 15-Year Movement Simulation (Shared Daily SQLite DB — No Account Required)"
                    >
                        <span>⏱️</span>
                        <span>Live Simulation</span>
                    </a>

                    <a 
                        href="{{ route('login') }}" 
                        class="text-[11px] sm:text-xs font-bold text-slate-300 hover:text-white px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 border border-slate-800 transition whitespace-nowrap"
                    >
                        Sign In
                    </a>

                    <a 
                        href="{{ route('register') }}" 
                        class="px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl sm:rounded-2xl text-[11px] sm:text-xs font-bold bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-xl shadow-indigo-600/25 hover:shadow-indigo-600/40 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                    >
                        <span>⚡</span>
                        <span>Join Mission</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-24">

        <!-- ========================================== -->
        <!-- HERO SECTION: THE INVITATION TO REACH NAGOYA -->
        <!-- ========================================== -->
        <section class="relative flex flex-col lg:flex-row items-center gap-12 pt-4 lg:pt-8">
            <div class="flex-1 space-y-6 text-center lg:text-left">
                <!-- Location & Mission Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900/90 border border-slate-800 text-xs text-indigo-300 font-semibold shadow-inner">
                    <span>🌸</span>
                    <span>Nagoya, Aichi Prefecture • 2.3 Million Souls</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                </div>

                <!-- Main Call to Action Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.12]">
                    Reach Nagoya<br/>with Jesus 
                   
                    <span class="bg-gradient-to-r from-amber-400 via-emerald-400 to-indigo-400 bg-clip-text text-transparent">
                         <br/>Until There is <br/>No Place Left
                    </span>
                </h1>

                <!-- Vision Subtitle -->
                <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                    The Apostle Paul wrote in <strong class="text-white">Romans 15:23</strong>: <em>&ldquo;Having no place left in these parts&hellip;&rdquo;</em>
                    Step into the heart of Japan as a relational disciple-maker. In a metropolitan city of 2.3 million people where less than 0.5% know Jesus, God is moving through authentic friendships, deep listening, and multiplying simple churches across all 16 wards.
                </p>

                <!-- Primary Action Buttons -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-2">
                    @auth
                        <a 
                            href="{{ route('dashboard') }}" 
                            class="px-6 py-3.5 rounded-2xl text-sm font-black bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-xl shadow-indigo-600/30 hover:scale-[1.03] active:scale-[0.98] transition flex items-center gap-2.5 cursor-pointer"
                        >
                            <span>⚡</span>
                            <span>Enter Action HQ Dashboard</span>
                        </a>
                    @else
                        <a 
                            href="{{ route('register') }}" 
                            class="px-6 py-3.5 rounded-2xl text-sm font-black bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-xl shadow-indigo-600/30 hover:scale-[1.03] active:scale-[0.98] transition flex items-center gap-2.5 cursor-pointer"
                        >
                            <span>⚡</span>
                            <span>Accept the Call &bull; Join the Mission</span>
                        </a>
                    @endauth

                    <a 
                        href="{{ route('simulate') }}" 
                        class="px-5 py-3.5 rounded-2xl text-sm font-bold bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-300 hover:text-amber-200 transition flex items-center gap-2"
                        title="Explore the 15-Year Movement Simulation (Shared Daily SQLite Database — No Sign Up Required)"
                    >
                        <span>⏱️</span>
                        <span>Explore 15-Year Simulation (Guest)</span>
                    </a>

                    <a 
                        href="{{ route('welcome') }}" 
                        class="px-5 py-3.5 rounded-2xl text-sm font-bold bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white transition flex items-center gap-2"
                    >
                        <span>📖</span>
                        <span>Game Overview &amp; Rules</span>
                    </a>
                </div>

                <!-- Live Quick Stats Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-6 border-t border-slate-800/80 max-w-xl mx-auto lg:mx-0 text-left">
                    <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                        <span class="text-xl sm:text-2xl font-black text-indigo-400">16</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Nagoya Wards</span>
                        <span class="block text-[10px] text-indigo-300/80 mt-0.5">Empty fields to reach</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                        <span class="text-xl sm:text-2xl font-black text-emerald-400">&lt; 0.5%</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Believers in Japan</span>
                        <span class="block text-[10px] text-emerald-300/80 mt-0.5">Fertile harvest frontier</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                        <span class="text-xl sm:text-2xl font-black text-amber-400">G1–G4+</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Church Streams</span>
                        <span class="block text-[10px] text-amber-300/80 mt-0.5">Indigenous multiplication</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                        <span class="text-xl sm:text-2xl font-black text-purple-400">2.3M</span>
                        <span class="block text-[11px] text-slate-400 font-medium">Precious Souls</span>
                        <span class="block text-[10px] text-purple-300/80 mt-0.5">Awaiting the Good News</span>
                    </div>
                </div>
            </div>

            <!-- Artwork Feature Card -->
            <div class="flex-1 w-full max-w-xl relative group">
                <div class="absolute -inset-1 rounded-3xl bg-gradient-to-tr from-amber-500/30 via-emerald-500/30 to-indigo-600/40 blur-xl opacity-80 group-hover:opacity-100 transition duration-700"></div>
                <div class="relative rounded-3xl overflow-hidden border border-slate-800 shadow-2xl bg-slate-900/90 backdrop-blur-md p-3.5 space-y-3">
                    <img 
                        src="{{ asset('images/until-no-place-left-logo.jpeg') }}" 
                        alt="Nagoya Oikos: The Four Fields Official Movement Artwork" 
                        class="w-full h-auto rounded-2xl object-cover shadow-inner"
                    >
                    <div class="p-2 flex items-center justify-between text-xs text-slate-400">
                        <span class="flex items-center gap-1.5 font-bold text-white">
                            <span>🗾</span>
                            <span>Nagoya Oikos Movement</span>
                        </span>
                        <span class="text-indigo-400 font-semibold">Romans 15:23 &bull; Acts 2:42-47</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- SECTION 1: THE VISION & SPIRITUAL REALITY -->
        <!-- ========================================== -->
        <section id="vision" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🔥</span>
                    <span>The Heart of the Mission</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Why Nagoya? The Apostolic Mandate</h2>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Nagoya is known across Japan as the engine of Japanese manufacturing, home to deep traditions, ancient Shinto shrines, and millions of hardworking individuals carrying the weight of corporate fatigue and loneliness.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Vision Card 1 -->
                <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4 hover:border-rose-500/50 transition">
                    <div class="w-12 h-12 rounded-2xl bg-rose-950/60 border border-rose-800/60 flex items-center justify-center text-2xl text-rose-400">
                        ⛩️
                    </div>
                    <h3 class="text-lg font-bold text-white">Beyond the "Missionary Graveyard"</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        For decades, Japan has been called tough soil. Cold pulpit preaching and institutional steeples often face polite Japanese hesitation. But when believers listen with genuine humility, pray for physical healing, and respect family heritage, the Gospel pierces directly into the heart.
                    </p>
                    <div class="pt-2 text-[11px] text-rose-300 font-semibold border-t border-slate-800">
                        💡 Key Truth: Compassionate listening breaks barriers that loud sermons cannot.
                    </div>
                </div>

                <!-- Vision Card 2 -->
                <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4 hover:border-indigo-500/50 transition">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-950/60 border border-indigo-800/60 flex items-center justify-center text-2xl text-indigo-400">
                        🌐
                    </div>
                    <h3 class="text-lg font-bold text-white">The Power of the Oikos Web</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        In the New Testament, <strong class="text-indigo-400">Oikos</strong> describes a person&rsquo;s natural relational universe: family, coworkers, close school friends, and neighbors. In Japan, relational trust precedes faith. Once a Japanese &ldquo;Person of Peace&rdquo; receives Christ, their whole household can be swept into God&rsquo;s kingdom.
                    </p>
                    <div class="pt-2 text-[11px] text-indigo-300 font-semibold border-t border-slate-800">
                        💡 Key Truth: Movements multiply through existing relational trust, not strangers on street corners.
                    </div>
                </div>

                <!-- Vision Card 3 -->
                <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4 hover:border-emerald-500/50 transition">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 flex items-center justify-center text-2xl text-emerald-400">
                        🌾
                    </div>
                    <h3 class="text-lg font-bold text-white">No Place Left (Romans 15:23)</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Paul dared to state that he had <em>no place left</em> in entire provinces because he planted self-reproducing disciples who planted simple churches that multiplied. Our goal is not a single large mega-church, but hundreds of indigenous, reproducing Acts 2 house churches filling all 16 wards.
                    </p>
                    <div class="pt-2 text-[11px] text-emerald-300 font-semibold border-t border-slate-800">
                        💡 Victory: Multi-generational (G4+) church streams active in every single ward!
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- SECTION 2: THE FOUR FIELDS MOVEMENT CYCLE -->
        <!-- ========================================== -->
        <section id="four-fields" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🌱</span>
                    <span>Kingdom Growth Cycle</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">The Four Fields Movement Framework</h2>
                <p class="text-sm text-slate-400">
                    Drawn directly from Jesus&rsquo; agricultural parable in Mark 4:26-29, the Four Fields framework guides every step of your ministry from initial prayer walk to 4th generation movements.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Field 1 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 hover:border-indigo-500/50 transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">🗺️</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-950 text-indigo-400 border border-indigo-800">Field 1</span>
                        </div>
                        <h3 class="text-base font-bold text-white">Entry &bull; Empty Fields</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Intercessory prayer walking the 16 wards, connecting through hospitality and casual talk, and discovering receptive <em>Persons of Peace</em> (Luke 10:6).
                        </p>
                    </div>
                    <div class="pt-2 text-[10px] text-indigo-300 font-semibold border-t border-slate-800/80">
                        Actions: Prayer Walk, Casual Talk, Healing Prayer
                    </div>
                </div>

                <!-- Field 2 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 hover:border-amber-500/50 transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">🌱</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-950 text-amber-400 border border-amber-800">Field 2</span>
                        </div>
                        <h3 class="text-base font-bold text-white">Gospel &bull; Sown Fields</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Planting the seeds of truth using concise tools: the 15-Second Testimony, the 3 Circles Gospel, and leading seekers to salvation and baptism.
                        </p>
                    </div>
                    <div class="pt-2 text-[10px] text-amber-300 font-semibold border-t border-slate-800/80">
                        Actions: 15s Testimony, 3 Circles, Baptism
                    </div>
                </div>

                <!-- Field 3 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 hover:border-emerald-500/50 transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">🌿</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800">Field 3</span>
                        </div>
                        <h3 class="text-base font-bold text-white">Discipleship &bull; Growing</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Rooting believers in Christ with the 411 foundational training, Discovery Bible Studies (DBS), and obeying Jesus&rsquo; 7 basic commands.
                        </p>
                    </div>
                    <div class="pt-2 text-[10px] text-emerald-300 font-semibold border-t border-slate-800/80">
                        Actions: 411 Training, DBS Groups, Commands of Christ
                    </div>
                </div>

                <!-- Field 4 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 hover:border-cyan-500/50 transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">🌾</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-400 border border-cyan-800">Field 4</span>
                        </div>
                        <h3 class="text-base font-bold text-white">Church &bull; Harvest</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Forming vibrant Acts 2 Church Circles in living rooms, parks, and cafes: fellowship, breaking bread, worship, giving, and local leadership.
                        </p>
                    </div>
                    <div class="pt-2 text-[10px] text-cyan-300 font-semibold border-t border-slate-800/80">
                        Actions: Form Acts 2 Circle, Appoint Elders
                    </div>
                </div>

                <!-- Field 5 -->
                <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 hover:border-purple-500/50 transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">👑</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-950 text-purple-400 border border-purple-800">Field 5</span>
                        </div>
                        <h3 class="text-base font-bold text-white">Leaders &bull; Multiplication</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Developing local Timothys (L2) and peer catalysts (L3) through Iron-on-Iron sharpening, unleashing cross-ward Swarms (L4/L5) to 4th generation streams.
                        </p>
                    </div>
                    <div class="pt-2 text-[10px] text-purple-300 font-semibold border-t border-slate-800/80">
                        Actions: Mentor Timothy, Iron-on-Iron, Swarm
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- SECTION 3: THE 16 WARDS OF NAGOYA -->
        <!-- ========================================== -->
        <section id="wards" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-purple-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🗾</span>
                    <span>The Mission Geography</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Nagoya&rsquo;s 16 Diverse Wards</h2>
                <p class="text-sm text-slate-400">
                    Each ward has its own distinct demographic pulse, spiritual openness, and unique needs. Which district will you adopt as your Home Base?
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-center">
                @php
                    $allWards = [
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

                @foreach($allWards as $w)
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

            <!-- Home Base Callout -->
            <div class="p-6 rounded-3xl bg-gradient-to-r from-indigo-950/60 via-slate-900 to-emerald-950/60 border border-indigo-800/60 flex flex-col md:flex-row items-center justify-between gap-5 shadow-xl">
                <div class="flex items-center gap-4">
                    <span class="text-3xl sm:text-4xl">🏡</span>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>Incarnational Home Base</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-900/80 text-emerald-300 border border-emerald-700">Romans 15:20</span>
                        </h4>
                        <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                            When you join, you will choose one of these 16 wards as your physical residence. Living in your <strong>Home Ward</strong> eliminates transit delays (<strong>0 AP transit cost</strong>) and provides a <strong>+25% follow-up trust bonus</strong>. Pioneer in empty frontier wards where no other co-laborers reside to blanket the city!
                        </p>
                    </div>
                </div>

                @auth
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="shrink-0 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-1.5"
                    >
                        <span>🧭</span>
                        <span>Manage Home Base in HQ</span>
                    </a>
                @else
                    <a 
                        href="{{ route('register') }}" 
                        class="shrink-0 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-1.5"
                    >
                        <span>⚡</span>
                        <span>Claim Your Starting Ward</span>
                    </a>
                @endauth
            </div>
        </section>

        <!-- ========================================== -->
        <!-- SECTION 4: TWO WAYS TO EXPERIENCE NAGOYA -->
        <!-- ========================================== -->
        <section id="how-to-join" class="space-y-8 scroll-mt-24">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest flex items-center justify-center gap-1.5">
                    <span>🎮</span>
                    <span>Experience The Movement</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Two Ways to Step In</h2>
                <p class="text-sm text-slate-400">
                    Whether you want to play a living missionary persona with weekly action stewardship or observe a 15-year simulation projection, Nagoya is waiting.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Option 1: Live Missionary Player Journey -->
                <div class="p-8 rounded-3xl bg-gradient-to-br from-indigo-950/40 via-slate-900 to-slate-900 border border-indigo-500/40 shadow-2xl space-y-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-800 text-xs font-bold">
                            <span>⚡</span>
                            <span>Option 1: Interactive Missionary Journey</span>
                        </div>

                        <h3 class="text-2xl font-black text-white">Create Your Player &amp; Join the Team</h3>

                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Step into the boots of a church planting catalyst in Nagoya. Select your missionary avatar from over <strong>95 sending nations</strong>, complete the 14-question candidate evaluation across <strong>7 missionary dimensions</strong>, receive your Movement Host orientation, and steward your weekly <strong>20 Action Points (AP)</strong>.
                        </p>

                        <div class="space-y-2 text-xs text-slate-400">
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Cooperate with co-laborers on live city leaderboards and cooperative prayer chat.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Balance high-impact outreach with Sabbath rest and spiritual retreats to avoid burnout.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Passwordless magic-link authentication — no passwords to remember.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        @auth
                            <a 
                                href="{{ route('dashboard') }}" 
                                class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-black text-sm flex items-center justify-center gap-2 shadow-xl shadow-indigo-600/30 transition hover:scale-[1.01]"
                            >
                                <span>⚡</span>
                                <span>Open Your Action HQ Dashboard</span>
                            </a>
                        @else
                            <a 
                                href="{{ route('register') }}" 
                                class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-black text-sm flex items-center justify-center gap-2 shadow-xl shadow-indigo-600/30 transition hover:scale-[1.01]"
                            >
                                <span>⚡</span>
                                <span>Create Player Profile &amp; Join Mission</span>
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Option 2: 15-Year Longitudinal Simulation -->
                <div class="p-8 rounded-3xl bg-gradient-to-br from-amber-950/30 via-slate-900 to-slate-900 border border-amber-500/40 shadow-2xl space-y-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-950 text-amber-300 border border-amber-800 text-xs font-bold">
                            <span>⏱️</span>
                            <span>Option 2: 15-Year Simulation Sandbox</span>
                        </div>

                        <h3 class="text-2xl font-black text-white">Explore the 15-Year Movement Horizons</h3>

                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Curious how a disciple-making movement unfolds over a missionary career? Explore our longitudinal simulation sandbox. Instantly travel through <strong>8 milestone horizons</strong>: Day 1 Arrival, 3 Months, 6 Months, 1 Year, 2 Years, 5 Years, 10 Years, and 15 Years of generational multiplication.
                        </p>

                        <div class="space-y-2 text-xs text-slate-400">
                            <div class="flex items-center gap-2">
                                <span class="text-amber-400 font-bold">✓</span>
                                <span>100% Free guest mode — no account or registration required.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-amber-400 font-bold">✓</span>
                                <span>Inspect interactive GIS ward maps, church circles, and generational church trees.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-amber-400 font-bold">✓</span>
                                <span>Compare all 7 stages side-by-side to witness how multiplication overtakes addition.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <a 
                            href="{{ route('simulate') }}" 
                            class="w-full py-3.5 px-6 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-xl shadow-amber-500/20 transition hover:scale-[1.01]"
                        >
                            <span>⏱️</span>
                            <span>Launch 15-Year Live Simulation</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- SECTION 5: COMPLETE GAME OVERVIEW & RULES BANNER -->
        <!-- ========================================== -->
        <section class="p-8 sm:p-10 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/60 to-slate-900 border border-indigo-500/30 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center md:text-left">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest flex items-center justify-center md:justify-start gap-1.5">
                    <span>📖</span>
                    <span>Comprehensive Player Guide</span>
                </span>
                <h3 class="text-2xl font-black text-white">Looking for the Complete Game Overview &amp; Rules?</h3>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Read the complete rulebook covering weekly Action Points (AP), Home Base transit friction, the 5-Level Leadership Toolbox, Spiritual Rhythms &amp; Retreats, Candidate Screening, and the 70% Foundation Strategy.
                </p>
            </div>

            <a 
                href="{{ route('welcome') }}" 
                class="shrink-0 px-6 py-3.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs sm:text-sm border border-slate-700 hover:border-cyan-500/50 shadow-xl transition flex items-center gap-2"
            >
                <span>📜</span>
                <span>Open Game Overview &amp; Rules &rarr;</span>
            </a>
        </section>

        <!-- ========================================== -->
        <!-- SECTION 6: SCRIPTURE & COMMENCEMENT CALL -->
        <!-- ========================================== -->
        <section class="text-center max-w-3xl mx-auto space-y-6 pt-6">
            <div class="inline-block p-6 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-xl space-y-3">
                <blockquote class="text-sm sm:text-base italic text-indigo-200 leading-relaxed font-serif">
                    &ldquo;Then He said to His disciples, &lsquo;The harvest is plentiful, but the workers are few. Therefore pray earnestly to the Lord of the harvest to send out workers into His harvest field.&rsquo;&rdquo;
                </blockquote>
                <div class="text-xs font-bold text-slate-400">
                    &bull; Matthew 9:37-38 &bull;
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-3xl font-black text-white">Will You Say &ldquo;Here Am I, Send Me&rdquo;?</h3>
                <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto">
                    Nagoya is ready. The 16 wards are open. Step in and plant indigenous churches until there is No Place Left.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                @auth
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="px-8 py-4 rounded-2xl text-sm font-black bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-2xl shadow-indigo-600/40 hover:scale-[1.03] active:scale-[0.98] transition flex items-center gap-2.5"
                    >
                        <span>⚡</span>
                        <span>Enter Action HQ Now</span>
                    </a>
                @else
                    <a 
                        href="{{ route('register') }}" 
                        class="px-8 py-4 rounded-2xl text-sm font-black bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 text-white shadow-2xl shadow-indigo-600/40 hover:scale-[1.03] active:scale-[0.98] transition flex items-center gap-2.5"
                    >
                        <span>⚡</span>
                        <span>Join the Mission &bull; Create Player</span>
                    </a>
                @endauth

                <a 
                    href="{{ route('simulate') }}" 
                    class="px-6 py-4 rounded-2xl text-sm font-bold bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-300 hover:text-amber-200 transition flex items-center gap-2"
                >
                    <span>⏱️</span>
                    <span>Explore Guest Simulation</span>
                </a>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="mt-20 border-t border-slate-800 bg-slate-950 py-12 text-slate-400 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                    alt="Nagoya Oikos Logo" 
                    class="w-8 h-8 rounded-xl object-contain border border-slate-800"
                >
                <div>
                    <span class="font-bold text-white block">Nagoya Oikos: The Four Fields</span>
                    <span class="text-[10px] text-slate-400">Until There is No Place Left in Central Japan (Romans 15:23)</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-4 text-[11px] font-semibold text-slate-400">
                <a href="{{ url('/') }}" class="hover:text-white transition">Root Invitation</a>
                <span class="text-slate-700">&bull;</span>
                <a href="{{ route('welcome') }}" class="hover:text-cyan-400 transition">Game Overview &amp; Rules</a>
                <span class="text-slate-700">&bull;</span>
                <a href="{{ route('simulate') }}" class="hover:text-amber-400 transition">15-Year Simulation</a>
                <span class="text-slate-700">&bull;</span>
                <a href="{{ route('login') }}" class="hover:text-indigo-400 transition">Sign In / Join</a>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

