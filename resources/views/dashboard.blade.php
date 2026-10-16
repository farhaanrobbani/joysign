<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold">{{ __('Selamat datang, :name', ['name' => $user->name]) }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('NIK') }}: <span class="font-mono">{{ $user->nik ?? '—' }}</span>
                        &middot; {{ $user->email }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('documents.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            {{ __('Unggah Dokumen') }}
                        </a>
                        <a href="{{ route('certificates.show') }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            {{ __('Kelola Sertifikat') }}
                        </a>
                    </div>
                </div>
            </div>

            @if (! $stats['certificate_active'])
                <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-300 px-4 py-4 rounded-lg sm:px-6">
                    <p class="font-semibold">{{ __('Lengkapi identitas tanda tangan Anda') }}</p>
                    <p class="mt-1 text-sm">
                        {{ __('Anda memerlukan sertifikat digital aktif sebelum dapat menandatangani dokumen.') }}
                    </p>
                    <a href="{{ route('certificates.show') }}" class="mt-2 inline-block text-sm font-medium underline hover:text-amber-900">
                        {{ __('Buat / perbarui sekarang') }} →
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach ([
                    ['label' => __('Total Dokumen'), 'value' => $stats['documents_total'], 'color' => 'bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100'],
                    ['label' => __('Draft'), 'value' => $stats['documents_draft'], 'color' => 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-300'],
                    ['label' => __('Ditandatangani'), 'value' => $stats['documents_signed'], 'color' => 'bg-green-50 dark:bg-green-500/10 text-green-800 dark:text-green-300'],
                    ['label' => __('Dicabut'), 'value' => $stats['documents_revoked'], 'color' => 'bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-300'],
                    ['label' => __('TTD Diberikan'), 'value' => $stats['signatures_given'], 'color' => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-300'],
                    ['label' => __('Sertifikat'), 'value' => $stats['certificate_active'] ? __('Aktif') : __('Belum'), 'color' => $stats['certificate_active'] ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400'],
                ] as $stat)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg {{ $stat['color'] }}">
                        <div class="p-4">
                            <p class="text-xs font-medium opacity-75">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold mt-1">{{ $stat['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Dokumen Terbaru') }}</h3>
                        <a href="{{ route('documents.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ __('Lihat Semua') }} →
                        </a>
                    </div>

                    @if ($recentDocuments->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen. Unggah PDF pertama Anda untuk mulai menandatangani.') }}</p>
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
                                    @foreach ($recentDocuments as $document)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $document->title }}</td>
                                            <td class="px-4 py-3">
                                                <span @class([
                                                    'px-2 py-1 rounded-full text-xs font-medium',
                                                    'bg-yellow-100 text-yellow-800 dark:text-yellow-300' => $document->status->value === 'draft',
                                                    'bg-green-100 text-green-800 dark:text-green-300' => $document->status->value === 'signed',
                                                    'bg-red-100 text-red-800 dark:text-red-300' => $document->status->value === 'revoked',
                                                ])>
                                                    {{ match ($document->status->value) {
                                                        'signed' => __('Ditandatangani'),
                                                        'revoked' => __('Dicabut'),
                                                        default => __('Draft'),
                                                    } }}
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
