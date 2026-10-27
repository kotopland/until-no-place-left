<div 
    class="space-y-4"
    x-data="{
        scrollToBottom() {
            $nextTick(() => {
                const el = this.$refs.messageContainer;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
    }"
    x-init="scrollToBottom()"
    @chat-message-sent.window="scrollToBottom()"
    wire:poll.15s
>
    <!-- Top Header: Title, Session Context & Quick Actions -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/80 to-slate-900 border border-slate-800 rounded-3xl p-5 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/40 flex items-center justify-center text-2xl shadow-inner shrink-0">
                    💬
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] uppercase tracking-widest font-extrabold text-indigo-400 bg-indigo-950/80 border border-indigo-800/60 px-2 py-0.5 rounded-full">
                            Asynchronous Multiplayer
                        </span>
                        @if($session)
                            <span class="text-xs font-bold text-slate-300">
                                {{ $session->monthLabel }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-xl font-black text-white tracking-tight mt-0.5">
                        Missionary Dispatch &amp; Co-op Chat
                    </h2>
                    <p class="text-xs text-slate-400">
                        Coordinate citywide strategy, share prayer requests, and track local ward dispatches across different real-world schedules.
                    </p>
                </div>
            </div>

            <!-- Action buttons: Mark Read & Search -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search messages..." 
                        class="pl-8 pr-3 py-1.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 w-40 sm:w-56"
                    />
                    <svg class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <button 
                    type="button"
                    wire:click="markAllRead"
                    class="px-3 py-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/80 text-xs font-bold text-slate-300 hover:text-white transition cursor-pointer flex items-center gap-1.5"
                    title="Mark all messages in this session as read"
                >
                    <span>✓✓</span>
                    <span class="hidden sm:inline">Mark Read</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Pinned Strategic Directives Banner (if any exist) -->
    @if($pinnedMessages->isNotEmpty())
        <div class="bg-amber-950/30 border border-amber-600/40 rounded-2xl p-3.5 text-xs text-amber-200 flex items-start gap-3 shadow-md" x-data="{ expanded: false }">
            <span class="text-lg shrink-0 mt-0.5">📌</span>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold uppercase tracking-wider text-[10px] text-amber-400">Pinned Missionary Directive</span>
                    @if($pinnedMessages->count() > 1)
                        <button 
                            type="button" 
                            @click="expanded = !expanded" 
                            class="text-[10px] font-bold text-amber-300 underline hover:text-amber-100"
                        >
                            <span x-text="expanded ? 'Show Less' : '+{{ $pinnedMessages->count() - 1 }} More Directives'"></span>
                        </button>
                    @endif
                </div>

                <!-- First pinned message preview -->
                <div class="mt-1 font-medium text-amber-100 leading-relaxed">
                    <span class="font-bold text-white">{{ $pinnedMessages->first()->user?->name }}:</span>
                    {{ $pinnedMessages->first()->message }}
                    <span class="text-[10px] text-amber-400/80 ml-1.5">({{ $pinnedMessages->first()->timeContextLabel }})</span>
                </div>

                <!-- Expanded extra pinned messages -->
                <div x-show="expanded" class="mt-2 space-y-2 border-t border-amber-800/50 pt-2" style="display: none;">
                    @foreach($pinnedMessages->slice(1) as $pinned)
                        <div class="text-[11px] leading-relaxed">
                            <span class="font-bold text-white">{{ $pinned->user?->name }}:</span>
                            {{ $pinned->message }}
                            <span class="text-[10px] text-amber-400/80 ml-1">({{ $pinned->timeContextLabel }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Channel Selector Tabs & Ward Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-2 shadow-sm">
        <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0">
            <!-- 🌐 Citywide Channel -->
            <button 
                type="button"
                wire:click="setChannel('citywide')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer {{ $activeChannel === 'citywide' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
            >
                <span>🌐</span>
                <span>Citywide</span>
                @if($unreadCounts['citywide'] > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-rose-500 text-white">
                        {{ $unreadCounts['citywide'] }}
                    </span>
                @endif
            </button>

            <!-- 📍 Ward Field Dispatch -->
            <button 
                type="button"
                wire:click="setChannel('ward')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer {{ $activeChannel === 'ward' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
            >
                <span>📍</span>
                <span>Ward Dispatch</span>
                @if($unreadCounts['ward'] > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-rose-500 text-white">
                        {{ $unreadCounts['ward'] }}
                    </span>
                @endif
            </button>

            <!-- 🙏 Prayer Wall & Praises -->
            <button 
                type="button"
                wire:click="setChannel('prayer')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer {{ $activeChannel === 'prayer' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
            >
                <span>🙏</span>
                <span>Prayer Wall</span>
                @if($unreadCounts['prayer'] > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-amber-500 text-slate-950">
                        {{ $unreadCounts['prayer'] }}
                    </span>
                @endif
            </button>

            <!-- 📣 Movement Announcements -->
            <button 
                type="button"
                wire:click="setChannel('announcements')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer {{ $activeChannel === 'announcements' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
            >
                <span>📣</span>
                <span>Announcements</span>
                @if($unreadCounts['announcements'] > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-indigo-500 text-white">
                        {{ $unreadCounts['announcements'] }}
                    </span>
                @endif
            </button>
        </div>

        <!-- Ward filter selector (active only when in 'ward' channel or quick filter) -->
        @if($activeChannel === 'ward')
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] font-medium text-slate-400">Ward:</span>
                <select 
                    wire:model.live="selectedWardId"
                    class="bg-slate-100 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl px-2.5 py-1 text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                    <option value="">All 16 Wards</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}">{{ $ward->name }} ({{ $ward->name_ja }})</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <!-- Main Message Stream Window -->
    <div 
        x-ref="messageContainer"
        class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 sm:p-6 h-[520px] overflow-y-auto space-y-4 shadow-inner"
    >
        @php
            $hasRenderedUnreadDivider = false;
        @endphp

        @forelse($messages as $msg)
            <!-- Unread divider for asynchronous players returning to the game -->
            @if(! $hasRenderedUnreadDivider && $parsedLastRead && $msg->created_at->gt($parsedLastRead) && $msg->user_id !== $currentUser?->id)
                @php $hasRenderedUnreadDivider = true; @endphp
                <div class="flex items-center gap-3 my-4">
                    <div class="flex-1 border-t border-rose-500/40"></div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-500 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900/60 px-3 py-1 rounded-full shadow-sm">
                        🔴 New Messages Since Your Last Visit
                    </span>
                    <div class="flex-1 border-t border-rose-500/40"></div>
                </div>
            @endif

            <!-- Message Card -->
            <div 
                wire:key="msg-{{ $msg->id }}"
                class="group flex items-start gap-3 p-3.5 rounded-2xl transition {{ $msg->is_pinned ? 'bg-amber-500/5 dark:bg-amber-950/20 border border-amber-500/30' : ($msg->user_id === $currentUser?->id ? 'bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200/50 dark:border-indigo-900/40' : 'bg-slate-50/70 dark:bg-slate-950/50 border border-slate-200/70 dark:border-slate-800/60') }}"
            >
                <!-- Avatar / Flag Badge -->
                <div class="shrink-0 pt-0.5">
                    @if($msg->user)
                        <div class="w-10 h-10 rounded-2xl bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center text-xl shadow-sm relative overflow-hidden" title="{{ $msg->user->nationality ?? 'Missionary' }}">
                            @if($msg->user->nationality)
                                <span>{{ $avatarService->flagEmoji($msg->user->nationality) }}</span>
                            @else
                                <span>👤</span>
                            @endif
                        </div>
                    @else
                        <div class="w-10 h-10 rounded-2xl bg-indigo-950 border border-indigo-700 flex items-center justify-center text-xl">
                            📣
                        </div>
                    @endif
                </div>

                <!-- Message Body -->
                <div class="flex-1 min-w-0">
                    <!-- Author header, in-game turn context, and real-world time -->
                    <div class="flex flex-wrap items-center justify-between gap-1.5 mb-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-black text-slate-900 dark:text-white">
                                {{ $msg->user?->name ?? 'Nagoya Movement Dispatch' }}
                            </span>

                            @if($msg->user?->is_ai_colaborer)
                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded-md bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-800" title="Automated AI Missionary Co-laborer">
                                    🤖 AI Co-laborer
                                </span>
                            @endif

                            @if($msg->user?->homeWard)
                                <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
                                    📍 {{ $msg->user->homeWard->name }}
                                </span>
                            @endif

                            @if($msg->ward)
                                <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 px-2 py-0.5 rounded-md">
                                    Ward Focus: {{ $msg->ward->name }}
                                </span>
                            @endif

                            @if($msg->is_pinned)
                                <span class="text-[9px] font-extrabold uppercase text-amber-500 bg-amber-100 dark:bg-amber-950/60 px-1.5 py-0.5 rounded-md border border-amber-400/40">
                                    📌 Pinned
                                </span>
                            @endif
                        </div>

                        <!-- Asynchronous Turn Stamp + Real-world Relative Time -->
                        <div class="flex items-center gap-2 text-[10px] text-slate-400">
                            <!-- In-Game Calendar Turn Stamp -->
                            <span class="font-bold text-indigo-600 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800/80 px-2 py-0.5 rounded-full" title="In-game simulation turn at moment of dispatch">
                                {{ $msg->timeContextLabel }}
                            </span>
                            <!-- Real-World Clock Time -->
                            <span title="{{ $msg->created_at->format('Y-m-d H:i') }}">
                                {{ $msg->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    <!-- Message text -->
                    <div class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line break-words font-medium">
                        {{ $msg->message }}
                    </div>

                    <!-- Reactions Bar & Actions -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mt-2.5 pt-2 border-t border-slate-200/50 dark:border-slate-800/40">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @php
                                $grouped = $msg->groupedReactions($currentUser?->id);
                            @endphp

                            <!-- Quick Reaction Toggles -->
                            @foreach([
                                'amen' => ['icon' => '🙏', 'label' => 'Amen'],
                                'praise' => ['icon' => '🙌', 'label' => 'Praise'],
                                'fire' => ['icon' => '🔥', 'label' => 'Fire'],
                                'handshake' => ['icon' => '🤝', 'label' => 'Co-labor'],
                                'idea' => ['icon' => '💡', 'label' => 'Idea'],
                            ] as $reactKey => $reactMeta)
                                @php
                                    $data = $grouped[$reactKey] ?? null;
                                    $count = $data['count'] ?? 0;
                                    $hasReacted = $data['reacted_by_auth'] ?? false;
                                    $names = ! empty($data['user_names']) ? implode(', ', array_unique($data['user_names'])) : '';
                                @endphp

                                <button 
                                    type="button"
                                    wire:click="toggleReaction({{ $msg->id }}, '{{ $reactKey }}')"
                                    class="px-2 py-1 rounded-xl text-[11px] font-bold transition flex items-center gap-1 cursor-pointer {{ $hasReacted ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-400' : ($count > 0 ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-800/60 opacity-60 hover:opacity-100') }}"
                                    title="{{ $names ?: $reactMeta['label'] }}"
                                >
                                    <span>{{ $reactMeta['icon'] }}</span>
                                    @if($count > 0)
                                        <span class="text-[10px]">{{ $count }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <!-- Special Action: Pin/Unpin (for owner or lead) -->
                        <div class="opacity-0 group-hover:opacity-100 transition flex items-center gap-1">
                            <button 
                                type="button"
                                wire:click="togglePin({{ $msg->id }})"
                                class="text-[10px] font-bold text-slate-400 hover:text-amber-400 px-2 py-0.5 rounded transition cursor-pointer"
                                title="{{ $msg->is_pinned ? 'Unpin directive' : 'Pin as missionary directive' }}"
                            >
                                {{ $msg->is_pinned ? 'Unpin' : '📌 Pin' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                <div class="text-4xl mb-2">🕊️</div>
                <h4 class="text-sm font-bold text-slate-300">No Messages Yet in #{{ $activeChannel }}</h4>
                <p class="text-xs text-slate-500 max-w-sm mt-1">
                    Be the first missionary to share an encouragement, prayer request, or strategic dispatch for this session.
                </p>
            </div>
        @endforelse
    </div>

    <!-- Quick Strategy Tags & Injection Bar -->
    <div class="flex flex-wrap items-center gap-1.5 px-1 text-xs">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Quick Tags:</span>
        <button 
            type="button" 
            wire:click="insertTag('#PrayerRequest')"
            class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-400 text-slate-500 dark:text-slate-400 text-[10px] font-semibold transition cursor-pointer"
        >
            🙏 #PrayerRequest
        </button>
        <button 
            type="button" 
            wire:click="insertTag('#PrayerWalk')"
            class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-400 text-slate-500 dark:text-slate-400 text-[10px] font-semibold transition cursor-pointer"
        >
            🚶 #PrayerWalk
        </button>
        <button 
            type="button" 
            wire:click="insertTag('#PersonOfPeace')"
            class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-400 text-slate-500 dark:text-slate-400 text-[10px] font-semibold transition cursor-pointer"
        >
            🌾 #PersonOfPeace
        </button>
        <button 
            type="button" 
            wire:click="insertTag('#ChurchPlant')"
            class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-400 text-slate-500 dark:text-slate-400 text-[10px] font-semibold transition cursor-pointer"
        >
            ⛪ #ChurchPlant
        </button>

        @if($currentUser?->homeWard)
            <button 
                type="button" 
                wire:click="insertTag('#{{ str_replace([' ', '-ku'], '', $currentUser->homeWard->name) }}')"
                class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 text-[10px] font-semibold transition cursor-pointer"
            >
                📍 #{{ str_replace([' ', '-ku'], '', $currentUser->homeWard->name) }}
            </button>
        @endif
    </div>

    <!-- Message Composer Box -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-3 sm:p-4 shadow-xl">
        <form wire:submit.prevent="sendMessage" class="space-y-3">
            <div class="relative">
                <textarea 
                    wire:model="newMessage"
                    rows="3"
                    placeholder="Type a field dispatch or prayer request for Nagoya missionaries... (Press Enter to send)"
                    @keydown.enter.exact.prevent="$wire.sendMessage()"
                    class="w-full bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none font-medium leading-relaxed"
                ></textarea>

                @error('newMessage')
                    <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Composer Actions Footer -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div class="flex items-center gap-3">
                    <!-- Pin Directive Checkbox -->
                    <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                        <input 
                            type="checkbox" 
                            wire:model="isPinned"
                            class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-[11px] font-semibold">Pin as Strategic Directive</span>
                    </label>

                    <span class="text-[10px] text-slate-400 hidden sm:inline">
                        Channel: <span class="font-bold uppercase text-indigo-400">#{{ $activeChannel }}</span>
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-400 hidden md:inline">
                        Tip: Press <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono text-[9px]">Enter</kbd> to send
                    </span>

                    <button 
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-xs font-black text-white shadow-md shadow-indigo-600/30 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>Send Dispatch 🚀</span>
                        <span wire:loading>Sending...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
