<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand & Game Title with Official Logo -->
            <a href="{{ route('welcome') }}" class="flex items-center gap-3 group cursor-pointer" title="View Story, Lore & Game Rules">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                    alt="Nagoya Oikos: The Four Fields Logo" 
                    class="w-11 h-11 object-contain rounded-2xl shadow-lg shadow-indigo-500/20 border border-indigo-500/40 group-hover:scale-105 group-hover:border-indigo-400 transition duration-200"
                >
                <div>
                    <h1 class="text-sm font-black text-white tracking-tight leading-none flex items-center gap-1.5">
                        <span>Nagoya Oikos</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-400 border border-indigo-800 uppercase">Four Fields</span>
                    </h1>
                    <span class="text-[11px] text-slate-400 font-medium group-hover:text-indigo-300 transition">Multiplayer Movement Simulation • No Place Left</span>
                </div>
            </a>

            <!-- Main Tab Navigation -->
            <nav class="hidden md:flex items-center gap-1 p-1 bg-slate-950/80 rounded-2xl border border-slate-800 text-xs font-semibold">
                <button 
                    type="button" 
                    wire:click="setTab('planner')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                >
                    <span>⚡</span>
                    <span>Action HQ</span>
                </button>
                <button 
                    type="button" 
                    wire:click="setTab('churches')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                >
                    <span>⛪</span>
                    <span>Church Circles</span>
                </button>
                <button 
                    type="button" 
                    wire:click="setTab('leaderboard')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                >
                    <span>🏆</span>
                    <span>Leaderboard & Co-op</span>
                </button>
                <button 
                    type="button" 
                    wire:click="setTab('map')"
                    class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'map' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                >
                    <span>🗺️</span>
                    <span>Nagoya Map</span>
                </button>
                <a 
                    href="{{ route('welcome') }}"
                    class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-slate-400 hover:text-amber-300 hover:bg-slate-900 border border-transparent hover:border-slate-800"
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
                    wire:key="dash-clock-{{ $session?->current_game_day }}-{{ $session?->days_processed_this_month }}-{{ $user?->days_played_today }}"
                    x-data="digitalClock({{ max(0, $session?->secondsUntilNextDailyTick ?? 600) }}, '{{ $session?->next_daily_tick_at?->toIso8601String() }}', {{ (!($user?->hasReachedDailyPlayLimit ?? false) && ($session?->days_processed_this_month ?? 0) < 30) ? 'true' : 'false' }})"
                    x-init="init()"
                    class="hidden lg:flex items-center gap-2 px-2.5 py-1 rounded-xl bg-slate-950/80 border border-slate-800"
                >
                    <!-- Day & Cap badge -->
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-white flex items-center justify-end gap-1">
                            <span>Day {{ $session?->current_game_day ?? 1 }}/30</span>
                            <span class="text-[9px] {{ ($user?->days_played_today ?? 0) >= 30 ? 'text-amber-400 font-black' : 'text-slate-400' }}">({{ $user?->days_played_today ?? 0 }}/30d)</span>
                        </div>
                        <div class="text-[9px] text-slate-500 uppercase tracking-tighter">
                            {{ ($user?->hasReachedDailyPlayLimit ?? false) ? 'Night Rest' : 'Next Tick' }}
                        </div>
                    </div>

                    <!-- LED Display -->
                    <div class="bg-black/95 border border-slate-800/90 rounded-lg px-2 py-0.5 shadow-[inset_0_1px_4px_rgba(0,0,0,0.8)]">
                        @if($user?->hasReachedDailyPlayLimit ?? false)
                            <span class="font-mono text-xs font-black text-amber-400 drop-shadow-[0_0_5px_rgba(245,158,11,0.5)]">30:30</span>
                        @elseif(($session?->days_processed_this_month ?? 0) >= 30)
                            <span class="font-mono text-xs font-black text-indigo-400">MONTH</span>
                        @else
                            <span class="font-mono text-xs font-black text-emerald-400 drop-shadow-[0_0_6px_rgba(52,211,153,0.6)] flex items-center">
                                <span class="tabular-nums" x-text="minutes">09</span><span class="animate-pulse">:</span><span class="tabular-nums" x-text="seconds">59</span>
                            </span>
                        @endif
                    </div>

                    <!-- Turn Advance Button -->
                    <button 
                        type="button" 
                        wire:click="advanceDay(true)"
                        wire:loading.attr="disabled"
                        @disabled(($user?->hasReachedDailyPlayLimit ?? false) || ($session?->days_processed_this_month ?? 0) >= 30)
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer {{ (($user?->hasReachedDailyPlayLimit ?? false) || ($session?->days_processed_this_month ?? 0) >= 30) ? 'bg-slate-900 text-slate-600 border border-slate-800/80 cursor-not-allowed' : 'bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 text-white shadow-sm hover:scale-[1.02]' }}"
                        title="{{ ($user?->hasReachedDailyPlayLimit ?? false) ? 'Daily limit of 30 days reached. Next month starts tonight!' : 'Manually advance to next day' }}"
                    >
                        <span wire:loading.remove wire:target="advanceDay">⏭️</span>
                        <span wire:loading wire:target="advanceDay" class="w-2.5 h-2.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span wire:loading.remove wire:target="advanceDay" class="hidden xl:inline">Next Day</span>
                    </button>
                </div>

                <!-- Ambient Music Toggle Button -->
                <button 
                    type="button" 
                    @click="$store.audio?.toggle()" 
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                    :class="$store.audio?.isPlaying ? 'bg-indigo-950/90 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500/40' : 'bg-slate-800 border-slate-700 text-slate-400 hover:text-white'"
                    :title="$store.audio?.isPlaying ? 'Turn Background Music OFF' : 'Turn Background Music ON (Japanese Ambient)'"
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

                <!-- Player Profile Pill -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-800">
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
        <div class="flex md:hidden items-center justify-around px-2 py-2 border-t border-slate-800/80 text-[11px] font-semibold bg-slate-950">
            <button wire:click="setTab('planner')" class="px-2 py-1 rounded-lg {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">⚡ HQ</button>
            <button wire:click="setTab('churches')" class="px-2 py-1 rounded-lg {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">⛪ Circles</button>
            <button wire:click="setTab('leaderboard')" class="px-2 py-1 rounded-lg {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">🏆 Ladder</button>
            <button wire:click="setTab('map')" class="px-2 py-1 rounded-lg {{ $currentTab === 'map' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">🗺️ Map</button>
            <button type="button" @click="$store.audio?.toggle()" class="px-2 py-1 rounded-lg" :class="$store.audio?.isPlaying ? 'text-indigo-300 font-bold' : 'text-slate-400'"><span x-text="$store.audio?.isPlaying ? '🎵 On' : '🔇 Off'"></span></button>
            <button wire:click="openEditNameModal" class="px-2 py-1 rounded-lg text-indigo-400">👤 Name</button>
            <a href="{{ route('welcome') }}" class="px-2 py-1 rounded-lg text-amber-400 font-bold">📖 Story</a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full">
        <!-- Toast Notification Listener -->
        <div 
            x-data="{ show: false, message: '', type: 'info' }"
            x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition
            class="fixed bottom-5 right-5 z-50 p-4 rounded-2xl shadow-2xl border flex items-center gap-3 text-xs font-bold"
            :class="{
                'bg-emerald-950/90 text-emerald-300 border-emerald-700': type === 'success',
                'bg-rose-950/90 text-rose-300 border-rose-700': type === 'error',
                'bg-amber-950/90 text-amber-300 border-amber-700': type === 'warning',
                'bg-indigo-950/90 text-indigo-300 border-indigo-700': type === 'info'
            }"
            style="display: none;"
        >
            <span x-text="type === 'success' ? '✅' : (type === 'error' ? '❌' : (type === 'warning' ? '⚠️' : 'ℹ️'))"></span>
            <span x-text="message"></span>
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
</div>
