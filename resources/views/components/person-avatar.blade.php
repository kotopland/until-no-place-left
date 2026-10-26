<div 
    {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center shrink-0 group select-none']) }}
    style="width: {{ $pixelSize }}px; height: {{ $pixelSize }}px;"
    title="{{ $nationality }} • {{ ucfirst($gender) }} • {{ $age }} yrs ({{ $ageGroupLabel }})"
>
    <!-- Base SVG Avatar -->
    <div class="w-full h-full rounded-2xl overflow-hidden flex items-center justify-center transition-transform duration-200 group-hover:scale-105">
        {!! $svg !!}
    </div>

    @if($showBadges)
        <!-- Top-Right Nationality Flag Pill -->
        <span 
            class="absolute -top-1.5 -right-1.5 flex items-center justify-center bg-slate-950/95 rounded-full border border-slate-700/80 shadow-md transition-transform group-hover:scale-110 pointer-events-none"
            style="width: {{ max(16, (int) round($pixelSize * 0.38)) }}px; height: {{ max(16, (int) round($pixelSize * 0.38)) }}px; font-size: {{ max(10, (int) round($pixelSize * 0.24)) }}px;"
            title="Nationality: {{ $nationality }} {{ $flag }}"
        >
            {{ $flag }}
        </span>

        @if($showAgePill || $pixelSize >= 48)
            <!-- Bottom Gender & Age Pill -->
            <span 
                class="absolute -bottom-1.5 inset-x-0 mx-auto w-max max-w-full px-1.5 py-0.2 rounded-full bg-slate-950/95 border border-slate-700/80 shadow-sm flex items-center justify-center gap-0.5 text-[9px] font-bold text-slate-200 tracking-tight leading-tight pointer-events-none"
                style="font-size: {{ max(8, (int) round($pixelSize * 0.17)) }}px;"
            >
                <span class="{{ strtolower($gender) === 'female' ? 'text-pink-400' : 'text-sky-400' }}">{{ $genderIcon }}</span>
                <span>{{ $age }}y</span>
            </span>
        @endif
    @endif
</div>