<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences • Nagoya Oikos</title>
    
    <link rel="icon" href="{{ asset('images/until-no-place-left-logo-small.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 font-sans selection:bg-indigo-500 selection:text-white min-h-screen flex flex-col justify-center items-center px-4 relative overflow-hidden">
    <!-- Ambient Glow Backdrop -->
    <div class="fixed top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-gradient-to-tr from-indigo-900/30 via-slate-900 to-purple-900/20 blur-3xl rounded-full pointer-events-none -z-10"></div>

    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/80 text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-slate-800/80 border border-slate-700/80 flex items-center justify-center text-3xl mx-auto shadow-inner">
            🔕
        </div>

        <div class="space-y-2">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800 text-[11px] font-bold text-slate-300">
                <span>Preferences Updated</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">You're Opted Out</h1>
            <p class="text-xs text-slate-400 leading-relaxed max-w-sm mx-auto">
                {{ $user->name }}, we have successfully turned off email notifications for <strong class="text-indigo-300 font-mono">{{ $user->email }}</strong>. You will no longer receive inactivity reminders or automated movement reports.
            </p>
        </div>

        @if(session('success'))
            <div class="p-3.5 rounded-2xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center justify-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-left text-xs text-slate-400 space-y-2">
            <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">•</span>
                <span>Your player account, churches, and scores remain completely safe and active.</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-amber-400 font-bold">•</span>
                <span>You can still sign in anytime via magic links or change this setting in your profile.</span>
            </div>
        </div>

        <div class="space-y-3 pt-2">
            @if(! $user->notify_email)
                <form action="{{ URL::signedRoute('notifications.opt-in', ['user' => $user->id]) }}" method="POST">
                    @csrf
                    <button 
                        type="submit" 
                        class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition cursor-pointer"
                    >
                        <span>🔔 Changed your mind? Re-enable email notifications</span>
                    </button>
                </form>
            @endif

            <a 
                href="{{ url('/') }}" 
                class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-black text-xs flex items-center justify-center gap-2 shadow-xl shadow-indigo-600/30 transition cursor-pointer"
            >
                <span>⚡</span>
                <span>Return to Nagoya Movement</span>
            </a>
        </div>
    </div>
</body>
</html>

