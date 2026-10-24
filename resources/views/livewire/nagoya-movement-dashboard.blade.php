<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand & Game Title -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-emerald-500 flex items-center justify-center text-xl shadow-lg shadow-indigo-500/20">
                    🌾
                </div>
                <div>
                    <h1 class="text-sm font-black text-white tracking-tight leading-none flex items-center gap-1.5">
                        <span>Nagoya Oikos</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-400 border border-indigo-800 uppercase">Four Fields</span>
                    </h1>
                    <span class="text-[11px] text-slate-400 font-medium">Multiplayer Movement Simulation • No Place Left</span>
                </div>
            </div>

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
            </nav>

            <!-- User Status & Notification Bell -->
            <div class="flex items-center gap-3">
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
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-sm">
                        {{ $user?->initials() ?? 'MC' }}
                    </div>
                    <div class="hidden sm:block text-left text-xs">
                        <div class="font-bold text-white leading-tight">{{ $user?->name ?? 'Player' }}</div>
                        <div class="text-[10px] text-emerald-400 font-semibold">{{ number_format($user?->compositeScore ?? 0) }} pts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Tab Navigation Bar -->
        <div class="flex md:hidden items-center justify-around px-2 py-2 border-t border-slate-800/80 text-[11px] font-semibold bg-slate-950">
            <button wire:click="setTab('planner')" class="px-2 py-1 rounded-lg {{ $currentTab === 'planner' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">⚡ HQ</button>
            <button wire:click="setTab('churches')" class="px-2 py-1 rounded-lg {{ $currentTab === 'churches' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">⛪ Circles</button>
            <button wire:click="setTab('leaderboard')" class="px-2 py-1 rounded-lg {{ $currentTab === 'leaderboard' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">🏆 Ladder</button>
            <button wire:click="setTab('map')" class="px-2 py-1 rounded-lg {{ $currentTab === 'map' ? 'bg-indigo-600 text-white' : 'text-slate-400' }}">🗺️ Map</button>
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
</div>
