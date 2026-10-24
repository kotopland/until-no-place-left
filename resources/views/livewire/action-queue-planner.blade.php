<div class="space-y-6" wire:poll.60s="touchHeartbeat">
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

            <!-- AP Bandwidth Meter & Daily Ticks Status -->
            <div class="flex flex-wrap items-center gap-4 bg-slate-950/80 px-4 py-2.5 rounded-xl border border-slate-800">
                <!-- Action Points Bandwidth -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-400 font-medium">Bandwidth AP:</span>
                        <span class="font-bold text-white ml-2">{{ $player?->action_points ?? 0 }} / {{ $player?->max_action_points ?? 12 }} AP</span>
                    </div>
                    <div class="w-32 h-2.5 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-300" style="width: {{ $player?->remainingBandwidthPercent ?? 0 }}%"></div>
                    </div>
                </div>

                <!-- Daily Real-Life Play Cap (30 Days Max) -->
                <div class="border-l border-slate-800 pl-4">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-400 font-medium">Played Today:</span>
                        <span class="font-bold {{ ($player?->days_played_today ?? 0) >= 30 ? 'text-amber-400' : 'text-slate-200' }} ml-2">
                            {{ $player?->days_played_today ?? 0 }} / 30 Days
                        </span>
                    </div>
                    <div class="w-28 h-2.5 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full {{ ($player?->days_played_today ?? 0) >= 30 ? 'bg-amber-400' : 'bg-cyan-500' }} rounded-full transition-all duration-300" style="width: {{ min(100, round((($player?->days_played_today ?? 0) / 30) * 100)) }}%"></div>
                    </div>
                </div>

                <!-- 10-Minute Next Tick Countdown -->
                <div class="border-l border-slate-800 pl-4 text-right">
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">10m Online Tick</span>
                    <span class="text-xs font-bold text-emerald-400">
                        @if(($player?->days_played_today ?? 0) >= 30)
                            <span class="text-amber-400">Daily Cap Reached</span>
                        @elseif($session?->next_daily_tick_at)
                            in {{ max(0, (int) round(now()->diffInSeconds($session->next_daily_tick_at, false) / 60)) }}m
                        @else
                            Active
                        @endif
                    </span>
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
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>⚡</span>
                        <span>Schedule Relational Action</span>
                    </h3>
                    @if($selectedNpc)
                        <span class="text-xs text-indigo-400 font-medium">Target: {{ $selectedNpc->name }}</span>
                    @else
                        <span class="text-xs text-amber-400 font-medium">Ward Platform Action</span>
                    @endif
                </div>

                <!-- Action Type Selector -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                    @foreach($actionTypes as $action)
                        <label 
                            class="flex items-start gap-2 p-2.5 rounded-xl border cursor-pointer transition-all {{ $selectedActionType === $action->value ? 'bg-indigo-950/50 border-indigo-500 text-white' : 'bg-slate-800/40 border-slate-800 text-slate-300 hover:border-slate-700' }}"
                        >
                            <input 
                                type="radio" 
                                name="action_type" 
                                value="{{ $action->value }}" 
                                wire:model.live="selectedActionType"
                                class="mt-1 text-indigo-500 focus:ring-indigo-500"
                            >
                            <div class="min-w-0">
                                <div class="text-xs font-semibold flex items-center justify-between gap-1">
                                    <span class="truncate">{{ $action->icon() }} {{ $action->label() }}</span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-800 text-amber-400 shrink-0">
                                        {{ $action->apCost() }} AP
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $action->fieldName() }}</div>
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
</div>
