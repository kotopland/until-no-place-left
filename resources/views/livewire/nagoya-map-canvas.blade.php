<div class="relative w-full h-[720px] rounded-3xl overflow-hidden border border-slate-800 shadow-2xl bg-slate-950 flex flex-col">
    <!-- Top Floating Map Control Bar -->
    <div class="absolute top-4 left-4 right-4 z-20 flex flex-wrap items-center justify-between gap-3 p-3 bg-slate-900/90 backdrop-blur-md rounded-2xl border border-slate-800 shadow-xl pointer-events-auto">
        <div class="flex items-center gap-2.5">
            <span class="text-xl">🗾</span>
            <div>
                <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                    <span>Nagoya Movement Canvas</span>
                    <span class="px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-400 border border-indigo-800 text-[10px]">16 Wards Live</span>
                </h3>
                <p class="text-[10px] text-slate-400">Click any ward or church to inspect Acts 2 Church Circles and Oikos networks.</p>
            </div>
        </div>

        <!-- Layer Toggle Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-semibold">
            <button 
                type="button" 
                wire:click="toggleLayer('heatmap')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1 text-[11px] {{ $showHeatmap ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
            >
                <span>🔥</span>
                <span>Heatmap</span>
            </button>
            <button 
                type="button" 
                wire:click="toggleLayer('churches')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1 text-[11px] {{ $showChurches ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
            >
                <span>⛪</span>
                <span>G1–G4 Churches</span>
            </button>
            <button 
                type="button" 
                wire:click="toggleLayer('oikos')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1 text-[11px] {{ $showOikosLines ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
            >
                <span>🌐</span>
                <span>Oikos Arcs</span>
            </button>
            <button 
                type="button" 
                wire:click="toggleLayer('mercy')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1 text-[11px] {{ $showMercySpots ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
            >
                <span>🍲</span>
                <span>Mercy Hubs</span>
            </button>
            <button 
                type="button" 
                wire:click="toggleLayer('events')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1 text-[11px] {{ $showEventPins ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
            >
                <span>🎉</span>
                <span>Event Platforms</span>
            </button>
            <button 
                type="button" 
                wire:click="toggleLayer('prayer_walks')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1 text-[11px] {{ $showPrayerWalks ? 'bg-amber-600 text-white shadow' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
                title="Toggle Prayer Walking Street Routes"
            >
                <span>🚶</span>
                <span>Prayer Walks ({{ count($prayerWalksData) }})</span>
            </button>
        </div>
    </div>

    <!-- Map Container -->
    <div 
        id="nagoya-leaflet-map" 
        class="w-full flex-1 z-10" 
        style="min-height: 640px;"
        wire:ignore
        x-data="nagoyaMap({
            wardsGeoJson: @js($wardsGeoJson),
            churches: @js($churchesData),
            lines: @js($linesData),
            specialSpots: @js($specialSpots),
            prayerWalks: @js($prayerWalksData),
            showHeatmap: @entangle('showHeatmap'),
            showChurches: @entangle('showChurches'),
            showOikosLines: @entangle('showOikosLines'),
            showMercySpots: @entangle('showMercySpots'),
            showEventPins: @entangle('showEventPins'),
            showPrayerWalks: @entangle('showPrayerWalks')
        })"
        x-init="initMap()"
    ></div>

    <!-- Map Legend Footer Overlay -->
    <div class="absolute bottom-4 left-4 z-20 hidden sm:flex items-center gap-4 px-3.5 py-2 bg-slate-900/90 backdrop-blur-md rounded-2xl border border-slate-800 text-[11px] font-medium text-slate-300 shadow-xl pointer-events-auto">
        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Streams:</span>
        <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span><span>G1 (Player-Led)</span></div>
        <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span><span>G2 (Planted by G1)</span></div>
        <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span><span>G3 (Granddaughter)</span></div>
        <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span>G4+ (Movement Stream!)</span></div>
        <div class="flex items-center gap-1.5"><span class="w-3.5 h-1 rounded-full bg-amber-400 shadow-sm shadow-amber-400"></span><span>Prayer Walk Streets</span></div>
    </div>

    <!-- Slide-over Drawer for Selected Church (Acts 2 Circle Diagram & Lineage) -->
    @if($selectedChurch)
        <div class="absolute inset-y-0 right-0 z-30 w-full sm:w-[420px] bg-slate-900/95 backdrop-blur-xl border-l border-slate-800 shadow-2xl p-5 overflow-y-auto flex flex-col animate-in slide-in-from-right duration-300">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">⛪</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">{{ $selectedChurch->name }}</h4>
                        <span class="text-[11px] text-slate-400">{{ $selectedChurch->ward->fullName }}</span>
                    </div>
                </div>

                <button 
                    type="button" 
                    wire:click="closeChurchSlideover"
                    class="p-1 rounded-lg bg-slate-800 text-slate-400 hover:text-white text-xs transition cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- Embed Church Circle SVG Component -->
            <div class="mb-4">
                <livewire:church-circle-svg :church="$selectedChurch" :key="'slideover-'.$selectedChurch->id" />
            </div>

            <!-- Generational Stream Lineage Tree -->
            <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 text-xs space-y-3">
                <h5 class="text-[11px] font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1">
                    <span>🌱</span>
                    <span>Generational Stream Tree</span>
                </h5>

                <div class="border-l-2 border-indigo-500/50 pl-3 space-y-2">
                    @if($selectedChurch->parentChurch)
                        <div>
                            <span class="text-[10px] text-slate-500 block uppercase font-semibold">Mother Church (G{{ $selectedChurch->parentChurch->generation }})</span>
                            <span class="font-bold text-indigo-400">{{ $selectedChurch->parentChurch->name }}</span>
                        </div>
                    @else
                        <div>
                            <span class="text-[10px] text-slate-500 block uppercase font-semibold">Origin</span>
                            <span class="font-bold text-blue-400">Pioneer Root Church (G1 Seed)</span>
                        </div>
                    @endif

                    <div class="pt-1">
                        <span class="text-[10px] text-emerald-400 block uppercase font-semibold">Current Node (G{{ $selectedChurch->generation }})</span>
                        <span class="font-bold text-white">{{ $selectedChurch->name }}</span>
                    </div>

                    @if($selectedChurch->daughterChurches->isNotEmpty())
                        <div class="pt-1">
                            <span class="text-[10px] text-purple-400 block uppercase font-semibold">Daughter Churches Planted ({{ $selectedChurch->daughterChurches->count() }})</span>
                            <div class="space-y-1 mt-1">
                                @foreach($selectedChurch->daughterChurches as $daughter)
                                    <div class="px-2 py-1 rounded bg-slate-900 border border-slate-800 font-semibold text-slate-300">
                                        G{{ $daughter->generation }}: {{ $daughter->name }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

