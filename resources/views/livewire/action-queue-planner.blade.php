<div class="space-y-6" wire:poll.60s="touchHeartbeat">
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
                        <span>Day {{ $session?->current_game_day ?? 1 }} / 30</span>
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
                    wire:key="digital-clock-{{ $session?->current_game_day }}-{{ $session?->days_processed_this_month }}-{{ $player?->days_played_today }}"
                    x-data="digitalClock({{ max(0, $session?->secondsUntilNextDailyTick ?? 600) }}, '{{ $session?->next_daily_tick_at?->toIso8601String() }}', {{ (!($player?->hasReachedDailyPlayLimit ?? false) && ($session?->days_processed_this_month ?? 0) < 30) ? 'true' : 'false' }})"
                    x-init="init()"
                    class="border-l border-slate-800 pl-4 flex items-center gap-3"
                >
                    <div>
                        <div class="text-[9px] uppercase font-bold tracking-wider mb-1 flex items-center gap-1.5 {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'text-amber-400' : 'text-slate-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'bg-amber-400' : 'bg-emerald-400 animate-pulse' }}"></span>
                            <span>{{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'Night Rest • 30d Cap' : 'Auto Day Tick' }}</span>
                        </div>
                        
                        <!-- Digital LED Clock Face -->
                        <div class="bg-black/95 border border-slate-800/90 rounded-xl px-2.5 py-1 flex items-center justify-center shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)]">
                            @if($player?->hasReachedDailyPlayLimit ?? false)
                                <div class="font-mono text-base font-black tracking-widest text-amber-400 drop-shadow-[0_0_6px_rgba(245,158,11,0.6)]">
                                    <span>30</span><span class="font-bold">:</span><span>30</span>
                                </div>
                            @elseif(($session?->days_processed_this_month ?? 0) >= 30)
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

                    <!-- Manual Advance to Next Day Button -->
                    <div class="pt-3.5">
                        <button 
                            type="button" 
                            wire:click="advanceDay(true)"
                            wire:loading.attr="disabled"
                            @disabled(($player?->hasReachedDailyPlayLimit ?? false) || ($session?->days_processed_this_month ?? 0) >= 30)
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-md cursor-pointer {{ (($player?->hasReachedDailyPlayLimit ?? false) || ($session?->days_processed_this_month ?? 0) >= 30) ? 'bg-slate-900 text-slate-500 border border-slate-800 cursor-not-allowed' : 'bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 text-white border border-emerald-400/40 shadow-emerald-950/40 hover:scale-[1.02] active:scale-[0.98]' }}"
                            title="{{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'Daily limit of 30 days reached. Next month starts tonight!' : 'Manually advance to the next game day tick now' }}"
                        >
                            <span wire:loading.remove wire:target="advanceDay">⏭️</span>
                            <span wire:loading wire:target="advanceDay" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span wire:loading.remove wire:target="advanceDay">
                                @if($player?->hasReachedDailyPlayLimit ?? false)
                                    Daily Cap (30/30)
                                @elseif(($session?->days_processed_this_month ?? 0) >= 30)
                                    Month Done
                                @else
                                    Turn to Day {{ min(30, ($session?->current_game_day ?? 1) + 1) }}
                                @endif
                            </span>
                            <span wire:loading wire:target="advanceDay">Advancing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main 3-Column Planning Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Column 1: Nagoya 16 Wards (lg:col-span-3) -->
        <div class="lg:col-span-3 bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 shadow-lg flex flex-col h-[640px]">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📍</span>
                    <span>Nagoya 16 Wards</span>
                </h3>
                <span class="text-[11px] text-slate-400">{{ $wards->count() }} districts</span>
            </div>

            <div class="overflow-y-auto space-y-1.5 pr-1 flex-1 custom-scrollbar">
                @foreach($wards as $ward)
                    <button 
                        type="button"
                        wire:click="selectWard({{ $ward->id }})"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-xs transition-all flex items-center justify-between {{ $selectedWardId === $ward->id ? 'bg-indigo-600/30 text-white border border-indigo-500/60 shadow-sm' : 'bg-slate-800/40 text-slate-300 hover:bg-slate-800/70 border border-transparent' }}"
                    >
                        <div>
                            <div class="font-semibold">{{ $ward->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $ward->name_ja }}</div>
                        </div>
                        <div class="text-right">
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
        </div>

        <!-- Column 2: Ward Residents / NPCs (lg:col-span-4) -->
        <div class="lg:col-span-4 bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 shadow-lg flex flex-col h-[640px]">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
                <div>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>👥</span>
                        <span>{{ $selectedWard?->name ?? 'Ward' }} Residents</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">{{ $npcs->count() }} simulated residents</p>
                </div>

                @if($selectedWard && $selectedWard->poverty_need_index >= 50)
                    <span class="px-2 py-0.5 rounded text-[10px] bg-rose-950 text-rose-300 border border-rose-800">
                        Urgent Mercy Hub
                    </span>
                @endif
            </div>

            <div class="overflow-y-auto space-y-2 pr-1 flex-1 custom-scrollbar">
                @forelse($npcs as $npc)
                    <div 
                        wire:click="selectNpc({{ $npc->id }})"
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
                                    @if($npc->trained_411)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-purple-950 text-purple-300 border border-purple-700 font-bold">
                                            411 Sower
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
        <div class="lg:col-span-5 flex flex-col gap-5">
            <!-- Action Selector Card -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
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
                            <span>More Info</span>
                        </button>
                    </div>
                    @if($selectedNpc)
                        <span class="text-xs text-indigo-400 font-medium">Target: {{ $selectedNpc->name }}</span>
                    @else
                        <span class="text-xs text-amber-400 font-medium">Ward Platform Action</span>
                    @endif
                </div>

                <!-- Action Type Selector -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
                    @foreach($actionTypes as $action)
                        <label 
                            class="flex items-start gap-2.5 p-2.5 rounded-xl border cursor-pointer transition-all {{ $selectedActionType === $action->value ? 'bg-indigo-950/60 border-indigo-500 text-white shadow-md ring-1 ring-indigo-500/50' : 'bg-slate-800/40 border-slate-800 text-slate-300 hover:border-slate-700' }}"
                        >
                            <input 
                                type="radio" 
                                name="action_type" 
                                value="{{ $action->value }}" 
                                wire:model.live="selectedActionType"
                                class="mt-1 text-indigo-500 focus:ring-indigo-500"
                            >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-1.5">
                                    <span class="text-xs font-semibold leading-snug break-words">
                                        {{ $action->icon() }} {{ $action->label() }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-900/90 text-amber-400 border border-slate-700/50 shrink-0">
                                        {{ $action->apCost() }} AP
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-1 mt-1.5">
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $action->fieldName() }}</span>
                                    <button 
                                        type="button" 
                                        wire:click.stop="openActionInfoModal('{{ $action->value }}')" 
                                        class="px-1.5 py-0.5 rounded bg-slate-900/90 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/60 text-[9px] font-bold transition cursor-pointer flex items-center gap-1 shrink-0"
                                        title="View descriptive text & strategy for {{ $action->label() }}"
                                    >
                                        <span>ℹ️</span>
                                        <span>More Info</span>
                                    </button>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

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
                    class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                    <span>➕</span>
                    <span>Queue for Month {{ $session?->current_game_month ?? 1 }} Resolution Tick</span>
                </button>
            </div>

            <!-- Current Month Queued Actions Drawer -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-lg flex-1 flex flex-col">
                <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-800">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>📋</span>
                        <span>Queued for This Month</span>
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-semibold">
                        {{ $queuedActions->count() }} Actions Queued
                    </span>
                </div>

                <div class="overflow-y-auto space-y-2 flex-1 max-h-56 pr-1 custom-scrollbar">
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
                        <div class="text-center py-6 text-slate-500 text-xs">
                            No actions scheduled yet for this month.<br>Spend your AP bandwidth above to start sowing!
                        </div>
                    @endforelse
                </div>
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
                    @if($infoActionEnum->requiresTargetNpc())
                        <span class="px-2.5 py-1 rounded-xl bg-blue-950/60 border border-blue-600/50 text-blue-300 text-[11px] font-semibold">
                            🎯 Requires Target Resident
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-xl bg-purple-950/60 border border-purple-600/50 text-purple-300 text-[11px] font-semibold">
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
                            💡 Movement Lore & Four Fields Strategy:
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
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::Share15SecTestimony)
                            <p>A simple 15-second testimony (My life before Christ -> How Christ met me -> My life now with Christ) empowers any believer to reproduce without fear.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::Share3Circles)
                            <p>A clear visual gospel tool: God’s Design -> Brokenness -> Gospel Rescue -> Repent & Believe -> Recover & Pursue. Highly accessible in visual Japanese culture.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::PraySalvation)
                            <p>Guiding an open seeker to pray and confess Jesus Christ as Lord and Savior (Romans 10:9-10). When a Person of Peace is saved, their entire family network often follows!</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::CommitToBaptism)
                            <p>Baptism in Japan carries deep spiritual significance, often requiring courage to transcend ancestral Buddhist/Shinto traditions. Essential for Acts 2 Church Circles.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::Train411)
                            <p>The catalytic discipleship engine! 4 Questions, 1 sheet of paper, 1 hour. Trains new believers to immediately share with their own Oikos, sparking G2 Church reproduction.</p>
                        @elseif($infoActionEnum === \App\Enums\MinistryActionType::MentorTimothy)
                            <p>Investing in emerging Japanese leaders (2 Timothy 2:2) to transition churches from missionary dependence to autonomous local reproduction (Field 5).</p>
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
