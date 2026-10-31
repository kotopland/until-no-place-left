<div>
    @if($isOpen && $report)
        <!-- Modal Backdrop -->
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md transition-all">
            <!-- Modal Dialog Container -->
            <div class="relative w-full max-w-2xl max-h-[90vh] bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="relative p-6 bg-gradient-to-r from-indigo-900/60 via-purple-900/40 to-slate-900 border-b border-slate-800">
                    <button 
                        type="button" 
                        wire:click="closeModal" 
                        class="absolute top-5 right-5 text-slate-400 hover:text-white p-1 rounded-lg bg-slate-800/60 text-sm transition"
                    >
                        ✕
                    </button>

                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-2xl">🌱</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/40">
                            Month {{ $report->game_month }} Resolution Digest
                        </span>
                    </div>
                    <h2 class="text-xl font-extrabold text-white tracking-tight">
                        {{ $report->summary_title }}
                    </h2>
                    <p class="text-xs text-slate-300 mt-1">
                        Your relational sowing, prayers, and autonomous disciples have yielded fruit in Nagoya!
                    </p>
                </div>

                <!-- Scrollable Body -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1 custom-scrollbar text-xs">
                    <!-- Key Metric KPI Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-2xl bg-slate-800/50 border border-slate-700/60 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Score Gained</span>
                            <span class="text-lg font-black {{ $report->generational_score_gained < 0 ? 'text-rose-400' : 'text-emerald-400' }} mt-0.5 block">{{ $report->generational_score_gained >= 0 ? '+' : '' }}{{ $report->generational_score_gained }} pts</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-800/50 border border-slate-700/60 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Disciples Multiplied</span>
                            <span class="text-lg font-black text-indigo-400 mt-0.5 block">{{ $report->disciples_multiplied_count }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-800/50 border border-slate-700/60 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Churches Planted</span>
                            <span class="text-lg font-black text-purple-400 mt-0.5 block">{{ $report->churches_planted_count }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-800/50 border border-slate-700/60 text-center">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Prayers Answered</span>
                            <span class="text-lg font-black text-amber-400 mt-0.5 block">{{ $report->answered_prayers_json['count'] ?? 0 }} 🙏</span>
                        </div>
                    </div>

                    <!-- Relational Breakthroughs & Monthly Highlights -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <span>✨</span>
                            <span>Relational Breakthroughs & Sowing Highlights</span>
                        </h4>
                        <div class="space-y-2">
                            @forelse($report->highlights_json ?? [] as $highlight)
                                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 text-slate-300 flex items-start gap-2.5 leading-relaxed">
                                    <span class="text-indigo-400 shrink-0 mt-0.5">●</span>
                                    <span>{{ $highlight }}</span>
                                </div>
                            @empty
                                <div class="p-3 rounded-xl bg-slate-950/40 text-slate-500 text-center">
                                    No breakthroughs recorded this month. Keep building relational bridges!
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- AI Coach Tips: "Why This Worked (Kingdom Principles)" -->
                    @if(!empty($report->coach_tips_json))
                        <div class="bg-gradient-to-r from-amber-950/40 via-indigo-950/30 to-slate-950 border border-amber-900/40 rounded-2xl p-4 shadow-inner">
                            <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <span>🧠</span>
                                <span>Kingdom Movement Coach Insights</span>
                            </h4>
                            <div class="space-y-2.5">
                                @foreach($report->coach_tips_json as $tip)
                                    <div class="border-l-2 border-amber-500 pl-3">
                                        <div class="text-[11px] font-semibold text-white">{{ $tip['action'] ?? 'Principle' }}</div>
                                        <div class="text-[11px] text-amber-200/90 mt-0.5 leading-relaxed italic">
                                            "{{ $tip['tip'] }}"
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Overseas Supporter Prayer Letter & Focus Selection -->
                    @if($report->isPrayerLetterDispatched)
                        <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-800/60 flex items-center justify-between gap-3 shadow-inner">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">✉️</span>
                                <div>
                                    <div class="text-xs font-bold text-emerald-300">
                                        Month {{ $report->game_month }} Supporter Prayer Letter Dispatched
                                    </div>
                                    <div class="text-[11px] text-emerald-400/90 mt-0.5">
                                        Overseas Prayer Shield Active: 
                                        <strong>{{ match($report->selected_prayer_focus) {
                                            'pioneering' => '🧭 Pioneer Sowing (+15% Field 1/2 Receptivity)',
                                            'family_shield' => '🛡️ Family Altar Shield (+15% Baptism)',
                                            'timothy_multiplier' => '👑 Timothy Multiplication (+15% Leadership)',
                                            default => 'General Prayer Backing'
                                        } }}</strong>
                                    </div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-900/80 text-emerald-200 border border-emerald-700/80 shrink-0">
                                ✓ Sent to Sending Base
                            </span>
                        </div>
                    @else
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-950/60 via-slate-900 to-slate-950 border border-indigo-800/60 space-y-3 shadow-lg">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">✉️</span>
                                    <div>
                                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">
                                            Dispatch Supporter Letter &amp; Choose Next Month's Prayer Focus
                                        </h4>
                                        <p class="text-[11px] text-slate-300 mt-0.5">
                                            Send this month's updates to your overseas partner churches and select your intercessory focus to activate your Overseas Prayer Shield:
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                                <button 
                                    type="button" 
                                    wire:click="dispatchPrayerLetter('pioneering')"
                                    class="p-3 rounded-xl bg-slate-900/90 hover:bg-indigo-950/80 border border-slate-700/80 hover:border-indigo-400 text-left transition group cursor-pointer shadow-sm"
                                >
                                    <div class="text-lg mb-1">🧭</div>
                                    <div class="text-xs font-bold text-white group-hover:text-indigo-300">Pioneer Sowing</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                                        +15% discovery &amp; receptivity on Field 1 &amp; 2 actions
                                    </div>
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="dispatchPrayerLetter('family_shield')"
                                    class="p-3 rounded-xl bg-slate-900/90 hover:bg-emerald-950/80 border border-slate-700/80 hover:border-emerald-400 text-left transition group cursor-pointer shadow-sm"
                                >
                                    <div class="text-lg mb-1">🛡️</div>
                                    <div class="text-xs font-bold text-white group-hover:text-emerald-300">Family Altar Shield</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                                        +15% baptism commitment success &amp; family peace
                                    </div>
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="dispatchPrayerLetter('timothy_multiplier')"
                                    class="p-3 rounded-xl bg-slate-900/90 hover:bg-purple-950/80 border border-slate-700/80 hover:border-purple-400 text-left transition group cursor-pointer shadow-sm"
                                >
                                    <div class="text-lg mb-1">👑</div>
                                    <div class="text-xs font-bold text-white group-hover:text-purple-300">Timothy Multiplier</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                                        +15% leadership growth in Field 4/5 discipleship
                                    </div>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer Action -->
                <div class="p-4 bg-slate-950 border-t border-slate-800 flex items-center justify-between gap-3">
                    <span class="text-[11px] text-slate-400">
                        Total Generational Score: <strong class="text-white">{{ $report->total_generational_score }} pts</strong>
                    </span>

                    <button 
                        type="button" 
                        wire:click="closeModal" 
                        class="px-5 py-2.5 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>Got It, Plan Next Month!</span>
                        <span>➔</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
