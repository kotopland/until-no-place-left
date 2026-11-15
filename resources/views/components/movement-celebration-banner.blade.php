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
            'mapHex' => '#10b981',
        ],
        'baptism' => [
            'border' => 'border-cyan-300 dark:border-cyan-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(6,182,212,0.35)]',
            'gradient' => 'from-cyan-50/95 via-sky-50/90 to-white dark:from-cyan-950/95 dark:via-blue-950/90 dark:to-slate-900/95',
            'accent' => 'text-cyan-700 dark:text-cyan-400',
            'badge' => 'bg-cyan-100 text-cyan-900 border-cyan-300 dark:bg-cyan-900/80 dark:text-cyan-300 dark:border-cyan-600/60',
            'headerIcon' => '🌊',
            'halo' => 'bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-500/20 dark:text-cyan-400 dark:border-cyan-500/40',
            'mapHex' => '#06b6d4',
        ],
        'church_planted' => [
            'border' => 'border-indigo-300 dark:border-indigo-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(99,102,241,0.35)]',
            'gradient' => 'from-indigo-50/95 via-purple-50/90 to-white dark:from-indigo-950/95 dark:via-purple-950/90 dark:to-slate-900/95',
            'accent' => 'text-indigo-700 dark:text-indigo-400',
            'badge' => 'bg-indigo-100 text-indigo-900 border-indigo-300 dark:bg-indigo-900/80 dark:text-indigo-300 dark:border-indigo-600/60',
            'headerIcon' => '⛪',
            'halo' => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-500/20 dark:text-indigo-400 dark:border-indigo-500/40',
            'mapHex' => '#6366f1',
        ],
        'group_formed' => [
            'border' => 'border-amber-300 dark:border-amber-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(245,158,11,0.35)]',
            'gradient' => 'from-amber-50/95 via-orange-50/90 to-white dark:from-amber-950/95 dark:via-orange-950/90 dark:to-slate-900/95',
            'accent' => 'text-amber-800 dark:text-amber-400',
            'badge' => 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-900/80 dark:text-amber-300 dark:border-amber-600/60',
            'headerIcon' => '📖',
            'halo' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/40',
            'mapHex' => '#f59e0b',
        ],
        default => [
            'border' => 'border-purple-300 dark:border-purple-500/80',
            'glow' => 'shadow-lg dark:shadow-[0_0_50px_rgba(168,85,247,0.35)]',
            'gradient' => 'from-purple-50/95 via-indigo-50/90 to-white dark:from-purple-950/95 dark:via-indigo-950/90 dark:to-slate-900/95',
            'accent' => 'text-purple-700 dark:text-purple-400',
            'badge' => 'bg-purple-100 text-purple-900 border-purple-300 dark:bg-purple-900/80 dark:text-purple-300 dark:border-purple-600/60',
            'headerIcon' => '✨',
            'halo' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-500/20 dark:text-purple-400 dark:border-purple-500/40',
            'mapHex' => '#a855f7',
        ],
    };

    $mainNpc = $celebration->npc;
    $disciplerPerson = ($celebration->generation > 1 && $mainNpc?->disciplerNpc)
        ? $mainNpc->disciplerNpc
        : $celebration->user;
    $relatedChurch = $celebration->church ?? $mainNpc?->church;
    $focusWard = $celebration->ward;

    $mapMarkers = [];
    if ($mainNpc && $mainNpc->lat && $mainNpc->lng) {
        $mapMarkers[] = [
            'lat' => (float) $mainNpc->lat,
            'lng' => (float) $mainNpc->lng,
            'label' => $mainNpc->name,
            'type' => 'person',
            'color' => $theme['mapHex'],
        ];
    }
    if ($relatedChurch && $relatedChurch->lat && $relatedChurch->lng) {
        $mapMarkers[] = [
            'lat' => (float) $relatedChurch->lat,
            'lng' => (float) $relatedChurch->lng,
            'label' => $relatedChurch->name,
            'type' => 'church',
            'color' => '#818cf8',
        ];
    }
    if ($mapMarkers === [] && $focusWard) {
        $mapMarkers[] = [
            'lat' => (float) $focusWard->center_lat,
            'lng' => (float) $focusWard->center_lng,
            'label' => $focusWard->name,
            'type' => 'ward',
            'color' => $theme['mapHex'],
        ];
    }
@endphp

<div 
    x-data="{ showConfetti: true }"
    class="relative overflow-hidden rounded-3xl bg-gradient-to-r {{ $theme['gradient'] }} border-2 {{ $theme['border'] }} p-5 sm:p-6 {{ $theme['glow'] }} transition-all duration-300 animate-in fade-in slide-in-from-top-4"
>
    <!-- Top Celebration Ribbon -->
    <div class="absolute -top-1 inset-x-0 h-1 bg-gradient-to-r from-transparent via-amber-400 via-emerald-400 to-transparent opacity-80 animate-pulse"></div>

    <!-- Festive Background Ambient Particle Orbs -->
    <div class="pointer-events-none absolute -right-12 -top-12 w-48 h-48 rounded-full bg-emerald-500/10 blur-2xl"></div>
    <div class="pointer-events-none absolute -left-12 -bottom-12 w-48 h-48 rounded-full bg-indigo-500/10 blur-2xl"></div>

    <!-- Header: Type Badge, Headline & Close Action -->
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-white/10">
        <div class="flex items-start gap-3.5">
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

        <!-- Counter & Close Button -->
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

    <!-- Main Atmospheric Content: People & Group/Church (Left) + Nagoya Location Map (Right) -->
    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-4 pt-4">
        <!-- Who & Group/Church Behind (7 cols on lg) -->
        <div class="lg:col-span-7 flex flex-col gap-3">
            <!-- Big Avatars Spotlight Card -->
            <div class="relative overflow-hidden p-4 rounded-2xl bg-white/85 dark:bg-black/45 border border-slate-200/80 dark:border-white/10 backdrop-blur-sm shadow-xs">
                <!-- Subtle Church/Group Watermark Backdrop -->
                @if($relatedChurch)
                    <div class="pointer-events-none absolute -right-4 -bottom-4 text-7xl opacity-10 select-none">⛪</div>
                @elseif($type === 'group_formed')
                    <div class="pointer-events-none absolute -right-4 -bottom-4 text-7xl opacity-10 select-none">📖</div>
                @endif

                <div class="flex items-center justify-between text-[11px] mb-3">
                    <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                        <span>👥</span>
                        <span>People &amp; Laborers Involved</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-900 border border-indigo-200 dark:bg-indigo-950/80 dark:text-indigo-300 dark:border-indigo-700/60">
                        Discipleship Chain
                    </span>
                </div>

                <!-- Big Avatars Row -->
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <!-- Main Person Big Avatar -->
                    <div class="flex items-center gap-3.5 flex-1 min-w-0">
                        <div class="shrink-0 ring-4 ring-emerald-400/30 dark:ring-emerald-500/30 rounded-2xl p-0.5 bg-white/60 dark:bg-slate-900/60 shadow-lg">
                            @if($mainNpc)
                                <x-person-avatar :person="$mainNpc" size="3xl" :showAgePill="true" />
                            @else
                                <x-person-avatar :seed="$celebration->person_name" size="3xl" :showAgePill="true" />
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider {{ $theme['accent'] }}">
                                {{ $type === 'church_planted' ? 'Church Planter / Leader' : ($type === 'group_formed' ? 'Group Host / Seeker' : 'Disciple') }}
                            </div>
                            <div class="text-sm sm:text-base font-black text-slate-900 dark:text-white leading-tight break-words">
                                {{ $celebration->person_name }}
                            </div>
                            @if($celebration->person_role)
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-0.5">
                                    {{ $celebration->person_role }}
                                </div>
                            @endif
                            @if($mainNpc)
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>{{ $mainNpc->nationality }}</span>
                                    <span>•</span>
                                    <span>Age {{ $mainNpc->age }}</span>
                                    <span>•</span>
                                    <span>G{{ max(1, $mainNpc->generation) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($celebration->discipler_name)
                        <div class="hidden sm:flex flex-col items-center justify-center px-1 text-slate-400 dark:text-slate-500 shrink-0">
                            <span class="text-lg">🤝</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider">with</span>
                        </div>

                        <!-- Discipler / Co-laborer Big Avatar -->
                        <div class="flex items-center gap-3 flex-1 min-w-0 pt-3 sm:pt-0 border-t sm:border-t-0 sm:border-l border-slate-200/80 dark:border-white/10 sm:pl-4">
                            <div class="shrink-0 ring-3 ring-indigo-400/30 dark:ring-indigo-500/30 rounded-2xl p-0.5 bg-white/60 dark:bg-slate-900/60 shadow-md">
                                @if($disciplerPerson)
                                    <x-person-avatar :person="$disciplerPerson" size="xl" :showAgePill="true" />
                                @else
                                    <x-person-avatar :seed="$celebration->discipler_name" role="missionary" size="xl" :showAgePill="false" />
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                    🤝 Discipled by
                                </div>
                                <div class="text-sm font-black text-indigo-800 dark:text-indigo-200 leading-tight break-words">
                                    {{ $celebration->discipler_name }}
                                </div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $disciplerPerson instanceof \App\Models\Npc ? 'Local Multiplying Disciple' : 'Missionary Co-Laborer' }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Group or Church Behind Card -->
            <div class="p-3.5 rounded-2xl bg-white/80 dark:bg-black/40 border border-slate-200/80 dark:border-white/10 backdrop-blur-sm shadow-xs">
                @if($relatedChurch)
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-100 dark:bg-indigo-950/90 border border-indigo-300 dark:border-indigo-700/70 flex items-center justify-center text-xl shrink-0 shadow-xs">
                                {{ $relatedChurch->is_healthy_church ? '⛪' : '📖' }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                        Church / Group Behind This
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded-md text-[9px] font-black bg-indigo-100 text-indigo-800 dark:bg-indigo-900/70 dark:text-indigo-300">
                                        {{ $relatedChurch->generationBadge }}
                                    </span>
                                </div>
                                <div class="text-sm font-black text-slate-900 dark:text-white truncate">
                                    {{ $relatedChurch->name }}
                                </div>
                                <div class="text-[11px] text-slate-600 dark:text-slate-300">
                                    {{ $relatedChurch->statusLabel }} • {{ $relatedChurch->ward?->name ?? $celebration->ward_name }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 text-[11px] font-bold shrink-0">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                👥 {{ $relatedChurch->attenders_count }} Attenders
                            </span>
                            <span class="px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                ✝️ {{ $relatedChurch->believers_count }} Believers
                            </span>
                            <span class="px-2.5 py-1 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/60">
                                🌊 {{ $relatedChurch->baptized_count }} Baptized
                            </span>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-amber-100 dark:bg-amber-950/80 border border-amber-300 dark:border-amber-700/60 flex items-center justify-center text-xl shrink-0 shadow-xs">
                                {{ $type === 'group_formed' ? '📖' : '🏠' }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Household &amp; Group Context
                                </div>
                                <div class="text-sm font-black text-slate-900 dark:text-white">
                                    {{ $type === 'group_formed' ? 'SWORD Discovery Bible Study Group' : 'Local Oikos Relational Network' }}
                                </div>
                                <div class="text-[11px] text-slate-600 dark:text-slate-300">
                                    Rooted in <strong class="text-slate-900 dark:text-white">{{ $celebration->ward_name }}</strong> — equipping disciples so there is <em class="text-emerald-700 dark:text-emerald-300 font-semibold">No Place Left</em>.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Location on Nagoya Map (5 cols on lg) -->
        <div class="lg:col-span-5 p-3.5 rounded-2xl bg-white/80 dark:bg-black/40 border border-slate-200/80 dark:border-white/10 flex flex-col justify-between gap-2.5 backdrop-blur-sm shadow-xs">
            <div class="flex items-center justify-between text-[11px]">
                <span class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                    <span>📍</span>
                    <span>Kingdom Location</span>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-900 border border-emerald-200 dark:bg-emerald-950/80 dark:text-emerald-300 dark:border-emerald-700/60">
                    {{ $celebration->ward_name }}
                </span>
            </div>

            <x-nagoya-ward-minimap
                :ward="$focusWard"
                :markers="$mapMarkers"
                :accentColor="$theme['mapHex']"
                :subtitle="'M'.$celebration->game_month.' • W'.$celebration->game_week"
                class="w-full h-44 sm:h-48"
            />

            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-snug">
                The Holy Spirit is moving across the neighborhoods and Oikos households of <strong class="text-slate-900 dark:text-white">{{ $celebration->ward_name }}</strong>.
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
