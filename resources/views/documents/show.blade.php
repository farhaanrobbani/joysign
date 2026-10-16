<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ $document->title }}
            </h2>
            <a href="{{ route('documents.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline">
                {{ __('Kembali ke Daftar') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</span>
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-yellow-100 text-yellow-800 dark:text-yellow-300' => $document->status->value === 'draft',
                            'bg-orange-100 text-orange-800' => $document->status->value === 'partially_signed',
                            'bg-green-100 text-green-800 dark:text-green-300' => $document->status->value === 'signed',
                            'bg-red-100 text-red-800 dark:text-red-300' => $document->status->value === 'revoked',
                        ])>
                            {{ match ($document->status->value) {
                                'partially_signed' => 'menunggu tanda tangan lain',
                                'signed' => 'signed',
                                'revoked' => 'revoked',
                                default => 'draft',
                            } }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Hash SHA-256 Asli') }}</span>
                        <span class="font-mono break-all text-right">{{ $document->original_hash }}</span>
                    </div>
                    @if ($document->signed_hash)
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Hash SHA-256 Hasil TTE') }}</span>
                            <span class="font-mono break-all text-right">{{ $document->signed_hash }}</span>
                        </div>
                    @endif
                    @if ($document->revoked_at)
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Dicabut pada') }}</span>
                            <span class="text-gray-900 dark:text-gray-100 text-right">{{ $document->revoked_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Alasan pencabutan') }}</span>
                            <span class="text-gray-900 dark:text-gray-100 text-right">{{ $document->revoke_reason }}</span>
                        </div>
                    @endif
                    <div class="flex gap-3 pt-2">
                        @if ($document->signed_path)
                            <a href="{{ route('documents.download', $document) }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                                {{ __('Unduh PDF Bertanda Tangan') }}
                            </a>
                        @endif

                        @if ($document->signed_path || $document->status->value === 'revoked')
                            <a href="{{ route('documents.audit-certificate', $document) }}"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                                {{ __('Unduh Sertifikat Pembuktian') }}
                            </a>
                        @endif

                        @if ($document->status->value === 'signed' && auth()->user()?->can('revoke', $document))
                            <button type="button" x-data=""
                                x-on:click="$dispatch('open-modal', 'confirm-revoke-document')"
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-500">
                                {{ __('Cabut Dokumen') }}
                            </button>
                        @endif

                        @if ($document->status->value === 'draft' && auth()->user()?->can('delete', $document))
                            <form method="POST" action="{{ route('documents.destroy', $document) }}"
                                onsubmit="return confirm('{{ __('Hapus dokumen draft ini?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-500">
                                    {{ __('Hapus Draft') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @if ($document->assignments->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Penandatangan Ditugaskan') }}</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-400">
                                <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Penandatangan') }}</th>
                                        <th class="px-4 py-3">{{ __('Status') }}</th>
                                        <th class="px-4 py-3">{{ __('Waktu Tanda Tangan') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($document->assignments as $assignment)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                                {{ $assignment->signer?->name }}
                                                @if ($assignment->signer_id === auth()->id())
                                                    <span class="text-xs text-gray-400">({{ __('Anda') }})</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <span @class([
                                                    'px-2 py-1 rounded-full text-xs font-medium',
                                                    'bg-green-100 text-green-800 dark:text-green-300' => $assignment->status->value === 'signed',
                                                    'bg-yellow-100 text-yellow-800 dark:text-yellow-300' => $assignment->status->value === 'pending',
                                                ])>
                                                    {{ $assignment->status->value === 'signed' ? __('Sudah tanda tangan') : __('Menunggu') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">{{ $assignment->signed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if (in_array($document->status->value, ['draft', 'partially_signed']) && auth()->user()?->can('sign', $document))
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-1">{{ __('Bubuhkan Tanda Tangan') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            {{ __('Seret kotak tanda tangan pada pratinjau PDF untuk mengatur posisi.') }}
                            {{ __('Koordinat dalam satuan Point (1 pt = 1/72 inci), origin kiri-atas halaman.') }}
                            {{ __('Persiapkan sertifikat aktif pada halaman Sertifikat Digital.') }}
                        </p>

                        @if ($errors->has('sign'))
                            <div class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4">
                                {{ $errors->first('sign') }}
                            </div>
                        @endif

                        <div class="space-y-6">
                            <div id="signature-viewer"
                                data-preview-url="{{ route('documents.preview', $document) }}"
                                data-form-id="sign-form">
                                <div class="mb-3 flex flex-wrap items-center gap-3">
                                    <label for="sig-page-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Halaman aktif') }}
                                    </label>
                                    <select id="sig-page-select" class="border-gray-300 dark:border-gray-600 text-sm rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                                    <span id="sig-status" class="text-sm text-gray-500 dark:text-gray-400">{{ __('Memuat PDF…') }}</span>
                                </div>

                                <p id="sig-error" hidden
                                    class="mb-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-300 px-4 py-3 rounded text-sm">
                                </p>

                                <div id="sig-pages" class="space-y-8 max-h-[80vh] overflow-auto p-3 bg-gray-100 dark:bg-gray-900 rounded-md"></div>
                            </div>

                            <form id="sign-form" method="POST" action="{{ route('documents.sign', $document) }}" class="space-y-4 self-start">
                                @csrf

                                <input type="hidden" id="page_number" name="page_number" value="{{ old('page_number', 1) }}">
                                <input type="hidden" id="coord_x" name="coord_x" value="{{ old('coord_x', 72) }}">
                                <input type="hidden" id="coord_y" name="coord_y" value="{{ old('coord_y', 700) }}">
                                <input type="hidden" id="width" name="width" value="{{ old('width', 150) }}">
                                <input type="hidden" id="height" name="height" value="{{ old('height', 50) }}">

                                <div>
                                    <x-input-label for="passphrase" :value="__('Passphrase Kunci')" />
                                    <x-text-input id="passphrase" name="passphrase" type="password" class="mt-1 block w-full" required autocomplete="off" />
                                    <x-input-error :messages="$errors->get('passphrase')" class="mt-2" />
                                </div>

                                @foreach (['page_number', 'coord_x', 'coord_y', 'width', 'height'] as $coordinateField)
                                    <x-input-error :messages="$errors->get($coordinateField)" class="mt-2" />
                                @endforeach

                                <x-primary-button>{{ __('Tandatangani Dokumen') }}</x-primary-button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Riwayat Tanda Tangan') }}</h3>

                    @if ($document->signatures->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada tanda tangan.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-400">
                                <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Penandatangan') }}</th>
                                        <th class="px-4 py-3">{{ __('Halaman') }}</th>
                                        <th class="px-4 py-3">{{ __('Waktu') }}</th>
                                        <th class="px-4 py-3">{{ __('Token') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($document->signatures as $signature)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $signature->signer_name ?? $signature->signer?->name }}</td>
                                            <td class="px-4 py-3">{{ $signature->page_number }}</td>
                                            <td class="px-4 py-3">{{ $signature->signed_at?->format('Y-m-d H:i') }}</td>
                                            <td class="px-4 py-3 font-mono text-xs break-all">
                                                <a class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                                    href="{{ route('verify.show', ['code' => $signature->verification_code]) }}">
                                                    {{ \App\Services\Verification\VerificationCode::display($signature->verification_code) }}
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

    <x-modal name="confirm-revoke-document" :show="false" maxWidth="lg">
        <form method="POST" action="{{ route('documents.revoke', $document) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Cabut Dokumen') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Dokumen yang dicabut akan ditandai sebagai REVOKED dan tidak lagi dianggap valid saat diverifikasi. Masukkan alasan pencabutan.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="revoke-reason" :value="__('Alasan Pencabutan')" />
                <textarea id="revoke-reason" name="reason" rows="3" required maxlength="500"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="{{ __('Contoh: Dokumen ditarik kembali karena kesalahan data.') }}"></textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                <x-input-error :messages="$errors->get('revoke')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close-modal', 'confirm-revoke-document')"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                    {{ __('Batal') }}
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-500">
                    {{ __('Cabut Sekarang') }}
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
