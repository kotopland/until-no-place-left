@props([
    'id' => null,
    'name' => null,
    'value' => null,
    'placeholder' => 'Search and select country of origin...',
])

@php
    $countries = app(\App\Services\PersonAvatarService::class)->commonCountries();
    $currentVal = $value ?? 'Japanese';
@endphp

<div 
    x-data="{
        open: false,
        search: '',
        selected: '{{ $currentVal }}',
        countries: {{ Js::from($countries) }},
        highlightIndex: 0,

        get filteredCountries() {
            if (!this.search.trim()) {
                return this.countries;
            }
            const q = this.search.toLowerCase().trim();
            return this.countries.filter(c => 
                c.country.toLowerCase().includes(q) || 
                c.nationality.toLowerCase().includes(q)
            );
        },

        get currentItem() {
            const raw = typeof this.selected === 'object' && this.selected !== null 
                ? (this.selected.nationality || 'Japanese') 
                : (this.selected || '{{ $currentVal }}');
            const val = String(raw).toLowerCase();
            return this.countries.find(c => c.nationality.toLowerCase() === val || c.country.toLowerCase() === val) 
                || this.countries.find(c => c.nationality.toLowerCase() === 'japanese') 
                || this.countries[0];
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                this.highlightIndex = 0;
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        },

        close() {
            this.open = false;
            this.search = '';
        },

        selectCountry(item) {
            const val = typeof item === 'object' && item !== null 
                ? (item.nationality || 'Japanese') 
                : String(item);
            this.selected = val;
            this.open = false;
            this.search = '';

            if (this.$refs.nativeSelect) {
                this.$refs.nativeSelect.value = val;
            }
        },

        selectHighlighted() {
            const items = this.filteredCountries;
            if (items.length > 0) {
                const target = items[this.highlightIndex] || items[0];
                this.selectCountry(target);
            }
        },

        nextItem() {
            const max = this.filteredCountries.length - 1;
            if (max >= 0) {
                this.highlightIndex = this.highlightIndex < max ? this.highlightIndex + 1 : 0;
            }
        },

        prevItem() {
            const max = this.filteredCountries.length - 1;
            if (max >= 0) {
                this.highlightIndex = this.highlightIndex > 0 ? this.highlightIndex - 1 : max;
            }
        }
    }"
    x-modelable="selected"
    {{ $attributes->whereDoesntStartWith('class') }}
    @click.outside="close()"
    @keydown.escape.window="close()"
    class="relative w-full"
>
    <!-- Hidden Accessible Select for Test Assertions & HTML Form Submissions -->
    <select 
        x-ref="nativeSelect"
        x-model="selected"
        @if($id) id="{{ $id }}" @endif
        @if($name) name="{{ $name }}" @endif
        class="sr-only"
        tabindex="-1"
        aria-hidden="true"
    >
        @foreach($countries as $item)
            <option value="{{ $item['nationality'] }}">
                {{ $item['flag'] }} {{ $item['country'] }} ({{ $item['nationality'] }})
            </option>
        @endforeach
    </select>

    <!-- Custom Searchable Trigger Button -->
    <button 
        type="button" 
        @click="toggle()"
        class="w-full bg-slate-950/80 hover:bg-slate-900 border {{ $attributes->has('class') && str_contains($attributes->get('class'), 'border-rose-500') ? 'border-rose-500' : 'border-slate-800 hover:border-slate-700 focus:border-indigo-500' }} rounded-2xl px-4 py-3 text-left flex items-center justify-between gap-2.5 transition group focus:outline-none focus:ring-2 focus:ring-indigo-500/20 cursor-pointer shadow-inner"
        aria-haspopup="listbox"
        :aria-expanded="open"
    >
        <div class="flex items-center gap-2.5 min-w-0">
            <!-- Flag Badge -->
            <span class="text-xl shrink-0 group-hover:scale-110 transition-transform" x-text="currentItem ? currentItem.flag : '🇯🇵'"></span>
            
            <!-- Country Name & Nationality Pill -->
            <div class="min-w-0 truncate">
                <span class="text-sm font-bold text-white truncate" x-text="currentItem ? currentItem.country : 'Japan'"></span>
                <span class="ml-1.5 text-xs text-indigo-300 font-medium" x-text="currentItem ? '(' + currentItem.nationality + ')' : '(Japanese)'"></span>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider group-hover:text-slate-400">Search</span>
            <span 
                class="text-xs text-slate-400 group-hover:text-white transition-transform duration-200"
                :class="{ 'rotate-180': open }"
            >
                ▼
            </span>
        </div>
    </button>

    <!-- Dropdown Panel with Search & Filterable Countries -->
    <div 
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-98"
        class="absolute left-0 right-0 z-50 mt-2 bg-slate-950 border border-slate-700/80 rounded-2xl shadow-2xl p-2.5 backdrop-blur-xl space-y-2"
        role="listbox"
    >
        <!-- Search Input Bar -->
        <div class="relative">
            <input 
                x-ref="searchInput"
                type="text" 
                x-model="search"
                @keydown.arrow-down.prevent="nextItem()"
                @keydown.arrow-up.prevent="prevItem()"
                @keydown.enter.prevent="selectHighlighted()"
                placeholder="🔍 Type country or nationality (e.g. Brazil, Korea, Canada)..." 
                class="w-full bg-slate-900 border border-slate-700 focus:border-indigo-500 rounded-xl pl-3 pr-8 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 transition"
            >
            <template x-if="search.length > 0">
                <button 
                    type="button" 
                    @click.stop="search = ''; $refs.searchInput.focus()"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs cursor-pointer p-0.5"
                    title="Clear search"
                >
                    ✕
                </button>
            </template>
        </div>

        <!-- Filter Count Pill -->
        <div class="flex items-center justify-between px-1 text-[10px] text-slate-400 font-medium">
            <span>Select sending nation</span>
            <span class="text-indigo-400 font-bold" x-text="filteredCountries.length + ' countries'"></span>
        </div>

        <!-- Scrollable Options List -->
        <div class="max-h-60 overflow-y-auto space-y-0.5 pr-1 divide-y divide-slate-800/40">
            <template x-for="(item, idx) in filteredCountries" :key="item.nationality">
                <button 
                    type="button" 
                    @click="selectCountry(item)"
                    @mouseenter="highlightIndex = idx"
                    class="w-full px-2.5 py-2 rounded-xl text-left flex items-center justify-between gap-2 transition cursor-pointer"
                    :class="{
                        'bg-indigo-600/30 text-white border border-indigo-500/40': String(selected || '').toLowerCase() === item.nationality.toLowerCase(),
                        'bg-slate-900/90 text-white': idx === highlightIndex && String(selected || '').toLowerCase() !== item.nationality.toLowerCase(),
                        'text-slate-300 hover:bg-slate-900': idx !== highlightIndex && String(selected || '').toLowerCase() !== item.nationality.toLowerCase()
                    }"
                    role="option"
                    :aria-selected="String(selected || '').toLowerCase() === item.nationality.toLowerCase()"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-lg shrink-0" x-text="item.flag"></span>
                        <div class="min-w-0 truncate">
                            <span class="text-xs font-bold text-white truncate" x-text="item.country"></span>
                            <span class="ml-1 text-[11px] text-slate-400" x-text="'(' + item.nationality + ')'"></span>
                        </div>
                    </div>

                    <template x-if="String(selected || '').toLowerCase() === item.nationality.toLowerCase()">
                        <span class="text-xs text-indigo-400 font-bold shrink-0">✓ Selected</span>
                    </template>
                </button>
            </template>

            <!-- Empty State -->
            <template x-if="filteredCountries.length === 0">
                <div class="p-4 text-center space-y-2">
                    <p class="text-xs text-slate-400">
                        No countries found matching "<span class="text-white font-bold" x-text="search"></span>"
                    </p>
                    <button 
                        type="button" 
                        @click="search = ''; $refs.searchInput.focus()"
                        class="text-[11px] text-indigo-400 hover:text-indigo-300 underline font-medium cursor-pointer"
                    >
                        Show all countries
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
