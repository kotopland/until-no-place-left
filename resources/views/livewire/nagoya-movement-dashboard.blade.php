<div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white transition-colors duration-200">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-md transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand & Game Title with Official Logo -->
            <a href="{{ route('welcome') }}" class="flex items-center gap-2.5 sm:gap-3 group cursor-pointer shrink-0" title="View Story, Lore & Game Rules">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                    alt="Nagoya Oikos: The Four Fields Logo" 
                    class="w-10 h-10 sm:w-11 sm:h-11 object-contain rounded-2xl shadow-lg shadow-indigo-500/20 border border-indigo-500/40 group-hover:scale-105 group-hover:border-indigo-400 transition duration-200 shrink-0"
                >
                <div class="flex flex-col justify-center whitespace-nowrap min-w-0">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight leading-none flex items-center gap-1.5 whitespace-nowrap">
                        <span>Nagoya Oikos</span>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-800 uppercase tracking-wider">Four Fields</span>
                    </h1>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition whitespace-nowrap leading-none mt-1 hidden sm:block">
                        <span>Multiplayer Movement Simulation</span>
                        <span class="text-slate-400 dark:text-slate-600 mx-1 hidden xl:inline">•</span>
                        <span class="hidden xl:inline text-slate-500">No Place Left</span>
                    </p>
                </div>
            </a>

            <!-- Main Tab Navigation -->
            <nav class="hidden md:flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs font-semibold">
                <button 
                    type="button" 
                    wire:click="setTab('planner')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>⚡</span>
                    <span>Action HQ</span>
                </button>
                <button 
                    type="button" 
                    wire:click="setTab('churches')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>⛪</span>
                    <span>Church Circles</span>
                </button>
                <button 
                    type="button" 
                    wire:click="setTab('leaderboard')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>🏆</span>
                    <span>Leaderboard & Co-op</span>
                </button>
                <button 
                    type="button" 
                    wire:click="setTab('map')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'map' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>🗺️</span>
                    <span>Nagoya Map</span>
                </button>
                <a 
                    href="{{ route('welcome') }}"
                    class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-300 hover:bg-slate-200 dark:hover:bg-slate-900 border border-transparent hover:border-slate-300 dark:hover:border-slate-800"
                    title="View Full Story, Biblical Framework & Game Rules"
                >
                    <span>📖</span>
                    <span>Story & Rules</span>
                </a>
            </nav>

            <!-- User Status, Music Switch & Notification Bell -->
            <div class="flex items-center gap-2.5">
                <!-- Day Progress & Digital Clock -->
                <div 
                    wire:key="dash-clock-{{ $user?->current_game_day }}-{{ $user?->days_processed_this_month }}-{{ $user?->days_played_today }}"
                    x-data="digitalClock({{ max(0, $user?->secondsUntilNextDailyTick ?? 600) }}, '{{ $user?->next_daily_tick_at?->toIso8601String() }}', {{ (!($user?->hasReachedDailyPlayLimit ?? false) && ($user?->days_processed_this_month ?? 0) < 30) ? 'true' : 'false' }})"
                    x-init="init()"
                    class="hidden lg:flex items-center gap-2 px-2.5 py-1 rounded-xl bg-slate-950/80 border border-slate-800"
                >
                    <!-- Day & Cap badge -->
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-white flex items-center justify-end gap-1">
                            <span>Day {{ $user?->current_game_day ?? 1 }}/30</span>
                            <span class="text-[9px] {{ ($user?->days_played_today ?? 0) >= 30 ? 'text-amber-400 font-black' : 'text-slate-400' }}">({{ $user?->days_played_today ?? 0 }}/30d)</span>
                        </div>
                        <div class="text-[9px] text-slate-500 uppercase tracking-tighter">
                            {{ ($user?->hasReachedDailyPlayLimit ?? false) ? 'Night Rest' : (($user?->days_processed_this_month ?? 0) >= 30 ? 'Month Done' : 'Next Tick') }}
                        </div>
                    </div>

                    <!-- LED Display -->
                    <div class="bg-black/95 border border-slate-800/90 rounded-lg px-2 py-0.5 shadow-[inset_0_1px_4px_rgba(0,0,0,0.8)]">
                        @if($user?->hasReachedDailyPlayLimit ?? false)
                            <span class="font-mono text-xs font-black text-amber-400 drop-shadow-[0_0_5px_rgba(245,158,11,0.5)]">30:30</span>
                        @elseif(($user?->days_processed_this_month ?? 0) >= 30)
                            <span class="font-mono text-xs font-black text-indigo-400">MONTH</span>
                        @else
                            <span class="font-mono text-xs font-black text-emerald-400 drop-shadow-[0_0_6px_rgba(52,211,153,0.6)] flex items-center">
                                <span class="tabular-nums" x-text="minutes">09</span><span class="animate-pulse">:</span><span class="tabular-nums" x-text="seconds">59</span>
                            </span>
                        @endif
                    </div>

                    <!-- Turn Advance Button -->
                    @if(($user?->days_processed_this_month ?? 0) >= 30)
                        <button 
                            type="button" 
                            wire:click="advanceMonth"
                            wire:loading.attr="disabled"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-sm hover:scale-[1.02]"
                            title="Advance to Month {{ ($user?->current_game_month ?? 1) + 1 }}"
                        >
                            <span wire:loading.remove wire:target="advanceMonth">🚀</span>
                            <span wire:loading wire:target="advanceMonth" class="w-2.5 h-2.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span wire:loading.remove wire:target="advanceMonth" class="hidden xl:inline">Next Month</span>
                        </button>
                    @else
                        <button 
                            type="button" 
                            wire:click="advanceDay(true)"
                            wire:loading.attr="disabled"
                            @disabled($user?->hasReachedDailyPlayLimit ?? false)
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer {{ ($user?->hasReachedDailyPlayLimit ?? false) ? 'bg-slate-900 text-slate-600 border border-slate-800/80 cursor-not-allowed' : 'bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 text-white shadow-sm hover:scale-[1.02]' }}"
                            title="{{ ($user?->hasReachedDailyPlayLimit ?? false) ? 'Daily limit of 30 days reached. Next month starts tonight!' : 'Manually advance to next day' }}"
                        >
                            <span wire:loading.remove wire:target="advanceDay">⏭️</span>
                            <span wire:loading wire:target="advanceDay" class="w-2.5 h-2.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span wire:loading.remove wire:target="advanceDay" class="hidden xl:inline">Next Day</span>
                        </button>
                    @endif
                </div>

                <!-- Theme Mode Toggle Button (Light/Dark) -->
                <button 
                    wire:ignore
                    type="button" 
                    onclick="window.__toggleTheme ? window.__toggleTheme() : null"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-amber-600 dark:text-amber-300 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-amber-700 dark:hover:text-white shadow-sm"
                    title="Switch Light/Dark Mode"
                >
                    <span data-theme-icon>☀️</span>
                    <span data-theme-label class="hidden xl:inline text-[11px]">Light</span>
                </button>

                <!-- Ambient Music Toggle Button -->
                <button 
                    type="button" 
                    @click="$store.audio?.toggle()" 
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                    :class="$store.audio?.isPlaying ? 'bg-indigo-950/90 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500/40' : 'bg-slate-800 border-slate-700 text-slate-400 hover:text-white'"
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
                    <span class="hidden lg:inline text-[11px]" x-text="$store.audio?.isPlaying ? 'Music ON' : 'Music OFF'"></span>
                </button>

                <!-- Notification Bell -->
                <button 
                    type="button"
                    wire:click="openReportModal"
                    class="relative p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 transition cursor-pointer text-slate-300 hover:text-white"
                    title="View Latest Monthly Movement Report"
                >
                    <span class="text-base">🔔</span>
                    @if($unreadReportsCount > 0)
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-[10px] font-black text-white flex items-center justify-center animate-bounce">
                            {{ $unreadReportsCount }}
                        </span>
                    @endif
                </button>

                <!-- Home Ward Pill -->
                @if($user?->homeWard)
                    <button 
                        type="button" 
                        wire:click="openOnboardingModal"
                        class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-white shadow-sm"
                        title="Your Embedded Residence: {{ $user->homeWard->name }} (Click to view or change)"
                    >
                        <span>🏡</span>
                        <span class="text-[11px] font-bold">{{ $user->homeWard->name }}</span>
                    </button>
                @endif

                <!-- Player Profile Pill -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-300 dark:border-slate-800">
                    <button 
                        type="button" 
                        wire:click="openEditNameModal"
                        class="flex items-center gap-2 group cursor-pointer hover:opacity-90 text-left"
                        title="Click to customize your missionary handle / player name"
                    >
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-sm group-hover:ring-2 group-hover:ring-indigo-400 transition">
                            {{ $user?->initials() ?? 'MC' }}
                        </div>
                        <div class="hidden sm:block text-left text-xs">
                            <div class="font-bold text-white leading-tight flex items-center gap-1">
                                <span>{{ $user?->name ?? 'Player' }}</span>
                                <span class="text-[10px] text-slate-500 group-hover:text-indigo-400">✏️</span>
                            </div>
                            <div class="text-[10px] text-emerald-400 font-semibold">{{ number_format($user?->compositeScore ?? 0) }} pts</div>
                        </div>
                    </button>

                    <!-- Logout Button -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button 
                            type="submit" 
                            class="p-1.5 rounded-xl bg-slate-800/80 hover:bg-rose-950/80 hover:text-rose-300 hover:border-rose-800/80 border border-slate-700/60 text-slate-400 text-xs transition cursor-pointer" 
                            title="Sign Out of Mission"
                        >
                            <span class="text-xs">🚪</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile Tab Navigation Bar -->
        <div class="flex md:hidden items-center justify-around px-2 py-2 border-t border-slate-200 dark:border-slate-800/80 text-[11px] font-semibold bg-white dark:bg-slate-950 transition-colors duration-200">
            <button wire:click="setTab('planner')" class="px-2 py-1 rounded-lg {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400' }}">⚡ HQ</button>
            <button wire:click="setTab('churches')" class="px-2 py-1 rounded-lg {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400' }}">⛪ Circles</button>
            <button wire:click="setTab('leaderboard')" class="px-2 py-1 rounded-lg {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400' }}">🏆 Ladder</button>
            <button wire:click="setTab('map')" class="px-2 py-1 rounded-lg {{ $currentTab === 'map' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-400' }}">🗺️ Map</button>
            <button type="button" @click="$store.audio?.toggle()" class="px-2 py-1 rounded-lg" :class="$store.audio?.isPlaying ? 'text-indigo-600 dark:text-indigo-300 font-bold' : 'text-slate-500 dark:text-slate-400'"><span x-text="$store.audio?.isPlaying ? '🎵 On' : '🔇 Off'"></span></button>
            <button wire:ignore type="button" onclick="window.__toggleTheme ? window.__toggleTheme() : null" class="px-2 py-1 rounded-lg text-amber-500 dark:text-amber-400 font-bold" title="Toggle Light / Dark Mode"><span data-theme-icon>☀️</span></button>
            <button wire:click="openEditNameModal" class="px-2 py-1 rounded-lg text-indigo-600 dark:text-indigo-400">👤 Name</button>
            <a href="{{ route('welcome') }}" class="px-2 py-1 rounded-lg text-amber-600 dark:text-amber-400 font-bold">📖 Story</a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full">
        <!-- Toast Notification Listener -->
        <div 
            x-data="{ show: false, message: '', type: 'info', timer: null }"
            x-on:notify.window="
                let raw = Array.isArray($event.detail) ? ($event.detail[0] || {}) : ($event.detail || {});
                if (typeof raw === 'string') { raw = { message: raw, type: 'info' }; }
                message = raw.message || '';
                type = raw.type || 'info';
                show = true;
                if (timer) clearTimeout(timer);
                timer = setTimeout(() => { show = false; }, 4500);
            "
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            class="fixed bottom-5 right-5 z-50 p-4 rounded-2xl shadow-2xl border flex items-center gap-3 text-xs font-bold max-w-md pointer-events-auto backdrop-blur-md"
            :class="{
                'bg-emerald-950/95 text-emerald-200 border-emerald-600 shadow-emerald-950/50': type === 'success',
                'bg-rose-950/95 text-rose-200 border-rose-600 shadow-rose-950/50': type === 'error',
                'bg-amber-950/95 text-amber-200 border-amber-600 shadow-amber-950/50': type === 'warning',
                'bg-indigo-950/95 text-indigo-200 border-indigo-600 shadow-indigo-950/50': type === 'info'
            }"
            style="display: none;"
        >
            <span class="text-base shrink-0" x-text="type === 'success' ? '✅' : (type === 'error' ? '❌' : (type === 'warning' ? '⚠️' : 'ℹ️'))"></span>
            <span class="leading-snug" x-text="message"></span>
            <button type="button" @click="show = false" class="ml-2 text-slate-400 hover:text-white text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        @if($currentTab === 'planner')
            <!-- Action Queue Planner -->
            <livewire:action-queue-planner />

        @elseif($currentTab === 'churches')
            <!-- Tab 2: Acts 2 Church Circles Viewer & Generational Lineage -->
            <div class="space-y-6">
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>⛪</span>
                            <span>Field 4: The Harvested Field</span>
                        </span>
                        <h2 class="text-xl font-black text-white mt-0.5">Acts 2 Church Circles</h2>
                        <p class="text-xs text-slate-400 mt-1">
                            A healthy church practices all 11 core elements: Repentance/Belief, Baptism, Word, Communion, Prayer, Fellowship, Mercy/Giving, Worship, Local Leaders, and Discipleship.
                        </p>
                    </div>

                    <!-- Church Selector Tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto max-w-full pb-1">
                        @foreach($churches as $ch)
                            <button 
                                type="button"
                                wire:click="selectChurch({{ $ch->id }})"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition shrink-0 cursor-pointer flex items-center gap-1.5 {{ $activeChurchId === $ch->id ? 'bg-indigo-600 text-white shadow-lg' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}"
                            >
                                <span>G{{ $ch->generation }}</span>
                                <span class="truncate max-w-[140px]">{{ $ch->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($activeChurch)
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Church Circle SVG Visualizer -->
                        <div class="lg:col-span-6">
                            <livewire:church-circle-svg :church="$activeChurch" :key="'church-'.$activeChurch->id" />
                        </div>

                        <!-- Generational Stream Details -->
                        <div class="lg:col-span-6 bg-slate-900/80 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                                    <h3 class="text-sm font-bold text-white">Generational Lineage Details</h3>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-950 text-indigo-300 border border-indigo-800">
                                        Founded Month {{ $activeChurch->founded_game_month }}
                                    </span>
                                </div>

                                <div class="space-y-3 text-xs">
                                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                                        <span class="text-slate-400">Meeting Ward:</span>
                                        <span class="font-bold text-white">{{ $activeChurch->ward->fullName }}</span>
                                    </div>
                                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                                        <span class="text-slate-400">Pastoral Leader:</span>
                                        <span class="font-bold text-indigo-400">
                                            {{ $activeChurch->leaderNpc?->displayName ?? ($activeChurch->leaderUser?->name ?? 'None') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                                        <span class="text-slate-400">Autonomous Status:</span>
                                        <span class="font-bold {{ $activeChurch->isAutonomous ? 'text-emerald-400' : 'text-blue-400' }}">
                                            {{ $activeChurch->isAutonomous ? 'Autonomous Local Leader (Field 5)' : 'Player Missionary-Led (G1)' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                                        <span class="text-slate-400">Mother Church:</span>
                                        <span class="font-medium text-slate-300">
                                            {{ $activeChurch->parentChurch?->name ?? 'Root Seed Church (Nagoya Origin)' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 mt-6 text-xs text-slate-400 leading-relaxed">
                                💡 <strong class="text-white">Field 4 Multiplication Rule:</strong> Once a 3/3rds group practices all essential Acts 2 rhythms, they transition from a dashed circle to a solid line, qualifying as an autonomous healthy church.
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        @elseif($currentTab === 'leaderboard')
            <!-- Multiplayer Leaderboard -->
            <livewire:multiplayer-leaderboard />

        @elseif($currentTab === 'map')
            <!-- Interactive Nagoya Map Canvas -->
            <livewire:nagoya-map-canvas />
        @endif
    </main>

    <!-- Modal: Month In Review Summary -->
    <livewire:month-in-review-modal />

    <!-- Modal: Edit Player Name -->
    @if($showEditNameModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl relative">
                <h3 class="text-base font-black text-white tracking-tight flex items-center gap-2">
                    <span>✏️</span>
                    <span>Customize Player Name</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Update how your missionary name appears across the movement, leaderboard, and church planting records.
                </p>

                <form wire:submit="savePlayerName" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Player / Missionary Handle
                        </label>
                        <input 
                            type="text" 
                            wire:model="editingName" 
                            class="w-full bg-slate-950 border {{ $errors->has('editingName') ? 'border-rose-500' : 'border-slate-800 focus:border-indigo-500' }} rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            placeholder="e.g. Kenji Tanaka, Coach Paul"
                            autofocus
                        >
                        @error('editingName')
                            <p class="text-[11px] text-rose-400 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button 
                            type="button" 
                            wire:click="closeEditNameModal"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-800/60 hover:bg-slate-800 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition cursor-pointer"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal: Choose Starting Home Ward Onboarding Prompt -->
    @if($showOnboardingModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 bg-black/80 backdrop-blur-md overflow-hidden animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl max-w-3xl lg:max-w-4xl w-full shadow-2xl flex flex-col max-h-[85vh] sm:max-h-[90vh] overflow-hidden transition-colors duration-200">
                <!-- Header (Pinned at Top) -->
                <div class="shrink-0 px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-800 uppercase tracking-wider">
                                    🗾 Nagoya Mission Field
                                </span>
                                <span class="text-[10px] sm:text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <span>✨</span>
                                    <span>Until No Place Left</span>
                                </span>
                            </div>
                            <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                Choose Your Starting Home Base
                            </h2>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Living in your <strong class="text-slate-900 dark:text-slate-200">Home Ward</strong> grants <strong class="text-emerald-600 dark:text-emerald-400">0 AP transit cost</strong> and a <strong class="text-emerald-600 dark:text-emerald-400">+25% trust bonus</strong> with neighbors. Choose where to establish your residence!
                            </p>
                        </div>

                        @if($user?->home_ward_id)
                            <button 
                                type="button" 
                                wire:click="closeOnboardingModal"
                                class="p-1.5 sm:p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                title="Close"
                            >
                                ✕
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Body (Scrollable Content Area) -->
                <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-4 space-y-4 text-slate-900 dark:text-slate-100">
                    <!-- Recommended Pioneering Frontier Spotlight Banner (High Contrast & Theme Adaptive) -->
                    @if($recommendedWard)
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 dark:bg-slate-800 border border-emerald-300 dark:border-emerald-500/50 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-3.5 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-600 text-white shadow-xs">
                                        ✨ Recommended Pioneering Frontier
                                    </span>
                                    <span class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                                        0 Co-laborers Stationed Here
                                    </span>
                                </div>
                                <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-950 dark:text-white flex items-center gap-2">
                                    <span>{{ $recommendedWard->name }}</span>
                                    <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">({{ $recommendedWard->name_ja }})</span>
                                </h3>
                                <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed max-w-xl font-normal">
                                    <strong class="text-emerald-800 dark:text-emerald-300 font-bold">{{ number_format($recommendedWard->spiritual_openness) }}% Spiritual Openness</strong> • High seeker readiness. In the spirit of Romans 15:20, settling where no other missionary lives accelerates citywide saturation!
                                </p>
                            </div>

                            <button 
                                type="button" 
                                wire:click="selectStartingWard({{ $recommendedWard->id }})"
                                class="shrink-0 px-4 py-2.5 rounded-xl text-xs font-black transition cursor-pointer shadow-md {{ $selectedHomeWardId === $recommendedWard->id ? 'bg-emerald-600 text-white ring-2 ring-emerald-400 shadow-emerald-600/30' : 'bg-slate-900 dark:bg-emerald-600 text-white hover:bg-slate-800 dark:hover:bg-emerald-500' }}"
                            >
                                @if($selectedHomeWardId === $recommendedWard->id)
                                    <span>✓ Recommended Choice Selected</span>
                                @else
                                    <span>Choose Recommended Base ➔</span>
                                @endif
                            </button>
                        </div>
                    @endif

                    <!-- All 16 Nagoya Wards Grid -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                All 16 Nagoya Districts (Click to Select):
                            </h4>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $pioneeringWards->count() }} Pioneering</span> (0 Co-laborers)
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                            @foreach($allWards as $ward)
                                @php
                                    $isSelected = ($selectedHomeWardId === $ward->id);
                                    $coLaborers = $missionariesByWard[$ward->id] ?? collect();
                                    $isPioneering = $coLaborers->isEmpty();
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectStartingWard({{ $ward->id }})"
                                    class="p-2.5 sm:p-3 rounded-2xl border text-left transition cursor-pointer relative flex flex-col justify-between gap-2 {{ $isSelected ? 'bg-indigo-50 dark:bg-indigo-950/70 border-indigo-500 dark:border-indigo-400 shadow-md ring-2 ring-indigo-500/30' : 'bg-white dark:bg-slate-950/60 border-slate-200 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/80' }}"
                                >
                                    <div>
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-xs font-bold text-slate-900 dark:text-white leading-tight">
                                                {{ $ward->name }}
                                            </span>
                                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                                {{ $ward->name_ja }}
                                            </span>
                                        </div>

                                        <!-- Missionary Stationed Badge -->
                                        <div class="mt-1.5">
                                            @if($isPioneering)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                                    <span>✨</span>
                                                    <span>Pioneering (0)</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 dark:bg-slate-800 text-amber-800 dark:text-slate-300 border border-amber-300 dark:border-slate-700" title="{{ $coLaborers->pluck('name')->join(', ') }}">
                                                    <span>👥</span>
                                                    <span>{{ $coLaborers->count() }} Co-laborer{{ $coLaborers->count() > 1 ? 's' : '' }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Metrics & Select indicator -->
                                    <div class="pt-1.5 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400">
                                        <div class="flex items-center gap-2">
                                            <span title="Spiritual Openness Index">
                                                🌱 <strong class="text-slate-800 dark:text-slate-200">{{ number_format($ward->spiritual_openness) }}%</strong>
                                            </span>
                                            <span title="Need & Brokenness Index">
                                                ❤️ <strong class="text-slate-800 dark:text-slate-200">{{ number_format($ward->poverty_index) }}%</strong>
                                            </span>
                                        </div>
                                        @if($isSelected)
                                            <span class="w-4 h-4 rounded-full bg-indigo-600 text-white font-black text-[10px] flex items-center justify-center">✓</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Footer Action Bar (Pinned at Bottom) -->
                @php
                    $selectedWard = $allWards->firstWhere('id', $selectedHomeWardId);
                @endphp
                <div class="shrink-0 px-4 sm:px-6 py-3.5 sm:py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 z-10 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-slate-600 dark:text-slate-400 text-center sm:text-left">
                        @if($selectedWard)
                            <span>Selected Home Base: <strong class="text-slate-900 dark:text-white font-bold">{{ $selectedWard->name }} ({{ $selectedWard->name_ja }})</strong></span>
                            <span class="block text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                +25% Neighbor Trust Bonus & 0 AP Commute in {{ $selectedWard->name }}
                            </span>
                        @else
                            <span class="text-amber-600 dark:text-amber-400 font-medium">Please pick a district above to establish your residence.</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        @if($user?->home_ward_id)
                            <button 
                                type="button" 
                                wire:click="closeOnboardingModal"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
                            >
                                Keep Current Base
                            </button>
                        @endif

                        <button 
                            type="button" 
                            wire:click="confirmStartingWard"
                            wire:loading.attr="disabled"
                            @disabled(! $selectedHomeWardId)
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg transition cursor-pointer flex items-center justify-center gap-2 {{ $selectedHomeWardId ? 'bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 shadow-indigo-600/30' : 'bg-slate-400 dark:bg-slate-800 text-slate-600 dark:text-slate-500 cursor-not-allowed' }}"
                        >
                            <span wire:loading.remove wire:target="confirmStartingWard">🏡 Establish Home Base & Begin Mission ➔</span>
                            <span wire:loading wire:target="confirmStartingWard" class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span>Establishing Base...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
