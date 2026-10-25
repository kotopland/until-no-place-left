<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans selection:bg-indigo-500 selection:text-white">
    <!-- Ambient Glow Backdrop -->
    <div class="fixed top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-indigo-900/30 via-emerald-900/20 to-purple-900/20 blur-3xl rounded-full pointer-events-none -z-10"></div>

    <!-- Top Floating Header Bar -->
    <header class="absolute top-0 left-0 right-0 p-6 flex items-center justify-between max-w-7xl mx-auto w-full">
        <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
            <img 
                src="{{ asset('images/until-no-place-left-logo-small.png') }}" 
                alt="Nagoya Oikos Logo" 
                class="w-10 h-10 object-contain rounded-2xl shadow-lg shadow-indigo-500/20 border border-indigo-500/30 group-hover:scale-105 transition"
            >
            <div>
                <span class="text-xs font-black tracking-wide uppercase text-white block">Nagoya Oikos</span>
                <span class="text-[10px] text-slate-400 font-medium">Until No Place Left</span>
            </div>
        </a>

        <div class="flex items-center gap-2">
            <!-- Theme Mode Toggle Button (Light/Dark) -->
            <button 
                wire:ignore
                type="button" 
                onclick="window.__toggleTheme ? window.__toggleTheme() : null"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer select-none bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-800 text-amber-600 dark:text-amber-300 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-amber-700 dark:hover:text-white"
                title="Switch Light/Dark Mode"
            >
                <span data-theme-icon>☀️</span>
                <span data-theme-label class="hidden sm:inline text-[11px]">Light</span>
            </button>

            <a 
                href="{{ route('welcome') }}" 
                class="text-xs font-bold text-slate-400 hover:text-white px-3.5 py-1.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 border border-slate-800 transition flex items-center gap-1.5"
            >
                <span>📖</span>
                <span>Story & Rules</span>
            </a>
        </div>
    </header>

    <!-- Main Auth Card -->
    <div class="w-full max-w-md my-12 z-10">
        <!-- Status Messages -->
        @if (session('error'))
            <div class="mb-4 p-4 rounded-2xl bg-rose-950/80 border border-rose-800/80 text-rose-200 text-xs flex items-center gap-2.5 shadow-lg shadow-rose-950/40">
                <span class="text-base">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('info'))
            <div class="mb-4 p-4 rounded-2xl bg-indigo-950/80 border border-indigo-800/80 text-indigo-200 text-xs flex items-center gap-2.5 shadow-lg shadow-indigo-950/40">
                <span class="text-base">ℹ️</span>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/80 relative">
            @if ($sent)
                <!-- Magic Link Sent State -->
                <div class="text-center space-y-5 animate-in fade-in duration-300">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-950/80 border border-emerald-500/40 flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/20">
                        ✉️
                    </div>

                    <div>
                        <h2 class="text-xl font-black text-white tracking-tight">Magic Link Dispatched!</h2>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            We've sent a cryptographic sign-in link to:
                        </p>
                        <p class="text-sm font-bold text-indigo-300 mt-1 font-mono break-all px-3 py-1 bg-slate-950/80 rounded-xl border border-slate-800 inline-block">
                            {{ $sentEmail }}
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-left text-xs text-slate-300 space-y-2">
                        <div class="flex items-start gap-2">
                            <span class="text-emerald-400 font-bold">1.</span>
                            <span>Open your email inbox and look for <strong>Nagoya Oikos</strong>.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-emerald-400 font-bold">2.</span>
                            <span>Click the <strong>⚡ Enter Nagoya Mission</strong> button in the email.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-emerald-400 font-bold">3.</span>
                            <span>Your session will instantly activate with no passwords required!</span>
                        </div>
                    </div>

                    @if ($localMagicLink)
                        <!-- Local Dev / Testing Helper -->
                        <div class="p-4 rounded-2xl bg-amber-950/40 border border-amber-500/40 text-left space-y-2.5">
                            <div class="flex items-center justify-between text-amber-300 text-[11px] font-bold">
                                <span>🛠️ Local Testing / Herd Mode</span>
                                <span class="text-[9px] uppercase tracking-wider bg-amber-900/60 text-amber-200 px-1.5 py-0.5 rounded border border-amber-700/50">Preview Active</span>
                            </div>
                            <p class="text-[11px] text-amber-200/90 leading-tight">
                                Mail preview captured the email. Click to inspect the rendered email or log in instantly:
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <a 
                                    href="/spatie-mail-preview" 
                                    target="_blank"
                                    class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 border border-indigo-500/40 font-bold text-xs flex items-center justify-center gap-1.5 transition"
                                >
                                    <span>📬</span>
                                    <span>View Sent Email</span>
                                </a>
                                <a 
                                    href="{{ $localMagicLink }}" 
                                    class="py-2 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/20 transition cursor-pointer"
                                >
                                    <span>⚡</span>
                                    <span>Instant Login</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="pt-2">
                        <button 
                            type="button" 
                            wire:click="resetForm" 
                            class="text-xs text-slate-400 hover:text-white underline transition cursor-pointer"
                        >
                            ← Use a different email or name
                        </button>
                    </div>
                </div>
            @else
                <!-- Form State -->
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="text-center space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-950/80 border border-indigo-800 text-[11px] font-bold text-indigo-400 mb-1">
                            <span>🔑</span>
                            <span>Passwordless Authentication</span>
                        </div>
                        <h2 class="text-2xl font-black text-white tracking-tight">
                            {{ $isExistingUser ? 'Welcome Back, Disciple' : 'Create Your Player Name' }}
                        </h2>
                        <p class="text-xs text-slate-400 leading-relaxed max-w-sm mx-auto">
                            {{ $isExistingUser 
                                ? 'Enter your email to receive an instant magic sign-in link.' 
                                : 'Choose your missionary handle and email. We will send you a secure activation link.' }}
                        </p>
                    </div>

                    <!-- Livewire Form -->
                    <form wire:submit="sendMagicLink" class="space-y-4">
                        <!-- Player / User Name Input -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                <span>Missionary / Player Name</span>
                                @if ($isExistingUser)
                                    <span class="text-[10px] text-indigo-400 font-medium lowercase">known disciple</span>
                                @else
                                    <span class="text-[10px] text-emerald-400 font-medium">required for new players</span>
                                @endif
                            </label>
                            <div class="relative">
                                <input 
                                    id="name"
                                    type="text" 
                                    wire:model="name"
                                    placeholder="e.g. Kenji Tanaka, Sarah T., Coach Mark" 
                                    class="w-full bg-slate-950/80 border {{ $errors->has('name') ? 'border-rose-500 focus:border-rose-400' : 'border-slate-800 focus:border-indigo-500' }} rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition"
                                    autofocus
                                >
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">👤</span>
                            </div>
                            @error('name')
                                <p class="text-[11px] text-rose-400 font-medium mt-1.5">{{ $message }}</p>
                            @enderror
                            <p class="text-[10px] text-slate-500 mt-1">
                                This name appears on the city leaderboard, church trees, and co-op notifications.
                            </p>
                        </div>

                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                <span>Email Address</span>
                                <span class="text-[10px] text-slate-500">passwordless delivery</span>
                            </label>
                            <div class="relative">
                                <input 
                                    id="email"
                                    type="email" 
                                    wire:model.live.debounce.400ms="email"
                                    placeholder="your-email@domain.com" 
                                    class="w-full bg-slate-950/80 border {{ $errors->has('email') ? 'border-rose-500 focus:border-rose-400' : 'border-slate-800 focus:border-indigo-500' }} rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition"
                                    required
                                >
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">✉️</span>
                            </div>
                            @error('email')
                                <p class="text-[11px] text-rose-400 font-medium mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Feature Badges -->
                        <div class="grid grid-cols-2 gap-2 pt-1 text-[11px] text-slate-400">
                            <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-950/40 border border-slate-800/60">
                                <span class="text-emerald-400">✓</span>
                                <span>No password to leak</span>
                            </div>
                            <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-950/40 border border-slate-800/60">
                                <span class="text-indigo-400">✓</span>
                                <span>Instant activation link</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button 
                                type="submit" 
                                wire:loading.attr="disabled"
                                class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 hover:shadow-indigo-600/50 hover:scale-[1.01] active:scale-[0.99] transition duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <span wire:loading.remove>⚡ Send Magic Sign-In Link</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Creating Signed Link...</span>
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Bottom Nav -->
                    <div class="text-center pt-2 border-t border-slate-800/80">
                        <a 
                            href="{{ route('welcome') }}" 
                            class="text-xs text-slate-400 hover:text-indigo-300 transition"
                        >
                            Read the Nagoya Story, 16 Wards & 4 Fields Framework →
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
