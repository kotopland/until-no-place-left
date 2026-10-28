<div class="space-y-6">
    <!-- City-Wide Co-op Goal: Reaching Nagoya Together -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/70 to-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <span class="text-xs uppercase tracking-widest font-extrabold text-amber-400 flex items-center gap-1.5">
                    <span>👑</span>
                    <span>City-Wide Co-op Goal: No Place Left</span>
                </span>
                <h3 class="text-xl font-black text-white mt-0.5">Reaching Nagoya Together</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Every player competes for depth while cooperating to saturate all 16 Nagoya wards with healthy multiplying churches.
                </p>
            </div>

            <!-- Saturation Stats Counter & Session Mode Status -->
            <div class="flex flex-wrap items-center gap-3">
                <div 
                    class="px-3.5 py-2 rounded-2xl border text-xs font-bold flex items-center gap-2 select-none {{ $session?->isCoopMode ? 'bg-indigo-950/80 border-indigo-500/50 text-indigo-300' : 'bg-slate-950/80 border-slate-700/60 text-slate-400' }}"
                    title="Gameplay mode configured by administrator via artisan command (game:mode)"
                >
                    <span class="text-sm">{{ $session?->isCoopMode ? '🤝' : '🌱' }}</span>
                    <span class="text-left">
                        <span class="block text-[11px] leading-tight font-black text-white">{{ $session?->isCoopMode ? 'Team Co-op Mode' : 'Solo Pioneer Mode' }}</span>
                        <span class="block text-[9px] font-medium text-slate-400 leading-none mt-0.5">{{ $session?->isCoopMode ? '3 AI Co-laborers Playing' : 'Single Player Pioneer' }}</span>
                    </span>
                    <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded {{ $session?->isCoopMode ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400' }}">
                        {{ $session?->isCoopMode ? 'Active' : 'Solo' }}
                    </span>
                </div>

                <div class="px-3.5 py-2 rounded-2xl bg-slate-950/70 border border-slate-800 text-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Wards Reached</span>
                    <span class="text-base font-extrabold text-white">{{ $reachedWardsCount }} / {{ $totalWards }}</span>
                </div>
                <div class="px-3.5 py-2 rounded-2xl bg-slate-950/70 border border-slate-800 text-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Active Believers</span>
                    <span class="text-base font-extrabold text-emerald-400">{{ $totalBelievers }}</span>
                </div>
                <div class="px-3.5 py-2 rounded-2xl bg-slate-950/70 border border-slate-800 text-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Deepest Stream</span>
                    <span class="text-base font-extrabold text-purple-400">G{{ $deepestGeneration }}</span>
                </div>
            </div>
        </div>

        <!-- Visual Saturation Progress Bar -->
        <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-slate-300 font-medium">Nagoya Gospel Saturation Progress</span>
                <span class="font-extrabold text-amber-400">{{ $citySaturationPercent }}%</span>
            </div>
            <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden p-0.5 border border-slate-700/50">
                <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-400 rounded-full transition-all duration-500" style="width: {{ max(5, $citySaturationPercent) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Dual Leaderboards & Live Multiplayer Stream Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Leaderboard Table (lg:col-span-8) -->
        <div class="lg:col-span-8 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col">
            <!-- Tab Switcher -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                <div class="flex items-center gap-2 p-1 bg-slate-950 rounded-2xl border border-slate-800">
                    <button 
                        type="button"
                        wire:click="setTab('generational')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'generational' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}"
                    >
                        🌳 Generational Depth Ladder
                    </button>
                    <button 
                        type="button"
                        wire:click="setTab('coop')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $activeTab === 'coop' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}"
                    >
                        🤝 1 Cor 3:6 Co-op Sower Assists
                    </button>
                </div>

                <span class="text-xs text-slate-500 hidden sm:inline">Top Movement Catalysts</span>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto flex-1">
                @if($activeTab === 'generational')
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800/80 pb-2">
                                <th class="py-2.5 px-3">Rank</th>
                                <th class="py-2.5 px-3">Player / Catalyst</th>
                                <th class="py-2.5 px-3 text-center">Churches Led</th>
                                <th class="py-2.5 px-3 text-center">Disciples Multiplied</th>
                                <th class="py-2.5 px-3 text-right">Generational Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @forelse($generationalLeaders as $index => $leader)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-3">
                                        @if($index === 0)
                                            <span class="text-lg">🥇</span>
                                        @elseif($index === 1)
                                            <span class="text-lg">🥈</span>
                                        @elseif($index === 2)
                                            <span class="text-lg">🥉</span>
                                        @else
                                            <span class="font-bold text-slate-500 pl-1">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-sm">
                                                {{ $leader->initials() }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-white flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ $leader->name }}</span>
                                                    @if($leader->is_ai_colaborer)
                                                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold bg-indigo-950 text-indigo-300 border border-indigo-700/60 shadow-xs" title="{{ $leader->ai_strategy_notes }}">
                                                            {{ $leader->archetypeBadgeLabel }}
                                                        </span>
                                                    @elseif($leader->isScoreDecliningFromInactivity)
                                                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold bg-rose-950 text-rose-300 border border-rose-700/60 shadow-xs" title="{{ $leader->inactivityStatusLabel }}">
                                                            📉 -5%/mo ({{ $leader->consecutive_inactive_months }}mo inactive)
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-slate-400">
                                                    {{ $leader->homeWard?->name ? 'Base: '.$leader->homeWard->name.' • ' : '' }}Bandwidth: {{ $leader->action_points }} AP
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold text-slate-300">
                                        {{ $leader->led_churches_count }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold text-indigo-400">
                                        {{ $leader->discipled_npcs_count }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-emerald-400 text-sm">
                                        {{ number_format($leader->total_generational_score) }} pts
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500">No players recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800/80 pb-2">
                                <th class="py-2.5 px-3">Rank</th>
                                <th class="py-2.5 px-3">Co-Laborer</th>
                                <th class="py-2.5 px-3 text-center">Biblical Synergy Motif</th>
                                <th class="py-2.5 px-3 text-right">Synergy Assist Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @forelse($coopLeaders as $index => $leader)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-3">
                                        @if($index === 0)
                                            <span class="text-lg">🥇</span>
                                        @elseif($index === 1)
                                            <span class="text-lg">🥈</span>
                                        @elseif($index === 2)
                                            <span class="text-lg">🥉</span>
                                        @else
                                            <span class="font-bold text-slate-500 pl-1">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-600 to-teal-600 flex items-center justify-center font-bold text-white text-xs shadow-sm">
                                                {{ $leader->initials() }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-white flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ $leader->name }}</span>
                                                    @if($leader->is_ai_colaborer)
                                                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold bg-indigo-950 text-indigo-300 border border-indigo-700/60 shadow-xs">
                                                            {{ $leader->archetypeBadgeLabel }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-slate-400">Combined: {{ $leader->compositeScore }} pts</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-950 text-indigo-300 border border-indigo-800">
                                            Paul & Apollos (1 Cor 3:6)
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-amber-400 text-sm">
                                        +{{ number_format($leader->coop_synergy_score) }} pts
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500">No collaborative touches logged yet. Water each other's seeds!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Recent Multiplayer Live Stream (lg:col-span-4) -->
        <div class="lg:col-span-4 bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Multiplayer Live Stream</span>
                </h3>
                <span class="text-[11px] text-slate-500">Real-Time</span>
            </div>

            <div class="space-y-3 flex-1 overflow-y-auto max-h-[460px] pr-1 custom-scrollbar text-xs">
                @forelse($recentActivity as $act)
                    <div class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <span class="font-bold text-white">{{ $act->user?->name ?? 'Missionary' }}</span>
                            <span class="text-[10px] text-slate-400">{{ $act->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            {{ $act->notes ?? 'Engaged resident in relational ministry.' }}
                        </p>
                        <div class="mt-2 flex items-center justify-between text-[10px] pt-1.5 border-t border-slate-800/60">
                            <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-medium">
                                {{ $act->contribution_role?->label() ?? 'Waterer' }}
                            </span>
                            @if($act->points_awarded > 0)
                                <span class="font-bold text-emerald-400">+{{ $act->points_awarded }} pts</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-slate-500 text-xs">
                        No recent multiplayer activity recorded.<br>Queue your relational actions now!
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
