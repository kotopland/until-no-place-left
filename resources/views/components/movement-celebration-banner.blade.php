@props([
    'celebration',
    'totalCelebrations' => 1,
    'currentIndex' => 0,
])

@php
    $type = $celebration->type;
    $theme = match ($type) {
        'salvation' => [
            'border' => 'border-emerald-300 dark:border-emerald-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(16,185,129,0.35)]',
            'gradient' => 'from-emerald-50/95 via-teal-50/90 to-white dark:from-emerald-950/95 dark:via-teal-950/90 dark:to-slate-900/95',
            'accent' => 'text-emerald-700 dark:text-emerald-400',
            'badge' => 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-900/80 dark:text-emerald-300 dark:border-emerald-600/60',
            'headerIcon' => '🎉',
            'halo' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/40',
        ],
        'baptism' => [
            'border' => 'border-cyan-300 dark:border-cyan-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(6,182,212,0.35)]',
            'gradient' => 'from-cyan-50/95 via-sky-50/90 to-white dark:from-cyan-950/95 dark:via-blue-950/90 dark:to-slate-900/95',
            'accent' => 'text-cyan-700 dark:text-cyan-400',
            'badge' => 'bg-cyan-100 text-cyan-900 border-cyan-300 dark:bg-cyan-900/80 dark:text-cyan-300 dark:border-cyan-600/60',
            'headerIcon' => '🌊',
            'halo' => 'bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-500/20 dark:text-cyan-400 dark:border-cyan-500/40',
        ],
        'church_planted' => [
            'border' => 'border-indigo-300 dark:border-indigo-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(99,102,241,0.35)]',
            'gradient' => 'from-indigo-50/95 via-purple-50/90 to-white dark:from-indigo-950/95 dark:via-purple-950/90 dark:to-slate-900/95',
            'accent' => 'text-indigo-700 dark:text-indigo-400',
            'badge' => 'bg-indigo-100 text-indigo-900 border-indigo-300 dark:bg-indigo-900/80 dark:text-indigo-300 dark:border-indigo-600/60',
            'headerIcon' => '⛪',
            'halo' => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-500/20 dark:text-indigo-400 dark:border-indigo-500/40',
        ],
        'group_formed' => [
            'border' => 'border-amber-300 dark:border-amber-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(245,158,11,0.35)]',
            'gradient' => 'from-amber-50/95 via-orange-50/90 to-white dark:from-amber-950/95 dark:via-orange-950/90 dark:to-slate-900/95',
            'accent' => 'text-amber-800 dark:text-amber-400',
            'badge' => 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-900/80 dark:text-amber-300 dark:border-amber-600/60',
            'headerIcon' => '📖',
            'halo' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/40',
        ],
        default => [
            'border' => 'border-purple-300 dark:border-purple-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(168,85,247,0.35)]',
            'gradient' => 'from-purple-50/95 via-indigo-50/90 to-white dark:from-purple-950/95 dark:via-indigo-950/90 dark:to-slate-900/95',
            'accent' => 'text-purple-700 dark:text-purple-400',
            'badge' => 'bg-purple-100 text-purple-900 border-purple-300 dark:bg-purple-900/80 dark:text-purple-300 dark:border-purple-600/60',
            'headerIcon' => '✨',
            'halo' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-500/20 dark:text-purple-400 dark:border-purple-500/40',
        ],
    };
@endphp

<div 
    x-data="{ showConfetti: true }"
    class="relative overflow-hidden rounded-3xl bg-gradient-to-r {{ $theme['gradient'] }} border-2 {{ $theme['border'] }} p-5 sm:p-6 {{ $theme['glow'] }} transition-all duration-300 animate-in fade-in slide-in-from-top-4"
>
    <!-- Top Celebration Ribbon with Particle Icons -->
    <div class="absolute -top-1 inset-x-0 h-1 bg-gradient-to-r from-transparent via-amber-400 via-emerald-400 to-transparent opacity-80 animate-pulse"></div>

    <!-- Festive Background Ambient Particle Orbs -->
    <div class="pointer-events-none absolute -right-12 -top-12 w-48 h-48 rounded-full bg-emerald-500/10 blur-2xl"></div>
    <div class="pointer-events-none absolute -left-12 -bottom-12 w-48 h-48 rounded-full bg-indigo-500/10 blur-2xl"></div>

    <!-- Header: Type Badge, Headline & Close Action -->
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-white/10">
        <div class="flex items-start gap-3.5">
            <!-- Animated Celebration Icon with Pulsing Halo -->
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl {{ $theme['halo'] }} border flex items-center justify-center text-2xl sm:text-3xl shrink-0 shadow-md relative">
                <span class="animate-bounce select-none">{{ $theme['headerIcon'] }}</span>
                <span class="absolute -top-1 -right-1 text-xs animate-ping">✨</span>
            </div>

            <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $theme['badge'] }} flex items-center gap-1 shadow-xs">
                        <span>{{ $celebration->icon }}</span>
                        <span>{{ $celebration->typeLabelJa }}</span>
                        <span>•</span>
                        <span>{{ strtoupper($celebration->type) }}</span>
                    </span>

                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-white/10 dark:text-white/90 dark:border-white/15">
                        Month {{ $celebration->game_month }}, Week {{ $celebration->game_week }}
                    </span>

                    @if($celebration->generation > 1)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-200 dark:bg-purple-900/60 dark:text-purple-300 dark:border-purple-700/60">
                            Generation {{ $celebration->generation }} Stream
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-900 border border-indigo-200 dark:bg-indigo-900/60 dark:text-indigo-300 dark:border-indigo-700/60">
                            Pioneer Generation (G1)
                        </span>
                    @endif
                </div>

                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <span>{{ $celebration->title }}</span>
                </h3>

                <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 font-medium">
                    {{ $celebration->headline }}
                </p>
            </div>
        </div>

        <!-- Counter & Dismiss / Celebrate Button -->
        <div class="flex items-center gap-2 self-end sm:self-start shrink-0">
            @if($totalCelebrations > 1)
                <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-white/10 dark:text-white/80 dark:border-white/15">
                    {{ $currentIndex + 1 }} of {{ $totalCelebrations }} Breakthroughs
                </span>
            @endif

            <button 
                type="button" 
                wire:click="dismissCelebration({{ $celebration->id }})"
                class="px-4 py-2 rounded-xl text-xs font-black text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 shadow-md shadow-emerald-950/20 dark:shadow-emerald-950/50 border border-emerald-400/50 transition-all hover:scale-105 active:scale-95 cursor-pointer flex items-center gap-1.5"
                title="Celebrate and acknowledge this breakthrough"
            >
                <span>Close</span>
            </button>
        </div>
    </div>

    <!-- Main Content: Location Card & People Involved Card -->
    <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-4">
        <!-- Location Details Card -->
        <div class="p-3.5 rounded-2xl bg-white/80 dark:bg-black/40 border border-slate-200/80 dark:border-white/10 space-y-1.5 backdrop-blur-sm shadow-xs">
            <div class="flex items-center justify-between text-[11px]">
                <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                    <span>📍</span>
                    <span>Kingdom Location</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-900 border border-emerald-200 dark:bg-emerald-950/80 dark:text-emerald-300 dark:border-emerald-700/60">
                    Nagoya Movement Frontier
                </span>
            </div>

            <div class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>{{ $celebration->ward_name }}</span>
            </div>

            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-snug">
                The Holy Spirit is moving across the neighborhoods and relational households (Oikos) of <strong class="text-slate-900 dark:text-white">{{ $celebration->ward_name }}</strong>.
            </p>
        </div>

        <!-- People Involved Details Card -->
        <div class="p-3.5 rounded-2xl bg-white/80 dark:bg-black/40 border border-slate-200/80 dark:border-white/10 space-y-1.5 backdrop-blur-sm shadow-xs">
            <div class="flex items-center justify-between text-[11px]">
                <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                    <span>👥</span>
                    <span>People &amp; Laborers Involved</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-900 border border-indigo-200 dark:bg-indigo-950/80 dark:text-indigo-300 dark:border-indigo-700/60">
                    Discipleship Chain
                </span>
            </div>

            <div class="flex items-center gap-2 flex-wrap text-xs">
                <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                    <span>🌱</span>
                    <span>{{ $celebration->person_name }}</span>
                    @if($celebration->person_role)
                        <span class="text-[10px] font-normal text-slate-500 dark:text-slate-400">({{ $celebration->person_role }})</span>
                    @endif
                </span>

                @if($celebration->discipler_name)
                    <span class="text-slate-400 dark:text-slate-500">•</span>
                    <span class="text-slate-600 dark:text-slate-300 flex items-center gap-1">
                        <span>🤝 Discipled by:</span>
                        <strong class="text-indigo-700 dark:text-indigo-300">{{ $celebration->discipler_name }}</strong>
                    </span>
                @endif
            </div>

            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-snug">
                Generational multiplication in action: disciples equipping disciples so there is <em class="text-emerald-700 dark:text-emerald-300 font-semibold">No Place Left</em> without the Gospel.
            </p>
        </div>
    </div>

    <!-- Narrative Story & Scripture Anchor -->
    <div class="relative z-10 mt-3.5 p-3.5 rounded-2xl bg-white/60 dark:bg-white/5 border border-slate-200/80 dark:border-white/10 space-y-2 shadow-xs">
        <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-normal">
            {{ $celebration->details }}
        </p>

        @if($celebration->scripture_ref)
            <div class="flex items-center gap-2 pt-1 border-t border-slate-200/80 dark:border-white/10 text-[11px] {{ $theme['accent'] }} font-semibold italic">
                <span>📖</span>
                <span>{{ $celebration->scripture_ref }}</span>
            </div>
        @endif
    </div>
</div>
