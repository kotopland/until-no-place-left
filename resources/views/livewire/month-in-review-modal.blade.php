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
