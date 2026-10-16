<div wire:poll.5s.visible>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Daftar Dokumen') }}</h3>

            @if ($documents->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-400">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('Judul') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3">{{ __('Diunggah') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($documents as $document)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span>{{ $document->title }}</span>
                                            @if ($document->assignments->contains(fn ($assignment) => $assignment->isPending()))
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-300">
                                                    {{ __('Perlu ditandatangani') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            'bg-yellow-100 text-yellow-800 dark:text-yellow-300' => $document->status->value === 'draft',
                                            'bg-green-100 text-green-800 dark:text-green-300' => $document->status->value === 'signed',
                                            'bg-red-100 text-red-800 dark:text-red-300' => $document->status->value === 'revoked',
                                        ])>
                                            {{ $document->status->value }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $document->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('documents.show', $document) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ __('Detail') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
