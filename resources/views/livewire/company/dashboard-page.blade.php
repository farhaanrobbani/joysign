<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($status)
                <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ $status }}
                </div>
            @endif

            @if ($mode === 'create')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 max-w-xl">
                        <h3 class="text-lg font-semibold">{{ __('Daftarkan Perusahaan Anda') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Dengan membuat perusahaan, Anda menjadi operator dan dapat mengunggah dokumen serta menugaskan penandatangan. Sertifikat digital tetap milik masing-masing penandatangan.') }}
                        </p>

                        <form wire:submit="createCompany" class="mt-6 space-y-4">
                            <div>
                                <x-input-label for="name" :value="__('Nama Perusahaan')" />
                                <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" required autofocus placeholder="PT Contoh Nusantara" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <x-primary-button>{{ __('Buat Perusahaan') }}</x-primary-button>
                        </form>
                    </div>
                </div>
            @else
                @if ($isOperator)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('Logo Perusahaan') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                {{ __('Logo akan dibubuhkan ke dokumen saat penandatanganan, di samping QR Code verifikasi.') }}
                            </p>

                            <div class="flex items-start gap-6">
                                <div class="w-40 h-40 flex items-center justify-center border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 overflow-hidden">
                                    @if ($company->logo_path)
                                        <img src="{{ route('company.logo') }}" alt="{{ __('Logo Perusahaan') }}" class="max-h-full max-w-full object-contain">
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Belum ada logo') }}</span>
                                    @endif
                                </div>

                                <div class="flex-1 space-y-4">
                                    <form wire:submit="saveLogo" class="space-y-3 max-w-md">
                                        <div>
                                            <x-input-label for="company_logo" :value="__('Upload Logo (PNG/JPG/SVG, maks 2 MB)')" />
                                            <input id="company_logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/svg+xml" required
                                                class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-400 file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-800 file:text-white hover:file:bg-gray-700" />
                                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                                        </div>

                                        <div wire:loading wire:target="logo" class="text-sm text-gray-500">{{ __('Mengunggah…') }}</div>

                                        <x-primary-button>{{ __('Simpan Logo') }}</x-primary-button>
                                    </form>

                                    @if ($company->logo_path)
                                        <button type="button" wire:click="removeLogo"
                                            wire:confirm="{{ __('Hapus logo perusahaan ini? Dokumen berikutnya hanya akan memuat QR Code.') }}"
                                            class="text-red-600 dark:text-red-400 hover:underline text-sm">{{ __('Hapus Logo') }}</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Anggota Perusahaan') }}</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-400">
                                <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Nama') }}</th>
                                        <th class="px-4 py-3">{{ __('Email') }}</th>
                                        <th class="px-4 py-3">{{ __('NIK') }}</th>
                                        <th class="px-4 py-3">{{ __('Peran') }}</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($members as $member)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $member->name }}</td>
                                            <td class="px-4 py-3">{{ $member->email }}</td>
                                            <td class="px-4 py-3 font-mono">{{ $member->nik }}</td>
                                            <td class="px-4 py-3">
                                                <span @class([
                                                    'px-2 py-1 rounded-full text-xs font-medium',
                                                    'bg-indigo-100 text-indigo-800 dark:text-indigo-300' => $member->role->value === 'operator',
                                                    'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300' => $member->role->value === 'signer',
                                                ])>
                                                    {{ $member->role->value === 'operator' ? __('Operator') : __('Penandatangan') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                @if ($isOperator && $member->id !== auth()->id())
                                                    <button type="button" wire:click="removeMember('{{ $member->id }}')"
                                                        wire:confirm="{{ __('Keluarkan anggota ini?') }}"
                                                        class="text-red-600 dark:text-red-400 hover:underline">{{ __('Keluarkan') }}</button>

                                                    @if ($member->role->value === 'signer')
                                                        @php $hasData = ($member->documents_count + $member->signatures_count + $member->certificates_count) > 0; @endphp
                                                        <button type="button" wire:click="destroyMember('{{ $member->id }}')"
                                                            wire:confirm="{{ $hasData ? __('Hapus akun anggota ini? Identitas dihapus, dokumen & verifikasi tetap utuh.') : __('Hapus permanen akun anggota ini?') }}"
                                                            class="text-red-600 dark:text-red-400 hover:underline ml-3">{{ __('Hapus') }}</button>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if ($isOperator)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 max-w-xl">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Tambah Penandatangan') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Akun penandatangan dibuat langsung. Catat email & password yang Anda tentukan untuk disampaikan ke penandatangan.') }}</p>

                            <form wire:submit="addMember" class="mt-4 space-y-4">
                                <div>
                                    <x-input-label for="member_name" :value="__('Nama')" />
                                    <x-text-input id="member_name" type="text" class="mt-1 block w-full" wire:model="memberName" required />
                                    <x-input-error :messages="$errors->get('memberName')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="member_email" :value="__('Email')" />
                                    <x-text-input id="member_email" type="email" class="mt-1 block w-full" wire:model="memberEmail" required />
                                    <x-input-error :messages="$errors->get('memberEmail')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="member_nik" :value="__('NIK (16 digit)')" />
                                    <x-text-input id="member_nik" type="text" class="mt-1 block w-full" wire:model="memberNik" required maxlength="16" pattern="[0-9]{16}" inputmode="numeric" />
                                    <x-input-error :messages="$errors->get('memberNik')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="member_password" :value="__('Password Awal')" />
                                    <x-text-input id="member_password" type="text" class="mt-1 block w-full" wire:model="memberPassword" required minlength="8" />
                                    <x-input-error :messages="$errors->get('memberPassword')" class="mt-2" />
                                </div>

                                <x-primary-button>{{ __('Tambah Anggota') }}</x-primary-button>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 max-w-xl">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Tambah Anggota Terdaftar') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Masukkan email akun yang sudah terdaftar untuk melampirkannya ke perusahaan ini sebagai penandatangan.') }}</p>

                            <form wire:submit="attachMember" class="mt-4 space-y-4">
                                <div>
                                    <x-input-label for="attach_email" :value="__('Email Akun Terdaftar')" />
                                    <x-text-input id="attach_email" type="email" class="mt-1 block w-full" wire:model="email" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <x-primary-button>{{ __('Lampirkan Anggota') }}</x-primary-button>
                            </form>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
