<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
                        <select wire:model.live="event" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('Semua Event') }}</option>
                            @foreach ($events as $ev)
                                <option value="{{ $ev }}">{{ $ev }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="userId" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('Semua User') }}</option>
                            @foreach ($logUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model.live.debounce.400ms="documentId"
                            placeholder="{{ __('ID Dokumen…') }}"
                            class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <div class="flex gap-2">
                            <button type="button" wire:click="resetFilters"
                                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 w-full">
                                {{ __('Reset') }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-400">
                            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Waktu') }}</th>
                                    <th class="px-4 py-3">{{ __('Event') }}</th>
                                    <th class="px-4 py-3">{{ __('User') }}</th>
                                    <th class="px-4 py-3">{{ __('Dokumen') }}</th>
                                    <th class="px-4 py-3">{{ __('IP') }}</th>
                                    <th class="px-4 py-3">{{ __('Detail') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-900 whitespace-nowrap">{{ $log->event }}</span></td>
                                        <td class="px-4 py-3">{{ $log->user?->name ?? __('Publik/Sistem') }}</td>
                                        <td class="px-4 py-3">{{ $log->document?->title ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $log->ip_address }}</td>
                                        <td class="px-4 py-3 text-xs">
                                            @if ($log->payload)
                                                <details>
                                                    <summary class="cursor-pointer text-indigo-600 dark:text-indigo-400">{{ __('Lihat') }}</summary>
                                                    <pre class="mt-1 bg-gray-50 dark:bg-gray-800 rounded p-2 text-xs overflow-x-auto max-w-md">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</pre>
                                                </details>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('Tidak ada log ditemukan.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
