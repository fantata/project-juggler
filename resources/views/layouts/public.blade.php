<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Capability URL: keep it out of search engines and referrers. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="referrer" content="no-referrer">

        <title>{{ $title ?? 'Board' }}</title>

        <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><path d='M20 70 Q50-10 80 70' fill='none' stroke='%23cbd5e1' stroke-width='4' stroke-linecap='round'/><circle cx='20' cy='70' r='15' fill='%23C2714F'/><circle cx='50' cy='18' r='15' fill='%236B8F71'/><circle cx='80' cy='70' r='15' fill='%238B6914'/></svg>">
        <meta name="theme-color" content="#C2714F">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=atkinson-hyperlegible-next:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Client board follows the visitor's OS light/dark preference. No toggle
             here — it's a lean, single-purpose page, not the full app. --}}
        <script>
            (function () {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream-100 dark:bg-gray-900 text-bark-800 dark:text-cream-200 min-h-screen">
        {{ $slot }}
        @livewireScripts
    </body>
</html>
