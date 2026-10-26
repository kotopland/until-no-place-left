@php
    $selectedActionEnum = $selectedActionType ? \App\Enums\MinistryActionType::tryFrom($selectedActionType) : null;
    $selectedActionCost = $selectedActionEnum ? ($selectedActionEnum->apCost() + $commuteApCost) : 0;
    $hasEnoughApForSelected = $selectedActionEnum && ($player?->action_points ?? 0) >= $selectedActionCost;
    $remainingApAfterQueue = $selectedActionEnum ? max(0, ($player?->action_points ?? 0) - $selectedActionCost) : ($player?->action_points ?? 0);
    $targetPersonLabel = $selectedNpc ? $selectedNpc->displayName : ($selectedWard?->name ? $selectedWard->name.' (Ward Action)' : 'Ward Action');
@endphp


<div class="space-y-6 pb-28 lg:pb-0" wire:poll.300s="touchHeartbeat" x-data="{ 
    mobileStep: @entangle('mobileStep'),
    scrollToQueue() {
        this.$nextTick(() => {
            setTimeout(() => {
                const target = document.getElementById('queue-action-button') || document.getElementById('queue-action-button-placeholder') || document.getElementById('ready-to-queue-box');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 60);
        });
    },
    scrollToStep2() {
        this.mobileStep = 2;
        this.$nextTick(() => {
            setTimeout(() => {
                const breadcrumb = document.getElementById('step-2-breadcrumb');
                const box = document.getElementById('step-2-box');
                // Only select breadcrumb if it is actually rendered and visible (on desktop)
                const isBreadcrumbVisible = breadcrumb && (breadcrumb.offsetParent !== null || breadcrumb.getBoundingClientRect().height > 0);
                const target = isBreadcrumbVisible ? breadcrumb : (box || breadcrumb);
                if (target) {
                    const rect = target.getBoundingClientRect();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const navBar = document.querySelector('header.sticky') || document.querySelector('header');
                    const navHeight = navBar ? navBar.offsetHeight : 64;
                    // Generous extra offset so Step 2 scrolls higher up, avoiding sticky header obstruction with ample breathing room
                    const top = Math.max(0, rect.top + scrollTop - navHeight - 48);
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            }, 60);
        });
    }
}" @scroll-to-queue-box.window="scrollToQueue()" @scroll-to-step-2.window="scrollToStep2()">
    <!-- Welcome Guide Banner with Official Logo -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-100 via-indigo-50/60 to-slate-100 dark:from-slate-900 dark:via-indigo-950/40 dark:to-slate-900 border border-slate-200 dark:border-slate-800/80 p-4 shadow-sm dark:shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <img 
                src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                alt="Nagoya Oikos Logo" 
                class="w-12 h-12 object-contain rounded-xl border border-indigo-500/30 shadow-md shrink-0"
            >
            <div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>New to Nagoya Oikos?</span>
                    <span class="px-1.5 py-0.2 rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-semibold border border-indigo-300 dark:border-indigo-700">Story & Field Guide</span>
                </h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">
                    Explore the biblical narrative, the 5 Fields of CPM multiplication, and the rules of relational church planting in Japan.
                </p>
            </div>
        </div>

        <a 
            href="{{ route('welcome') }}" 
            class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white border border-indigo-400/40 transition shrink-0 flex items-center gap-1.5 cursor-pointer self-start sm:self-center shadow"
        >
            <span>📖</span>
            <span>Read Story & Rules</span>
        </a>
    </div>

    <!-- Header: Time & Energy Bandwidth Status Bar -->
    <div class="bg-gradient-to-r from-white via-indigo-50/50 to-white dark:from-slate-900 dark:via-indigo-950/60 dark:to-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xl">🗾</span>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Nagoya Relational Action HQ</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700">
                        {{ $session?->monthLabel ?? 'Month 1' }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                        <span>Day {{ $player?->current_game_day ?? 1 }} / 30</span>
                    </span>
                    @if($player?->isOnline)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-cyan-100 dark:bg-cyan-950 text-cyan-800 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800">
                            🟢 Online (10m Ticks Active)
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 font-medium leading-relaxed">
                    Relational evangelization takes steady faithfulness. 1 Game Day processes every 10 min while online (max 30 days/day). Remaining days resolve at midnight!
                </p>
            </div>

            <!-- AP Bandwidth Meter, Daily Limit & Digital Countdown Clock -->
            <div class="flex flex-wrap items-center gap-4 bg-slate-100/90 dark:bg-slate-950/90 px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl">
                <!-- Action Points Bandwidth -->
                <div class="pr-2">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Bandwidth AP:</span>
                        <span class="font-bold text-slate-900 dark:text-white ml-2">{{ $player?->action_points ?? 0 }} / {{ $player?->max_action_points ?? 12 }} AP</span>
                    </div>
                    <div class="w-28 h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-300" style="width: {{ $player?->remainingBandwidthPercent ?? 0 }}%"></div>
                    </div>
                </div>

                <!-- Overwork Fatigue & Burnout Status Meter -->
                <div class="border-l border-slate-200 dark:border-slate-800 pl-4 pr-2">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Vitality:</span>
                        <span class="font-bold ml-2 text-xs {{ $player?->is_burned_out ? 'text-rose-500' : (($player?->fatigue_penalty_ap ?? 0) > 0 ? 'text-amber-500' : 'text-emerald-500') }}">
                            @if($player?->is_burned_out)
                                🚨 Burnout ({{ $player?->burnoutMonthsRemaining($player?->current_game_month) }}m leave)
                            @elseif(($player?->fatigue_penalty_ap ?? 0) > 0)
                                ⚠️ -{{ $player->fatigue_penalty_ap }} AP Fatigue
                            @elseif(($player?->consecutive_exhaustion_days ?? 0) > 0)
                                ⚡ Overworked Today
                            @else
                                🕊️ Rested & Healthy
                            @endif
                        </span>
                    </div>
                    <div class="w-32 h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        @php
                            $fatigueWidth = $player?->is_burned_out ? 100 : min(100, (($player?->consecutive_exhaustion_days ?? 0) / 5) * 100);
                            $fatigueBarColor = $player?->is_burned_out ? 'bg-rose-500 animate-pulse' : (($player?->consecutive_exhaustion_days ?? 0) >= 3 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="h-full {{ $fatigueBarColor }} rounded-full transition-all duration-300" style="width: {{ $fatigueWidth }}%"></div>
                    </div>
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 block mt-0.5 truncate max-w-[130px]">
                        {{ $player?->is_burned_out ? 'Mandatory Sabbatical' : ($player?->consecutive_exhaustion_days ? $player->consecutive_exhaustion_days.'d overwork without rest' : 'Sabbath rhythm active') }}
                    </span>
                </div>

                <!-- Daily Real-Life Play Cap (30 Days Max) -->
                <div class="border-l border-slate-200 dark:border-slate-800 pl-4 pr-2">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Played Today:</span>
                        <span class="font-bold {{ ($player?->days_played_today ?? 0) >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200' }} ml-2">
                            {{ $player?->days_played_today ?? 0 }} / 30 Days
                        </span>
                    </div>
                    <div class="w-28 h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full {{ ($player?->days_played_today ?? 0) >= 30 ? 'bg-amber-500 shadow-sm shadow-amber-400' : 'bg-cyan-500' }} rounded-full transition-all duration-300" style="width: {{ min(100, round((($player?->days_played_today ?? 0) / 30) * 100)) }}%"></div>
                    </div>
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 block mt-0.5">New month every night</span>
                </div>

                <!-- Digital Clock Visualizer (10m Countdown) -->
                <div 
                    wire:key="digital-clock-{{ $player?->current_game_day }}-{{ $player?->days_processed_this_month }}-{{ $player?->days_played_today }}"
                    x-data="digitalClock({{ max(0, $player?->secondsUntilNextDailyTick ?? 600) }}, '{{ $player?->next_daily_tick_at?->toIso8601String() }}', {{ (!($player?->hasReachedDailyPlayLimit ?? false) && ($player?->days_processed_this_month ?? 0) < 30) ? 'true' : 'false' }})"
                    x-init="init()"
                    class="border-l border-slate-200 dark:border-slate-800 pl-4 flex items-center gap-3"
                >
                    <div>
                        <div class="text-[9px] uppercase font-bold tracking-wider mb-1 flex items-center gap-1.5 {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ ($player?->hasReachedDailyPlayLimit ?? false) ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse' }}"></span>
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

    <!-- Action HQ Mode / Sub-Menu Navigation Switcher -->
    <div class="flex items-center justify-between gap-3 p-1.5 bg-slate-900/90 border border-slate-800 rounded-2xl mb-4 shadow-md">
        <div class="flex items-center gap-1.5 flex-wrap">
            <!-- Sub-tab 1: Field Actions (Steps 1, 2, 3) -->
            <button 
                type="button" 
                wire:click="setHqSubView('planner')" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer {{ $hqSubView === 'planner' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-1 ring-indigo-400/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}"
            >
                <span>🎯</span>
                <span>Field Actions (Steps 1–3)</span>
            </button>

            <!-- Sub-tab 2: Spiritual Rhythms & Movement Retreats -->
            <button 
                type="button" 
                wire:click="setHqSubView('rhythms')" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer {{ $hqSubView === 'rhythms' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-1 ring-indigo-400/50' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' }}"
            >
                <span>🕊️</span>
                <span>Spiritual Rhythms &amp; Retreats</span>
                @if($isDayBlocked)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1 animate-pulse">
                        <span>⛔</span>
                        <span>Active</span>
                    </span>
                @else
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-800 text-slate-300">
                        4 Rhythms
                    </span>
                @endif
            </button>
        </div>

        @if($isDayBlocked)
            <!-- Compact Active Retreat Indicator in Sub-Menu -->
            <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-xl bg-amber-950/60 border border-amber-800/60 text-xs text-amber-300">
                <span>{{ $activeWorkerRhythm?->action_type?->icon() ?? '🕊️' }}</span>
                <span class="font-bold truncate max-w-[200px]">Today: {{ $activeWorkerRhythm?->actionTitle ?? 'Worker Rhythm' }}</span>
                @if($hqSubView === 'planner')
                    <button 
                        type="button" 
                        wire:click="setHqSubView('rhythms')" 
                        class="text-[11px] underline font-bold hover:text-white cursor-pointer ml-1"
                    >
                        Details →
                    </button>
                @endif
            </div>
        @endif
    </div>

    @if($hqSubView === 'rhythms')
        <!-- Dedicated Sub-View: Worker Spiritual Rhythms & Movement Retreats -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl mb-4 transition-all space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🕊️</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-white uppercase tracking-wider">
                                Worker Spiritual Rhythms &amp; Strategy Retreats
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-950/90 text-indigo-300 border border-indigo-700/60">
                                Time-Blocking Rest &amp; Strategy
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Gain points and spiritual renewal through mentoring, sabbath, fasting retreats, and NPL mid-level strategy conferences. These activities block time for all other actions.
                        </p>
                    </div>
                </div>

                <button 
                    type="button" 
                    wire:click="setHqSubView('planner')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition flex items-center gap-1.5 cursor-pointer shadow"
                >
                    <span>➔</span>
                    <span>Back to Field Actions (Steps 1–3)</span>
                </button>
            </div>

            <!-- Active Retreat Notice Banner if currently time-blocked -->
            @if($isDayBlocked && $activeWorkerRhythm)
                <div class="p-3.5 rounded-xl bg-gradient-to-r from-amber-950/40 via-indigo-950/40 to-slate-900 border border-amber-700/50 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">{{ $activeWorkerRhythm->action_type->icon() }}</span>
                        <div>
                            <div class="text-xs font-bold text-white flex items-center gap-2">
                                <span>Currently Active Today: {{ $activeWorkerRhythm->actionTitle }}</span>
                                <span class="text-[10px] font-semibold text-emerald-400">+{{ $activeWorkerRhythm->action_type->rhythmPoints() }} pts upon day advance</span>
                            </div>
                            <p class="text-[11px] text-amber-200/90 mt-0.5">
                                Day {{ $player?->current_game_day ?? 1 }} is reserved. All other field actions are blocked until this rhythm completes or is cancelled.
                            </p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        wire:click="cancelAction({{ $activeWorkerRhythm->id }})"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-300 bg-rose-950/60 hover:bg-rose-900/80 border border-rose-800 transition cursor-pointer"
                        title="Cancel rhythm and unblock day"
                    >
                        Cancel Rhythm ✕
                    </button>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                @foreach($workerRhythmStatuses as $key => $status)
                    @php
                        $actionEnum = $status['actionType'];
                    @endphp
                    <div class="p-4 rounded-2xl border flex flex-col justify-between transition-all {{ $status['active'] ? 'bg-indigo-950/40 border-indigo-500/60 shadow-lg ring-1 ring-indigo-500/40' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $actionEnum->icon() }}</span>
                                    <div>
                                        <h4 class="text-xs font-bold text-white leading-tight">
                                            {{ $actionEnum->label() }}
                                        </h4>
                                        <span class="text-[10px] text-slate-400 block">
                                            {{ $actionEnum->labelJa() }}
                                        </span>
                                    </div>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold shrink-0 {{ $status['active'] ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-900 text-slate-300 border border-slate-800' }}">
                                    {{ $status['days'] }} {{ Str::plural('Day', $status['days']) }}
                                </span>
                            </div>

                            <!-- Rewards & Duration Pills -->
                            <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950/70 text-emerald-300 border border-emerald-800/60">
                                    🏆 +{{ $status['points'] }} Points
                                </span>
                                @if($key === 'sabbath_rest')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950/70 text-amber-300 border border-amber-800/60">
                                        ⚡ 100% AP Reset
                                    </span>
                                @endif
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-900 text-slate-400 border border-slate-800">
                                    {{ in_array($key, ['fasting_retreat', 'midlevel_conference']) ? 'Max 1/mo' : 'Max 1/wk' }}
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-300/90 mt-2.5 leading-relaxed">
                                @if($key === 'midlevel_conference')
                                    A problem-solving and strategy retreat for active movement practitioners rather than an introductory training session.
                                @else
                                    {{ $actionEnum->description() }}
                                @endif
                            </p>
                        </div>

                        <div class="mt-3.5 pt-2.5 border-t border-slate-800/80">
                            @if($status['active'])
                                <div class="w-full py-1.5 px-3 rounded-lg text-center text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-800 flex items-center justify-center gap-1.5">
                                    <span>✓</span>
                                    <span>Scheduled for Today</span>
                                </div>
                            @elseif($status['eligible'])
                                <button 
                                    type="button" 
                                    wire:click="scheduleWorkerRhythm('{{ $actionEnum->value }}')"
                                    wire:loading.attr="disabled"
                                    class="w-full py-2 px-3 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] text-white shadow-md shadow-indigo-950 transition cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <span wire:loading.remove wire:target="scheduleWorkerRhythm('{{ $actionEnum->value }}')">
                                        <span>🗓️</span>
                                        <span>Schedule (Day {{ $player?->current_game_day ?? 1 }}{{ $status['days'] > 1 ? '–' . min(30, ($player?->current_game_day ?? 1) + $status['days'] - 1) : '' }})</span>
                                    </span>
                                    <span wire:loading wire:target="scheduleWorkerRhythm('{{ $actionEnum->value }}')" class="flex items-center gap-1">
                                        <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                        <span>Scheduling...</span>
                                    </span>
                                </button>
                            @else
                                <button 
                                    type="button" 
                                    disabled
                                    class="w-full py-1.5 px-3 rounded-lg text-[11px] font-medium bg-slate-900 text-slate-500 border border-slate-800/80 cursor-not-allowed text-center truncate"
                                    title="{{ $status['reason'] }}"
                                >
                                    {{ $status['reason'] ?? 'Unavailable' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <!-- Planner Sub-View: Field Actions (Steps 1, 2, 3) -->
        @if($isDayBlocked && $activeWorkerRhythm)
            <div class="mb-3 p-3 rounded-xl bg-gradient-to-r from-amber-950/40 via-indigo-950/20 to-slate-900 border border-amber-700/60 flex items-center justify-between gap-3 text-xs text-amber-300 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-base">{{ $activeWorkerRhythm->action_type?->icon() ?? '⛔' }}</span>
                    <span><strong>Day {{ $player?->current_game_day ?? 1 }} is reserved for {{ $activeWorkerRhythm->actionTitle }}.</strong> Field actions cannot be scheduled today. Advance to the next day to resolve and gain points!</span>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        wire:click="setHqSubView('rhythms')"
                        class="text-xs font-bold text-amber-200 hover:text-white px-2.5 py-1 rounded-lg bg-amber-900/60 hover:bg-amber-800 transition cursor-pointer"
                    >
                        View Rhythms ➔
                    </button>
                    <button 
                        type="button" 
                        wire:click="cancelAction({{ $activeWorkerRhythm->id }})"
                        class="text-xs font-bold text-rose-300 hover:text-rose-200 px-2.5 py-1 rounded-lg bg-rose-950/60 hover:bg-rose-900 transition cursor-pointer"
                    >
                        Cancel Rhythm ✕
                    </button>
                </div>
            </div>
        @endif

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

            <!-- Step 3 Button: Action & Queue -->
            <button 
                type="button" 
                @click="mobileStep = 3; $wire.setMobileStep(3)"
                class="flex-1 py-2 px-1.5 rounded-xl text-center transition-all flex flex-col items-center justify-center gap-0.5 cursor-pointer"
                :class="mobileStep === 3 ? 'bg-indigo-600 text-white shadow-md font-bold ring-1 ring-indigo-400' : 'bg-slate-800/60 text-slate-400 hover:text-white'"
            >
                <span class="text-[9px] uppercase tracking-wider flex items-center gap-1 opacity-90">
                    <span class="w-3.5 h-3.5 rounded-full bg-white/20 inline-flex items-center justify-center text-[8px] font-bold">3</span>
                    <span>Queue</span>
                </span>
                <span class="text-xs font-bold truncate max-w-[95px] text-amber-300">
                    {{ $player->action_points ?? 12 }} AP left
                </span>
            </button>
        </div>
    </div>

    @if($player?->is_burned_out)
        <div class="mb-5 p-5 rounded-3xl bg-rose-950/80 border border-rose-800 shadow-2xl space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-3xl animate-bounce">🚨</span>
                    <div>
                        <h3 class="text-sm md:text-base font-black text-rose-200">Severe Ministry Burnout — Mandatory 3-Month Sabbatical Leave</h3>
                        <p class="text-xs text-rose-300/80">
                            You worked to total exhaustion without observing weekly Sabbath rest, draining all your AP for 5 consecutive days.
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-xl text-xs font-black bg-rose-900 text-rose-100 border border-rose-600 shadow-md">
                    {{ $player?->burnoutMonthsRemaining($player?->current_game_month) }} Month(s) Remaining
                </span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                All field ministry and training scheduling are suspended to allow physical, emotional, and spiritual recovery until <strong>Month {{ $player?->burned_out_until_game_month }}</strong>. You can advance game months to complete your mandatory sabbatical rest.
            </p>
        </div>
    @endif

    <!-- Desktop 3-Step Guided Workflow Bar (Visible on lg screens) -->
    <div class="hidden lg:grid grid-cols-12 gap-5 mb-1">
        <!-- Step 1: District -->
        <div class="col-span-3 px-3.5 py-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/90 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white font-extrabold text-[11px] flex items-center justify-center shadow-sm">1</span>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold leading-none">Step 1</span>
                    <span class="font-bold text-white leading-tight">Pick District</span>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-950 text-indigo-300 border border-slate-800 truncate max-w-[100px]">
                {{ $selectedWard?->name ?? 'Select Ward' }}
            </span>
        </div>

        <!-- Step 2: Seeker / Resident -->
        <div id="step-2-breadcrumb" class="scroll-mt-28 col-span-4 px-3.5 py-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/90 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white font-extrabold text-[11px] flex items-center justify-center shadow-sm">2</span>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold leading-none">Step 2</span>
                    <span class="font-bold text-white leading-tight">Pick Resident / Seeker</span>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-950 text-indigo-300 border border-slate-800 truncate max-w-[130px]">
                {{ $selectedNpc?->displayName ?? 'Ward-wide Action' }}
            </span>
        </div>

        <!-- Step 3: Action & Queue -->
        <div class="col-span-5 px-3.5 py-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/90 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white font-extrabold text-[11px] flex items-center justify-center shadow-sm">3</span>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold leading-none">Step 3 • Action HQ</span>
                    <span class="font-bold text-white leading-tight">Queue for Day {{ $player?->current_game_day ?? 1 }}</span>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-950 text-amber-300 border border-slate-800">
                    ⚡ {{ $player?->action_points ?? 0 }} AP Available
                </span>
            </div>
        </div>
    </div>

    <!-- Main Planning Grid (3 Columns on Desktop, Step-Focused on Mobile) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Column 1: Nagoya 16 Wards (lg:col-span-3) -->
        <div 
            class="lg:col-span-3 bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 shadow-lg flex-col h-[660px] sm:h-[700px] lg:h-[780px] xl:h-[840px] relative"
            :class="mobileStep === 1 ? 'flex' : 'hidden lg:flex'"
        >
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
                <div class="flex items-center gap-1.5">
                    <span class="text-sm">📍</span>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                        Nagoya 16 Wards
                    </h3>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 flex items-center gap-1 shadow-sm">
                        <span class="animate-pulse">↕</span>
                        <span>Scrollable</span>
                    </span>
                    <span class="text-[11px] text-slate-400 font-medium">{{ $wards->count() }} districts</span>
                </div>
            </div>

            <!-- Scrollable Wards Container with Interactive Scroll Indicator -->
            <div 
                x-data="{
                    canScrollDown: true,
                    checkScroll(el) {
                        if (!el) return;
                        this.canScrollDown = (el.scrollHeight - el.scrollTop) > (el.clientHeight + 16);
                    }
                }"
                x-init="$nextTick(() => checkScroll($refs.wardsScroll))"
                class="relative flex-1 min-h-0 flex flex-col"
            >
                <div 
                    x-ref="wardsScroll"
                    @scroll.passive="checkScroll($event.target)"
                    class="overflow-y-auto space-y-1.5 pr-1.5 flex-1 custom-scrollbar pb-8"
                >
                    @foreach($wards as $ward)
                        @php
                            $isHome = $player && $player->home_ward_id === $ward->id;
                            $distKm = $player ? $player->distanceToWard($ward) : 0.0;
                            $transitAp = $player ? $player->commuteApCostForWard($ward) : 0;
                            $otherMissionariesInWard = ($residentMissionariesByWard->get($ward->id) ?? collect())->filter(fn($u) => $u->id !== $player?->id);
                        @endphp
                        <button 
                            type="button"
                            wire:key="ward-btn-{{ $ward->id }}"
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

                <!-- Floating Bottom Scroll Indicator -->
                <div 
                    x-show="canScrollDown"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="pointer-events-none absolute bottom-0 inset-x-0 flex flex-col items-center justify-end pb-1.5 pt-8 bg-gradient-to-t from-slate-900 via-slate-900/90 to-transparent rounded-b-xl"
                >
                    <button
                        type="button"
                        @click="$refs.wardsScroll.scrollBy({ top: 140, behavior: 'smooth' })"
                        class="pointer-events-auto cursor-pointer px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-600/95 hover:bg-indigo-500 text-white shadow-lg border border-indigo-400/50 flex items-center gap-1.5 backdrop-blur transition active:scale-95 animate-bounce"
                        title="Click to scroll down"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        <span>Scroll for more wards</span>
                    </button>
                </div>
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
            id="step-2-box"
            class="scroll-mt-28 lg:col-span-4 bg-slate-900/70 border border-slate-800/80 rounded-2xl p-4 shadow-lg flex-col h-[660px] sm:h-[700px] lg:h-[780px] xl:h-[840px] relative"
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

                <div class="flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-700/60 flex items-center gap-1 shadow-sm">
                        <span class="animate-pulse">↕</span>
                        <span>Scrollable</span>
                    </span>
                    @if($selectedWard && $selectedWard->poverty_need_index >= 50)
                        <span class="px-2 py-0.5 rounded text-[10px] bg-rose-950 text-rose-300 border border-rose-800">
                            Urgent Mercy Hub
                        </span>
                    @endif
                </div>
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

                    @if($aiCoLaborersInWard->isNotEmpty())
                        <div class="mt-2.5 pt-2 border-t border-slate-700/60 space-y-2">
                            @foreach($aiCoLaborersInWard as $aiCoLaborer)
                                <div wire:key="colaborer-{{ $aiCoLaborer->id }}" class="bg-indigo-950/40 border border-indigo-500/30 rounded-xl p-2.5 text-xs">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <x-person-avatar :person="$aiCoLaborer" size="sm" :showBadges="true" class="shrink-0" />
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold text-white">{{ $aiCoLaborer->name }}</span>
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                                        {{ $aiCoLaborer->archetypeBadgeLabel }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 mt-0.5">
                                                    {{ $aiCoLaborer->nationalityFlag }} {{ $aiCoLaborer->nationality }} • {{ $aiCoLaborer->genderIcon }} {{ $aiCoLaborer->age }}y
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-indigo-300 font-medium">
                                            Co-op: {{ $aiCoLaborer->coop_synergy_score }} pts
                                        </span>
                                    </div>
                                    @if($aiCoLaborer->ai_strategy_notes)
                                        <p class="text-[10px] text-slate-300 mt-1 leading-snug">
                                            {{ $aiCoLaborer->ai_strategy_notes }}
                                        </p>
                                    @endif
                                    <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                        @if($jointWalkStatus['allowed'] ?? true)
                                            <button 
                                                type="button"
                                                wire:click="conductJointPrayerWalk({{ $aiCoLaborer->id }})"
                                                class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] transition cursor-pointer shadow flex items-center gap-1 active:scale-95"
                                                title="Conduct a 2-by-2 joint prayer walk with {{ $aiCoLaborer->name }} (+15 Co-op score, +50% ward openness softening). {{ $jointWalkStatus['remaining_this_week'] ?? 3 }}/3 left this week (max 1/day)."
                                            >
                                                <span>👣</span>
                                                <span>Joint Prayer Walk (2 AP)</span>
                                                <span class="text-[9px] text-emerald-200 opacity-90">({{ $jointWalkStatus['remaining_this_week'] ?? 3 }}/3 left)</span>
                                            </button>
                                        @else
                                            <button 
                                                type="button"
                                                disabled
                                                class="px-2 py-1 rounded-lg bg-slate-800 text-slate-400 font-bold text-[10px] cursor-not-allowed opacity-75 flex items-center gap-1 border border-slate-700/60"
                                                title="{{ $jointWalkStatus['reason'] ?? 'Joint Prayer Walk limit reached.' }}"
                                            >
                                                <span>👣</span>
                                                <span>Joint Prayer Walk (2 AP)</span>
                                                <span class="text-[9px] {{ ($jointWalkStatus['walks_today'] ?? 0) >= 1 ? 'text-amber-400' : 'text-rose-400' }}">({{ ($jointWalkStatus['walks_today'] ?? 0) >= 1 ? '1/day cap' : '3/wk cap' }})</span>
                                            </button>
                                        @endif
                                        @php
                                            $isCoLaborerDoneToday = in_array($aiCoLaborer->id, $doneColaborerIdsToday ?? [], true);
                                            $isIoICappedToday = ($totalIoIToday ?? 0) >= 3;
                                        @endphp
                                        @if(! $isCoLaborerDoneToday && ! $isIoICappedToday)
                                            <button 
                                                type="button"
                                                wire:click="conductIronOnIron({{ $aiCoLaborer->id }})"
                                                class="px-2 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] transition cursor-pointer shadow flex items-center gap-1 active:scale-95"
                                                title="Iron-on-Iron meeting with {{ $aiCoLaborer->name }} (+2 bonus AP, +25 Co-op score, and tactical missionary advice). {{ $remainingIoIToday ?? 3 }}/3 sessions left today (max 1/person/day)."
                                            >
                                                <span>⚔️</span>
                                                <span>Iron-on-Iron (+2 AP)</span>
                                                <span class="text-[9px] text-indigo-200 opacity-90">({{ $remainingIoIToday ?? 3 }}/3 left)</span>
                                            </button>
                                        @elseif($isCoLaborerDoneToday)
                                            <button 
                                                type="button"
                                                disabled
                                                class="px-2 py-1 rounded-lg bg-slate-800 text-slate-400 font-bold text-[10px] cursor-not-allowed opacity-75 flex items-center gap-1 border border-slate-700/60"
                                                title="You have already conducted Iron on Iron with {{ $aiCoLaborer->name }} today (max 1 per person per day)."
                                            >
                                                <span>⚔️</span>
                                                <span>Iron-on-Iron (+2 AP)</span>
                                                <span class="text-[9px] text-amber-400">(Done today)</span>
                                            </button>
                                        @else
                                            <button 
                                                type="button"
                                                disabled
                                                class="px-2 py-1 rounded-lg bg-slate-800 text-slate-400 font-bold text-[10px] cursor-not-allowed opacity-75 flex items-center gap-1 border border-slate-700/60"
                                                title="Daily Iron on Iron limit reached: max 3 sessions allowed per day (3/3 used today)."
                                            >
                                                <span>⚔️</span>
                                                <span>Iron-on-Iron (+2 AP)</span>
                                                <span class="text-[9px] text-rose-400">(3/day cap)</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($coLaborersInWard->isNotEmpty())
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

            <!-- Scrollable Residents Container with Interactive Scroll Indicator -->
            <div 
                x-data="{
                    canScrollDown: true,
                    checkScroll(el) {
                        if (!el) return;
                        this.canScrollDown = (el.scrollHeight - el.scrollTop) > (el.clientHeight + 16);
                    }
                }"
                x-init="$nextTick(() => checkScroll($refs.residentsScroll))"
                class="relative flex-1 min-h-0 flex flex-col"
            >
                <div 
                    x-ref="residentsScroll"
                    @scroll.passive="checkScroll($event.target)"
                    class="overflow-y-auto space-y-2.5 pr-1.5 flex-1 custom-scrollbar pb-8"
                >
                    @forelse($npcs as $npc)
                        <div 
                            wire:key="npc-card-{{ $npc->id }}"
                            wire:click="selectNpc({{ $npc->id }})"
                            @click="mobileStep = 3"
                            class="p-3 rounded-xl border transition-all cursor-pointer {{ $selectedNpcId === $npc->id ? 'bg-indigo-950/40 border-indigo-500 shadow-md ring-1 ring-indigo-500/50' : 'bg-slate-800/40 border-slate-800 hover:border-slate-700' }}"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Demographic Avatar (Nationality, Gender, Age) -->
                                <x-person-avatar :person="$npc" size="md" :showBadges="true" class="shrink-0" />

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-sm font-semibold text-white truncate">{{ $npc->displayName }}</span>
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
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-blue-950 text-blue-300 border border-blue-700 font-bold" title="Committed to Baptism (Month {{ $npc->baptism_cost_months }}/{{ $npc->target_baptism_months }} in 6mo-2yr journey)">
                                                        🌊 Committed ({{ $npc->baptism_cost_months }}/{{ $npc->target_baptism_months }}m)
                                                    </span>
                                                @elseif($npc->hasPrayedSalvation)
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-950 text-amber-300 border border-amber-700 font-bold" title="Counting Cost / Family Consideration (Month {{ $npc->baptism_cost_months }}/{{ $npc->target_baptism_months }} in 6mo-2yr journey)">
                                                        💭 Cost ({{ $npc->baptism_cost_months }}/{{ $npc->target_baptism_months }}m)
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
                                            <div class="flex items-center gap-1.5 text-[11px] text-slate-400 mt-0.5 flex-wrap">
                                                <span>{{ $npc->role_archetype->label() }}</span>
                                                <span class="text-slate-600">•</span>
                                                <span class="text-slate-300 font-medium" title="{{ $npc->nationality }}">{{ $npc->nationalityFlag }} {{ $npc->nationality }}</span>
                                                <span class="text-slate-600">•</span>
                                                <span class="text-slate-300">{{ $npc->genderIcon }} {{ $npc->age }} yrs</span>
                                            </div>
                                        </div>

                                        <!-- Traffic Light Status -->
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold border shrink-0 {{ $npc->status->badgeClasses() }}" title="{{ $npc->status === \App\Enums\ReceptivityStatus::Yellow ? "Seeking for {$npc->seeking_months} of {$npc->target_seeking_months} months (gradual conversion journey in Japan)" : $npc->status->value }}">
                                            {{ $npc->status->icon() }} {{ $npc->status->value }}
                                            @if($npc->status === \App\Enums\ReceptivityStatus::Yellow)
                                                <span class="text-[9px] font-normal opacity-90">({{ $npc->seeking_months }}/{{ $npc->target_seeking_months }}m)</span>
                                            @endif
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
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-slate-500 text-xs">
                            No residents surfaced yet in this ward.<br>Host an International Event or Prayer Walk to discover seekers!
                        </div>
                    @endforelse
                </div>

                <!-- Floating Bottom Scroll Indicator for Residents -->
                <div 
                    x-show="canScrollDown && {{ $npcs->count() }} > 2"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="pointer-events-none absolute bottom-0 inset-x-0 flex flex-col items-center justify-end pb-1.5 pt-8 bg-gradient-to-t from-slate-900 via-slate-900/90 to-transparent rounded-b-xl"
                >
                    <button
                        type="button"
                        @click="$refs.residentsScroll.scrollBy({ top: 160, behavior: 'smooth' })"
                        class="pointer-events-auto cursor-pointer px-3 py-1 rounded-full text-[10px] font-bold bg-indigo-600/95 hover:bg-indigo-500 text-white shadow-xl border border-indigo-400/50 flex items-center gap-1.5 backdrop-blur transition active:scale-95 animate-bounce"
                        title="Click to scroll down"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        <span>Scroll for more people ({{ $npcs->count() }} total)</span>
                    </button>
                </div>
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
                        <div class="flex items-center gap-2.5">
                            <x-person-avatar :person="$selectedNpc" size="md" :showBadges="true" />
                            <div class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <span class="text-xs text-indigo-400 font-bold block">{{ $selectedNpc->displayName }}</span>
                                    @if($selectedNpc->leadership_tier->level() > 0)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $selectedNpc->leadership_tier->badgeClasses() }}">
                                            {{ $selectedNpc->leadership_tier->levelCode() }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-300">
                                    <span>{{ $selectedNpc->nationalityFlag }} {{ $selectedNpc->nationality }}</span> • <span>{{ $selectedNpc->genderIcon }} {{ $selectedNpc->age }}y</span>
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
                            <span>{{ $filterEligibleOnly ? 'Eligible Only' : 'Filter by Ability' }}</span>
                        </button>
                    </div>
                @else
                    <div class="mb-3 p-2.5 rounded-xl bg-indigo-950/40 border border-indigo-800/60 text-[11px] text-indigo-300 flex items-center justify-between gap-2 shadow-inner">
                        <div class="flex items-center gap-2">
                            <span>💡</span>
                            <span><strong>No resident selected:</strong> Pick a person in Step 2 to unlock 1-on-1 ministry actions (Casual Talk, 3 Circles, Discipleship, Mentoring), or choose a Ward Action below like <strong>Prayer Walk</strong> or <strong>Cultural Event</strong>.</span>
                        </div>
                    </div>
                @endif

                <!-- Action Type Selector -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
                    @foreach($actionTypes as $action)
                        @php
                            $isEligible = $action->isEligibleFor($selectedNpc);
                            $ineligibilityReason = $action->ineligibilityReason($selectedNpc);
                            if ($action === \App\Enums\MinistryActionType::IronOnIron && $isEligible) {
                                if (($totalIoIToday ?? 0) >= 3) {
                                    $isEligible = false;
                                    $ineligibilityReason = 'Daily Iron on Iron limit reached: max 3 sessions allowed per day (3/3 used today).';
                                } elseif ($selectedNpc && in_array($selectedNpc->id, $doneNpcIdsToday ?? [], true)) {
                                    $isEligible = false;
                                    $ineligibilityReason = 'You have already scheduled Iron on Iron with '.$selectedNpc->displayName.' today (max 1 per person per day).';
                                }
                            }
                            $isSelected = $selectedActionType === $action->value;
                            $isRecommended = $selectedNpc && ($recommendedActionType === $action->value);
                        @endphp
                        <label 
                            @if($isEligible)
                                @click="scrollToQueue()"
                            @endif
                            class="flex items-start gap-2.5 p-2.5 rounded-xl border transition-all relative {{ ! $isEligible ? 'opacity-60 bg-slate-900/40 border-slate-800/60 text-slate-400 cursor-not-allowed' : ($isSelected ? 'bg-slate-800 border-2 border-emerald-400 text-white shadow-lg ring-2 ring-emerald-500/40 cursor-pointer' : 'bg-slate-800/40 border-slate-800 text-slate-300 hover:border-slate-700 cursor-pointer') }}"
                            title="{{ $isEligible ? $action->description() : 'Locked: '.$ineligibilityReason }}"
                        >
                            <input 
                                type="radio" 
                                name="action_type" 
                                value="{{ $action->value }}" 
                                wire:model.live="selectedActionType"
                                @disabled(! $isEligible)
                                class="mt-1 text-emerald-500 focus:ring-emerald-500 disabled:opacity-40"
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
                                    <div class="flex items-center gap-1 shrink-0 flex-wrap justify-end">
                                        @if($isSelected && $isEligible)
                                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-emerald-500 text-slate-950 shadow-sm flex items-center gap-0.5">
                                                <span>✓</span>
                                                <span>SELECTED</span>
                                            </span>
                                        @endif
                                        @if($isRecommended && ! $isSelected)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-0.5" title="Recommended action for this person">
                                                <span>💡</span>
                                                <span>Recommended</span>
                                            </span>
                                        @endif
                                        @if(! $isEligible)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-950/80 text-rose-300 border border-rose-800/60 flex items-center gap-0.5" title="{{ $ineligibilityReason }}">
                                                <span>🔒</span>
                                                <span>Locked</span>
                                            </span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $isSelected ? 'bg-slate-950 text-amber-300 border border-slate-700' : 'bg-slate-900/90 text-amber-400 border border-slate-700/50' }} flex items-center gap-1">
                                            <span>{{ $action->apCost() + $commuteApCost }} AP</span>
                                            @if($commuteApCost > 0)
                                                <span class="text-[9px] text-amber-300 font-normal opacity-90" title="Includes +{{ $commuteApCost }} AP transit cost from your home base">(+{{ $commuteApCost }})</span>
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

                <!-- Step 3: READY TO QUEUE DAY X Box (Solid single color, clean section header, scrolls here on action click) -->
                <div id="ready-to-queue-box" class="mt-4 p-4 rounded-2xl bg-slate-950 border border-slate-800 shadow-lg space-y-3">
                    <!-- Section Header (Clean header, not styled like a button) -->
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                        <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                            <span>⚡</span>
                            <span>Ready to Queue • Day {{ $player?->current_game_day ?? 1 }}</span>
                        </h3>
                        @if($selectedActionEnum)
                            <span class="text-xs font-bold {{ $hasEnoughApForSelected ? 'text-amber-400' : 'text-rose-400' }}">
                                Cost: {{ $selectedActionCost }} AP
                                @if($commuteApCost > 0)
                                    <span class="text-[10px] text-amber-300 font-normal">(+{{ $commuteApCost }} transit)</span>
                                @endif
                            </span>
                        @else
                            <span class="text-[10px] text-slate-500 font-medium">Step 3 of 3</span>
                        @endif
                    </div>

                    @if($isDayBlocked)
                        <!-- Time Blocked Alert Banner for Step 3 -->
                        <div class="p-3.5 rounded-xl bg-amber-950/40 border border-amber-800/60 text-xs text-amber-300 flex items-start gap-2.5">
                            <span class="text-base shrink-0">⛔</span>
                            <div>
                                <div class="font-bold text-amber-200">Field Actions Time-Blocked for Day {{ $player?->current_game_day ?? 1 }}</div>
                                <p class="text-[11px] text-amber-300/90 mt-0.5 leading-relaxed">
                                    You are participating in <strong>{{ $activeWorkerRhythm?->actionTitle ?? 'a worker spiritual rhythm' }}</strong> today.
                                    Worker rhythms consume your schedule for this day. Regular field actions cannot be queued. Advance the day to continue, or cancel the retreat in the Queued Actions list.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($selectedActionEnum)
                        <!-- Selected Action Summary -->
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="text-base">{{ $selectedActionEnum->icon() }}</span>
                                    <span class="text-xs font-bold text-white truncate">{{ $selectedActionEnum->label() }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300 shrink-0">
                                        {{ $selectedActionEnum->fieldName() }}
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $hasEnoughApForSelected ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-rose-950 text-rose-300 border border-rose-800' }}">
                                    {{ $hasEnoughApForSelected ? 'AP Available' : 'Low AP' }}
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-300 mt-2 flex items-center gap-2 flex-wrap">
                                <span class="flex items-center gap-1.5">
                                    @if($selectedNpc)
                                        <x-person-avatar :person="$selectedNpc" size="xs" :showBadges="false" class="shrink-0" />
                                    @endif
                                    <span>🎯 Target: <strong class="text-white">{{ $targetPersonLabel }}</strong></span>
                                </span>
                                <span class="text-slate-600">•</span>
                                <span class="font-bold {{ $hasEnoughApForSelected ? 'text-amber-400' : 'text-rose-400' }}">
                                    ⚡ {{ $selectedActionCost }} AP
                                    @if($commuteApCost > 0)
                                        <span class="text-[10px] text-amber-300 font-normal">(+{{ $commuteApCost }} transit)</span>
                                    @endif
                                </span>
                                <span class="text-slate-600">•</span>
                                <span class="text-slate-400 text-[10px]">
                                    Remaining: <strong class="text-slate-200">{{ $remainingApAfterQueue }} AP</strong>
                                </span>
                            </div>
                        </div>

                        <!-- Optional Notes -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                                Personal / Prayer Notes (Optional):
                            </label>
                            <input 
                                type="text" 
                                wire:model="notes" 
                                placeholder="Add prayer focus or personal notes (optional)..." 
                                class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                            >
                        </div>

                        <!-- Bottom Queue Button -->
                        <button 
                            id="queue-action-button"
                            type="button" 
                            wire:click="queueAction"
                            @click="scrollToStep2()"
                            wire:loading.attr="disabled"
                            @disabled($isDayBlocked || ! $hasEnoughApForSelected || ($player?->is_burned_out ?? false))
                            class="w-full py-3 px-4 rounded-xl font-black text-xs transition-all flex items-center justify-center gap-2 cursor-pointer shadow-lg active:scale-[0.99] {{ ($hasEnoughApForSelected && ! $isDayBlocked && ! ($player?->is_burned_out ?? false)) ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-950 ring-2 ring-emerald-400/50 hover:scale-[1.01]' : 'bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed opacity-60' }}"
                        >
                            @if($player?->is_burned_out)
                                <span class="flex items-center gap-1.5 text-rose-300">
                                    <span>🚨</span>
                                    <span>Mandatory Sabbatical: Burnout Recovery ({{ $player?->burnoutMonthsRemaining($player?->current_game_month) }}m left)</span>
                                </span>
                            @elseif($isDayBlocked)
                                <span class="flex items-center gap-1.5 text-amber-300">
                                    <span>⛔</span>
                                    <span>Day {{ $player?->current_game_day ?? 1 }} Blocked by {{ $activeWorkerRhythm?->actionTitle ?? 'Worker Rhythm' }}</span>
                                </span>
                            @else
                                <span wire:loading.remove wire:target="queueAction" class="flex items-center gap-1.5">
                                    <span class="text-sm">➕</span>
                                    <span>Queue for Day {{ $player?->current_game_day ?? 1 }} • {{ $selectedActionCost }} AP (Executes on Next Day)</span>
                                </span>
                                <span wire:loading wire:target="queueAction" class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    <span>Queueing Action for Day {{ $player?->current_game_day ?? 1 }}...</span>
                                </span>
                            @endif
                        </button>
                    @else
                        <!-- No Action Selected Prompt -->
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-dashed border-slate-800 text-center">
                            <p class="text-xs text-slate-300 font-medium">
                                No action selected yet
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Click an action from the list above to schedule it for Day {{ $player?->current_game_day ?? 1 }}.
                            </p>
                        </div>

                        <button 
                            id="queue-action-button-placeholder"
                            type="button" 
                            disabled 
                            class="w-full py-3 px-4 rounded-xl font-bold text-xs bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed opacity-60"
                        >
                            Select an Action Above to Queue for Day {{ $player?->current_game_day ?? 1 }}
                        </button>
                    @endif

                    <!-- Mobile Quick Action: Choose another person in ward -->
                    <div class="pt-1 flex items-center justify-between text-xs lg:hidden">
                        <button 
                            type="button" 
                            @click="mobileStep = 2; $wire.setMobileStep(2)"
                            class="w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 transition cursor-pointer border border-slate-800"
                        >
                            <span>👥</span>
                            <span>Select Another Person in {{ $selectedWard?->name }}</span>
                        </button>
                    </div>
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
                                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                    @if($queued->action_type?->isWorkerRhythm())
                                        <span class="w-7 h-7 rounded-lg bg-indigo-950/90 border border-indigo-700/60 flex items-center justify-center text-sm shrink-0">
                                            {{ $queued->action_type->icon() }}
                                        </span>
                                    @elseif($queued->targetNpc)
                                        <x-person-avatar :person="$queued->targetNpc" size="xs" :showBadges="true" class="shrink-0" />
                                    @else
                                        <span class="w-7 h-7 rounded-lg bg-slate-800/90 border border-slate-700/60 flex items-center justify-center text-sm shrink-0">🏛️</span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-semibold text-white flex items-center gap-1.5">
                                            <span>{{ $queued->actionTitle }}</span>
                                            @if($queued->action_type?->isWorkerRhythm())
                                                <span class="text-[10px] font-bold text-emerald-400">(Time Blocked • +{{ $queued->action_type->rhythmPoints() }} pts)</span>
                                            @else
                                                <span class="text-[10px] font-bold text-amber-400">({{ $queued->ap_cost }} AP)</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                                            @if($queued->action_type?->isWorkerRhythm())
                                                Worker Rhythm • Spiritual Rest / Strategy
                                            @else
                                                {{ $queued->ward->name }} • {{ $queued->targetNpc?->name ?? 'Ward-wide platform event' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="cancelAction({{ $queued->id }})"
                                    class="text-rose-400 hover:text-rose-300 px-2 py-1 rounded bg-rose-950/50 border border-rose-900 text-[10px] shrink-0 transition cursor-pointer"
                                    title="{{ $queued->action_type?->isWorkerRhythm() ? 'Cancel rhythm and unblock day' : 'Cancel and refund AP' }}"
                                >
                                    {{ $queued->action_type?->isWorkerRhythm() ? 'Cancel Rhythm ✕' : 'Cancel ✕' }}
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
    @endif

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

    <!-- Mobile Sticky Bottom Confirmation Dock (Visible on mobile/tablet in Step 3 when an action is selected) -->
    <div 
        class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-slate-950 border-t border-slate-800 p-3 shadow-2xl"
        x-show="@js($hqSubView === 'planner') && mobileStep === 3 && @js($selectedActionEnum !== null)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
    >
        <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">
                    Ready to Queue • Day {{ $player?->current_game_day ?? 1 }}
                </div>
                <div class="text-xs font-bold text-white truncate flex items-center gap-1 mt-0.5">
                    <span>{{ $selectedActionEnum?->icon() ?? '⚡' }}</span>
                    <span>{{ $selectedActionEnum?->label() ?? 'Selected Action' }}</span>
                </div>
                <div class="text-[11px] text-slate-300 truncate flex items-center gap-1.5 mt-0.5">
                    @if($selectedNpc)
                        <x-person-avatar :person="$selectedNpc" size="xs" :showBadges="false" class="shrink-0" />
                    @endif
                    <span>For <strong class="text-indigo-300">{{ $selectedNpc ? $selectedNpc->displayName : ($selectedWard?->name ?? 'Ward') }}</strong> • <span class="font-bold {{ $hasEnoughApForSelected ? 'text-amber-400' : 'text-rose-400' }}">⚡ {{ $selectedActionCost }} AP</span></span>
                </div>
            </div>

            <button 
                type="button"
                wire:click="queueAction"
                @click="scrollToStep2()"
                wire:loading.attr="disabled"
                @disabled($isDayBlocked || ! $hasEnoughApForSelected || ($player?->is_burned_out ?? false))
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition shadow-lg shrink-0 flex items-center justify-center gap-1.5 {{ ($hasEnoughApForSelected && ! $isDayBlocked && ! ($player?->is_burned_out ?? false)) ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-950 active:scale-95' : 'bg-slate-800 text-slate-500 border border-slate-700 cursor-not-allowed opacity-60' }}"
            >
                @if($player?->is_burned_out)
                    <span class="flex items-center gap-1 text-rose-300">
                        <span>🚨</span>
                        <span>Burnout Leave</span>
                    </span>
                @elseif($isDayBlocked)
                    <span class="flex items-center gap-1 text-amber-300">
                        <span>⛔</span>
                        <span>Time Blocked</span>
                    </span>
                @else
                    <span wire:loading.remove wire:target="queueAction" class="flex items-center gap-1">
                        <span>➕</span>
                        <span>Queue for Day {{ $player?->current_game_day ?? 1 }}</span>
                    </span>
                    <span wire:loading wire:target="queueAction" class="flex items-center gap-1">
                        <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span>Queueing...</span>
                    </span>
                @endif
            </button>
        </div>
    </div>
</div>
