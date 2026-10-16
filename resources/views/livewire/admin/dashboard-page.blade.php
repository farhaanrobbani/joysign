<div wire:poll.5s>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ([
                    ['label' => __('Total Pengguna'), 'value' => $stats['users'], 'color' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-800 dark:text-blue-300'],
                    ['label' => __('Total Dokumen'), 'value' => $stats['documents_total'], 'color' => 'bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100'],
                    ['label' => __('Dokumen Signed'), 'value' => $stats['documents_signed'], 'color' => 'bg-green-50 dark:bg-green-500/10 text-green-800 dark:text-green-300'],
                    ['label' => __('Dokumen Draft'), 'value' => $stats['documents_draft'], 'color' => 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-300'],
                    ['label' => __('Dokumen Revoked'), 'value' => $stats['documents_revoked'], 'color' => 'bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-300'],
                    ['label' => __('Total Signature'), 'value' => $stats['signatures'], 'color' => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-300'],
                    ['label' => __('Audit Log 24 Jam'), 'value' => $stats['audit_logs_24h'], 'color' => 'bg-purple-50 dark:bg-purple-500/10 text-purple-800 dark:text-purple-300'],
                ] as $stat)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg {{ $stat['color'] }}">
                        <div class="p-4">
                            <p class="text-xs font-medium opacity-75">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold mt-1">{{ $stat['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
