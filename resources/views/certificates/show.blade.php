<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Sertifikat Digital') }}
        </h2>
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
                    <h3 class="text-lg font-semibold mb-2">{{ __('Status Sertifikat') }}</h3>

                    @if ($certificate)
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Algoritma') }}</span>
                            <span>{{ $certificate->key_algorithm }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Diterbitkan') }}</span>
                            <span>{{ $certificate->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Berlaku s/d') }}</span>
                            <span>{{ $certificate->expires_at?->format('Y-m-d') ?? __('Tidak dibatasi') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:text-green-300">{{ __('Aktif') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 pt-2">
                            {{ __('Private key Anda disimpan terenkripsi (AES-256-GCM + Argon2id). Passphrase tidak pernah disimpan oleh sistem.') }}
                        </p>
                    @else
                        <p class="text-yellow-700 dark:text-yellow-300 bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/30 px-4 py-3 rounded">
                            {{ __('Belum ada sertifikat aktif. Aktifkan pasangan kunci sebelum menandatangani dokumen.') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-1">{{ __('Aktivasi Pasangan Kunci') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ __('Aktivasi baru akan menonaktifkan pasangan kunci aktif sebelumnya (aturan: satu keypair aktif per akun).') }}
                    </p>

                    <form method="POST" action="{{ route('certificates.store') }}" class="space-y-4 max-w-md">
                        @csrf

                        <div>
                            <x-input-label for="passphrase" :value="__('Passphrase Rahasia (min. 8 karakter)')" />
                            <x-text-input id="passphrase" name="passphrase" type="password" class="mt-1 block w-full" required autocomplete="off" />
                            <x-input-error :messages="$errors->get('passphrase')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="passphrase_confirmation" :value="__('Ulangi Passphrase')" />
                            <x-text-input id="passphrase_confirmation" name="passphrase_confirmation" type="password" class="mt-1 block w-full" required autocomplete="off" />
                        </div>

                        <x-primary-button>{{ __('Aktifkan Sertifikat') }}</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
