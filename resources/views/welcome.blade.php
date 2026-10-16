<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ setting('seo.title', config('app.name')) }}</title>
        <meta name="description" content="{{ setting('seo.description', setting('site.tagline')) }}">

        <link rel="icon" href="{{ asset('favicon.ico') }}">

        <script>
            (function () {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            html { scroll-behavior: smooth; }
            .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
            .reveal.is-visible { opacity: 1; transform: none; }
            @media (prefers-reduced-motion: reduce) {
                html { scroll-behavior: auto; }
                .reveal { opacity: 1; transform: none; transition: none; }
            }
            .mesh {
                background:
                    radial-gradient(60% 60% at 15% 20%, rgba(99,102,241,.35) 0%, transparent 60%),
                    radial-gradient(50% 50% at 85% 15%, rgba(139,92,246,.32) 0%, transparent 55%),
                    radial-gradient(55% 55% at 70% 90%, rgba(34,211,238,.28) 0%, transparent 55%),
                    radial-gradient(45% 45% at 20% 85%, rgba(79,70,229,.28) 0%, transparent 55%);
            }
        </style>
    </head>

    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased bg-gray-50 dark:bg-gray-950">
        {{-- ===== Navbar ===== --}}
        <header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-gray-200/70 dark:border-gray-800/70 bg-white/80 dark:bg-gray-950/80 backdrop-blur-md">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <x-application-logo class="h-8 w-8 fill-current text-indigo-600 dark:text-indigo-400" />
                    <span class="text-lg font-extrabold tracking-tight text-gray-900 dark:text-white">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="#fitur" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">Fitur</a>
                    <a href="#cara-kerja" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">Cara Kerja</a>
                    <a href="#keamanan" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">Keamanan</a>
                    <a href="#faq" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">FAQ</a>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" data-theme-toggle aria-label="Ganti tema gelap/terang"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <svg data-theme-icon-moon class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                        <svg data-theme-icon-sun class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                    </button>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="hidden rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition sm:inline-flex">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800 transition sm:inline-flex">Masuk</a>
                    @endauth

                    <button type="button" @click="open = !open" aria-label="Buka menu"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 md:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                </div>
            </nav>

            <div x-show="open" x-cloak class="border-t border-gray-200 dark:border-gray-800 md:hidden">
                <div class="space-y-1 px-4 py-3">
                    <a href="#fitur" @click="open = false" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Fitur</a>
                    <a href="#cara-kerja" @click="open = false" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Cara Kerja</a>
                    <a href="#keamanan" @click="open = false" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Keamanan</a>
                    <a href="#faq" @click="open = false" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">FAQ</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="block rounded-lg bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Masuk</a>
                    @endauth
                </div>
            </div>
        </header>

        {{-- ===== Hero ===== --}}
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 mesh opacity-90 dark:opacity-70"></div>
            <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div class="reveal">
                        <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 dark:border-indigo-500/40 bg-white/70 dark:bg-gray-900/70 px-3 py-1 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                            {{ setting('landing.hero.badge') }}
                        </span>
                        <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl lg:text-6xl">
                            {{ setting('landing.hero.title') }} <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500 bg-clip-text text-transparent">{{ setting('landing.hero.title_accent') }}</span>
                        </h1>
                        <p class="mt-5 max-w-xl text-lg text-gray-600 dark:text-gray-300">
                            {{ setting('landing.hero.subtitle') }}
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 hover:bg-indigo-500 transition">
                                Masuk Sekarang
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                            </a>
                            <a href="{{ route('verify.index') }}" class="inline-flex items-center rounded-xl border border-gray-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/80 px-6 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-900 transition">
                                Verifikasi Dokumen
                            </a>
                        </div>
                        <dl class="mt-10 grid max-w-md grid-cols-3 gap-6">
                            <div>
                                <dt class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ setting('landing.hero.stat1_value') }}</dt>
                                <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ setting('landing.hero.stat1_label') }}</dd>
                            </div>
                            <div>
                                <dt class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ setting('landing.hero.stat2_value') }}</dt>
                                <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ setting('landing.hero.stat2_label') }}</dd>
                            </div>
                            <div>
                                <dt class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ setting('landing.hero.stat3_value') }}</dt>
                                <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ setting('landing.hero.stat3_label') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="reveal">
                        <div class="relative mx-auto w-full max-w-md">
                            <div class="absolute -inset-4 rounded-3xl bg-gradient-to-tr from-indigo-500/20 via-violet-500/20 to-cyan-400/20 blur-2xl"></div>
                            <div class="relative rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
                                <div class="flex items-center gap-1.5 border-b border-gray-100 dark:border-gray-800 px-4 py-3">
                                    <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                                    <span class="ml-2 text-xs text-gray-400">dokumen-kerja.pdf</span>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-2">
                                        <div class="h-3 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                                        <div class="h-3 w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                                        <div class="h-3 w-5/6 rounded bg-gray-200 dark:bg-gray-700"></div>
                                        <div class="h-3 w-2/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                                    </div>
                                    <div class="mt-6 flex items-end justify-between">
                                        <div>
                                            <p class="text-[11px] uppercase tracking-wider text-gray-400">Ditandatangani oleh</p>
                                            <p class="mt-1 font-semibold text-gray-900 dark:text-white">Budi Santoso</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Sertifikat digital terbit</p>
                                        </div>
                                        <div class="flex h-16 w-16 items-center justify-center rounded-lg border border-dashed border-indigo-300 dark:border-indigo-500/50 bg-indigo-50 dark:bg-indigo-500/10">
                                            <svg class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/></svg>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2 rounded-lg bg-green-50 dark:bg-green-500/10 px-3 py-2">
                                        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        <span class="text-xs font-semibold text-green-700 dark:text-green-400">Terverifikasi — VALID</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Fitur ===== --}}
        <section id="fitur" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="reveal mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ setting('landing.fitur.badge') }}</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ setting('landing.fitur.title') }}</h2>
                <p class="mt-3 text-gray-600 dark:text-gray-300">{{ setting('landing.fitur.subtitle') }}</p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $featureIcons = [
                        'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z',
                        'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
                        'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75',
                        'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                        'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                        'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                    ];
                    $features = [];
                    foreach ($featureIcons as $i => $icon) {
                        $n = $i + 1;
                        $features[] = [setting("landing.fitur.$n.title"), setting("landing.fitur.$n.desc"), $icon];
                    }
                @endphp

                @foreach ($features as [$title, $desc, $icon])
                    <div class="reveal group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ===== Cara Kerja ===== --}}
        <section id="cara-kerja" class="border-y border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/50">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ setting('landing.steps.badge') }}</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ setting('landing.steps.title') }}</h2>
                </div>

                <ol class="mt-14 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    @php
                        $steps = [];
                        for ($i = 1; $i <= 4; $i++) {
                            $steps[] = [setting("landing.steps.$i.title"), setting("landing.steps.$i.desc")];
                        }
                    @endphp

                    @foreach ($steps as $i => [$title, $desc])
                        <li class="reveal relative rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 p-6">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-bold">{{ $i + 1 }}</span>
                            <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $desc }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ===== Portal Verifikasi ===== --}}
        <section id="verifikasi" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="reveal overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-cyan-500 p-1">
                <div class="rounded-[1.375rem] bg-white dark:bg-gray-950 p-8 sm:p-12">
                    <div class="grid items-center gap-10 lg:grid-cols-2">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ setting('landing.verify.badge') }}</p>
                            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ setting('landing.verify.title') }}</h2>
                            <p class="mt-3 text-gray-600 dark:text-gray-300">{{ setting('landing.verify.subtitle') }}</p>
                            <a href="{{ route('verify.index') }}" class="mt-5 inline-flex items-center text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Buka portal verifikasi lengkap
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>

                        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 p-6">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Verifikasi via Token</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masukkan token dari QR Code pada dokumen.</p>
                            <form method="GET" action="{{ route('verify.show', ['code' => '__CODE__']) }}" class="mt-4 flex flex-col gap-2 sm:flex-row"
                                onsubmit="this.action = this.action.replace('__CODE__', encodeURIComponent(document.getElementById('landing-verify-code').value.trim())); return document.getElementById('landing-verify-code').value.trim() !== '';">
                                <input type="text" id="landing-verify-code" name="code" required
                                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 whitespace-nowrap transition">
                                    Verifikasi
                                </button>
                            </form>
                            <div class="mt-4 flex items-center gap-3">
                                <span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>
                                <span class="text-xs text-gray-400">atau</span>
                                <span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>
                            </div>
                            <a href="{{ route('verify.index') }}" class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                Buka Portal Verifikasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Keamanan ===== --}}
        <section id="keamanan" class="border-y border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/50">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ setting('landing.security.badge') }}</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ setting('landing.security.title') }}</h2>
                    <p class="mt-3 text-gray-600 dark:text-gray-300">{{ setting('landing.security.subtitle') }}</p>
                </div>

                <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $security = [];
                        for ($i = 1; $i <= 6; $i++) {
                            $security[] = [setting("landing.security.$i.title"), setting("landing.security.$i.desc")];
                        }
                    @endphp

                    @foreach ($security as [$title, $desc])
                        <div class="reveal flex items-start gap-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 p-5">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-500/10 text-green-600 dark:text-green-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $desc }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===== FAQ ===== --}}
        <section id="faq" class="mx-auto max-w-3xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="reveal text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ setting('landing.faq.badge') }}</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ setting('landing.faq.title') }}</h2>
            </div>

            <div class="reveal mt-10 space-y-3">
                @php
                    $faqs = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $faqs[] = [setting("landing.faq.$i.q"), setting("landing.faq.$i.a")];
                    }
                @endphp

                @foreach ($faqs as [$q, $a])
                    <details class="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 open:shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 font-semibold text-gray-900 dark:text-white">
                            {{ $q }}
                            <svg class="h-5 w-5 shrink-0 text-gray-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <p class="px-6 pb-5 text-sm text-gray-600 dark:text-gray-300">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- ===== CTA penutup ===== --}}
        <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="reveal relative overflow-hidden rounded-3xl bg-gray-900 dark:bg-gray-900 px-8 py-14 text-center sm:px-12">
                <div class="absolute inset-0 mesh opacity-40"></div>
                <div class="relative">
                    <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ setting('landing.cta.title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-gray-300">{{ setting('landing.cta.subtitle') }}</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-100 transition">Masuk Sekarang</a>
                        <a href="{{ route('verify.index') }}" class="inline-flex items-center rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">Verifikasi Dokumen</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== Footer ===== --}}
        <footer class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <div>
                        <div class="flex items-center gap-2">
                            <x-application-logo class="h-7 w-7 fill-current text-indigo-600 dark:text-indigo-400" />
                            <span class="font-extrabold text-gray-900 dark:text-white">{{ config('app.name') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ setting('site.tagline') }}</p>
                    </div>
                    <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <a href="#fitur" class="hover:text-indigo-600 dark:hover:text-indigo-400">Fitur</a>
                        <a href="#cara-kerja" class="hover:text-indigo-600 dark:hover:text-indigo-400">Cara Kerja</a>
                        <a href="#keamanan" class="hover:text-indigo-600 dark:hover:text-indigo-400">Keamanan</a>
                        <a href="#faq" class="hover:text-indigo-600 dark:hover:text-indigo-400">FAQ</a>
                        <a href="{{ route('verify.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Verifikasi</a>
                        <a href="{{ route('login') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Masuk</a>
                    </nav>
                </div>
                <div class="mt-8 border-t border-gray-200 dark:border-gray-800 pt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }} — Tanda Tangan Elektronik Kriptografis. Hak cipta dilindungi.
                </div>
            </div>
        </footer>

        <script>
            (function () {
                var root = document.documentElement;
                var moon = document.querySelectorAll('[data-theme-icon-moon]');
                var sun = document.querySelectorAll('[data-theme-icon-sun]');

                function syncIcons() {
                    var dark = root.classList.contains('dark');
                    moon.forEach(function (el) { el.classList.toggle('hidden', dark); });
                    sun.forEach(function (el) { el.classList.toggle('hidden', !dark); });
                }

                document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        root.classList.toggle('dark');
                        localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
                        syncIcons();
                    });
                });

                syncIcons();

                var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var items = document.querySelectorAll('.reveal');
                if (reduce || !('IntersectionObserver' in window)) {
                    items.forEach(function (el) { el.classList.add('is-visible'); });
                } else {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                io.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                    items.forEach(function (el) { io.observe(el); });
                }
            })();
        </script>
    </body>
</html>
