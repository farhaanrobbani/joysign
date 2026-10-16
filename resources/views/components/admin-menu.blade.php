@props(['layout' => 'aside'])

@php
    $items = [
        ['label' => __('Dashboard'), 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => __('User Management'), 'href' => route('admin.users'), 'active' => request()->routeIs('admin.users*')],
        ['label' => __('Log Audit'), 'href' => route('admin.audit-logs'), 'active' => request()->routeIs('admin.audit-logs')],
        ['label' => __('Pengaturan'), 'href' => route('admin.settings'), 'active' => request()->routeIs('admin.settings*')],
    ];
@endphp

@if ($layout === 'tabs')
    <div class="lg:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex overflow-x-auto px-2" aria-label="{{ __('Admin Menu') }}">
            @foreach ($items as $item)
                <a href="{{ $item['href'] }}"
                    @class([
                        'shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition duration-150 ease-in-out',
                        'border-indigo-500 text-indigo-600 dark:text-indigo-300' => $item['active'],
                        'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' => ! $item['active'],
                    ])>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
@else
    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-white dark:bg-gray-800 border-e border-gray-200 dark:border-gray-700">
        <div class="py-6">
            <div class="px-4 mb-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Admin Panel') }}</p>
            </div>
            <nav class="space-y-1">
                @foreach ($items as $item)
                    <a href="{{ $item['href'] }}"
                        @class([
                            'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-sm font-medium transition duration-150 ease-in-out focus:outline-none',
                            'border-indigo-400 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/20' => $item['active'],
                            'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-gray-300 dark:hover:border-gray-600' => ! $item['active'],
                        ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </aside>
@endif
