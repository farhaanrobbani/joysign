<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Mobile: horizontal admin menu -->
            <x-admin-menu layout="tabs" />

            <div class="flex min-h-[calc(100vh-4rem)]">
                <x-admin-menu layout="aside" />

                <div class="flex-1 flex flex-col min-w-0">
                    <!-- Page Heading -->
                    @isset($header)
                        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm dark:shadow-none">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <!-- Page Content -->
                    <main class="flex-1">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        @livewireScriptConfig
    </body>
</html>
