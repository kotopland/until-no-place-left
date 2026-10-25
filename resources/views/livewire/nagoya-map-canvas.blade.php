<div 
    class="relative w-full h-[720px] rounded-3xl overflow-hidden border border-slate-800 shadow-2xl bg-slate-950 flex flex-col"
    x-data="nagoyaMap({
        wardsGeoJson: @js($wardsGeoJson),
        churches: @js($churchesData),
        lines: @js($linesData),
        specialSpots: @js($specialSpots),
        prayerWalks: @js($prayerWalksData),
        houses: @js($housesData),
        showHeatmap: @entangle('showHeatmap').live,
        showChurches: @entangle('showChurches').live,
        showOikosLines: @entangle('showOikosLines').live,
        showMercySpots: @entangle('showMercySpots').live,
        showEventPins: @entangle('showEventPins').live,
        showPrayerWalks: @entangle('showPrayerWalks').live,
        showHouses: @entangle('showHouses').live
    })"
>
    <style>
        .leaflet-tooltip {
            background: rgba(15, 23, 42, 0.96) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(51, 65, 85, 0.85) !important;
            border-radius: 14px !important;
            color: #f8fafc !important;
            padding: 4px !important;
            box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.8), 0 4px 6px -2px rgba(0, 0, 0, 0.5) !important;
        }
        .leaflet-tooltip-top:before { border-top-color: rgba(15, 23, 42, 0.96) !important; }
        .leaflet-tooltip-bottom:before { border-bottom-color: rgba(15, 23, 42, 0.96) !important; }
        .leaflet-tooltip-left:before { border-left-color: rgba(15, 23, 42, 0.96) !important; }
        .leaflet-tooltip-right:before { border-right-color: rgba(15, 23, 42, 0.96) !important; }
    </style>

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

        <!-- Layer Toggle Pills with Real-Time Alpine & Livewire Integration -->
        <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-semibold">
            <!-- Heatmap: Emerald -->
            <button 
                type="button" 
                @click="toggleLayer('heatmap')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showHeatmap ? 'bg-emerald-600 text-white border-emerald-400 shadow-md shadow-emerald-950/60 ring-1 ring-emerald-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle Ward Spiritual Openness Heatmap (Emerald)"
            >
                <span class="w-2 h-2 rounded-full" :class="showHeatmap ? 'bg-emerald-200 animate-pulse' : 'bg-emerald-500/50'"></span>
                <span>🔥 Heatmap</span>
            </button>

            <!-- G1-G4 Churches: Royal Blue -->
            <button 
                type="button" 
                @click="toggleLayer('churches')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showChurches ? 'bg-blue-600 text-white border-blue-400 shadow-md shadow-blue-950/60 ring-1 ring-blue-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle G1–G4 Church Plant Circles (Blue/Purple/Pink/Gold)"
            >
                <span class="w-2 h-2 rounded-full" :class="showChurches ? 'bg-blue-200 animate-pulse' : 'bg-blue-500/50'"></span>
                <span>⛪ G1–G4 Churches</span>
            </button>

            <!-- Oikos Arcs: Cyan -->
            <button 
                type="button" 
                @click="toggleLayer('oikos')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showOikosLines ? 'bg-cyan-600 text-white border-cyan-400 shadow-md shadow-cyan-950/60 ring-1 ring-cyan-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle Relational Oikos Bridges (Cyan Arcs)"
            >
                <span class="w-2 h-2 rounded-full" :class="showOikosLines ? 'bg-cyan-200 animate-pulse' : 'bg-cyan-500/50'"></span>
                <span>🌐 Oikos Arcs</span>
            </button>

            <!-- Mercy Hubs: Rose -->
            <button 
                type="button" 
                @click="toggleLayer('mercy')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showMercySpots ? 'bg-rose-600 text-white border-rose-400 shadow-md shadow-rose-950/60 ring-1 ring-rose-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle Mercy Outreach Centers (Rose 🍜)"
            >
                <span class="w-2 h-2 rounded-full" :class="showMercySpots ? 'bg-rose-200 animate-pulse' : 'bg-rose-500/50'"></span>
                <span>🍲 Mercy Hubs</span>
            </button>

            <!-- Event Platforms: Purple -->
            <button 
                type="button" 
                @click="toggleLayer('events')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showEventPins ? 'bg-purple-600 text-white border-purple-400 shadow-md shadow-purple-950/60 ring-1 ring-purple-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle Platform Outreach Events (Purple 🎉)"
            >
                <span class="w-2 h-2 rounded-full" :class="showEventPins ? 'bg-purple-200 animate-pulse' : 'bg-purple-500/50'"></span>
                <span>🎉 Event Platforms</span>
            </button>

            <!-- Prayer Walks: Amber -->
            <button 
                type="button" 
                @click="toggleLayer('prayer_walks')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showPrayerWalks ? 'bg-amber-600 text-white border-amber-400 shadow-md shadow-amber-950/60 ring-1 ring-amber-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle Prayer Walking Street Routes (Amber 🚶)"
            >
                <span class="w-2 h-2 rounded-full" :class="showPrayerWalks ? 'bg-amber-200 animate-pulse' : 'bg-amber-500/50'"></span>
                <span>🚶 Prayer Walks ({{ count($prayerWalksData) }})</span>
            </button>

            <!-- Houses / Missionary Bases: Emerald 🏡 -->
            <button 
                type="button" 
                @click="toggleLayer('houses')" 
                class="px-2.5 py-1 rounded-xl transition cursor-pointer flex items-center gap-1.5 text-[11px] font-bold border"
                :class="showHouses ? 'bg-emerald-600 text-white border-emerald-400 shadow-md shadow-emerald-950/60 ring-1 ring-emerald-400/40' : 'bg-slate-800/90 text-slate-400 border-slate-700/70 hover:text-white hover:bg-slate-800'"
                title="Toggle Missionary Residences & Houses (🏡)"
            >
                <span class="w-2 h-2 rounded-full" :class="showHouses ? 'bg-emerald-200 animate-pulse' : 'bg-emerald-500/50'"></span>
                <span>🏡 Houses ({{ count($housesData) }})</span>
            </button>
        </div>
    </div>

    <!-- Map Container -->
    <div 
        id="nagoya-leaflet-map" 
        class="w-full flex-1 z-10" 
        style="min-height: 640px;"
        wire:ignore
        x-init="initMap()"
    ></div>

    <!-- Map Legend Footer Overlay with Cohesive Uniform Color System -->
    <div class="absolute bottom-4 left-4 z-20 hidden md:flex flex-wrap items-center gap-x-4 gap-y-1.5 px-4 py-2.5 bg-slate-900/95 backdrop-blur-md rounded-2xl border border-slate-800 text-[11px] font-medium text-slate-300 shadow-2xl pointer-events-auto">
        <!-- Churches Group -->
        <div class="flex items-center gap-2">
            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Churches:</span>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></span><span>G1</span></div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-sm shadow-purple-500/50"></span><span>G2</span></div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-pink-500 shadow-sm shadow-pink-500/50"></span><span>G3</span></div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span><span>G4+</span></div>
        </div>

        <span class="w-px h-3.5 bg-slate-800"></span>

        <!-- Ministries & Layers Group -->
        <div class="flex items-center gap-3">
            <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Layers:</span>
            <div class="flex items-center gap-1.5">
                <span class="text-xs">🏡</span>
                <span class="text-emerald-400 font-semibold">Your Home</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-xs">🏠</span>
                <span class="text-indigo-400 font-semibold">Co-Laborers</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-1.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                <span class="text-amber-300 font-semibold">🚶 Prayer Walks</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50"></span>
                <span>🍜 Mercy Hubs</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-sm shadow-purple-500/50"></span>
                <span>🎉 Events</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-0.5 border-t-2 border-dashed border-cyan-400"></span>
                <span>🌐 Oikos Arcs</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded bg-emerald-500/60 border border-emerald-400/60"></span>
                <span>🔥 Open Wards</span>
            </div>
        </div>
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

