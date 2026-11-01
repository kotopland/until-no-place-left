<div class="relative flex flex-col items-center p-4 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm dark:shadow-xl backdrop-blur-md">
    <div class="w-full flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800/80 mb-2">
        <div>
            <h4 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                <span class="text-base">⛪</span>
                <span>{{ $church?->name ?? 'Acts 2 Church Circle' }}</span>
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ $church?->ward?->fullName ?? 'Nagoya Ward' }} • 
                <span class="font-medium text-amber-700 dark:text-amber-400">Gen {{ $generation }}</span>
                @if($church?->isAutonomous)
                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-900/60 dark:text-purple-300 dark:border-purple-700">Autonomous Leader</span>
                @else
                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900/60 dark:text-blue-300 dark:border-blue-700">Player-Led (G1)</span>
                @endif
            </p>
        </div>

        <div class="text-right">
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $isHealthy ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950 dark:text-emerald-400 dark:border-emerald-700' : 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-950 dark:text-amber-400 dark:border-amber-700' }}">
                {{ $isHealthy ? 'Healthy Church (Acts 2)' : 'Developing Group' }}
            </span>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $healthScore }}% Health Metric</p>
        </div>
    </div>

    <!-- Interactive Acts 2 SVG Circle -->
    <div class="relative w-[300px] h-[300px]">
        <svg viewBox="0 0 300 300" class="w-full h-full drop-shadow-md" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <!-- Outer Orbit Circle: Solid if Healthy Church, Dashed if Developing -->
            <circle 
                cx="150" 
                cy="150" 
                r="110" 
                fill="none" 
                stroke="{{ $isHealthy ? '#10b981' : '#f59e0b' }}" 
                stroke-width="{{ $isHealthy ? '3' : '2' }}" 
                stroke-dasharray="{{ $isHealthy ? 'none' : '6 5' }}"
                opacity="0.85"
            />

            <!-- Central Hub Badge -->
            <circle cx="150" cy="150" r="48" class="fill-slate-100 dark:fill-slate-800 stroke-slate-300 dark:stroke-slate-700" stroke-width="2" />
            <text x="150" y="142" text-anchor="middle" font-size="20" font-weight="bold" class="fill-slate-900 dark:fill-slate-50">G{{ $generation }}</text>
            <text x="150" y="160" text-anchor="middle" font-size="10" font-weight="600" class="fill-slate-600 dark:fill-slate-400">
                {{ count($church?->elements_json ?? []) }}/11 Elements
            </text>

            <!-- Element Nodes around Circle -->
            @foreach($items as $node)
                <g class="cursor-pointer group">
                    <circle 
                        cx="{{ $node['x'] }}" 
                        cy="{{ $node['y'] }}" 
                        r="17" 
                        class="transition-all duration-200 group-hover:scale-110 {{ $node['isPracticed'] ? 'fill-emerald-100 stroke-emerald-600 dark:fill-emerald-950 dark:stroke-emerald-400' : 'fill-slate-100 stroke-slate-300 dark:fill-slate-800 dark:stroke-slate-700' }}"
                        stroke-width="{{ $node['isPracticed'] ? '2.5' : '1.5' }}" 
                        @if($node['isPracticed']) filter="url(#glow)" @endif
                    />
                    <text 
                        x="{{ $node['x'] }}" 
                        y="{{ $node['y'] + 5 }}" 
                        text-anchor="middle" 
                        font-size="14"
                    >
                        {{ $node['enum']->symbol() }}
                    </text>
                </g>
            @endforeach
        </svg>
    </div>

    <!-- Interactive Elements Checklist Grid -->
    <div class="w-full mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 text-xs">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
            @foreach($items as $node)
                <div class="flex items-center gap-1.5 px-2 py-1 rounded-md border {{ $node['isPracticed'] ? 'bg-emerald-50 text-emerald-900 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800/40 dark:text-slate-500 dark:border-slate-800' }}">
                    <span class="text-xs">{{ $node['enum']->symbol() }}</span>
                    <span class="truncate font-medium">{{ $node['enum']->label() }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
