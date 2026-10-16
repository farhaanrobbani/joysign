<x-verify-layout>
    <div class="text-center mb-8 sm:mb-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">{{ __('Portal Verifikasi Dokumen') }}</h1>
        <p class="mt-2 text-sm sm:text-base text-blue-100/80">{{ __('Verifikasi keaslian dokumen TTE melalui token atau scan QR Code.') }}</p>
    </div>

    @if (isset($result))
        <div @class([
            'rounded-2xl bg-white dark:bg-gray-900/85 shadow-2xl shadow-black/30 overflow-hidden border border-white/10 border-l-4 mb-6',
            'border-l-green-500' => $result->status === \App\Enums\VerificationStatus::Valid,
            'border-l-red-500' => in_array($result->status, [\App\Enums\VerificationStatus::Tampered, \App\Enums\VerificationStatus::Revoked, \App\Enums\VerificationStatus::Expired], true),
            'border-l-yellow-500' => in_array($result->status, [\App\Enums\VerificationStatus::UnsignedOriginal, \App\Enums\VerificationStatus::NotFound], true),
        ])>
            <div class="p-6 sm:p-8">
                @php
                    $isValid = $result->status === \App\Enums\VerificationStatus::Valid;
                    $statusMeta = [
                        \App\Enums\VerificationStatus::Valid->value => ['VALID', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
                        \App\Enums\VerificationStatus::Tampered->value => ['TAMPERED', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
                        \App\Enums\VerificationStatus::Expired->value => ['EXPIRED', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
                        \App\Enums\VerificationStatus::Revoked->value => ['REVOKED', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
                        \App\Enums\VerificationStatus::UnsignedOriginal->value => ['UNSIGNED ORIGINAL', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'],
                        \App\Enums\VerificationStatus::NotFound->value => ['NOT FOUND', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'],
                    ];
                    [$label, $badgeClass] = $statusMeta[$result->status->value] ?? [$result->status->value, 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100'];
                @endphp
                <div class="flex items-center gap-3 mb-5">
                    <span @class(['inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-bold', $badgeClass])>
                        @if ($isValid)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        @endif
                        {{ $label }}
                    </span>
                </div>

                @if ($result->document)
                    @php
                        $signatures = $result->document->signatures()->with('signer')->orderBy('signed_at')->orderBy('id')->get();
                        $viewCode = $code ?? $result->signature?->verification_code;
                    @endphp
                    <div class="text-sm space-y-3">
                        @if ($signatures->first()?->verification_code)
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Token') }}</span>
                                <span class="font-mono break-all text-right text-xs text-gray-900 dark:text-gray-100">{{ \App\Services\Verification\VerificationCode::display($signatures->first()->verification_code) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Judul Dokumen') }}</span>
                            <span class="text-gray-900 dark:text-gray-100 text-right font-medium">{{ $result->document->title }}</span>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 mt-4 space-y-3">
                            @if ($result->message)
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $result->message }}</p>
                            @endif
                            @foreach ($signatures as $index => $item)
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3 space-y-3">
                                    <div class="flex justify-between gap-4">
                                        <span class="text-gray-500 dark:text-gray-400 shrink-0">
                                            {{ $signatures->count() > 1 ? __('Penandatangan').' '.($index + 1) : __('Penandatangan') }}
                                        </span>
                                        <span class="text-gray-900 dark:text-gray-100 text-right">{{ $item->signer_name ?? $item->signer?->name }}</span>
                                    </div>
                                    @if ($item->signer?->jabatan)
                                        <div class="flex justify-between gap-4">
                                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Jabatan') }}</span>
                                            <span class="text-gray-900 dark:text-gray-100 text-right">{{ $item->signer->jabatan }}</span>
                                        </div>
                                    @endif
                                    @if ($item->signer?->unit_kerja)
                                        <div class="flex justify-between gap-4">
                                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Unit Kerja') }}</span>
                                            <span class="text-gray-900 dark:text-gray-100 text-right">{{ $item->signer->unit_kerja }}</span>
                                        </div>
                                    @endif
                                    @if ($item->signer?->instansi)
                                        <div class="flex justify-between gap-4">
                                            <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Instansi') }}</span>
                                            <span class="text-gray-900 dark:text-gray-100 text-right">{{ $item->signer->instansi }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between gap-4">
                                        <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Waktu Tanda Tangan') }}</span>
                                        <span class="text-gray-900 dark:text-gray-100 text-right">{{ $item->signed_at?->format('Y-m-d H:i:s') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($viewCode)
                            <div class="mt-5">
                                <a href="{{ route('verify.document', ['code' => $viewCode]) }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-teal-500 to-blue-500 hover:from-teal-400 hover:to-blue-400 text-white text-sm font-semibold shadow-lg shadow-blue-900/20 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    {{ __('Lihat Dokumen') }}
                                </a>
                            </div>
                        @endif
                    @elseif ($result->message)
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $result->message }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl bg-white/95 dark:bg-gray-900/85 backdrop-blur shadow-2xl shadow-black/30 border border-white/10 p-6 sm:p-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('Verifikasi via Token') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Masukkan token dari QR Code pada dokumen.') }}</p>
        <form method="GET" action="{{ route('verify.show', ['code' => '__CODE__']) }}" class="flex flex-col sm:flex-row gap-3"
            onsubmit="this.action = this.action.replace('__CODE__', encodeURIComponent(document.getElementById('verify-code').value.trim())); return document.getElementById('verify-code').value.trim() !== '';">
            <input type="text" id="verify-code" name="code"
                class="flex-1 h-12 rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-400 focus:ring-blue-400 text-base" required>
            <button type="submit" class="h-12 px-6 rounded-xl bg-gradient-to-r from-teal-500 to-blue-500 hover:from-teal-400 hover:to-blue-400 text-white text-sm font-semibold shadow-lg shadow-blue-900/20 whitespace-nowrap transition">
                {{ __('Verifikasi Dokumen') }}
            </button>
        </form>
    </div>
</x-verify-layout>
