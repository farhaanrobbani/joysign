<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Portal Verifikasi') }} — {{ config('app.name', 'Laravel') }}</title>

        <script>
            (function () {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gradient-to-br from-[#1E3A8A] via-[#16265C] to-[#0F1B3D] relative overflow-x-hidden">
            <div class="pointer-events-none absolute -top-32 -left-32 w-96 h-96 rounded-full bg-teal-400/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-24 w-[28rem] h-[28rem] rounded-full bg-blue-500/10 blur-3xl" aria-hidden="true"></div>

            <header class="relative max-w-3xl mx-auto px-6 pt-6 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ route('site.logo') }}" alt="{{ config('app.name') }}" class="h-9 w-auto rounded-lg shadow-lg shadow-black/20" onerror="this.style.display='none'">
                    <span class="text-white/90 font-semibold tracking-wide">{{ config('app.name') }}</span>
                </a>
                <a href="{{ route('home') }}" class="text-sm text-blue-100/70 hover:text-white transition-colors">{{ __('Kembali ke Beranda') }}</a>
            </header>

            <main class="relative max-w-3xl mx-auto px-6 py-10 sm:py-14">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
