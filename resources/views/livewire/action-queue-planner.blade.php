<div class="space-y-6" wire:poll.60s="touchHeartbeat" x-data="{ mobileStep: @entangle('mobileStep') }">
    <!-- Welcome Guide Banner with Official Logo -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800/80 p-4 shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <img 
                src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                alt="Nagoya Oikos Logo" 
                class="w-12 h-12 object-contain rounded-xl border border-indigo-500/30 shadow-md shrink-0"
            >
            <div>
                <h3 class="text-xs font-bold text-white flex items-center gap-1.5">
                    <span>New to Nagoya Oikos?</span>
                    <span class="px-1.5 py-0.2 rounded bg-indigo-900/60 text-indigo-300 text-[10px] font-semibold border border-indigo-700">Story & Field Guide</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Explore the biblical narrative, the 5 Fields of CPM multiplication, and the rules of relational church planting in Japan.
                </p>
            </div>
        </div>

        <a 
            href="{{ route('welcome') }}" 
            class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600/80 hover:bg-indigo-600 text-white border border-indigo-500/40 transition shrink-0 flex items-center gap-1.5 cursor-pointer self-start sm:self-center shadow"
        >
            <span>📖</span>
            <span>Read Story & Rules</span>
        </a>
    </div>

    <!-- Header: Time & Energy Bandwidth Status Bar -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/60 to-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xl">🗾</span>
                    <h2 class="text-lg font-bold text-white tracking-tight">Nagoya Relational Action HQ</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-950 text-indigo-300 border border-indigo-700">
                        {{ $session?->monthLabel ?? 'Month 1' }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/80 text-emerald-300 border border-emerald-700 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Day {{ $player?->current_game_day ?? 1 }} / 30</span>
                    </span>
                    @if($player?->isOnline)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-cyan-950 text-cyan-300 border border-cyan-800">
                            🟢 Online (10m Ticks Active)
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Relational evangelization takes steady faithfulness. 1 Game Day processes every 10 min while online (max 30 days/day). Remaining days resolve at midnight!
                </p>
            </div>

            <!-- AP Bandwidth Meter, Daily Limit & Digital Countdown Clock -->
            <div class="flex flex-wrap items-center gap-4 bg-slate-950/90 px-4 py-3 rounded-2xl border border-slate-800 shadow-xl">
                <!-- Action Points Bandwidth -->
                <div class="pr-2">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-400 font-medium">Bandwidth AP:</span>
                        <span class="font-bold text-white ml-2">{{ $player?->action_points ?? 0 }} / {{ $player?->max_action_points ?? 12 }} AP</span>
                    </div>
                    <div class="w-28 h-2 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-300" style="width: {{ $player?->remainingBandwidthPercent ?? 0 }}%"></div>
                    </div>
                </div>

                <!-- Daily Real-Life Play Cap (30 Days Max) -->
                <div class="border-l border-slate-800 pl-4 pr-2">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-400 font-medium">Played Today:</span>
                        <span class="font-bold {{ ($player?->days_played_today ?? 0) >= 30 ? 'text-amber-400' : 'text-slate-200' }} ml-2">
                            {{ $player?->days_played_today ?? 0 }} / 30 Days
                        </span>
                    </div>
                    <div class="w-28 h-2 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full {{ ($player?->days_played_today ?? 0) >= 30 ? 'bg-amber-400 shadow-sm shadow-amber-400' : 'bg-cyan-500' }} rounded-full transition-all duration-300" style="width: {{ min(100, round((($player?->days_played_today ?? 0) / 30) * 100)) }}%"></div>
                    </div>
                    <span class="text-[9px] text-slate-500 block mt-0.5">New month every night</span>
                </div>

                <!-- Digital Clock Visualizer (10m Countdown) -->
                <div 
                    wire:key="digital-clock-{{ $player?->current_game_day }}-{{ $player?->days_processed_this_month }}-{{ $player?->days_played_today }}"
                    x-data="digitalClock({{ max(0, $player?->secondsUntilNextDailyTick ?? 600) }}, '{{ $player?->next_daily_tick_at?->toIso8601String() }}', {{ (!($player?->hasReachedDailyPlayLimit ?? false) && ($player?->days_processed_this_month ?? 0) < 30) ? 'true' : 'false' }})"
                    x-init="init()"
                    class="border-l border-slate-800 pl-4 flex items-center gap-3"
                >
                    <div>
                        <div class="text-[9px] uppercase font-bold tracking-wider mb-1 flex items-center gap-1.5 {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'text-amber-400' : 'text-slate-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'bg-amber-400' : 'bg-emerald-400 animate-pulse' }}"></span>
                            <span>{{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'Night Rest • 30d Cap' : (($player?->days_processed_this_month ?? 0) >= 30 ? 'Month Done' : 'Auto Day Tick') }}</span>
                        </div>
                        
                        <!-- Digital LED Clock Face -->
                        <div class="bg-black/95 border border-slate-800/90 rounded-xl px-2.5 py-1 flex items-center justify-center shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)]">
                            @if($player?->hasReachedDailyPlayLimit ?? false)
                                <div class="font-mono text-base font-black tracking-widest text-amber-400 drop-shadow-[0_0_6px_rgba(245,158,11,0.6)]">
                                    <span>30</span><span class="font-bold">:</span><span>30</span>
                                </div>
                            @elseif(($player?->days_processed_this_month ?? 0) >= 30)
                                <div class="font-mono text-base font-black tracking-widest text-indigo-400 drop-shadow-[0_0_6px_rgba(129,140,248,0.6)]">
                                    <span>MONTH</span>
                                </div>
                            @else
                                <div class="font-mono text-base font-black tracking-widest text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.7)] flex items-center">
                                    <span class="tabular-nums" x-text="minutes">09</span>
                                    <span class="font-bold animate-pulse text-emerald-300">:</span>
                                    <span class="tabular-nums" x-text="seconds">59</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Turn Advance / Month Advance Button -->
                    <div class="pt-3.5">
                        @if(($player?->days_processed_this_month ?? 0) >= 30)
                            <button 
                                type="button" 
                                wire:click="advanceMonth"
                                wire:loading.attr="disabled"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-md cursor-pointer bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white border border-indigo-400/40 shadow-indigo-950/40 hover:scale-[1.02] active:scale-[0.98]"
                                title="Advance to Month {{ ($player?->current_game_month ?? 1) + 1 }}"
                            >
                                <span wire:loading.remove wire:target="advanceMonth">🚀</span>
                                <span wire:loading wire:target="advanceMonth" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span wire:loading.remove wire:target="advanceMonth">
                                    Advance to Month {{ ($player?->current_game_month ?? 1) + 1 }}
                                </span>
                                <span wire:loading wire:target="advanceMonth">Advancing Month...</span>
                            </button>
                        @else
                            <button 
                                type="button" 
                                wire:click="advanceDay(true)"
                                wire:loading.attr="disabled"
                                @disabled($player?->hasReachedDailyPlayLimit ?? false)
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-md cursor-pointer {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'bg-slate-900 text-slate-500 border border-slate-800 cursor-not-allowed' : 'bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 text-white border border-emerald-400/40 shadow-emerald-950/40 hover:scale-[1.02] active:scale-[0.98]' }}"
                                title="{{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'Daily limit of 30 days reached. Next month starts tonight!' : 'Manually advance to the next game day tick now' }}"
                            >
                                <span wire:loading.remove wire:target="advanceDay">⏭️</span>
                                <span wire:loading wire:target="advanceDay" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span wire:loading.remove wire:target="advanceDay">
                                    @if($player?->hasReachedDailyPlayLimit ?? false)
                                        Daily Cap (30/30)
                                    @else
                                        Turn to Day {{ min(30, ($player?->current_game_day ?? 1) + 1) }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="advanceDay">Advancing...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Step Funnel Navigation Bar (Visible only on mobile/tablet, hidden on lg screens) -->
    <div class="block lg:hidden bg-slate-900/90 border border-slate-800 rounded-2xl p-2 shadow-lg mb-2">
        <div class="flex items-center justify-between gap-1.5">
            <!-- Step 1 Button: Ward -->
            <button 
                type="button" 
                @click="mobileStep = 1; $wire.setMobileStep(1)"
                class="flex-1 py-2 px-1.5 rounded-xl text-center transition-all flex flex-col items-center justify-center gap-0.5 cursor-pointer"
                :class="mobileStep === 1 ? 'bg-indigo-600 text-white shadow-md font-bold ring-1 ring-indigo-400' : 'bg-slate-800/60 text-slate-400 hover:text-white'"
            >
                <span class="text-[9px] uppercase tracking-wider flex items-center gap-1 opacity-80">
                    <span class="w-3.5 h-3.5 rounded-full bg-white/20 inline-flex items-center justify-center text-[8px] font-bold">1</span>
                    <span>Ward</span>
                </span>
                <span class="text-xs font-bold truncate max-w-[95px]">
                    {{ $selectedWard?->name ? str_replace('-ku', '', $selectedWard->name) : 'Pick' }}
                </span>
            </button>

            <span class="text-slate-600 text-xs">➔</span>

            <!-- Step 2 Button: Residents -->
            <button 
                type="button" 
                @click="mobileStep = 2; $wire.setMobileStep(2)"
                class="flex-1 py-2 px-1.5 rounded-xl text-center transition-all flex flex-col items-center justify-center gap-0.5 cursor-pointer"
                :class="mobileStep === 2 ? 'bg-indigo-600 text-white shadow-md font-bold ring-1 ring-indigo-400' : 'bg-slate-800/60 text-slate-400 hover:text-white'"
            >
                <span class="text-[9px] uppercase tracking-wider flex items-center gap-1 opacity-80">
                    <span class="w-3.5 h-3.5 rounded-full bg-white/20 inline-flex items-center justify-center text-[8px] font-bold">2</span>
                    <span>People</span>
                </span>
                <span class="text-xs font-bold truncate max-w-[95px]">
                    @if($selectedNpc)
                        {{ $selectedNpc->displayName }}
                    @else
                        {{ $npcs->count() }} Residents
                    @endif
                </span>
            </button>

            <span class="text-slate-600 text-xs">➔</span>

            <!-- Step 3 Button: Action -->
            <button 
                type="button" 
                @click="mobileStep = 3; $wire.setMobileStep(3)"
                class="flex-1 py-2 px-1.5 rounded-xl text-center transition-all flex flex-col items-center justify-center gap-0.5 cursor-pointer"
                :class="mobileStep === 3 ? 'bg-indigo-600 text-white shadow-md font-bold ring-1 ring-indigo-400' : 'bg-slate-800/60 text-slate-400 hover:text-white'"
            >
                <span class="text-[9px] uppercase tracking-wider flex items-center gap-1 opacity-80">
                    <span class="w-3.5 h-3.5 rounded-full bg-white/20 inline-flex items-center justify-center text-[8px] font-bold">3</span>
                    <span>Action</span>
                </span>
                <span class="text-xs font-bold truncate max-w-[95px] text-amber-300">
                    {{ $player->action_points ?? 12 }} AP left
                </span>
            </button>
        </div>
    </div>

    <!-- Main Planning Grid (3 Columns on Desktop, Step-Focused on Mobile) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Column 1: Nagoya 16 Wards (lg:col-span-3) -->
        <div 
            class="lg:col-span-3 bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 shadow-lg flex-col h-[560px] lg:h-[640px]"
            :class="mobileStep === 1 ? 'flex' : 'hidden lg:flex'"
        >
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📍</span>
                    <span>Nagoya 16 Wards</span>
                </h3>
                <span class="text-[11px] text-slate-400">{{ $wards->count() }} districts</span>
            </div>

            <div class="overflow-y-auto space-y-1.5 pr-1 flex-1 custom-scrollbar">
                @foreach($wards as $ward)
                    @php
                        $isHome = $player && $player->home_ward_id === $ward->id;
                        $distKm = $player ? $player->distanceToWard($ward) : 0.0;
                        $transitAp = $player ? $player->commuteApCostForWard($ward) : 0;
                        $otherMissionariesInWard = ($residentMissionariesByWard->get($ward->id) ?? collect())->filter(fn($u) => $u->id !== $player?->id);
                    @endphp
                    <button 
                        type="button"
                        wire:click="selectWard({{ $ward->id }})"
                        @click="mobileStep = 2"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-xs transition-all flex items-center justify-between {{ $selectedWardId === $ward->id ? 'bg-indigo-600/30 text-white border border-indigo-500/60 shadow-sm' : 'bg-slate-800/40 text-slate-300 hover:bg-slate-800/70 border border-transparent' }}"
                    >
                        <div>
                            <div class="font-semibold flex items-center gap-1.5 flex-wrap">
                                <span>{{ $ward->name }}</span>
                                @if($isHome)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                        🏡 Home
                                    </span>
                                @endif
                                @if($otherMissionariesInWard->isNotEmpty())
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-indigo-500/20 text-indigo-300 border border-indigo-500/30" title="{{ $otherMissionariesInWard->pluck('name')->join(', ') }}">
                                        🏠 {{ $otherMissionariesInWard->count() }} co-worker{{ $otherMissionariesInWard->count() > 1 ? 's' : '' }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                <span>{{ $ward->name_ja }}</span>
                                @if(! $isHome && $player?->home_ward_id)
                                    <span class="text-[10px] {{ $transitAp > 0 ? 'text-amber-400 font-semibold' : 'text-slate-400' }}">
                                        • {{ $distKm }} km {{ $transitAp > 0 ? "(+{$transitAp} AP)" : '' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded {{ $ward->spiritual_openness >= 40 ? 'bg-emerald-950/70 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                                {{ $ward->spiritual_openness }}% Open
                            </span>
                            @if($ward->poverty_need_index >= 50)
                                <span class="block text-[9px] text-rose-400 mt-0.5">High Need 🍲</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            <!-- Mobile Quick Advance Footer -->
            <div class="mt-3 pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs lg:hidden">
                <span class="text-[11px] text-slate-400 truncate">Selected: <strong class="text-white">{{ $selectedWard?->name }}</strong></span>
                <button 
                    type="button"
                    @click="mobileStep = 2; $wire.setMobileStep(2)"
                    class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center gap-1 shadow shrink-0"
                >
                    <span>View People</span>
                    <span>➔</span>
                </button>
            </div>
        </div>

        <!-- Column 2: Ward Residents / NPCs (lg:col-span-4) -->
        <div 
            class="lg:col-span-4 bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 shadow-lg flex-col h-[560px] lg:h-[640px]"
            :class="mobileStep === 2 ? 'flex' : 'hidden lg:flex'"
        >
            <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="mobileStep = 1; $wire.setMobileStep(1)"
                            class="lg:hidden text-indigo-400 hover:text-indigo-300 text-xs font-bold flex items-center gap-0.5"
                            title="Switch Ward"
                        >
                            <span>‹</span>
                            <span>Wards</span>
                        </button>
                        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>👥</span>
                            <span>{{ $selectedWard?->name ?? 'Ward' }} Residents</span>
                        </h3>
                    </div>
                    <p class="text-[11px] text-slate-400">{{ $npcs->count() }} simulated residents</p>
                </div>

                @if($selectedWard && $selectedWard->poverty_need_index >= 50)
                    <span class="px-2 py-0.5 rounded text-[10px] bg-rose-950 text-rose-300 border border-rose-800">
                        Urgent Mercy Hub
                    </span>
                @endif
            </div>

            <!-- Home Base & Relocation Banner -->
            @if($selectedWard)
                @php
                    $coLaborersInWard = ($residentMissionariesByWard->get($selectedWard->id) ?? collect())->filter(fn($u) => $u->id !== $player?->id);
                @endphp
                <div class="mb-2 p-2.5 rounded-xl border {{ $isHomeWard ? 'bg-amber-950/30 border-amber-500/50 text-amber-200' : 'bg-slate-950/60 border-slate-800 text-slate-300' }} text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            @if($isHomeWard)
                                <div class="font-bold flex items-center gap-1.5 text-amber-300 text-xs">
                                    <span>🏡</span>
                                    <span>Your Home Base & Residence</span>
                                </div>
                                <div class="text-[10px] text-amber-400/90 mt-0.5 leading-tight">
                                    Embedded Neighbor: 0 min commute • <strong>+25% Trust & Rapid Follow-up Bonus</strong>
                                </div>
                            @else
                                <div class="font-semibold flex items-center gap-1.5 text-white text-xs">
                                    <span>{{ $commuteApCost > 0 ? '🚆' : '🚇' }}</span>
                                    <span>{{ $commuteDistanceKm }} km from Home ({{ $player?->homeWard?->name ?? 'Nagoya' }})</span>
                                    @if($commuteApCost > 0)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-950 text-amber-400 border border-amber-800">
                                            +{{ $commuteApCost }} AP Commute
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-emerald-950 text-emerald-300 border border-emerald-800">
                                            0 AP Commute
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 leading-tight">
                                    ~{{ $commuteMinutes }} min transit • Follow-up: {{ $commuteApCost > 1 ? 'Harder to follow up (-30% trust)' : ($commuteApCost > 0 ? 'Moderate friction (-15% trust)' : 'Standard follow-up') }}
                                </div>
                            @endif
                        </div>

                        @if(! $isHomeWard)
                            <button 
                                type="button" 
                                wire:click="moveToWard({{ $selectedWard->id }})"
                                class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] transition cursor-pointer shrink-0 shadow flex items-center gap-1 active:scale-95"
                                title="Relocate your residence and make {{ $selectedWard->name }} your ministry home base"
                            >
                                <span>🏡</span>
                                <span>Move Here</span>
                            </button>
                        @endif
                    </div>

                    @if($coLaborersInWard->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-slate-700/50 flex items-center gap-1.5 text-[10px] text-indigo-300">
                            <span>🏠</span>
                            <span><strong>Fellow Missionaries Living Here:</strong> {{ $coLaborersInWard->pluck('name')->join(', ') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Ward-Wide Platform Action Shortcut -->
            <div class="mb-2">
                <button 
                    type="button"
                    wire:click="selectWardPlatformAction('prayer_walk')"
                    @click="mobileStep = 3"
                    class="w-full py-2 px-3 rounded-xl border border-dashed border-emerald-500/50 bg-emerald-950/20 hover:bg-emerald-950/40 text-emerald-300 text-xs font-semibold flex items-center justify-between transition cursor-pointer"
                >
                    <span class="flex items-center gap-1.5 truncate">
                        <span>👣</span>
                        <span>Prayer Walk or Event in {{ $selectedWard?->name ?? 'Ward' }}</span>
                    </span>
                    <span class="text-[10px] font-bold text-emerald-400 bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-800 shrink-0">
                        1 AP ➔
                    </span>
                </button>
            </div>

            <div class="overflow-y-auto space-y-2 pr-1 flex-1 custom-scrollbar">
                @forelse($npcs as $npc)
                    <div 
                        wire:click="selectNpc({{ $npc->id }})"
                        @click="mobileStep = 3"
                        class="p-3 rounded-xl border transition-all cursor-pointer {{ $selectedNpcId === $npc->id ? 'bg-indigo-950/40 border-indigo-500 shadow-md ring-1 ring-indigo-500/50' : 'bg-slate-800/40 border-slate-800 hover:border-slate-700' }}"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-semibold text-white">{{ $npc->displayName }}</span>
                                    @if($npc->is_person_of_peace)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-950 text-amber-300 border border-amber-700 font-bold" title="Luke 10 Person of Peace">
                                            PoP ⭐
                                        </span>
                                    @endif
                                    @if($npc->hasPrayedSalvation)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-emerald-950 text-emerald-300 border border-emerald-700 font-bold" title="Prayed Sinner's Prayer">
                                            ✝️ Saved
                                        </span>
                                    @endif
                                    @if($npc->is_baptized)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-cyan-950 text-cyan-300 border border-cyan-700 font-bold" title="Baptized in Water">
                                            🕊️ Baptized
                                        </span>
                                    @elseif(($npc->baptism_status?->value ?? '') === 'committed')
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-blue-950 text-blue-300 border border-blue-700 font-bold" title="Committed to Baptism">
                                            🌊 Committed
                                        </span>
                                    @elseif($npc->hasPrayedSalvation)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-950 text-amber-300 border border-amber-700 font-bold" title="Counting Cost (Family Concerns)">
                                            💭 Cost
                                        </span>
                                    @endif
                                    @if($npc->leadership_tier->level() > 0)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $npc->leadership_tier->badgeClasses() }}" title="{{ $npc->leadership_tier->label() }}: {{ $npc->leadership_tier->coreFocus() }}">
                                            {{ $npc->leadership_tier->levelCode() }} {{ $npc->leadership_tier->roleTitle() }}
                                        </span>
                                    @elseif($npc->trained_411)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-purple-950 text-purple-300 border border-purple-700 font-bold">
                                            L1 Sower
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $npc->role_archetype->label() }}</div>
                            </div>

                            <!-- Traffic Light Status -->
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $npc->status->badgeClasses() }}">
                                {{ $npc->status->icon() }} {{ $npc->status->value }}
                            </span>
                        </div>

                        <!-- Need State Badge -->
                        @if($npc->need_state !== \App\Enums\NpcNeedState::None)
                            <div class="mt-2 flex items-center gap-1 text-[11px] px-2 py-0.5 rounded border {{ $npc->need_state->badgeClasses() }}">
                                <span>{{ $npc->need_state->icon() }}</span>
                                <span class="font-medium truncate">{{ $npc->need_state->label() }}</span>
                            </div>
                        @endif

                        <!-- Trust Progress Bar -->
                        <div class="mt-2.5">
                            <div class="flex justify-between text-[10px] text-slate-400 mb-0.5">
                                <span>Trust Level</span>
                                <span class="font-medium text-slate-300">{{ $npc->trust_level }}%</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $npc->trust_level }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-slate-500 text-xs">
                        No residents surfaced yet in this ward.<br>Host an International Event or Prayer Walk to discover seekers!
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 3: Action Planner & Queued Drawer (lg:col-span-5) -->
        <div 
            class="lg:col-span-5 flex flex-col gap-5"
            :class="mobileStep === 3 ? 'flex' : 'hidden lg:flex'"
        >
            <!-- Action Selector Card -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="mobileStep = 2; $wire.setMobileStep(2)"
                            class="lg:hidden text-indigo-400 hover:text-indigo-300 text-xs font-bold flex items-center gap-0.5 mr-1"
                            title="Back to Residents"
                        >
                            <span>‹</span>
                            <span>People</span>
                        </button>
                        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>⚡</span>
                            <span>Schedule Relational Action</span>
                        </h3>
                        <button 
                            type="button" 
                            wire:click="openActionInfoModal()"
                            class="px-2 py-0.5 rounded-lg bg-indigo-950/80 hover:bg-indigo-900 border border-indigo-700/60 text-indigo-300 text-[10px] font-bold flex items-center gap-1 transition cursor-pointer"
                            title="Open detailed guide with descriptive text for all actions"
                        >
                            <span>ℹ️</span>
                            <span>Info</span>
                        </button>
                    </div>
                    @if($selectedNpc)
                        <div class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="text-xs text-indigo-400 font-bold block">{{ $selectedNpc->displayName }}</span>
                                @if($selectedNpc->leadership_tier->level() > 0)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $selectedNpc->leadership_tier->badgeClasses() }}">
                                        {{ $selectedNpc->leadership_tier->levelCode() }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400">
                                {{ $selectedNpc->leadership_tier->roleTitle() }}
                            </div>
                            <button 
                                type="button" 
                                @click="mobileStep = 2; $wire.setMobileStep(2)" 
                                class="text-[10px] text-slate-400 hover:text-indigo-300 underline lg:hidden"
                            >
                                Change
                            </button>
                        </div>
                    @else
                        <div class="text-right">
                            <span class="text-xs text-amber-400 font-semibold block">{{ $selectedWard?->name }} (Ward Action)</span>
                            <button 
                                type="button" 
                                @click="mobileStep = 2; $wire.setMobileStep(2)" 
                                class="text-[10px] text-slate-400 hover:text-amber-300 underline lg:hidden"
                            >
                                Pick Person
                            </button>
                        </div>
                    @endif
                </div>

                <!-- 5 Levels Ability Banner & Filter Toggle -->
                @if($selectedNpc)
                    <div class="mb-3 p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-[11px] flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-inner">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">5 Levels Ability:</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $selectedNpc->leadership_tier->badgeClasses() }}">
                                    {{ $selectedNpc->leadership_tier->levelCode() }}: {{ $selectedNpc->leadership_tier->roleTitle() }}
                                </span>
                                @if($selectedNpc->leadership_tier->level() > 0)
                                    <span class="text-slate-400 text-[10px]">Toolset: <strong class="text-slate-300">{{ $selectedNpc->leadership_tier->coreToolset() }}</strong></span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5 truncate">
                                {{ $selectedNpc->leadership_tier->coreFocus() }}
                            </div>
                        </div>
                        <button 
                            type="button"
                            wire:click="toggleFilterEligibleOnly"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition shrink-0 cursor-pointer flex items-center justify-center gap-1 {{ $filterEligibleOnly ? 'bg-indigo-600 text-white border border-indigo-400 shadow' : 'bg-slate-800 text-slate-300 hover:text-white border border-slate-700' }}"
                            title="Filter actions matching abilities"
                        >
                            <span>{{ $filterEligibleOnly ? '✓' : '⚡' }}</span>
                            <span>{{ $filterEligibleOnly ? 'Eligible Only' : 'Filter by Ability' }}</span>
                        </button>
                    </div>
                @endif

                <!-- Action Type Selector -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
                    @foreach($actionTypes as $action)
                        @php
                            $isEligible = $action->isEligibleFor($selectedNpc);
                            $ineligibilityReason = $action->ineligibilityReason($selectedNpc);
                        @endphp
                        <label 
                            class="flex items-start gap-2.5 p-2.5 rounded-xl border transition-all relative {{ ! $isEligible ? 'opacity-60 bg-slate-900/40 border-slate-800/60 text-slate-400 cursor-not-allowed' : ($selectedActionType === $action->value ? 'bg-indigo-950/60 border-indigo-500 text-white shadow-md ring-1 ring-indigo-500/50 cursor-pointer' : 'bg-slate-800/40 border-slate-800 text-slate-300 hover:border-slate-700 cursor-pointer') }}"
                            title="{{ $isEligible ? $action->description() : 'Locked: '.$ineligibilityReason }}"
                        >
                            <input 
                                type="radio" 
                                name="action_type" 
                                value="{{ $action->value }}" 
                                wire:model.live="selectedActionType"
                                @disabled(! $isEligible)
                                class="mt-1 text-indigo-500 focus:ring-indigo-500 disabled:opacity-40"
                            >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-1.5">
                                    <span class="text-xs font-semibold leading-snug break-words flex items-center gap-1">
                                        <span>{{ $action->icon() }}</span>
                                        <span>
                                            @if($action === \App\Enums\MinistryActionType::PrayerWalk && $selectedNpc)
                                                Prayer Walk together with {{ $selectedNpc->displayName }}
                                            @else
                                                {{ $action->label() }}
                                            @endif
                                        </span>
                                    </span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        @if(! $isEligible)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-950/80 text-rose-300 border border-rose-800/60 flex items-center gap-0.5" title="{{ $ineligibilityReason }}">
                                                <span>🔒</span>
                                                <span>Locked</span>
                                            </span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-900/90 text-amber-400 border border-slate-700/50 flex items-center gap-1">
                                            <span>{{ $action->apCost() + $commuteApCost }} AP</span>
                                            @if($commuteApCost > 0)
                                                <span class="text-[9px] text-amber-300 font-normal opacity-90" title="Includes +{{ $commuteApCost }} AP transit cost from your home base">(+{{ $commuteApCost }} commute)</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                @if($action === \App\Enums\MinistryActionType::PrayerWalk && $selectedNpc && $isEligible)
                                    <div class="text-[10px] text-emerald-400/90 mt-1 leading-tight">
                                        👣 Joint harvest prayer walk (MAWL). Builds trust (+25%) & covers their Oikos.
                                    </div>
                                @endif

                                @if(! $isEligible)
                                    <div class="text-[10px] text-rose-400/90 mt-1 italic leading-tight">
                                        {{ $ineligibilityReason }}
                                    </div>
                                @endif

                                <div class="flex items-center justify-between gap-1 mt-1.5">
                                    <span class="text-[10px] text-slate-400 font-medium truncate">{{ $action->fieldName() }}</span>
                                    <button 
                                        type="button" 
                                        wire:click.stop="openActionInfoModal('{{ $action->value }}')" 
                                        class="px-1.5 py-0.5 rounded bg-slate-900/90 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/60 text-[9px] font-bold transition cursor-pointer flex items-center gap-1 shrink-0"
                                        title="View descriptive text & strategy for {{ $action->label() }}"
                                    >
                                        <span>ℹ️</span>
                                        <span>Info</span>
                                    </button>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Proximity & Commute Cost Indicator Banner -->
                @if($isHomeWard)
                    <div class="mb-3 px-3 py-2 rounded-xl bg-emerald-950/40 border border-emerald-800/60 text-[11px] text-emerald-300 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span>🏡</span>
                            <span><strong>Home Base Advantage:</strong> Local neighbor presence brings <strong>+25% follow-up trust</strong> and 0 AP commute overhead.</span>
                        </div>
                    </div>
                @elseif($commuteApCost > 0)
                    <div class="mb-3 px-3 py-2 rounded-xl bg-amber-950/40 border border-amber-800/60 text-[11px] text-amber-300 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span>🚇</span>
                            <span><strong>Commute Overhead:</strong> {{ $commuteDistanceKm }} km (~{{ $commuteMinutes }} min). Adds <strong>+{{ $commuteApCost }} AP transit cost</strong> and travel friction on follow-ups.</span>
                        </div>
                    </div>
                @else
                    <div class="mb-3 px-3 py-2 rounded-xl bg-slate-950/50 border border-slate-800 text-[11px] text-slate-300 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span>🚶</span>
                            <span><strong>Nearby Ward:</strong> {{ $commuteDistanceKm }} km (~{{ $commuteMinutes }} min transit). 0 AP surcharge; standard follow-up pace.</span>
                        </div>
                    </div>
                @endif

                <!-- Optional Notes -->
                <div class="mb-3">
                    <input 
                        type="text" 
                        wire:model="notes" 
                        placeholder="Add prayer focus or personal notes (optional)..." 
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                    >
                </div>

                <!-- Queue Button -->
                <button 
                    type="button"
                    wire:click="queueAction"
                    class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                >
                    <span>➕</span>
                    <span>Queue for Day {{ $player?->current_game_day ?? 1 }} • Executes on Next Day</span>
                </button>

                <!-- Mobile Quick Action: Choose another person in ward -->
                <div class="mt-3 pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs lg:hidden">
                    <button 
                        type="button"
                        @click="mobileStep = 2; $wire.setMobileStep(2)"
                        class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 transition cursor-pointer"
                    >
                        <span>👥</span>
                        <span>Select Another Person in {{ $selectedWard?->name }}</span>
                    </button>
                </div>
            </div>

            <!-- Current Scheduled Actions & Recent Breakthroughs Drawer -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-lg flex-1 flex flex-col space-y-4">
                <!-- Scheduled for Today -->
                <div>
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800">
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                                <span>📋</span>
                                <span>Scheduled for Day {{ $player?->current_game_day ?? 1 }}</span>
                            </h3>
                            <p class="text-[10px] text-slate-400">Executes immediately when turning to Day {{ min(30, ($player?->current_game_day ?? 1) + 1) }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-semibold">
                            {{ $queuedActions->count() }} Pending
                        </span>
                    </div>

                    <div class="overflow-y-auto space-y-2 max-h-44 pr-1 custom-scrollbar">
                        @forelse($queuedActions as $queued)
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs">
                                <div class="min-w-0 pr-2">
                                    <div class="font-semibold text-white flex items-center gap-1.5">
                                        <span>{{ $queued->actionTitle }}</span>
                                        <span class="text-[10px] font-bold text-amber-400">({{ $queued->ap_cost }} AP)</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                                        {{ $queued->ward->name }} • {{ $queued->targetNpc?->name ?? 'Ward-wide platform event' }}
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="cancelAction({{ $queued->id }})"
                                    class="text-rose-400 hover:text-rose-300 px-2 py-1 rounded bg-rose-950/50 border border-rose-900 text-[10px] shrink-0 transition cursor-pointer"
                                    title="Cancel and refund AP"
                                >
                                    Cancel ✕
                                </button>
                            </div>
                        @empty
                            <div class="text-center py-4 text-slate-500 text-xs">
                                No actions scheduled yet for Day {{ $player?->current_game_day ?? 1 }}.<br>Spend your AP bandwidth above, then advance the day to see developments!
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Daily Developments Feed -->
                @if($recentOutcomes->isNotEmpty())
                    <div class="pt-2 border-t border-slate-800/80">
                        <div class="flex items-center justify-between pb-1.5 mb-2">
                            <h4 class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                                <span>🌱</span>
                                <span>Recent Daily Developments</span>
                            </h4>
                            <span class="text-[10px] text-slate-500">Last {{ $recentOutcomes->count() }} outcomes</span>
                        </div>

                        <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                            @foreach($recentOutcomes as $outcome)
                                <div class="p-2.5 rounded-xl bg-slate-950/50 border border-slate-800/80 text-xs space-y-1">
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="font-bold text-indigo-300">{{ $outcome->actionTitle }}</span>
                                        <span class="text-slate-400">{{ $outcome->ward->name }} • Day {{ $outcome->game_day }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-300 leading-relaxed">
                                        {{ $outcome->outcome_notes }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: Action Information & Descriptive Text -->
    @if($showActionInfoModal && $infoActionEnum)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl relative max-h-[90vh] overflow-y-auto custom-scrollbar">
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-950/80 border border-indigo-500/40 flex items-center justify-center text-2xl shadow-lg">
                            {{ $infoActionEnum->icon() }}
                        </div>
                        <div>
                            <h3 class="text-base font-black text-white tracking-tight leading-snug">
                                {{ $infoActionEnum->label() }}
                            </h3>
                            <div class="text-xs text-indigo-400 font-medium">
                                {{ $infoActionEnum->labelJa() }}
                            </div>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        wire:click="closeActionInfoModal" 
                        class="text-slate-400 hover:text-white p-1.5 rounded-xl hover:bg-slate-800 transition cursor-pointer text-base"
                    >
                        ✕
                    </button>
                </div>

                <!-- Badges & AP Cost -->
                <div class="flex flex-wrap items-center gap-2 mt-4">
                    <span class="px-2.5 py-1 rounded-xl bg-amber-950/60 border border-amber-600/50 text-amber-300 text-xs font-bold flex items-center gap-1">
                        <span>⚡</span>
                        <span>{{ $infoActionEnum->apCost() }} Action Points</span>
                    </span>
                    <span class="px-2.5 py-1 rounded-xl bg-emerald-950/60 border border-emerald-600/50 text-emerald-300 text-xs font-bold">
                        {{ $infoActionEnum->fieldName() }}
                    </span>
                    <span class="px-2.5 py-1 rounded-xl bg-purple-950/60 border border-purple-600/50 text-purple-300 text-xs font-bold">
                        {{ $infoActionEnum->toolboxLevel() }}
                    </span>
                    @if($infoActionEnum->requiresTargetNpc())
                        <span class="px-2.5 py-1 rounded-xl bg-blue-950/60 border border-blue-600/50 text-blue-300 text-[11px] font-semibold">
                            🎯 Target Resident
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-xl bg-indigo-950/60 border border-indigo-600/50 text-indigo-300 text-[11px] font-semibold">
                            🏙️ Ward-Wide Strategic Event
                        </span>
                    @endif
                </div>

                <!-- Descriptive Content -->
                <div class="mt-5 space-y-4 text-xs text-slate-300 leading-relaxed">
                    <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800">
                        <strong class="text-white block font-bold mb-1 text-[11px] uppercase tracking-wider text-slate-400">
                            Description & Kingdom Practice:
                        </strong>
                        <p class="text-slate-200 leading-relaxed">
                            {{ $infoActionEnum->description() }}
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-indigo-950/30 border border-indigo-900/50 space-y-2">
                        <strong class="text-indigo-300 block font-bold text-[11px] uppercase tracking-wider">
                            💡 Movement Lore & 5 Levels Coaching Strategy:
                        </strong>
                        @if($infoActionEnum === \App\Enums\MinistryActionType::CasualTalk)
                            <p>In Japanese culture, building trust (*shinrai*) begins with natural, low-pressure conversation. Casual talk is the foundation of Field 1 (Entry Strategy), uncovering relational networks (Oikos).</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::DeepTalk)
                            <p>Listening to deep burdens and burnout in corporate Nagoya (the salaryman grind) softens dry ground. Validating feelings moves people from Red/Yellow to receptive Green.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::PrayerForHealing)
                            <p>Luke 10:9 instructs us to "Heal the sick who are there and tell them, 'The kingdom of God has come near to you.'" Sincere, compassionate prayer often opens whole households.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::MercyCareFoodBible)
                            <p>Serving vulnerable neighbors with hot meals, warm fellowship, and Japanese Bibles around Nagoya, Kanayama, or Sakae hubs provides an Acts 2 platform of compassion.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::InternationalEvent)
                            <p>Organizing a cultural exchange, cooking night, or language cafe in a ward casts a wide net, quickly surfacing seekers and Persons of Peace without needing an initial target resident.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::PrayerWalk)
                            <p>Luke 10:2-6 intercession on foot along neighborhood streets! Can be conducted solo across a ward to discover Persons of Peace, or jointly with a resident contact using MAWL (Model, Assist, Watch, Leave) to train them in harvest prayer and place their relational Oikos under intercession. Traces luminous street prayer paths on the Nagoya map.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::Share15SecTestimony)
                            <p>A simple 15-second testimony (My life before Christ -> How Christ met me -> My life now with Christ) empowers any believer to reproduce without fear.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::Share3Circles)
                            <p>A clear visual gospel tool: God’s Design -> Brokenness -> Gospel Rescue -> Repent & Believe -> Recover & Pursue. Highly accessible in visual Japanese culture.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::PraySalvation)
                            <p>Guiding an open seeker to pray and confess Jesus Christ as Lord and Savior (Romans 10:9-10). When a Person of Peace is saved, their entire family network often follows!</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::CommitToBaptism)
                            <p>Baptism in Japan carries deep spiritual significance, often requiring courage to transcend ancestral Buddhist/Shinto traditions. Essential for Acts 2 Church Circles.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::CommandsOfChrist)
                            <p>L2 Discipleship Curriculum: 7 to 9 basic commands of Jesus (Repent & Believe, Be Baptized, Pray, Love, Lord's Supper, Give, Make Disciples) accompanied by hand motions. Establishes immediate loving obedience in reproducing house churches.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::SwordBibleStudy)
                            <p>The primary Discovery Bible Study method in movements! Four simple questions: (1) What does this say about God? (2) What does this say about people? (3) Any sin to avoid or command to obey? (4) Any example to follow? Cultivates self-feeding disciples.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::Train411)
                            <p>The catalytic discipleship engine! 4 Questions, 1 sheet of paper, 1 hour. Trains new believers to immediately share with their own Oikos, sparking G2 Church reproduction as L1 Seed Sowers.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::ChurchCircles)
                            <p>Acts 2:38-47 healthy church formation! Uses the 3/3rds pattern: Look Back (Care, Worship, Loving Accountability), Look Up (Scripture discovery), and Look Ahead (Practice, Goals, Commissioning). Gives groups biblical identity as autonomous churches.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::MentorTimothy)
                            <p>Investing in emerging Japanese leaders (2 Timothy 2:2) to transition from L1 Seed Sower to L2 Church Planter. Breaks missionary bottlenecks by empowering local believers to pastor simple churches.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::IronOnIron)
                            <p>Quarterly peer coaching using Generational Mapping and 4 Fields diagnostic questions. Proverbs 27:17 "Iron sharpens iron" elevates L2 Church Planters to L3 Church Plant Multipliers through brutal facts, celebration, and bottleneck troubleshooting.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::SwarmTraining)
                            <p>Multi-team movement saturation! Brings experienced training teams (L4 Movement Trainers & L5 Strategy Coordinators) to model harvest entry, assist local sowers, watch them lead, and launch regional midlevel training cohorts.</p>
                        @endif
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="mt-6 flex items-center justify-between gap-3 pt-3 border-t border-slate-800">
                    <button 
                        type="button" 
                        wire:click="closeActionInfoModal" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-800 transition cursor-pointer"
                    >
                        Close
                    </button>
                    <button 
                        type="button" 
                        wire:click="selectActionFromModal('{{ $infoActionEnum->value }}')" 
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 shadow-lg shadow-indigo-600/30 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>✓</span>
                        <span>Select {{ $infoActionEnum->label() }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
