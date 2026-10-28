<div class="min-h-screen overflow-x-hidden bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white transition-colors duration-200">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-md transition-colors duration-200">
        <div class="max-w-7xl 2xl:max-w-[1536px] mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2 sm:gap-3">
            <!-- Brand & Game Title (Clean, minimal, no subheaders) -->
            <a href="{{ route('welcome') }}" class="flex items-center gap-2.5 group cursor-pointer shrink-0" title="Nagoya Oikos - Movement Simulation">
                <img 
                    src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                    alt="Nagoya Oikos Logo" 
                    class="w-9 h-9 object-contain rounded-xl shadow-md shadow-indigo-500/20 border border-indigo-500/30 group-hover:scale-105 transition duration-200 shrink-0"
                >
                <span class="text-sm font-black text-slate-900 dark:text-white tracking-tight">
                    Nagoya Oikos
                </span>
            </a>

            <!-- Main Tab Navigation (Core visible + More dropdown) -->
            <nav class="hidden md:flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs font-semibold shrink-0">
                <!-- Action HQ (Primary) -->
                <button 
                    type="button" 
                    wire:click="setTab('planner')"
                    class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    title="Action HQ - Daily Queue & Mission Planning"
                >
                    <span>⚡</span>
                    <span>Action HQ</span>
                </button>

                <!-- Church Circles & Gen Map -->
                <button 
                    type="button" 
                    wire:click="setTab('churches')"
                    class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    title="Church Circles & Visual Generation Map"
                >
                    <span>⛪</span>
                    <span>Circles & Gen Map</span>
                </button>

                <!-- Nagoya Map -->
                <button 
                    type="button" 
                    wire:click="setTab('map')"
                    class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'map' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    title="Nagoya Map - Ward Strategic Intelligence"
                >
                    <span>🗺️</span>
                    <span>Map</span>
                </button>

                <!-- Co-op Game Chat -->
                <button 
                    type="button" 
                    wire:click="setTab('chat')"
                    class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'chat' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    title="Missionary Dispatch & Co-op Chat"
                >
                    <span>💬</span>
                    <span>Chat</span>
                    @if(isset($unreadChatCount) && $unreadChatCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse">
                            {{ $unreadChatCount }}
                        </span>
                    @endif
                </button>

                <!-- More Dropdown (Leaderboard, Story & Rules) -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button 
                        type="button" 
                        @click="open = !open"
                        class="px-2.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1 {{ in_array($currentTab, ['leaderboard']) ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                        title="More Views & Guides"
                    >
                        <span>⋯</span>
                        <span>More</span>
                        <svg class="w-3 h-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
                        class="absolute left-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1.5 z-50 text-xs font-semibold"
                        style="display: none;"
                    >
                        <button 
                            type="button" 
                            wire:click="setTab('leaderboard')" 
                            @click="open = false"
                            class="w-full px-3.5 py-2.5 flex items-center gap-2.5 hover:bg-slate-100 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white transition text-left cursor-pointer {{ $currentTab === 'leaderboard' ? 'bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 font-bold' : '' }}"
                        >
                            <span class="text-base">🏆</span>
                            <div>
                                <div class="leading-tight">Leaderboard & Co-op</div>
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Player scores & collaborative tiers</div>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            wire:click="openSpiritualRhythms" 
                            @click="open = false"
                            class="w-full px-3.5 py-2.5 flex items-center gap-2.5 hover:bg-slate-100 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition text-left cursor-pointer border-t border-slate-100 dark:border-slate-800/60"
                        >
                            <span class="text-base">🕊️</span>
                            <div>
                                <div class="leading-tight">Spiritual Rhythms &amp; Retreats</div>
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Mentoring, Sabbath, Fasting &amp; Mid-Level</div>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            wire:click="toggleShowcaseBar" 
                            @click="open = false"
                            class="w-full px-3.5 py-2.5 flex items-center gap-2.5 hover:bg-slate-100 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400 transition text-left cursor-pointer border-t border-slate-100 dark:border-slate-800/60 {{ $showShowcaseBar ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 font-bold' : '' }}"
                        >
                            <span class="text-base">⏱️</span>
                            <div>
                                <div class="leading-tight">Time-Stage Showcase</div>
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Demo 3m, 6m, 1y, 2y, 5y, 10y &amp; 15y</div>
                            </div>
                        </button>

                        <a 
                            href="{{ route('welcome') }}" 
                            class="w-full px-3.5 py-2.5 flex items-center gap-2.5 hover:bg-slate-100 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400 transition text-left cursor-pointer border-t border-slate-100 dark:border-slate-800/60"
                        >
                            <span class="text-base">📖</span>
                            <div>
                                <div class="leading-tight">Story & Rules</div>
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Four Fields, 5 Levels & manual</div>
                            </div>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Controls, Clock & Profile Dropdown -->
            <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
                <!-- Interactive Time-Stage Showcase Toggle Button -->
                <button
                    type="button"
                    wire:click="toggleShowcaseBar"
                    class="hidden lg:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none {{ $showShowcaseBar ? 'bg-amber-500/20 border-amber-500/70 text-amber-300 shadow-md shadow-amber-500/10 ring-1 ring-amber-400/30' : 'bg-slate-100 dark:bg-slate-950/80 border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-amber-500/60 hover:text-amber-600 dark:hover:text-amber-300' }}"
                    title="Open Interactive Time-Stage Showcase (3m, 6m, 1y, 2y, 5y, 10y, 15y)"
                >
                    <span>⏱️</span>
                    <span>Showcase</span>
                    @if($showShowcaseBar)
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] font-black bg-amber-500 text-slate-950 uppercase">
                            {{ $activeShowcaseStage === 'reset' ? 'Day 1' : strtoupper($activeShowcaseStage) }}
                        </span>
                    @endif
                </button>

                <!-- Month, Week & Digital Clock -->
                <div 
                    wire:key="dash-clock-{{ $user?->current_game_month }}-{{ $user?->current_game_week }}-{{ $user?->weeks_processed_this_month }}-{{ $user?->weeks_played_today }}"
                    x-data="digitalClock({{ max(0, $user?->secondsUntilNextWeeklyTick ?? 900) }}, '{{ $user?->next_weekly_tick_at?->toIso8601String() }}', {{ (!($user?->hasReachedDailyPlayLimit ?? false) && ($user?->weeks_processed_this_month ?? 0) < 4) ? 'true' : 'false' }})"
                    x-init="init()"
                    class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-xl bg-slate-950/80 border border-slate-800 shrink-0"
                >
                    <!-- Month & Current Week badge -->
                    <div class="text-right whitespace-nowrap">
                        <div class="text-[10px] font-bold text-white flex items-center justify-end gap-1">
                            <span>Month {{ $user?->current_game_month ?? 1 }}</span>
                            <span class="text-slate-500">•</span>
                            <span>Week {{ $user?->current_game_week ?? 1 }}</span>
                        </div>
                        <div class="text-[9px] text-slate-400 uppercase tracking-tighter">
                            {{ ($user?->hasReachedDailyPlayLimit ?? false) ? 'Night Rest' : (($user?->weeks_processed_this_month ?? 0) >= 4 ? 'At Midnight' : 'Next Week In') }}
                        </div>
                    </div>

                    <!-- LED Display -->
                    <div class="bg-black/95 border border-slate-800/90 rounded-lg px-1.5 py-0.5 shadow-[inset_0_1px_4px_rgba(0,0,0,0.8)]">
                        @if($user?->hasReachedDailyPlayLimit ?? false)
                            <span class="font-mono text-xs font-black text-amber-400 drop-shadow-[0_0_5px_rgba(245,158,11,0.5)]">4:4</span>
                        @elseif(($user?->weeks_processed_this_month ?? 0) >= 4)
                            <span class="font-mono text-xs font-black text-indigo-400" title="Month completed! Server advances at midnight.">DONE</span>
                        @else
                            <span class="font-mono text-xs font-black text-emerald-400 drop-shadow-[0_0_6px_rgba(52,211,153,0.6)] flex items-center">
                                <span class="tabular-nums" x-text="minutes">14</span><span class="animate-pulse">:</span><span class="tabular-nums" x-text="seconds">59</span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Quick Utility Toggles (Music, Theme, Notifications) -->
                <div class="flex items-center gap-1 shrink-0">
                    <!-- Ambient Music Toggle Button with Hover Popover -->
                    <div class="relative group">
                        <button 
                            type="button" 
                            @click="$store.audio?.toggle()" 
                            class="flex items-center justify-center p-2 rounded-xl border text-xs font-bold transition cursor-pointer select-none"
                            :class="$store.audio?.isPlaying ? 'bg-indigo-950/90 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500/40' : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            title="Japanese Background Music (Sakura, Sukiyaki, etc.)"
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
                        </button>

                        <!-- Hover Popover: Song Title & Controls -->
                        <div 
                            class="absolute right-0 top-full mt-2 w-64 p-3.5 rounded-2xl bg-slate-900/95 dark:bg-slate-900/95 backdrop-blur-xl border border-indigo-500/30 shadow-2xl shadow-black/80 z-50 pointer-events-none opacity-0 group-hover:opacity-100 scale-95 group-hover:scale-100 transition-all duration-200 origin-top-right space-y-2 text-left"
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

                    <!-- Theme Mode Toggle Button (Light/Dark) -->
                    <button 
                        wire:ignore
                        type="button" 
                        onclick="window.__toggleTheme ? window.__toggleTheme() : null"
                        class="flex items-center justify-center p-2 rounded-xl border text-xs font-bold transition cursor-pointer select-none bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-amber-600 dark:text-amber-300 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-amber-700 dark:hover:text-white shadow-sm"
                        title="Switch Light/Dark Mode"
                    >
                        <span data-theme-icon>☀️</span>
                    </button>

                    <!-- Notification Bell -->
                    <button 
                        type="button"
                        wire:click="openReportModal"
                        class="relative p-2 rounded-xl bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 border border-slate-300 dark:border-slate-700 transition cursor-pointer text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white"
                        title="View Latest Monthly Movement Report"
                    >
                        <span class="text-sm">🔔</span>
                        @if($unreadReportsCount > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-[10px] font-black text-white flex items-center justify-center animate-bounce">
                                {{ $unreadReportsCount }}
                            </span>
                        @endif
                    </button>
                </div>

                <!-- Player & Missionary Profile Menu Dropdown (or Guest Sign-In / Join CTA) -->
                @auth
                <div class="relative pl-1.5 border-l border-slate-200 dark:border-slate-800" x-data="{ open: false }" @click.outside="open = false">
                    <button 
                        type="button" 
                        @click="open = !open"
                        class="flex items-center gap-1.5 sm:gap-2 p-1 pl-1.5 pr-2 sm:pr-2.5 rounded-2xl border transition cursor-pointer select-none bg-slate-100 dark:bg-slate-800/80 border-slate-300 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500 shadow-sm"
                        title="Missionary Profile, Clearance & Account Settings"
                    >
                        <div class="w-7 h-7 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-[11px] font-bold text-white shadow-sm shrink-0">
                            {{ $user?->initials() ?? 'MC' }}
                        </div>
                        <div class="hidden sm:block text-left text-xs whitespace-nowrap min-w-0">
                            <div class="font-bold text-slate-800 dark:text-white leading-none truncate max-w-[85px]">
                                {{ $user?->name ?? 'Player' }}
                            </div>
                            <div class="text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold leading-none mt-0.5">
                                {{ number_format($user?->compositeScore ?? 0) }} pts
                            </div>
                        </div>
                        @if(!($user?->screening_passed))
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shrink-0" title="Screening Pending"></span>
                        @endif
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
                        class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-1.5rem)] rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-2 z-50 text-xs"
                        style="display: none;"
                    >
                        <!-- User Card / Handle Edit -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/80 mb-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-sm shrink-0">
                                        {{ $user?->initials() ?? 'MC' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-white leading-tight truncate">
                                            {{ $user?->name ?? 'Player' }}
                                        </div>
                                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                            {{ number_format($user?->compositeScore ?? 0) }} composite pts
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="openEditNameModal"
                                    @click="open = false"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800/80 transition cursor-pointer shrink-0"
                                >
                                    ✏️ Rename
                                </button>
                            </div>
                        </div>

                        <!-- Missionary Capability Status -->
                        <div class="mb-2">
                            @if($user?->screening_passed)
                                <button 
                                    type="button" 
                                    wire:click="openScreeningScorecard"
                                    @click="open = false"
                                    class="w-full p-2.5 rounded-xl text-left transition cursor-pointer bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800/80 group"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                            <span>🎯</span>
                                            <span>Capability Scorecard</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-indigo-600 text-white">
                                            {{ $user->screening_score }}%
                                        </span>
                                    </div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200 text-xs mt-1 leading-snug">
                                        {{ $user->screening_profile_title ?: 'Field Cleared' }}
                                    </div>
                                    <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium mt-0.5 group-hover:underline">
                                        View scorecard & clearance →
                                    </div>
                                </button>
                            @else
                                <button 
                                    type="button" 
                                    wire:click="openOnboardingModal"
                                    @click="open = false"
                                    class="w-full p-2.5 rounded-xl text-left transition cursor-pointer bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 dark:hover:bg-amber-950/80 border border-amber-300 dark:border-amber-700/80 group animate-pulse"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center gap-1">
                                            <span>⚠️</span>
                                            <span>Screening Pending</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-600 text-white">Required</span>
                                    </div>
                                    <div class="text-[11px] text-amber-800 dark:text-amber-300 font-semibold mt-1">
                                        Candidate questionnaire required for full deployment.
                                    </div>
                                    <div class="text-[10px] text-amber-700 dark:text-amber-400 font-bold mt-1 group-hover:underline">
                                        Complete Screening Now →
                                    </div>
                                </button>
                            @endif
                        </div>

                        <!-- Game Host Field Orientation -->
                        <div class="mb-2">
                            <button 
                                type="button" 
                                wire:click="openHostWelcomeModal"
                                @click="open = false"
                                class="w-full p-2.5 rounded-xl text-left transition cursor-pointer bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800/80 group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <span>🗾</span>
                                        <span>Host Field Orientation</span>
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $user?->committed_to_language_training ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                        {{ $user?->committed_to_language_training ? ($user->isLanguageTrainingActive ? '70% AP Active' : 'Graduated') : 'Orientation' }}
                                    </span>
                                </div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs mt-1 leading-snug">
                                    Nagoya &amp; {{ $user?->homeWard?->name ?? 'Ward' }} Briefing
                                </div>
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5 group-hover:underline">
                                    Language, group culture &amp; 70% AP strategy →
                                </div>
                            </button>
                        </div>

                        <!-- Embedded Residence / Home Ward -->
                        <div class="mb-2">
                            <button 
                                type="button" 
                                wire:click="openOnboardingModal"
                                @click="open = false"
                                class="w-full p-2.5 rounded-xl text-left transition cursor-pointer bg-slate-50 dark:bg-slate-950/60 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800/80 group"
                            >
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <span>🏡</span>
                                    <span>Embedded Residence</span>
                                </div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs mt-0.5">
                                    {{ $user?->homeWard?->name ?? 'No Ward Assigned' }}
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                                    Click to view or change base →
                                </div>
                            </button>
                        </div>

                        <!-- Divider & Sign Out -->
                        <div class="pt-1 border-t border-slate-100 dark:border-slate-800/80">
                            <form action="{{ route('logout') }}" method="POST" class="w-full">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="w-full px-3 py-2 rounded-xl flex items-center justify-between text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer font-semibold"
                                >
                                    <span>Sign Out of Mission</span>
                                    <span>🚪</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @else
                <div class="flex items-center gap-1 sm:gap-1.5 pl-1 sm:pl-1.5 border-l border-slate-200 dark:border-slate-800">
                    <a
                        href="{{ route('login') }}"
                        class="px-2 sm:px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-[11px] sm:text-xs font-bold text-slate-700 dark:text-slate-200 transition whitespace-nowrap"
                    >
                        Sign In
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white text-[11px] sm:text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center gap-1 whitespace-nowrap"
                    >
                        <span>⚡</span>
                        <span>Join<span class="hidden sm:inline"> Mission</span></span>
                    </a>
                </div>
                @endauth
            </div>
        </div>

        <!-- Mobile Tab Navigation Bar -->
        <div 
            x-data="{ mobileMoreOpen: false }"
            @click.outside="mobileMoreOpen = false"
            class="md:hidden border-t border-slate-200 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md relative z-30 transition-colors duration-200"
        >
            <div class="grid grid-cols-5 gap-1 px-2 py-1.5 text-[11px] sm:text-xs font-semibold">
                <button 
                    type="button"
                    wire:click="setTab('planner')" 
                    class="min-w-0 px-1.5 py-1.5 rounded-xl transition flex items-center justify-center gap-1 whitespace-nowrap {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>⚡</span>
                    <span class="truncate">HQ</span>
                </button>

                <button 
                    type="button"
                    wire:click="setTab('churches')" 
                    class="min-w-0 px-1.5 py-1.5 rounded-xl transition flex items-center justify-center gap-1 whitespace-nowrap {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>⛪</span>
                    <span class="truncate">Circles</span>
                </button>

                <button 
                    type="button"
                    wire:click="setTab('map')" 
                    class="min-w-0 px-1.5 py-1.5 rounded-xl transition flex items-center justify-center gap-1 whitespace-nowrap {{ $currentTab === 'map' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>🗺️</span>
                    <span class="truncate">Map</span>
                </button>

                <button 
                    type="button"
                    wire:click="setTab('chat')" 
                    class="min-w-0 px-1.5 py-1.5 rounded-xl transition flex items-center justify-center gap-1 relative whitespace-nowrap {{ $currentTab === 'chat' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                >
                    <span>💬</span>
                    <span class="truncate">Chat</span>
                    @if(isset($unreadChatCount) && $unreadChatCount > 0)
                        <span class="px-1 py-0.2 rounded-full text-[9px] font-black bg-rose-500 text-white animate-pulse shrink-0">
                            {{ $unreadChatCount }}
                        </span>
                    @endif
                </button>

                <!-- More Submenu Button -->
                <button 
                    type="button" 
                    @click="mobileMoreOpen = !mobileMoreOpen"
                    class="min-w-0 px-1.5 py-1.5 rounded-xl transition flex items-center justify-center gap-0.5 whitespace-nowrap {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    :class="mobileMoreOpen && '{{ $currentTab }}' !== 'leaderboard' ? 'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white font-bold' : ''"
                    title="More Features & Controls"
                >
                    <span>⋯</span>
                    <span class="truncate">More</span>
                    <svg class="w-3 h-3 shrink-0 transition-transform duration-200" :class="mobileMoreOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile 'More' Dropdown Submenu -->
            <div 
                x-show="mobileMoreOpen" 
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-3 space-y-2.5 text-xs font-semibold"
                style="display: none;"
            >
                <div class="grid grid-cols-2 gap-2">
                    <button 
                        type="button" 
                        wire:click="setTab('leaderboard')" 
                        @click="mobileMoreOpen = false"
                        class="col-span-2 p-2.5 rounded-xl border flex items-center justify-between gap-2.5 text-left transition cursor-pointer {{ $currentTab === 'leaderboard' ? 'bg-indigo-50 dark:bg-indigo-950/80 border-indigo-400 dark:border-indigo-600 text-indigo-700 dark:text-indigo-300' : 'bg-slate-50 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 border-slate-200 dark:border-slate-700/80' }}"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-lg shrink-0">🏆</span>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-800 dark:text-white leading-tight truncate">Leaderboard &amp; Co-op</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal truncate">Player scores &amp; collaborative tiers</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 shrink-0">View →</span>
                    </button>

                    <button 
                        type="button" 
                        wire:click="openSpiritualRhythms" 
                        @click="mobileMoreOpen = false"
                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-emerald-50 dark:hover:bg-emerald-950/60 border border-slate-200 dark:border-slate-700/80 flex items-center gap-2 text-left transition cursor-pointer min-w-0"
                    >
                        <span class="text-lg shrink-0">🕊️</span>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 dark:text-white leading-tight truncate">Spiritual Rhythms</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal truncate">Sabbath &amp; Retreats</div>
                        </div>
                    </button>

                    <a 
                        href="{{ route('welcome') }}" 
                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 hover:bg-amber-50 dark:hover:bg-amber-950/60 border border-slate-200 dark:border-slate-700/80 flex items-center gap-2 text-left transition cursor-pointer min-w-0"
                    >
                        <span class="text-lg shrink-0">📖</span>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 dark:text-white leading-tight truncate">Story &amp; Rules</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal truncate">Four Fields Manual</div>
                        </div>
                    </a>

                    <button 
                        type="button" 
                        wire:click="toggleShowcaseBar" 
                        @click="mobileMoreOpen = false"
                        class="col-span-2 p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-950/70 border border-amber-300 dark:border-amber-800/80 flex items-center justify-between gap-2.5 text-left transition cursor-pointer"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-lg shrink-0">⏱️</span>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-800 dark:text-white leading-tight truncate">Time-Stage Showcase Mode</div>
                                <div class="text-[10px] text-amber-700 dark:text-amber-400 font-normal truncate">Demonstrate 3m, 6m, 1y, 2y, 5y, 10y &amp; 15y stages</div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-500 text-slate-950 shrink-0">
                            {{ $showShowcaseBar ? 'Active' : 'Open' }}
                        </span>
                    </button>
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 grid grid-cols-3 gap-1.5">
                    <!-- Music -->
                    <button 
                        type="button" 
                        @click="$store.audio?.toggle()" 
                        class="min-w-0 py-2 px-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold flex items-center justify-center gap-1 transition cursor-pointer truncate"
                    >
                        <span class="truncate" x-text="$store.audio?.isPlaying ? '🎵 Playing' : '🔇 Muted'"></span>
                    </button>

                    <!-- Theme -->
                    <button 
                        wire:ignore
                        type="button" 
                        onclick="window.__toggleTheme ? window.__toggleTheme() : null"
                        class="min-w-0 py-2 px-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold text-amber-500 dark:text-amber-400 flex items-center justify-center gap-1 transition cursor-pointer truncate"
                        title="Toggle Light / Dark Mode"
                    >
                        <span data-theme-icon>☀️</span>
                        <span data-theme-label class="truncate">Theme</span>
                    </button>

                    <!-- Rename Profile -->
                    <button 
                        type="button" 
                        wire:click="openEditNameModal" 
                        @click="mobileMoreOpen = false"
                        class="min-w-0 py-2 px-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 flex items-center justify-center gap-1 transition cursor-pointer truncate"
                    >
                        <span>👤</span>
                        <span class="truncate">Rename</span>
                    </button>
                </div>
            </div>
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

        <!-- Interactive Real-Life Time-Stage Showcase Presenter Panel -->
        @if($showShowcaseBar)
            <div class="mb-6 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-950 to-indigo-950/90 border border-amber-500/40 shadow-2xl shadow-amber-950/20 p-5 sm:p-6 space-y-5 relative overflow-hidden">
                <!-- Subtle top accent bar -->
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 via-emerald-400 to-indigo-500"></div>

                <!-- Header & Stage Switcher Row -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1.5">
                                <span>⏱️</span>
                                <span>Live Longitudinal Showcase Mode</span>
                            </span>
                            @if($isGuestShowcase)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-700/60">
                                    🌐 Shared Guest SQLite DB • Regenerated Once Daily ({{ $guestDatabaseGeneratedDate ?? now()->toDateString() }})
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-700/60">
                                    🧪 Isolated Temp SQLite DB • Computer Players Only
                                </span>
                            @endif
                            @if($showcaseSnapshot)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-950/90 text-indigo-300 border border-indigo-700/60">
                                    📅 {{ $showcaseSnapshot['calendar_date'] }} • Month {{ $showcaseSnapshot['months'] }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-base sm:text-lg font-black text-white tracking-tight">
                            Demonstrate Nagoya Movement Across Real-Life Time Horizons
                        </h2>
                        <p class="text-xs text-slate-400">
                            @if($isGuestShowcase)
                                Public Simulation for Non-Registered Players: Uses a shared SQLite database regenerated at most once per real-life day to reduce server CPU load. Register a free account to launch your own personal movement!
                            @else
                                Runs in a per-player isolated SQLite sandbox with computer players only — your live game data is 100% untouched. Explore the Map, Circles &amp; Gen Map, and Leaderboard at each stage.
                            @endif
                        </p>
                    </div>

                    <!-- Mode Switcher & Close Button -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if($isGuestShowcase)
                            <button
                                type="button"
                                wire:click="regenerateGuestSimulation"
                                wire:loading.attr="disabled"
                                class="px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                                title="Shared guest database can only be regenerated once per real-life day"
                            >
                                <span>🔄</span>
                                <span>Regenerate Daily DB</span>
                            </button>
                        @else
                            <div class="inline-flex items-center p-1 rounded-xl bg-slate-950/90 border border-slate-800 text-[11px] font-bold">
                                <button
                                    type="button"
                                    wire:click="setShowcaseMode('coop')"
                                    wire:loading.attr="disabled"
                                    class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 {{ $showcaseMode === 'coop' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                                    title="Simulate with AI Co-Laborers (Paul, Barnaby & Keiko)"
                                >
                                    <span>🤝</span>
                                    <span>Co-op Team</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="setShowcaseMode('solo')"
                                    wire:loading.attr="disabled"
                                    class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 {{ $showcaseMode === 'solo' ? 'bg-amber-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                                    title="Simulate as Solo Pioneer Missionary"
                                >
                                    <span>🧍</span>
                                    <span>Solo Pioneer</span>
                                </button>
                            </div>
                        @endif

                        <button
                            type="button"
                            wire:click="openShowcaseComparison(true)"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/40 text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                            title="Compare all 7 milestones (3m to 15y) side-by-side"
                        >
                            <span>📊</span>
                            <span>Compare All 7 Stages</span>
                        </button>

                        @if($isGuestShowcase)
                            <a
                                href="{{ route('register') }}"
                                class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white text-xs font-bold shadow-md transition flex items-center gap-1.5"
                                title="Create a free player account to play your own session"
                            >
                                <span>⚡</span>
                                <span>Create Player &amp; Join</span>
                            </a>
                        @else
                            <button
                                type="button"
                                wire:click="exitShowcaseSandbox"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-800/80 hover:bg-rose-950/80 text-slate-300 hover:text-rose-200 border border-slate-700 hover:border-rose-700 text-xs font-bold transition cursor-pointer flex items-center gap-1"
                                title="Exit Showcase Sandbox and return to your untouched live game"
                            >
                                <span>✕</span>
                                <span>Exit Showcase</span>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- 8 Interactive Time-Stage Horizon Buttons -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
                    <!-- Stage 0: Day 1 Reset -->
                    <button
                        type="button"
                        wire:click="loadShowcaseStage('reset')"
                        wire:loading.attr="disabled"
                        class="p-2.5 rounded-2xl border text-left transition cursor-pointer flex flex-col justify-between {{ $activeShowcaseStage === 'reset' ? 'bg-amber-500/20 border-amber-400 text-white shadow-lg shadow-amber-500/10 ring-1 ring-amber-400/50' : 'bg-slate-900/90 border-slate-800 text-slate-300 hover:border-slate-600 hover:bg-slate-800/70' }}"
                    >
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-black {{ $activeShowcaseStage === 'reset' ? 'text-amber-300' : 'text-white' }}">🌱 Day 1</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-800 text-slate-400">M1</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 leading-tight">Arrival &amp; G1 Seed</div>
                    </button>

                    @php
                        $stageShortSubtitles = [
                            '3m' => '70% Lang & Plowing',
                            '6m' => '1st Salvations & DBS',
                            '1y' => '1st Baptisms & G2',
                            '2y' => 'Local L2/L3 & G3',
                            '5y' => '1st Term & G4 Streams',
                            '10y' => 'Decade & L4/L5',
                            '15y' => '16/16 No Place Left',
                        ];
                    @endphp

                    @foreach($showcaseStageMeta as $stageKey => $meta)
                        <button
                            type="button"
                            wire:click="loadShowcaseStage('{{ $stageKey }}')"
                            wire:loading.attr="disabled"
                            class="p-2.5 rounded-2xl border text-left transition cursor-pointer flex flex-col justify-between {{ $activeShowcaseStage === $stageKey ? 'bg-amber-500/20 border-amber-400 text-white shadow-lg shadow-amber-500/10 ring-1 ring-amber-400/50' : 'bg-slate-900/90 border-slate-800 text-slate-300 hover:border-slate-600 hover:bg-slate-800/70' }}"
                        >
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs font-black {{ $activeShowcaseStage === $stageKey ? 'text-amber-300' : 'text-white' }}">
                                    {{ $stageKey === '15y' ? '🗾 ' : '' }}{{ $meta['label'] }}
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-800 text-slate-400">M{{ $meta['months'] }}</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1 leading-tight">
                                {{ $stageShortSubtitles[$stageKey] ?? 'Movement Stage' }}
                            </div>
                        </button>
                    @endforeach
                </div>

                <!-- Loading Indicator when simulating -->
                <div wire:loading.flex wire:target="loadShowcaseStage, setShowcaseMode, openShowcaseComparison" class="items-center justify-center gap-2.5 p-3 rounded-2xl bg-indigo-950/80 border border-indigo-500/40 text-xs font-bold text-indigo-200">
                    <svg class="w-4 h-4 animate-spin text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Simulating longitudinal Japanese church-planting dynamics across Nagoya wards...</span>
                </div>

                <!-- Active Stage Real-Life Missiological Briefing & Metrics -->
                @if($showcaseSnapshot)
                    @php
                        $snap = $showcaseSnapshot;
                        $gens = $snap['churches_by_generation'] ?? [1 => 1, 2 => 0, 3 => 0, 4 => 0];
                        $tiers = $snap['leaders_by_tier'] ?? ['L1' => 1, 'L2' => 0, 'L3' => 0, 'L4' => 0, 'L5' => 0];
                    @endphp
                    <div wire:loading.remove wire:target="loadShowcaseStage, setShowcaseMode" class="space-y-4">
                        <!-- Narrative Card -->
                        <div class="p-4 rounded-2xl bg-slate-950/85 border border-slate-800/90 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1.5 max-w-4xl">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-wider text-amber-400">
                                        {{ $snap['phase'] }}
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-400">
                                        ({{ $snap['label'] }} • {{ $snap['calendar_date'] }})
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    {{ $snap['reflection'] }}
                                </p>
                            </div>

                            <!-- 1-Click Live View Switchers for Presenter Demo -->
                            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    wire:click="showcaseJumpToView('map')"
                                    class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'map' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' }}"
                                >
                                    <span>🗺️</span>
                                    <span>16-Ward Map ({{ $snap['wards_reached'] }}/16)</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="showcaseJumpToView('churches', 'genmap')"
                                    class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' }}"
                                >
                                    <span>⛪</span>
                                    <span>Circles &amp; Gen Map (G1→G{{ $snap['deepest_generation'] }})</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="showcaseJumpToView('leaderboard')"
                                    class="px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' }}"
                                >
                                    <span>🏆</span>
                                    <span>Leaderboard</span>
                                </button>
                            </div>
                        </div>

                        <!-- 6 Key Real-Life Metrics Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 text-xs">
                            <!-- 1. Ward Saturation -->
                            <div class="p-3 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-1.5">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nagoya Wards</div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-base font-black text-emerald-400">{{ $snap['wards_reached'] }} / {{ $snap['total_wards'] }}</span>
                                    <span class="text-[11px] font-bold text-emerald-300">{{ $snap['saturation_percent'] }}%</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full" style="width: {{ max(6, $snap['saturation_percent']) }}%"></div>
                                </div>
                            </div>

                            <!-- 2. Oikos Spiritual Pipeline -->
                            <div class="p-3 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Spiritual Pipeline</div>
                                <div class="text-base font-black text-white">
                                    {{ $snap['green_believers_count'] }} <span class="text-xs font-bold text-emerald-400">Believers</span>
                                </div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1.5">
                                    <span class="text-amber-300 font-semibold">{{ $snap['yellow_seekers_count'] }} Yellow</span>
                                    <span>•</span>
                                    <span class="text-rose-300">{{ $snap['red_acquaintances_count'] }} Red</span>
                                </div>
                            </div>

                            <!-- 3. Japanese Baptism Cost Journey -->
                            <div class="p-3 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Water Baptisms</div>
                                <div class="text-base font-black text-sky-400">
                                    💧 {{ $snap['baptized_count'] }} <span class="text-xs font-bold text-slate-300">Baptized</span>
                                </div>
                                <div class="text-[10px] text-amber-300/90">
                                    ⏳ {{ $snap['counting_cost_count'] }} counting cost • {{ $snap['trained_411_count'] }} 411
                                </div>
                            </div>

                            <!-- 4. Generational Church Streams -->
                            <div class="p-3 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Acts 2 Churches</div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-base font-black text-indigo-300">⛪ {{ $snap['active_churches_count'] }} Active</span>
                                    <span class="px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-300 border border-indigo-700 text-[10px] font-black">G1→G{{ $snap['deepest_generation'] }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    G1:{{ $gens[1] }} • G2:{{ $gens[2] }} • G3:{{ $gens[3] }} • G4+:{{ $gens[4] }}
                                </div>
                            </div>

                            <!-- 5. Indigenous 5 Leadership Levels -->
                            <div class="p-3 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Japanese Leaders</div>
                                <div class="text-xs font-black text-purple-300 pt-0.5">
                                    L1:{{ $tiers['L1'] }} • L2:{{ $tiers['L2'] }} • L3:{{ $tiers['L3'] }}
                                </div>
                                <div class="text-[10px] text-amber-300 font-semibold">
                                    L4 Trainers: {{ $tiers['L4'] }} • L5 Cat: {{ $tiers['L5'] }}
                                </div>
                            </div>

                            <!-- 6. Real-Life Attrition & Care -->
                            <div class="p-3 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Real-Life Trials</div>
                                <div class="text-xs font-bold text-slate-200 pt-0.5">
                                    👣 {{ $snap['prayer_walks_count'] }} Prayer Walks
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    ⚠️ {{ $snap['dissolved_churches_count'] }} Dissolved • ❤️‍🩹 {{ $snap['cumulative_revitalizations'] }} Restored
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Movement Breakthrough Celebration Info Banner (Non-planner Tabs) -->
        @if($currentTab !== 'planner' && isset($activeCelebration) && $activeCelebration)
            <div class="mb-6">
                <x-movement-celebration-banner 
                    :celebration="$activeCelebration" 
                    :totalCelebrations="$totalCelebrations ?? 1"
                    :currentIndex="0"
                />
            </div>
        @endif

        @if($currentTab === 'planner')
            <!-- Action Queue Planner -->
            <livewire:action-queue-planner :key="'planner-'.$session?->id.'-'.$showcaseRevision" />

        @elseif($currentTab === 'churches')
            <!-- Tab 2: Acts 2 Church Circles & Visual Church Generation Map -->
            <div class="space-y-6">
                <!-- Subview Mode Navigation Switcher -->
                <div class="flex items-center justify-between gap-3 p-1.5 bg-slate-900/90 border border-slate-800 rounded-2xl">
                    <div class="flex items-center gap-1.5">
                        <button 
                            type="button" 
                            wire:click="setCirclesSubView('genmap')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer {{ $circlesSubView === 'genmap' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}"
                        >
                            <span>🌳</span>
                            <span>Visual Generation Map (Gen Map)</span>
                        </button>
                        <button 
                            type="button" 
                            wire:click="setCirclesSubView('inspector')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer {{ $circlesSubView === 'inspector' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}"
                        >
                            <span>🔍</span>
                            <span>Single Church Inspector (Acts 2)</span>
                        </button>
                    </div>

                    <span class="text-xs text-slate-400 hidden sm:inline-block pr-3">
                        Field 4 • Simple Gathering Streams
                    </span>
                </div>

                @if($circlesSubView === 'genmap')
                    <!-- Visual Church Generation Map -->
                    <livewire:church-generation-map :key="'church-gen-map-'.$session?->id.'-'.$showcaseRevision" />
                @else
                    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5 max-w-2xl">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>⛪</span>
                                    <span>Field 4: The Harvested Field</span>
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-950 text-indigo-300 border border-indigo-800 flex items-center gap-1 shadow-sm">
                                    <span>🌐</span>
                                    <span>City-Wide Scope • All 16 Wards</span>
                                </span>
                                @if($user?->homeWard)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800/90 text-slate-400 border border-slate-700">
                                        Your Base: {{ $user->homeWard->name }}
                                    </span>
                                @endif
                            </div>
                            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                                <span>Nagoya Acts 2 Church Circles</span>
                            </h2>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                <strong class="text-slate-200">Nagoya-Wide Network:</strong> This view catalogs all multiplying house churches across every district in Greater Nagoya, not just your local residence in <span class="text-indigo-400 font-semibold">{{ $user?->homeWard?->name ?? 'your home ward' }}</span>. A healthy church practices all 11 core Acts 2 elements.
                            </p>
                        </div>

                        <!-- Church Selector Tabs -->
                        @if(count($churches) > 0)
                            <div class="flex items-center gap-2 overflow-x-auto max-w-full pb-1">
                                @foreach($churches as $ch)
                                    <button 
                                        type="button"
                                        wire:click="selectChurch({{ $ch->id }})"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition shrink-0 cursor-pointer flex items-center gap-1.5 {{ $activeChurchId === $ch->id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}"
                                    >
                                        <span>G{{ $ch->generation }}</span>
                                        <span class="truncate max-w-[140px]">{{ $ch->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
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
                @endif
            </div>

        @elseif($currentTab === 'leaderboard')
            <!-- Multiplayer Leaderboard -->
            <livewire:multiplayer-leaderboard :key="'leaderboard-'.$session?->id.'-'.$showcaseRevision" />

        @elseif($currentTab === 'map')
            <!-- Interactive Nagoya Map Canvas -->
            <livewire:nagoya-map-canvas :key="'map-canvas-'.$session?->id.'-'.$showcaseRevision" />

        @elseif($currentTab === 'chat')
            <!-- Asynchronous Missionary Co-op Chat -->
            <livewire:game-chat :key="'chat-'.$session?->id.'-'.$showcaseRevision" />
        @endif
    </main>

    <!-- Modal: 7-Stage Real-Life Longitudinal Showcase Comparison Table -->
    @if($showShowcaseComparisonModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-in fade-in duration-200">
            <div class="bg-slate-900 border border-amber-500/40 rounded-3xl p-6 max-w-6xl w-full max-h-[90vh] overflow-y-auto shadow-2xl space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-800 pb-4">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                            <span>📊</span>
                            <span>Longitudinal Real-Life DMM Comparison (3 Months → 15 Years)</span>
                        </div>
                        <h3 class="text-lg font-black text-white tracking-tight mt-0.5">
                            Nagoya Movement Trajectory Across All 7 Real-Life Milestones
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Click any stage column button to load that exact time stage onto the live map and Church Generation Tree.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeShowcaseComparison"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold cursor-pointer"
                    >
                        ✕ Close
                    </button>
                </div>

                @if(count($showcaseMilestones) > 0)
                    <div class="overflow-x-auto rounded-2xl border border-slate-800">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-950 text-slate-300 border-b border-slate-800">
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-slate-400">Horizon</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-slate-400">Calendar</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-emerald-400">Wards Reached</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-amber-400"> Pipeline (U/R/Y/G)</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-sky-400">Baptized (Cost)</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-indigo-400">Churches (Gone)</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-purple-400">Gen (G1/G2/G3/G4+)</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-pink-400">Leaders (L1-L5)</th>
                                    <th class="p-3 font-black uppercase tracking-wider text-[10px] text-right">Inspect</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80 bg-slate-900/60">
                                @foreach($showcaseMilestones as $mKey => $mSnap)
                                    @php
                                        $mGens = $mSnap['churches_by_generation'] ?? [1 => 1, 2 => 0, 3 => 0, 4 => 0];
                                        $mTiers = $mSnap['leaders_by_tier'] ?? ['L1' => 1, 'L2' => 0, 'L3' => 0, 'L4' => 0, 'L5' => 0];
                                    @endphp
                                    <tr class="hover:bg-slate-800/50 transition {{ $activeShowcaseStage === $mKey ? 'bg-amber-500/10' : '' }}">
                                        <td class="p-3 font-black text-white whitespace-nowrap">
                                            {{ $mSnap['label'] }}
                                            <span class="text-[10px] font-normal text-slate-400 block">Month {{ $mSnap['months'] }}</span>
                                        </td>
                                        <td class="p-3 text-slate-300 whitespace-nowrap">{{ $mSnap['calendar_date'] }}</td>
                                        <td class="p-3 font-bold text-emerald-400 whitespace-nowrap">
                                            {{ $mSnap['wards_reached'] }}/16 ({{ $mSnap['saturation_percent'] }}%)
                                        </td>
                                        <td class="p-3 text-slate-300 whitespace-nowrap">
                                            {{ $mSnap['unreached_count'] }} / {{ $mSnap['red_acquaintances_count'] }} / <span class="text-amber-300 font-bold">{{ $mSnap['yellow_seekers_count'] }}</span> / <span class="text-emerald-400 font-bold">{{ $mSnap['green_believers_count'] }}</span>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span class="font-bold text-sky-400">💧 {{ $mSnap['baptized_count'] }}</span>
                                            <span class="text-[10px] text-amber-300">({{ $mSnap['counting_cost_count'] }} cost)</span>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span class="font-bold text-indigo-300">⛪ {{ $mSnap['active_churches_count'] }}</span>
                                            <span class="text-[10px] text-slate-400">({{ $mSnap['dissolved_churches_count'] }} diss.)</span>
                                        </td>
                                        <td class="p-3 text-slate-300 whitespace-nowrap">
                                            {{ $mGens[1] }} / {{ $mGens[2] }} / {{ $mGens[3] }} / {{ $mGens[4] }}
                                            <span class="px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-300 text-[10px] font-bold ml-1">G{{ $mSnap['deepest_generation'] }}</span>
                                        </td>
                                        <td class="p-3 text-slate-300 whitespace-nowrap font-mono text-[11px]">
                                            {{ $mTiers['L1'] }}/{{ $mTiers['L2'] }}/{{ $mTiers['L3'] }}/{{ $mTiers['L4'] }}/{{ $mTiers['L5'] }}
                                        </td>
                                        <td class="p-3 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                wire:click="loadShowcaseStage('{{ $mKey }}'); closeShowcaseComparison();"
                                                class="px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 border border-amber-500/40 text-[11px] font-bold transition cursor-pointer"
                                            >
                                                Load {{ $mSnap['label'] }} →
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Modal: Month In Review Summary -->
    <livewire:month-in-review-modal />

    <!-- Modal: Edit Player Profile (Name & Country of Origin) -->
    @if($showEditNameModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl relative space-y-4">
                <div>
                    <h3 class="text-base font-black text-white tracking-tight flex items-center gap-2">
                        <span>✏️</span>
                        <span>Customize Missionary Profile</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Update how your missionary handle and country of origin appear across the movement, leaderboard, and team co-op maps.
                    </p>
                </div>

                <!-- Live Demographic Avatar Preview -->
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-950/80 border border-slate-800 shadow-inner">
                    <x-person-avatar :nationality="$editingNationality" :gender="'male'" :age="$editingAge > 0 ? $editingAge : 30" size="md" :showBadges="true" class="shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-white truncate">
                            {{ $editingName !== '' ? $editingName : 'Missionary Name' }}
                        </div>
                        <div class="text-[11px] text-indigo-300 mt-0.5 flex items-center gap-1.5">
                            <span>{{ app(\App\Services\PersonAvatarService::class)->flagEmoji($editingNationality) }} {{ $editingNationality }}</span>
                            <span class="text-slate-500">•</span>
                            <span>{{ $editingAge > 0 ? $editingAge : 30 }} yrs ({{ app(\App\Services\PersonAvatarService::class)->ageGroupLabel($editingAge > 0 ? $editingAge : 30) }})</span>
                        </div>
                    </div>
                </div>

                <form wire:submit="savePlayerName" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Missionary / Player Handle
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

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Country of Origin
                        </label>
                        <x-searchable-country-select 
                            id="editingNationality" 
                            wire:model.live="editingNationality"
                            :value="$editingNationality"
                            class="{{ $errors->has('editingNationality') ? 'border-rose-500' : 'border-slate-800 focus:border-indigo-500' }}"
                        />
                        @error('editingNationality')
                            <p class="text-[11px] text-rose-400 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1 flex items-center justify-between">
                            <span>Age (Years)</span>
                            <span class="text-[10px] text-amber-400 font-medium">must be older than 18</span>
                        </label>
                        <input 
                            type="number" 
                            wire:model.live.debounce.300ms="editingAge" 
                            min="19"
                            max="120"
                            class="w-full bg-slate-950 border {{ $errors->has('editingAge') ? 'border-rose-500' : 'border-slate-800 focus:border-indigo-500' }} rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            placeholder="e.g. 24, 30, 45"
                        >
                        @error('editingAge')
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
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal: Missionary Candidate Screening & Home Ward Onboarding Flow -->
    @if($showOnboardingModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 bg-black/80 backdrop-blur-md overflow-hidden animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl max-w-3xl lg:max-w-4xl w-full shadow-2xl flex flex-col max-h-[85vh] sm:max-h-[90vh] overflow-hidden transition-colors duration-200">
                
                {{-- STAGE 1: Candidate Screening Questionnaire --}}
                @if($onboardingStage === 'screening')
                    <!-- Screening Header -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-800 uppercase tracking-wider">
                                        📋 Candidate Qualification Gate
                                    </span>
                                    <span class="text-[10px] sm:text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                        <span>🔒</span>
                                        <span>Field Clearance Required Prior to Deployment</span>
                                    </span>
                                </div>
                                <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                    Missionary Candidate Capability Screening
                                </h2>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                    Before stepping into Nagoya, evaluate your capability across <strong class="text-slate-900 dark:text-white">7 missionary dimensions</strong>: Cultural Connections, Calling, Language, Team Dynamics, Emotional Resilience, Dietary & Health, and Practical & Financial Exit.
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-black {{ count($screeningAnswers) === count($screeningQuestions) ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700' }}">
                                    {{ count($screeningAnswers) }} / {{ count($screeningQuestions) }} Answered
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 mt-3 overflow-hidden">
                            <div 
                                class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-1.5 rounded-full transition-all duration-300"
                                style="width: {{ count($screeningQuestions) > 0 ? (count($screeningAnswers) / count($screeningQuestions)) * 100 : 0 }}%"
                            ></div>
                        </div>
                    </div>

                    <!-- Screening Questions Body -->
                    <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-4 space-y-6 text-slate-900 dark:text-slate-100">
                        @foreach($screeningDimensions as $dimKey => $dim)
                            @php
                                $dimQuestions = collect($screeningQuestions)->where('dimension', $dimKey);
                            @endphp
                            <div class="space-y-4 p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
                                <!-- Dimension Category Banner -->
                                <div class="border-b border-slate-200 dark:border-slate-800/80 pb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">{{ $dim['icon'] }}</span>
                                        <div>
                                            <h3 class="text-sm font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                                <span>{{ $dim['name'] }}</span>
                                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">({{ $dim['name_ja'] }})</span>
                                            </h3>
                                            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">
                                                {{ $dim['description'] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions in this Dimension -->
                                <div class="space-y-4">
                                    @foreach($dimQuestions as $qId => $q)
                                        @php
                                            $selectedChoice = $screeningAnswers[$qId] ?? null;
                                        @endphp
                                        <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border {{ $selectedChoice ? 'border-indigo-300 dark:border-indigo-800 shadow-xs' : 'border-slate-200 dark:border-slate-800' }} space-y-2.5 transition">
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                                        {{ $q['category_label'] }}
                                                    </span>
                                                    @if($selectedChoice)
                                                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                            <span>✓ Choice Selected</span>
                                                        </span>
                                                    @else
                                                        <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                                            Pending Choice
                                                        </span>
                                                    @endif
                                                </div>
                                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-snug">
                                                    {{ $q['title'] }}
                                                </h4>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                                                    💡 Context: {{ $q['scenario'] }}
                                                </p>
                                            </div>

                                            <!-- Options (A, B, C) -->
                                            <div class="space-y-2 pt-1">
                                                @foreach($q['options'] as $option)
                                                    @php
                                                        $isSelected = ($selectedChoice === $option['key']);
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="recordScreeningAnswer('{{ $qId }}', '{{ $option['key'] }}')"
                                                        class="w-full text-left p-3 rounded-xl border text-xs transition cursor-pointer flex items-start gap-3 {{ $isSelected ? 'bg-indigo-50 dark:bg-indigo-950/80 border-indigo-500 text-slate-900 dark:text-white ring-2 ring-indigo-500/30 shadow-xs font-medium' : 'bg-slate-50/50 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-indigo-300 dark:hover:border-slate-700 hover:bg-slate-100/60 dark:hover:bg-slate-900/60' }}"
                                                    >
                                                        <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 text-[10px] font-black transition {{ $isSelected ? 'bg-indigo-600 text-white' : 'border border-slate-400 dark:border-slate-600 text-slate-500 dark:text-slate-400' }}">
                                                            {{ $option['key'] }}
                                                        </span>
                                                        <span class="leading-relaxed flex-1">
                                                            {{ $option['text'] }}
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Screening Footer -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 z-10 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-xs text-slate-600 dark:text-slate-400 text-center sm:text-left">
                            <span>Passing criteria: <strong class="text-slate-900 dark:text-white">70%+ Overall</strong> with <strong class="text-slate-900 dark:text-white">≥ 40% in each dimension</strong>.</span>
                            <span class="block text-[11px] {{ count($screeningAnswers) === count($screeningQuestions) ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ count($screeningAnswers) === count($screeningQuestions) ? '✓ All '.count($screeningQuestions).' scenario questions answered and ready for evaluation!' : 'Please complete all questions before submitting.' }}
                            </span>
                        </div>

                        <button 
                            type="button" 
                            wire:click="submitScreening"
                            wire:loading.attr="disabled"
                            @disabled(count($screeningAnswers) < count($screeningQuestions))
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg transition cursor-pointer flex items-center justify-center gap-2 {{ count($screeningAnswers) === count($screeningQuestions) ? 'bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 shadow-indigo-600/30' : 'bg-slate-400 dark:bg-slate-800 text-slate-600 dark:text-slate-500 cursor-not-allowed' }}"
                        >
                            <span wire:loading.remove wire:target="submitScreening">Submit Capability Assessment ➔</span>
                            <span wire:loading wire:target="submitScreening" class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span>Evaluating Candidate Readiness...</span>
                            </span>
                        </button>
                    </div>

                {{-- STAGE 2: Deployment Clearance Granted --}}
                @elseif($onboardingStage === 'qualified_clearance')
                    <!-- Clearance Header -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 uppercase tracking-wider">
                                🎖️ Deployment Clearance Granted
                            </span>
                            <span class="text-[10px] sm:text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                Candidate Approved for Nagoya Harvest Field
                            </span>
                        </div>
                        <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Congratulations! You Are Cleared for Deployment
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            You have demonstrated mature capability, cross-cultural humility, and gospel endurance. Your field credential has been officially recorded.
                        </p>
                    </div>

                    <!-- Clearance Body -->
                    <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-5 space-y-5 text-slate-900 dark:text-slate-100">
                        <!-- Score Card Banner -->
                        <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-indigo-500/10 to-purple-500/10 border border-emerald-500/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="space-y-1 text-center sm:text-left">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    Overall Capability Score
                                </span>
                                <div class="text-3xl font-black text-slate-900 dark:text-white flex items-center justify-center sm:justify-start gap-2">
                                    <span>{{ $screeningTotalScore }}%</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-600 text-white">READY FOR FIELD</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-300">
                                    Profile Archetype: <strong class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $screeningProfileTitle }}</strong>
                                </p>
                            </div>
                            <div class="text-4xl sm:text-5xl">
                                🗾✨
                            </div>
                        </div>

                        <!-- 5 Dimension Breakdown Progress Bars -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                Capability Breakdown by Dimension:
                            </h3>
                            <div class="space-y-2.5">
                                @foreach($screeningDimensions as $dimKey => $dim)
                                    @php
                                        $score = $screeningDimensionScores[$dimKey] ?? 0;
                                    @endphp
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ $dim['icon'] }}</span>
                                                <span>{{ $dim['name'] }}</span>
                                            </span>
                                            <span class="font-black text-emerald-600 dark:text-emerald-400">
                                                {{ $score }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                            <div 
                                                class="h-2 rounded-full {{ $score >= 70 ? 'bg-emerald-500' : ($score >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                                style="width: {{ $score }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Coaching Encouragement Note -->
                        @if(!empty($screeningCoaching))
                            <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 space-y-2">
                                <h4 class="text-xs font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                                    <span>🌱</span>
                                    <span>Mentorship & Continuous Growth Notes</span>
                                </h4>
                                <ul class="text-xs text-slate-700 dark:text-slate-300 space-y-1.5 list-disc list-inside">
                                    @foreach($screeningCoaching as $coachNote)
                                        <li class="leading-relaxed">{{ $coachNote }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- Clearance Footer -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 z-10 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-xs text-slate-600 dark:text-slate-400 text-center sm:text-left">
                            <span>Next Step: Select your home district residence in Nagoya to gain <strong class="text-emerald-600 dark:text-emerald-400">+25% trust</strong> and <strong class="text-emerald-600 dark:text-emerald-400">0 AP commute</strong>.</span>
                        </div>

                        <button 
                            type="button" 
                            wire:click="proceedToWardSelection"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 shadow-emerald-600/30 transition cursor-pointer"
                        >
                            Proceed to Choose Starting Home Base ➔
                        </button>
                    </div>

                {{-- STAGE 3: Gated Qualification / Coaching Feedback --}}
                @elseif($onboardingStage === 'gated_coaching')
                    <!-- Gated Header -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 uppercase tracking-wider">
                                ⚠️ Clearance On Hold • Mentorship Required
                            </span>
                            <span class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400">
                                Candidate Qualification Gate
                            </span>
                        </div>
                        <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Missionary Candidate Readiness Debrief
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Japan is a demanding cross-cultural harvest field. To protect candidates and neighborhood fruitfulness, candidates must reach <strong class="text-slate-900 dark:text-white">70%+ readiness</strong> and avoid critical red flags (≥ 40% in each dimension) before field deployment.
                        </p>
                    </div>

                    <!-- Gated Body -->
                    <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-5 space-y-5 text-slate-900 dark:text-slate-100">
                        <!-- Score Summary Banner -->
                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-700/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="space-y-1 text-center sm:text-left">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Current Evaluation Score
                                </span>
                                <div class="text-2xl sm:text-3xl font-black text-amber-700 dark:text-amber-300 flex items-center justify-center sm:justify-start gap-2">
                                    <span>{{ $screeningTotalScore }}%</span>
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">(Passing: 70%)</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    Coaching required to address identified vulnerabilities before entering Nagoya.
                                </p>
                            </div>
                            <div class="text-3xl sm:text-4xl">
                                🛡️
                            </div>
                        </div>

                        <!-- Critical Gaps / Coaching Notes -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                                <span>⚠️</span>
                                <span>Targeted Coaching Debrief by Dimension:</span>
                            </h3>

                            <div class="space-y-2.5">
                                @foreach($screeningDimensions as $dimKey => $dim)
                                    @php
                                        $score = $screeningDimensionScores[$dimKey] ?? 0;
                                        $coachNote = $screeningCoaching[$dimKey] ?? null;
                                        $isDeficit = ($score < 70);
                                    @endphp
                                    <div class="p-3.5 rounded-xl border {{ $score < 40 ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800' : ($score < 70 ? 'bg-amber-50/50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/60' : 'bg-slate-50 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800') }} space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ $dim['icon'] }}</span>
                                                <span>{{ $dim['name'] }}</span>
                                            </span>
                                            <span class="font-black {{ $score >= 70 ? 'text-emerald-600 dark:text-emerald-400' : ($score >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                                {{ $score }}%
                                            </span>
                                        </div>

                                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-1.5 rounded-full {{ $score >= 70 ? 'bg-emerald-500' : ($score >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                                style="width: {{ $score }}%"
                                            ></div>
                                        </div>

                                        @if($coachNote)
                                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed pt-1 border-t border-slate-200/60 dark:border-slate-800/60">
                                                <strong class="font-semibold text-slate-900 dark:text-white">Coaching Insight:</strong> {{ $coachNote }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Gated Footer -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 z-10 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-xs text-slate-600 dark:text-slate-400 text-center sm:text-left">
                            <span>Reflect on these missionary principles and retake the assessment when ready.</span>
                        </div>

                        <button 
                            type="button" 
                            wire:click="retakeScreening"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg bg-gradient-to-r from-amber-600 to-indigo-600 hover:from-amber-500 hover:to-indigo-500 shadow-indigo-600/30 transition cursor-pointer"
                        >
                            🔄 Review Insights & Retake Assessment
                        </button>
                    </div>

                {{-- STAGE 4: Choose Starting Home Ward --}}
                @elseif($onboardingStage === 'ward_selection')
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
                                        <span class="text-slate-400 dark:text-slate-600 mx-1">•</span>
                                        <span>Field Cleared: {{ $user?->screeningProfileBadge ?? 'Cleared' }}</span>
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

                {{-- STAGE 5: Game Host Introduction: Welcome to Nagoya & Chosen Ward --}}
                @elseif($onboardingStage === 'host_welcome')
                    @php
                        $activeWard = $selectedHomeWardId ? \App\Models\Ward::find($selectedHomeWardId) : ($user?->homeWard ?? \App\Models\Ward::first());
                    @endphp

                    <!-- Host Orientation Header -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10 flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 uppercase tracking-wider flex items-center gap-1">
                                    <span>🗾</span>
                                    <span>Game Host Field Orientation</span>
                                </span>
                                <span class="text-[10px] sm:text-xs font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                    <span>⛩️</span>
                                    <span>Welcome to Nagoya • 名古屋へようこそ</span>
                                </span>
                            </div>
                            <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2 flex-wrap">
                                <span>Welcome to Nagoya &amp; {{ $activeWard?->name ?? 'Your Chosen Ward' }}!</span>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">({{ $activeWard?->name_ja ?? '居住区' }})</span>
                            </h2>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Vital apostolic orientation from your Nagoya movement host on Japanese culture, language reality, and foundational strategy.
                            </p>
                        </div>

                        @if($user?->has_seen_host_intro)
                            <button 
                                type="button" 
                                wire:click="closeOnboardingModal"
                                class="p-1.5 sm:p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                title="Close Orientation"
                            >
                                ✕
                            </button>
                        @endif
                    </div>

                    <!-- Host Orientation Body (Scrollable) -->
                    <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-5 space-y-5 text-slate-900 dark:text-slate-100">
                        
                        <!-- Host Greeting Banner with Host Avatar & Ward Specs -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-indigo-500/10 to-purple-500/10 border border-emerald-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl overflow-hidden shrink-0 shadow-lg border-2 border-emerald-500/40 bg-white dark:bg-slate-800 flex items-center justify-center">
                                    {!! app(\App\Services\PersonAvatarService::class)->generateSvg('male', 52, 'Japanese', 'elder', 'apostolic', 64, true, 'game-host-kenjiro') !!}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black text-slate-900 dark:text-white">Kenjiro Sato (佐藤 健次郎)</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            Movement Host &amp; Field Director
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                                        "Peace to you, brother! As your movement host, I welcome you with open arms to the 2.3 million souls of Nagoya. You have chosen <strong class="text-emerald-600 dark:text-emerald-400">{{ $activeWard?->name }} ({{ $activeWard?->name_ja }})</strong> as your starting home base."
                                    </p>
                                </div>
                            </div>

                            <!-- Ward Summary Pill -->
                            @if($activeWard)
                                <div class="shrink-0 w-full md:w-auto p-3 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-center md:text-right space-y-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Chosen Home Ward</span>
                                    <div class="text-sm font-black text-slate-900 dark:text-white flex items-center justify-center md:justify-end gap-1.5">
                                        <span>🏡</span>
                                        <span>{{ $activeWard->name }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ number_format($activeWard->population) }} residents • Openness {{ $activeWard->spiritual_openness }}/100
                                    </div>
                                    <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                        +25% Neighbor Trust • 0 AP Commute
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- 3 Core Orientation Pillars -->
                        <div class="space-y-4">
                            
                            <!-- Pillar 1: Language Reality -->
                            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">🗣️</span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                            <span>What to Expect: The Japanese Language Reality</span>
                                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">(言葉の壁と心への架け橋)</span>
                                        </h3>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                            Language is the highway to the heart
                                        </span>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-700 dark:text-slate-300 space-y-2 leading-relaxed pt-1">
                                    <p>
                                        In Nagoya, English is only spoken by a tiny fraction of university students and returnees. If you rely on English or translation apps, you will remain insulated in an expat bubble and never reach ordinary Japanese families, company employees, or community elders.
                                    </p>
                                    <p>
                                        Japanese is an intricate, high-context language where nuance, politeness (<strong class="text-slate-900 dark:text-white">keigo 敬語</strong>), and unspoken consensus (<strong class="text-slate-900 dark:text-white">kuuki o yomu 空気を読む</strong> — "reading the air") govern daily interaction. Expect a steep learning curve. The only way forward is the posture of a humble child (<strong class="text-slate-900 dark:text-white">kodomo no kokoro 子どもの心</strong>): embrace making 50 clumsy mistakes every day with a smile. The Japanese deeply respect anyone who demonstrates genuine humility to learn their language.
                                    </p>
                                </div>
                            </div>

                            <!-- Pillar 2: Group Culture & Relational Trust -->
                            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">⛩️</span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                            <span>Group Culture &amp; Relational-Based Decisions</span>
                                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">(集団意識と信頼構築の法則)</span>
                                        </h3>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                            No Western Individualism • Deep Trust (Shinrai) Must Precede Decisions
                                        </span>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-700 dark:text-slate-300 space-y-2 leading-relaxed pt-1">
                                    <p>
                                        Japan is fundamentally a <strong class="text-slate-900 dark:text-white">group culture (shūdan-ishiki 集団意識)</strong> where communal harmony (<strong class="text-slate-900 dark:text-white">wa 和</strong>) is supreme. Western-style individualism does not exist here. A person’s identity is interwoven with their household (<strong class="text-slate-900 dark:text-white">ie 家</strong>), workplace company, school alumni, and local neighborhood association (<strong class="text-slate-900 dark:text-white">chōnaikai 町内会</strong>).
                                    </p>
                                    <p>
                                        Because identity is collective, <strong class="text-amber-600 dark:text-amber-400 font-bold">all major life decisions are relational-based</strong>. Japanese people will not make an impulsive spiritual decision in isolation. If an individual acts alone, they fear disrupting family peace or facing social exclusion (<strong class="text-slate-900 dark:text-white">murahachibu 村八分</strong>).
                                    </p>
                                    <p>
                                        Therefore, <strong class="text-emerald-600 dark:text-emerald-400 font-bold">you must build deep, authentic trust (shinrai 信頼) before they will make any spiritual decisions</strong>. Rushing or pressuring someone for an immediate "decision" triggers intense awkwardness and social withdrawal (<strong class="text-slate-900 dark:text-white">enryo 遠慮</strong>). Be patient, share meals, listen attentively (<strong class="text-slate-900 dark:text-white">keichō 傾聴</strong>), and weave yourself into the fabric of your ward. When trust is firmly established, entire households (<strong class="text-slate-900 dark:text-white">oikos</strong>) can enter the Kingdom together.
                                    </p>
                                </div>
                            </div>

                            <!-- Pillar 3: Recommended Strategy & 70% AP Allocation -->
                            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-indigo-50/80 to-emerald-50/80 dark:from-indigo-950/40 dark:to-emerald-950/40 border-2 border-indigo-300 dark:border-indigo-800 space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">⚡</span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                                            <span>The Best Strategy: First-Level NPL Training + Japanese Language School</span>
                                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">(最善の戦略基盤)</span>
                                        </h3>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                            Proven Movement Pathway: 70% AP Allocation in Months 1 &amp; 2
                                        </span>
                                    </div>
                                </div>

                                <div class="text-xs text-slate-700 dark:text-slate-300 space-y-2 leading-relaxed">
                                    <p>
                                        My strongest counsel as your game host is to establish a solid foundation immediately upon arrival. Do not attempt unguided pioneering alone. Instead, pursue this dual foundation:
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                        <div class="p-3 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-indigo-200 dark:border-indigo-800/80 space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5 text-xs">
                                                    <span>1️⃣</span>
                                                    <span>NPL First-Level Training</span>
                                                </span>
                                                <span class="text-[10px] font-black px-2 py-0.5 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                                    10% AP
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-snug">
                                                Receive simple, reproducible training from seasoned No Place Left co-workers in Nagoya (L1: Seed Sower, 411 foundational tool: 4 questions, 1 sheet of paper, 1 hour; 15-second testimony; 3 Circles gospel).
                                            </p>
                                        </div>

                                        <div class="p-3 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-emerald-200 dark:border-emerald-800/80 space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 text-xs">
                                                    <span>2️⃣</span>
                                                    <span>Japanese Language School</span>
                                                </span>
                                                <span class="text-[10px] font-black px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400">
                                                    60% AP
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-snug">
                                                Right away commit to start Japanese language school (日本語学校). Intensive classroom study paired with street immersion gives you the linguistic tools and cultural standing to make multiplying disciples.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- The 70% AP Rule Callout Box -->
                                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-300 dark:border-emerald-800 space-y-2 mt-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                                                 <span>⏱️</span>
                                                 <span>70% AP Bandwidth Investment Rule (First 4 Months):</span>
                                             </span>
                                             <span class="font-black text-emerald-600 dark:text-emerald-400 text-xs">
                                                 Months 1 to 4
                                             </span>
                                         </div>
                                         <div class="grid grid-cols-3 gap-2 text-center text-[11px]">
                                             <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800">
                                                 <div class="font-black text-indigo-600 dark:text-indigo-400 text-sm">60% AP</div>
                                                 <div class="text-[10px] text-slate-600 dark:text-slate-400">Language School ({{ (int) round(($user?->weekly_action_points ?? 20) * 0.60) }} AP/wk)</div>
                                             </div>
                                             <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800">
                                                 <div class="font-black text-emerald-600 dark:text-emerald-400 text-sm">10% AP</div>
                                                 <div class="text-[10px] text-slate-600 dark:text-slate-400">First-Level NPL ({{ (int) round(($user?->weekly_action_points ?? 20) * 0.10) }} AP/wk)</div>
                                             </div>
                                             <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800">
                                                 <div class="font-black text-purple-600 dark:text-purple-400 text-sm">30% AP</div>
                                                 <div class="text-[10px] text-slate-600 dark:text-slate-400">Ward Ministry ({{ (int) round(($user?->weekly_action_points ?? 20) * 0.30) }} AP/wk)</div>
                                             </div>
                                         </div>
                                         <p class="text-[11px] text-slate-600 dark:text-slate-400 italic">
                                             💡 Choosing this strategy reserves 70% of your AP during Months 1 to 4. After Month 4, you graduate with permanent cultural trust bonuses, First-Level certification, and 100% of your AP bandwidth is restored!
                                         </p>
                                         <p class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold pt-1 border-t border-slate-200 dark:border-slate-800">
                                             ⚠️ <strong>Bypassing Strategy Handicap:</strong> If you proceed independently without foundational training, you will incur the <strong>Language &amp; Cultural Barrier Handicap</strong>, receiving only <strong>14 AP / week</strong> (-6 AP effort friction penalty) because every interaction requires extra energy without language tools.
                                         </p>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Player Strategy Status (if already enrolled or handicapped) -->
                        @if($user?->committed_to_language_training)
                            <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🎓</span>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            Currently Enrolled in Recommended Strategy
                                        </div>
                                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400">
                                            @if($user->isLanguageTrainingActive)
                                                Month {{ $user->languageTrainingCurrentMonthNumber }} of 4 Active • {{ $user->languageTrainingMonthsRemaining }} month(s) remaining until full AP release
                                            @else
                                                Foundation Completed • 100% AP Released
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-600 text-white">
                                    Active Commitment
                                </span>
                            </div>
                        @elseif($user?->hasLanguageBarrierHandicap)
                            <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">⚠️</span>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            Language &amp; Cultural Barrier Handicap Active
                                        </div>
                                        <div class="text-[11px] text-amber-600 dark:text-amber-400">
                                            Receiving {{ $user->availableFieldWeeklyAp }} AP/week (-{{ \App\Models\User::LANGUAGE_BARRIER_AP_PENALTY }} AP effort friction). You can enroll below at any time to begin your 2-month foundation and remove this penalty!
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-600 text-white">
                                    Handicap Active
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Host Orientation Footer: Strategic Choices -->
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 z-10 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-xs text-slate-600 dark:text-slate-400 text-center sm:text-left">
                            <span>How would you like to begin your mission in <strong class="text-slate-900 dark:text-white">{{ $activeWard?->name ?? 'Nagoya' }}</strong>?</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button 
                                type="button" 
                                wire:click="chooseIndependentStrategy"
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <span>Proceed Independently</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800">14 AP • Handicap</span>
                            </button>

                            <button 
                                type="button" 
                                wire:click="chooseRecommendedLanguageStrategy"
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 shadow-emerald-600/30 transition cursor-pointer flex items-center justify-center gap-2"
                            >
                                <span wire:loading.remove wire:target="chooseRecommendedLanguageStrategy">⭐ Commit to Language &amp; L1 Training (70% AP) ➔</span>
                                <span wire:loading wire:target="chooseRecommendedLanguageStrategy" class="flex items-center gap-1.5">
                                    <span class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    <span>Enrolling...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Standalone Modal: Missionary Capability Profile & Scorecard (Accessible Anytime) -->
    @if($showScreeningScorecardModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 md:p-6 bg-black/80 backdrop-blur-md overflow-hidden animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh] sm:max-h-[90vh] overflow-hidden transition-colors duration-200">
                <!-- Scorecard Header -->
                <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 z-10 flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-800 uppercase tracking-wider">
                                🎯 Missionary Capability Profile
                            </span>
                            <span class="text-[10px] sm:text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                Field Clearance Status: Verified
                            </span>
                        </div>
                        <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $user?->name ?? 'Candidate' }}'s Capability Scorecard
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                            Evaluated across 7 core dimensions for church planting in Nagoya, Japan.
                        </p>
                    </div>

                    <button 
                        type="button" 
                        wire:click="closeScreeningScorecard"
                        class="p-1.5 sm:p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Close Scorecard"
                    >
                        ✕
                    </button>
                </div>

                <!-- Scorecard Body -->
                <div class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-4 space-y-4 text-slate-900 dark:text-slate-100">
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-emerald-500/10 border border-indigo-500/30 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                Certified Profile Archetype
                            </span>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                {{ $screeningProfileTitle ?: ($user?->screening_profile_title ?: 'Cross-Cultural Pioneer') }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Overall Score: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $screeningTotalScore ?: ($user?->screening_score ?? 85) }}%</strong>
                            </p>
                        </div>
                        <div class="text-3xl">
                            🗾🎖️
                        </div>
                    </div>

                    <!-- Dimension Meters -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Dimension Mastery:
                        </h4>

                        <div class="space-y-2.5">
                            @foreach($screeningDimensions as $dimKey => $dim)
                                @php
                                    $score = $screeningDimensionScores[$dimKey] ?? ($user?->screening_dimension_scores[$dimKey] ?? 80);
                                @endphp
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $dim['icon'] }}</span>
                                            <span>{{ $dim['name'] }}</span>
                                        </span>
                                        <span class="font-black text-emerald-600 dark:text-emerald-400">
                                            {{ $score }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                        <div 
                                            class="h-2 rounded-full {{ $score >= 70 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                            style="width: {{ $score }}%"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Scorecard Footer -->
                <div class="shrink-0 px-4 sm:px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 z-10 flex items-center justify-between gap-3">
                    <button 
                        type="button" 
                        wire:click="closeScreeningScorecard"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
                    >
                        Close
                    </button>

                    <button 
                        type="button" 
                        wire:click="openOnboardingModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition cursor-pointer shadow-sm"
                    >
                        🔄 Retake Screening Assessment
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
