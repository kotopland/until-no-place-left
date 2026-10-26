<div 
    class="relative flex flex-col w-full bg-slate-950 text-slate-100 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden font-sans selection:bg-indigo-500 selection:text-white"
    x-data="{
        zoom: @entangle('zoomLevel'),
        isDragging: false,
        startX: 0,
        startY: 0,
        scrollLeft: 0,
        scrollTop: 0,
        startPan(e) {
            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('.interactive-node')) return;
            this.isDragging = true;
            this.startX = e.pageX - this.$refs.canvasContainer.offsetLeft;
            this.startY = e.pageY - this.$refs.canvasContainer.offsetTop;
            this.scrollLeft = this.$refs.canvasContainer.scrollLeft;
            this.scrollTop = this.$refs.canvasContainer.scrollTop;
        },
        pan(e) {
            if (!this.isDragging) return;
            e.preventDefault();
            const x = e.pageX - this.$refs.canvasContainer.offsetLeft;
            const y = e.pageY - this.$refs.canvasContainer.offsetTop;
            this.$refs.canvasContainer.scrollLeft = this.scrollLeft - (x - this.startX);
            this.$refs.canvasContainer.scrollTop = this.scrollTop - (y - this.startY);
        },
        endPan() {
            this.isDragging = false;
        }
    }"
>
    <!-- Top Gen Map Header & Movement Milestone Tracker -->
    <div class="px-5 py-4 bg-slate-900/90 border-b border-slate-800 backdrop-blur-md flex flex-col gap-3">
        <!-- Milestone Banner: Reaching 4th Generation indicates self-sustaining reproduction -->
        @if($hasReachedG4Milestone)
            <div class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-amber-950/80 via-yellow-950/60 to-amber-900/40 border border-amber-500/60 shadow-lg flex items-center justify-between gap-3 text-amber-200">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl animate-bounce">👑</span>
                    <div>
                        <div class="text-xs font-black uppercase tracking-wider text-amber-300 flex items-center gap-1.5">
                            <span>4th Generation Movement Milestone Achieved!</span>
                            <span class="px-1.5 py-0.2 rounded text-[10px] bg-amber-500 text-slate-950 font-black">G4+</span>
                        </div>
                        <p class="text-[11px] text-amber-200/90 mt-0.5">
                            Reaching the 4th generation and beyond is a historic biblical breakthrough indicating that the movement in Nagoya has achieved self-sustaining life and autonomous multiplication!
                        </p>
                    </div>
                </div>
                <span class="hidden sm:inline-flex px-3 py-1 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/40 shrink-0">
                    Self-Sustaining Streams
                </span>
            </div>
        @else
            <div class="px-4 py-2 rounded-2xl bg-indigo-950/40 border border-indigo-900/50 flex items-center justify-between gap-3 text-indigo-300">
                <div class="flex items-center gap-2 text-xs font-medium">
                    <span class="text-base">🌱</span>
                    <span>
                        <strong class="text-white">Generational Movement Horizon:</strong> Currently at <span class="text-amber-400 font-bold">Gen {{ $deepestGeneration }}</span>. Reaching <strong>Gen 4+</strong> signifies self-sustaining reproduction across Greater Nagoya!
                    </span>
                </div>
                <div class="flex items-center gap-1 text-[11px] font-bold text-slate-400">
                    <span class="px-1.5 py-0.5 rounded {{ $deepestGeneration >= 1 ? 'bg-indigo-600 text-white' : 'bg-slate-800' }}">G1</span>
                    <span>→</span>
                    <span class="px-1.5 py-0.5 rounded {{ $deepestGeneration >= 2 ? 'bg-purple-600 text-white' : 'bg-slate-800' }}">G2</span>
                    <span>→</span>
                    <span class="px-1.5 py-0.5 rounded {{ $deepestGeneration >= 3 ? 'bg-pink-600 text-white' : 'bg-slate-800' }}">G3</span>
                    <span>→</span>
                    <span class="px-1.5 py-0.5 rounded {{ $deepestGeneration >= 4 ? 'bg-amber-500 text-black' : 'bg-slate-800 text-slate-500' }}">G4+ 🎯</span>
                </div>
            </div>
        @endif

        <!-- Controls Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Left: Title & Quick Stats -->
            <div class="flex items-center gap-4 flex-wrap">
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>🌳</span>
                        <span>Church Generation Map</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                            Gen Map (4 Fields)
                        </span>
                    </h3>
                    <p class="text-[11px] text-slate-400">
                        Tracks simple churches reproducing across generational tiers from mother to daughter groups
                    </p>
                </div>

                <!-- Live Metrics Summary Badges -->
                <div class="flex items-center gap-2 flex-wrap text-xs">
                    <span class="px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-1.5 font-bold text-emerald-400" title="Active healthy & developing simple churches">
                        <span>⛪</span>
                        <span>{{ $activeChurchesCount }} Active</span>
                    </span>

                    @if($dissolvedChurchesCount > 0)
                        <span class="px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-1.5 font-bold text-slate-400" title="Dissolved churches due to relocation, sickness, or conflict">
                            <span class="text-rose-400">⚠️</span>
                            <span>{{ $dissolvedChurchesCount }} Dissolved</span>
                        </span>
                    @endif

                    <div class="h-4 w-px bg-slate-800 hidden sm:block"></div>

                    <!-- Attenders / Believers / Baptized Tally -->
                    <div class="flex items-center gap-2 bg-slate-950/80 px-2.5 py-1 rounded-xl border border-slate-800/80 text-[11px]">
                        <span class="text-amber-400 font-bold flex items-center gap-1" title="Total Movement Attenders">
                            <span>😊</span>
                            <span>{{ $totalAttenders }}</span>
                        </span>
                        <span class="text-slate-600">•</span>
                        <span class="text-purple-400 font-bold flex items-center gap-1" title="Total Movement Believers">
                            <span>👑</span>
                            <span>{{ $totalBelievers }}</span>
                        </span>
                        <span class="text-slate-600">•</span>
                        <span class="text-cyan-400 font-bold flex items-center gap-1" title="Total Baptized">
                            <span>💧</span>
                            <span>{{ $totalBaptized }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Search, Filter, Mode & Zoom Controls -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Search Box -->
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchQuery" 
                        placeholder="Search leader, ward..." 
                        class="w-36 sm:w-44 px-3 py-1.5 pl-7 text-xs bg-slate-950 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                    />
                    <span class="absolute left-2.5 top-2 text-[10px] text-slate-500">🔍</span>
                    @if(!empty($searchQuery))
                        <button type="button" wire:click="$set('searchQuery', '')" class="absolute right-2 top-1.5 text-xs text-slate-400 hover:text-white">✕</button>
                    @endif
                </div>

                <!-- Filter: All / Active / Dissolved -->
                <div class="inline-flex rounded-xl bg-slate-950 p-0.5 border border-slate-800 text-xs font-semibold">
                    <button 
                        type="button" 
                        wire:click="setFilter('all')" 
                        class="px-2.5 py-1 rounded-lg transition {{ $filterStatus === 'all' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                    >
                        All ({{ count($churches) }})
                    </button>
                    <button 
                        type="button" 
                        wire:click="setFilter('active')" 
                        class="px-2.5 py-1 rounded-lg transition {{ $filterStatus === 'active' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                    >
                        Active
                    </button>
                    <button 
                        type="button" 
                        wire:click="setFilter('dissolved')" 
                        class="px-2.5 py-1 rounded-lg transition {{ $filterStatus === 'dissolved' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                    >
                        Dissolved
                    </button>
                </div>

                <!-- View Mode Toggle: Tree vs Columns -->
                <div class="inline-flex rounded-xl bg-slate-950 p-0.5 border border-slate-800 text-xs font-semibold">
                    <button 
                        type="button" 
                        wire:click="setViewMode('tree')" 
                        class="px-2 py-1 rounded-lg transition flex items-center gap-1 {{ $viewMode === 'tree' ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                        title="Hierarchical Tree Layout"
                    >
                        <span>🌳</span>
                        <span class="hidden md:inline">Tree</span>
                    </button>
                    <button 
                        type="button" 
                        wire:click="setViewMode('tiers')" 
                        class="px-2 py-1 rounded-lg transition flex items-center gap-1 {{ $viewMode === 'tiers' ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-white' }}"
                        title="Generational Tier Columns (Gen 1, Gen 2, Gen 3, Gen 4+)"
                    >
                        <span>📊</span>
                        <span class="hidden md:inline">Columns</span>
                    </button>
                </div>

                <!-- Zoom Controls -->
                <div class="flex items-center gap-1 bg-slate-950 px-1 py-0.5 rounded-xl border border-slate-800 text-xs">
                    <button type="button" wire:click="zoomOut" class="px-2 py-1 hover:bg-slate-800 rounded text-slate-300 font-bold" title="Zoom Out">−</button>
                    <span class="font-mono text-[10px] text-slate-400 px-1">{{ round($zoomLevel * 100) }}%</span>
                    <button type="button" wire:click="zoomIn" class="px-2 py-1 hover:bg-slate-800 rounded text-slate-300 font-bold" title="Zoom In">+</button>
                    <button type="button" wire:click="resetZoom" class="px-1.5 py-1 hover:bg-slate-800 rounded text-[10px] text-slate-400" title="Reset Zoom">↺</button>
                </div>

                <!-- Toggle Inspector Button -->
                <button 
                    type="button" 
                    wire:click="toggleDrawer" 
                    class="px-2.5 py-1.5 rounded-xl border text-xs font-semibold transition cursor-pointer flex items-center gap-1.5 {{ $showInspectorDrawer ? 'bg-indigo-950/70 text-indigo-300 border-indigo-700' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white' }}"
                    title="Toggle details inspector drawer"
                >
                    <span>👁️</span>
                    <span class="hidden sm:inline">Details</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Canvas & Inspector Container -->
    <div class="relative flex-1 flex overflow-hidden min-h-[580px] lg:min-h-[640px]">
        <!-- Scrollable Pan & Zoom Canvas Area -->
        <div 
            x-ref="canvasContainer"
            @mousedown="startPan($event)"
            @mousemove="pan($event)"
            @mouseup="endPan()"
            @mouseleave="endPan()"
            class="flex-1 overflow-auto cursor-grab active:cursor-grabbing p-8 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] relative"
        >
            <div 
                class="relative transition-transform duration-100 origin-top-left"
                :style="'transform: scale(' + zoom + '); width: {{ max(1000, $canvasWidth + 100) }}px; height: {{ max(650, $canvasHeight + 100) }}px;'"
            >
                <!-- Generational Column Labels (Visible in Tiers mode) -->
                @if($viewMode === 'tiers')
                    <div class="absolute top-0 left-0 right-0 flex gap-12 pl-24 pointer-events-none pb-4 border-b border-slate-800/40">
                        <div class="w-60 text-center">
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-blue-950/80 text-blue-300 border border-blue-800">
                                Gen 1 • Pioneer Mother Groups
                            </span>
                        </div>
                        <div class="w-60 text-center">
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-purple-950/80 text-purple-300 border border-purple-800">
                                Gen 2 • Daughter Churches
                            </span>
                        </div>
                        <div class="w-60 text-center">
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-pink-950/80 text-pink-300 border border-pink-800">
                                Gen 3 • Granddaughter Groups
                            </span>
                        </div>
                        <div class="w-60 text-center">
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-950/80 text-amber-300 border border-amber-800">
                                Gen 4+ • Movement Multiplication 🎯
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Connecting Lineage SVG Canvas -->
                <svg 
                    class="absolute inset-0 pointer-events-none" 
                    width="{{ max(1000, $canvasWidth + 100) }}" 
                    height="{{ max(650, $canvasHeight + 100) }}"
                >
                    <defs>
                        <linearGradient id="edgeGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.8" />
                            <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.8" />
                        </linearGradient>
                    </defs>

                    @foreach($edges as $edge)
                        @php
                            $fromX = $edge['from_x'];
                            $fromY = $edge['from_y'];
                            $toX = $edge['to_x'];
                            $toY = $edge['to_y'];
                            $midY = ($fromY + $toY) / 2;
                            $isDissolved = $edge['is_dissolved'];
                        @endphp
                        <path 
                            d="M {{ $fromX }} {{ $fromY }} C {{ $fromX }} {{ $midY }}, {{ $toX }} {{ $midY }}, {{ $toX }} {{ $toY }}"
                            fill="none"
                            stroke="{{ $isDissolved ? '#475569' : '#6366f1' }}"
                            stroke-width="{{ $isDissolved ? '1.5' : '2.5' }}"
                            stroke-dasharray="{{ $isDissolved ? '4 4' : 'none' }}"
                            opacity="{{ $isDissolved ? '0.45' : '0.85' }}"
                        />
                    @endforeach
                </svg>

                <!-- Church Circles Nodes -->
                @forelse($nodes as $id => $node)
                    @php
                        /** @var \App\Models\Church $church */
                        $church = $node['church'];
                        $posX = $node['x'];
                        $posY = $node['y'];
                        $isSelected = ($selectedChurchId === $church->id);
                        $isDissolved = $church->is_dissolved;
                        $isHealthy = $church->is_healthy_church;
                        $practiced = $church->elements_json ?? [];
                    @endphp

                    <div 
                        wire:key="church-node-{{ $church->id }}"
                        wire:click="selectChurch({{ $church->id }})"
                        style="left: {{ $posX }}px; top: {{ $posY }}px;"
                        class="absolute -translate-x-1/2 -translate-y-1/2 cursor-pointer group interactive-node select-none transition-all duration-200 {{ $isDissolved ? 'opacity-45 hover:opacity-75' : '' }}"
                    >
                        <!-- Top Metrics Bar (Smiley Attenders, Crown Believers, Waves Baptized) -->
                        <div class="flex items-center justify-center gap-3 mb-1 text-center font-bold">
                            <!-- Attenders -->
                            <div class="flex flex-col items-center">
                                <span class="text-xs leading-none" title="Total Attenders">😊</span>
                                <span class="text-[11px] font-black {{ $isDissolved ? 'text-slate-500' : 'text-amber-300' }}">
                                    {{ $church->attenders_count }}
                                </span>
                            </div>

                            <!-- Believers -->
                            <div class="flex flex-col items-center">
                                <span class="text-xs leading-none" title="Committed Believers">👑</span>
                                <span class="text-[11px] font-black {{ $isDissolved ? 'text-slate-500' : 'text-purple-300' }}">
                                    {{ $church->believers_count }}
                                </span>
                            </div>

                            <!-- Baptized -->
                            <div class="flex flex-col items-center">
                                <span class="text-xs leading-none" title="Baptized Believers">≈</span>
                                <span class="text-[11px] font-black {{ $isDissolved ? 'text-slate-500' : 'text-cyan-300' }}">
                                    {{ $church->baptized_count }}
                                </span>
                            </div>
                        </div>

                        <!-- Core Church Circle Wrapper with Left Rim Numbers & Center Icons -->
                        <div class="relative flex items-center justify-center">
                            <!-- Left Rim Numbers (1 to 7 Acts 2 parts as in 4 Fields Church Circle) -->
                            <div class="absolute -left-4 flex flex-col text-[9px] font-mono font-bold leading-tight {{ $isDissolved ? 'text-slate-600' : 'text-slate-400' }}">
                                <span>1</span>
                                <span>2</span>
                                <span>3</span>
                                <span>4</span>
                                <span>5</span>
                                <span>6</span>
                                <span>7</span>
                            </div>

                            <!-- The Church Circle itself -->
                            <div 
                                class="relative w-24 h-24 rounded-full flex flex-wrap items-center justify-center p-2.5 transition-all duration-200 shadow-xl
                                    {{ $isSelected ? 'bg-sky-400 ring-4 ring-sky-300/60 shadow-sky-500/50 scale-105' : ($isDissolved ? 'bg-slate-900/60' : 'bg-slate-900/95 group-hover:bg-slate-850') }}
                                    {{ $isHealthy ? 'border-[2.5px] border-emerald-400' : 'border-2 border-dashed border-amber-400' }}
                                    {{ $isDissolved ? 'border-slate-600 border-dashed !bg-slate-950/80' : '' }}
                                "
                            >
                                <!-- Practiced Acts 2 Elements Inside Circle -->
                                <div class="grid grid-cols-3 gap-1 items-center justify-center text-center w-full h-full">
                                    <!-- Word / Bible -->
                                    <span class="text-xs {{ in_array('word', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Bible Study">
                                        📖
                                    </span>

                                    <!-- Lord's Supper / Communion -->
                                    <span class="text-xs {{ in_array('communion', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Lord's Supper">
                                        🍷
                                    </span>

                                    <!-- Fellowship / Love -->
                                    <span class="text-xs {{ in_array('fellowship', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Fellowship & Mutual Care">
                                        ❤️
                                    </span>

                                    <!-- Prayer -->
                                    <span class="text-xs {{ in_array('prayer', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Prayer">
                                        🙏
                                    </span>

                                    <!-- Giving / Mercy -->
                                    <span class="text-xs {{ in_array('giving_mercy', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Giving & Mercy">
                                        🎁
                                    </span>

                                    <!-- Worship -->
                                    <span class="text-xs {{ in_array('worship', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Worship">
                                        🎵
                                    </span>

                                    <!-- Local Leaders -->
                                    <span class="text-xs {{ in_array('local_leaders', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Local Leaders Appointed">
                                        👤
                                    </span>

                                    <!-- Making Disciples / Evangelism -->
                                    <span class="text-xs {{ in_array('making_disciples', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Multiplying Disciples">
                                        🌱
                                    </span>

                                    <!-- Baptism -->
                                    <span class="text-xs {{ in_array('baptism', $practiced) ? ($isSelected ? 'text-slate-950 font-black' : 'text-slate-100') : 'opacity-15' }}" title="Baptism Practiced">
                                        💧
                                    </span>
                                </div>

                                <!-- Central Strikethrough if Dissolved -->
                                @if($isDissolved)
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="w-full h-0.5 bg-rose-500/80 -rotate-45"></div>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Badges next to Circle when Selected (Just like user reference diagram!) -->
                            @if($isSelected)
                                <div class="absolute -right-7 flex flex-col gap-1 z-20">
                                    <button 
                                        type="button" 
                                        wire:click.stop="clearSelection" 
                                        class="w-6 h-6 rounded-lg bg-orange-400 hover:bg-orange-500 text-white font-black text-xs flex items-center justify-center shadow-md transition cursor-pointer"
                                        title="Deselect"
                                    >
                                        ✕
                                    </button>
                                    @if($isDissolved)
                                        <button 
                                            type="button" 
                                            wire:click.stop="revitalizeChurch({{ $church->id }})" 
                                            class="w-6 h-6 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-md transition cursor-pointer"
                                            title="Revitalize Church Circle"
                                        >
                                            +
                                        </button>
                                    @else
                                        @php
                                            $coachingCheck = $church->coachingEligibility($user?->current_game_month ?? 1, $user?->current_game_day ?? 1);
                                        @endphp
                                        <button 
                                            type="button" 
                                            wire:click.stop="queueCoachingForChurch({{ $church->id }})" 
                                            class="w-6 h-6 rounded-lg {{ $coachingCheck['allowed'] ? 'bg-emerald-400 hover:bg-emerald-500 text-slate-950' : 'bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700' }} font-black text-xs flex items-center justify-center shadow-md transition cursor-pointer"
                                            title="{{ $coachingCheck['allowed'] ? 'Queue 3/3rds Church Circles Coaching (8 AP • once/week)' : $coachingCheck['reason'] }}"
                                        >
                                            {{ $coachingCheck['allowed'] ? '+' : '⏳' }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Metadata Below Circle: Leader Name, Church Name, Ward Location, Founded Month/Date -->
                        <div class="mt-1.5 text-center max-w-[150px] mx-auto">
                            <h4 class="text-xs font-black truncate leading-tight {{ $isDissolved ? 'text-slate-500 line-through' : ($isSelected ? 'text-sky-300' : 'text-white') }}">
                                {{ $church->leaderDisplayName }}
                            </h4>
                            <p class="text-[10px] text-slate-300 truncate font-semibold" title="{{ $church->name }}">
                                {{ $church->name }}
                            </p>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">
                                {{ $church->ward->name }}, M{{ $church->founded_game_month }}
                            </p>

                            @if($isDissolved)
                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-950/80 text-rose-300 border border-rose-800/80">
                                    Dissolved (M{{ $church->dissolved_game_month ?? '?' }})
                                </span>
                            @elseif($church->is_healthy_church)
                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-800/80">
                                    Healthy Church
                                </span>
                            @else
                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-medium bg-amber-950/60 text-amber-300/90 border border-amber-900/60">
                                    Developing Study
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-96 text-slate-400">
                        <span class="text-4xl mb-2">⛪</span>
                        <h4 class="text-base font-bold text-slate-200">No Church Circles Found</h4>
                        <p class="text-xs text-slate-500 mt-1">Try clearing your search query or status filter.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Side Details Inspector Drawer -->
        @if($showInspectorDrawer && $selectedChurch)
            <aside class="w-80 md:w-96 border-l border-slate-800 bg-slate-900/95 backdrop-blur-xl flex flex-col justify-between shadow-2xl z-30 shrink-0 overflow-y-auto">
                <div class="p-5 space-y-4">
                    <!-- Drawer Header -->
                    <div class="flex items-start justify-between pb-3 border-b border-slate-800 gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $selectedChurch->generationColor === '#3b82f6' ? 'bg-blue-950 text-blue-300 border border-blue-800' : ($selectedChurch->generationColor === '#8b5cf6' ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-amber-950 text-amber-300 border border-amber-800') }}">
                                    Gen {{ $selectedChurch->generation }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $selectedChurch->is_dissolved ? 'bg-slate-800 text-slate-400 border border-slate-700' : ($selectedChurch->is_healthy_church ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-amber-950 text-amber-300 border border-amber-800') }}">
                                    {{ $selectedChurch->statusLabel }}
                                </span>
                            </div>
                            <h3 class="text-sm font-black text-white mt-1.5 leading-snug">
                                {{ $selectedChurch->name }}
                            </h3>
                            <p class="text-[11px] text-slate-400">
                                {{ $selectedChurch->ward->fullName }} • Founded Month {{ $selectedChurch->founded_game_month }}
                            </p>
                        </div>
                        <button type="button" wire:click="toggleDrawer" class="text-slate-400 hover:text-white p-1 text-sm font-bold">✕</button>
                    </div>

                    <!-- Attrition / Dissolution Notice if Applicable -->
                    @if($selectedChurch->is_dissolved)
                        <div class="p-3 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 text-xs space-y-1.5">
                            <div class="flex items-center gap-1.5 font-bold text-rose-200">
                                <span>⚠️</span>
                                <span>Dissolved in Month {{ $selectedChurch->dissolved_game_month ?? $selectedChurch->last_attrition_month }}</span>
                            </div>
                            <p class="text-[11px] leading-relaxed text-rose-200/90">
                                <strong>Reason:</strong> {{ $selectedChurch->dissolution_reason ?? 'Severe member attrition or conflict halted gatherings.' }}
                            </p>
                            <button 
                                type="button" 
                                wire:click="revitalizeChurch({{ $selectedChurch->id }})" 
                                class="mt-2 w-full py-1.5 px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center justify-center gap-1.5 shadow"
                            >
                                <span>🌱</span>
                                <span>Revitalize Church Circle</span>
                            </button>
                        </div>
                    @elseif($selectedChurch->last_attrition_event)
                        <div class="p-2.5 rounded-xl bg-amber-950/40 border border-amber-900/60 text-amber-300 text-xs">
                            <span class="font-bold flex items-center gap-1">
                                <span>📉</span>
                                <span>Recent Life Trials / Attrition:</span>
                            </span>
                            <p class="text-[11px] text-amber-200/80 mt-1">
                                {{ $selectedChurch->last_attrition_event }}
                            </p>
                        </div>
                    @endif

                    <!-- Core Metrics Card -->
                    <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Church Health Metric</span>
                            <span class="font-black {{ $selectedChurch->health_score >= 50 ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ $selectedChurch->health_score }}%
                            </span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div 
                                class="h-1.5 rounded-full transition-all duration-300 {{ $selectedChurch->health_score >= 50 ? 'bg-emerald-500' : 'bg-amber-500' }}" 
                                style="width: {{ $selectedChurch->health_score }}%;"
                            ></div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-800 text-center">
                            <div class="p-2 rounded-xl bg-slate-900/80 border border-slate-800/60">
                                <span class="text-base">😊</span>
                                <div class="text-xs font-black text-amber-300">{{ $selectedChurch->attenders_count }}</div>
                                <div class="text-[9px] text-slate-400 uppercase tracking-tighter">Attenders</div>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-900/80 border border-slate-800/60">
                                <span class="text-base">👑</span>
                                <div class="text-xs font-black text-purple-300">{{ $selectedChurch->believers_count }}</div>
                                <div class="text-[9px] text-slate-400 uppercase tracking-tighter">Believers</div>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-900/80 border border-slate-800/60">
                                <span class="text-base">💧</span>
                                <div class="text-xs font-black text-cyan-300">{{ $selectedChurch->baptized_count }}</div>
                                <div class="text-[9px] text-slate-400 uppercase tracking-tighter">Baptized</div>
                            </div>
                        </div>
                    </div>

                    <!-- Leadership & Discipleship Heritage -->
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Pastoral Leader:</span>
                            <span class="font-bold text-indigo-400">{{ $selectedChurch->leaderDisplayName }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Autonomous Status:</span>
                            <span class="font-bold {{ $selectedChurch->isAutonomous ? 'text-emerald-400' : 'text-blue-400' }}">
                                {{ $selectedChurch->isAutonomous ? 'Autonomous Local Leader' : 'Player Pioneer-Led (G1)' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Mother / Planted By:</span>
                            <span class="font-bold text-slate-300">
                                {{ $selectedChurch->parentChurch?->name ?? ($selectedChurch->started_by_name ?? 'Apostolic Team') }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-800/60">
                            <span class="text-slate-400">Daughter Churches:</span>
                            <span class="font-bold text-amber-400">
                                {{ $selectedChurch->daughterChurches->count() }} planted
                            </span>
                        </div>
                    </div>

                    <!-- 11 Acts 2 Church Circle Elements -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                <span>⛪</span>
                                <span>Acts 2 Functions ({{ $selectedChurch->practicedElementsCount }}/11)</span>
                            </h4>
                            <span class="text-[10px] text-slate-400">3/3rds pattern</span>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                            @foreach($allElements as $elem)
                                @php
                                    $isPracticed = $selectedChurch->practicesElement($elem);
                                @endphp
                                <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg border {{ $isPracticed ? 'bg-emerald-950/40 text-emerald-300 border-emerald-900/60' : 'bg-slate-950/50 text-slate-500 border-slate-800/80' }}">
                                    <span>{{ $elem->symbol() }}</span>
                                    <span class="truncate font-medium">{{ $elem->label() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Drawer Action Footer -->
                <div class="p-4 bg-slate-950 border-t border-slate-800 flex flex-col gap-2">
                    @if(! $selectedChurch->is_dissolved)
                        @php
                            $drawerCoaching = $selectedChurch->coachingEligibility($user?->current_game_month ?? 1, $user?->current_game_day ?? 1);
                        @endphp
                        <button 
                            type="button" 
                            wire:click="queueCoachingForChurch({{ $selectedChurch->id }})" 
                            class="w-full py-2 px-3 rounded-xl text-xs font-bold {{ $drawerCoaching['allowed'] ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-950 cursor-pointer' : 'bg-slate-800 text-slate-400 border border-slate-700 cursor-not-allowed' }} transition flex items-center justify-center gap-1.5 shadow-lg"
                            @if(! $drawerCoaching['allowed']) title="{{ $drawerCoaching['reason'] }}" @endif
                        >
                            <span>{{ $drawerCoaching['allowed'] ? '⚡' : '⏳' }}</span>
                            <span>{{ $drawerCoaching['allowed'] ? 'Queue 3/3rds Church Circles Coaching (8 AP)' : 'Coached This Week (Next in '.$drawerCoaching['days_remaining'].'d)' }}</span>
                        </button>
                        @if(! $drawerCoaching['allowed'])
                            <p class="text-[10px] text-amber-400/90 text-center px-1 leading-tight">
                                {{ $drawerCoaching['reason'] }}
                            </p>
                        @endif
                    @endif

                    <div class="flex items-center justify-between text-[11px] text-slate-400 px-1">
                        <span>Meeting Ward: {{ $selectedChurch->ward->name }}</span>
                        <span>Month {{ $selectedChurch->founded_game_month }}</span>
                    </div>
                </div>
            </aside>
        @endif
    </div>
</div>
