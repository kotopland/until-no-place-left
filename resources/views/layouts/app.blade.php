<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Nagoya Oikos: The Four Fields' }}</title>

    <script>
        (function() {
            var lastToggleTime = 0;
            window.__getTheme = function() {
                var stored = localStorage.getItem('theme');
                if (stored) return stored;
                return 'dark';
            };
            window.__applyTheme = function(mode) {
                var isDark = mode === 'dark';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                document.querySelectorAll('[data-theme-icon]').forEach(function(el) {
                    el.textContent = isDark ? '☀️' : '🌙';
                });
                document.querySelectorAll('[data-theme-label]').forEach(function(el) {
                    el.textContent = isDark ? 'Light' : 'Dark';
                });
            };
            window.__toggleTheme = function() {
                var now = Date.now();
                if (now - lastToggleTime < 150) {
                    return;
                }
                lastToggleTime = now;

                var current = window.__getTheme();
                var next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', next);
                window.__applyTheme(next);
                if (window.Alpine && window.Alpine.store && window.Alpine.store('theme')) {
                    window.Alpine.store('theme').mode = next;
                }
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { mode: next } }));
            };
            window.__applyTheme(window.__getTheme());
            document.addEventListener('DOMContentLoaded', function() {
                window.__applyTheme(window.__getTheme());
            });
            document.addEventListener('livewire:navigated', function() {
                window.__applyTheme(window.__getTheme());
            });
            document.addEventListener('livewire:init', function() {
                window.__applyTheme(window.__getTheme());
            });
            document.addEventListener('livewire:morph', function() {
                window.__applyTheme(window.__getTheme());
            });
        })();
    </script>

    <link rel="icon" href="{{ asset('images/until-no-place-left-logo-small.jpeg') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('images/until-no-place-left-logo-small.jpeg') }}">
    @fonts

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased selection:bg-indigo-500 selection:text-white min-h-screen transition-colors duration-200">
    {{ $slot }}

    @livewireScripts
    <script>
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('request', function ({ fail }) {
                    fail(function ({ status, preventDefault }) {
                        if (status === 419) {
                            preventDefault();
                            window.location.reload();
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
