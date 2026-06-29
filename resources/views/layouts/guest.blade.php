<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><path d='M20 70 Q50-10 80 70' fill='none' stroke='%23cbd5e1' stroke-width='4' stroke-linecap='round'/><circle cx='20' cy='70' r='15' fill='%23f97316'/><circle cx='50' cy='18' r='15' fill='%236366f1'/><circle cx='80' cy='70' r='15' fill='%2310b981'/></svg>">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Theme: seed before paint, then re-assert if the class is stripped
             (wire:navigate morphs <html> to server markup with no dark class). --}}
        <script>
            (function () {
                const root = document.documentElement;
                const wantDark = function () {
                    const t = localStorage.getItem('theme');
                    return t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                };
                const apply = function () { root.classList.toggle('dark', wantDark()); };
                apply();
                new MutationObserver(function () {
                    if (root.classList.contains('dark') !== wantDark()) apply();
                }).observe(root, { attributes: true, attributeFilter: ['class'] });
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-cream-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
