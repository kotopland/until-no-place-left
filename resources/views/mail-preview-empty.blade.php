<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mail Preview • No Emails Yet</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-6 font-sans">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center shadow-2xl space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-indigo-950/80 border border-indigo-700/60 flex items-center justify-center text-3xl mx-auto shadow-lg shadow-indigo-500/20">
            📬
        </div>
        <h1 class="text-xl font-bold text-white">Local Mail Preview</h1>
        <p class="text-xs text-slate-400 leading-relaxed">
            No preview emails have been captured yet in <code class="text-indigo-300 font-mono">storage/email-previews</code>. When an email or passwordless magic link is sent, it will automatically appear here.
        </p>
        <div class="pt-2">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition">
                <span>⚡</span>
                <span>Go to Mission Login</span>
            </a>
        </div>
    </div>
</body>
</html>
