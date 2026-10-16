<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($status)
                <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ $status }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Buat Pengguna Baru') }}</h3>
                        <button type="button" wire:click="toggleCreating"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                            {{ $creating ? __('Tutup') : __('+ Buat Pengguna') }}
                        </button>
                    </div>

                    @if ($creating)
                        <form wire:submit="store" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
                        <div>
                            <x-input-label for="admin-name" :value="__('Nama')" />
                            <x-text-input id="admin-name" type="text" class="mt-1 block w-full" wire:model="name" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-email" :value="__('Email')" />
                            <x-text-input id="admin-email" type="email" class="mt-1 block w-full" wire:model="email" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-nik" :value="__('NIK (16 digit)')" />
                            <x-text-input id="admin-nik" type="text" class="mt-1 block w-full" wire:model="nik" required maxlength="16" pattern="[0-9]{16}" inputmode="numeric" />
                            <x-input-error :messages="$errors->get('nik')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-jabatan" :value="__('Jabatan')" />
                            <x-text-input id="admin-jabatan" type="text" class="mt-1 block w-full" wire:model="jabatan" />
                            <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-unit-kerja" :value="__('Unit Kerja')" />
                            <x-text-input id="admin-unit-kerja" type="text" class="mt-1 block w-full" wire:model="unitKerja" />
                            <x-input-error :messages="$errors->get('unitKerja')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-instansi" :value="__('Instansi')" />
                            <x-text-input id="admin-instansi" type="text" class="mt-1 block w-full" wire:model="instansi" />
                            <x-input-error :messages="$errors->get('instansi')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-password" :value="__('Password')" />
                            <x-text-input id="admin-password" type="text" class="mt-1 block w-full" wire:model="password" required minlength="8" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="admin-role" :value="__('Role')" />
                            <select id="admin-role" wire:model.live="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="signer">{{ __('Signer (perorangan)') }}</option>
                                <option value="operator">{{ __('Operator') }}</option>
                                <option value="admin">{{ __('Admin') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        @if ($role !== 'admin')
                            <div>
                                <x-input-label for="admin-company" :value="__('Perusahaan')" />
                                <select id="admin-company" wire:model="companyId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">{{ __('— Perorangan (tanpa perusahaan) —') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Wajib untuk Operator; opsional untuk Signer; diabaikan untuk Admin.') }}</p>
                                <x-input-error :messages="$errors->get('companyId')" class="mt-2" />
                            </div>
                        @endif

                        <div class="flex items-end">
                            <x-primary-button>{{ __('Simpan Pengguna') }}</x-primary-button>
                        </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex gap-2 mb-4">
                        <input type="text" wire:model.live.debounce.400ms="q"
                            placeholder="{{ __('Cari nama, email, atau NIK…') }}"
                            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-600 dark:text-gray-400">
                            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Nama') }}</th>
                                    <th class="px-4 py-3">{{ __('Email') }}</th>
                                    <th class="px-4 py-3">{{ __('NIK') }}</th>
                                    <th class="px-4 py-3">{{ __('Role') }}</th>
                                    <th class="px-4 py-3">{{ __('Perusahaan') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Sertifikat') }}</th>
                                    <th class="px-4 py-3">{{ __('Terdaftar') }}</th>
                                    <th class="px-4 py-3">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $user->name }}</td>
                                        <td class="px-4 py-3">{{ $user->email }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $user->nik ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span @class([
                                                'px-2 py-1 rounded-full text-xs font-medium',
                                                'bg-purple-100 text-purple-800 dark:text-purple-300' => $user->role->value === 'admin',
                                                'bg-amber-100 text-amber-800 dark:text-amber-300' => $user->role->value === 'operator',
                                                'bg-blue-100 text-blue-800 dark:text-blue-300' => $user->role->value === 'signer',
                                            ])>
                                                {{ $user->role->value }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $user->company?->name ?? ($user->role->value === 'admin' ? '—' : __('Perorangan')) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span @class([
                                                'px-2 py-1 rounded-full text-xs font-medium',
                                                'bg-green-100 text-green-800 dark:text-green-300' => ! $user->isDisabled(),
                                                'bg-red-100 text-red-800 dark:text-red-300' => $user->isDisabled(),
                                            ])>
                                                {{ $user->isDisabled() ? __('Nonaktif') : __('Aktif') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($user->certificates_count > 0)
                                                <span class="text-green-600 dark:text-green-400 text-xs font-medium">{{ $user->certificates_count }} aktif</span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $user->created_at->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-2 text-xs">
                                                @if ($user->id !== auth()->id())
                                                    @if ($user->isDisabled())
                                                        <button type="button" wire:click="enable({{ $user->id }})"
                                                            class="text-green-600 dark:text-green-400 hover:underline">{{ __('Aktifkan') }}</button>
                                                    @else
                                                        <button type="button" wire:click="disable({{ $user->id }})"
                                                            wire:confirm="{{ __('Nonaktifkan pengguna ini?') }}"
                                                            class="text-yellow-600 dark:text-yellow-400 hover:underline">{{ __('Nonaktifkan') }}</button>
                                                    @endif

                                                    @php $hasData = ($user->documents_count + $user->signatures_count + $user->certificates_count) > 0; @endphp
                                                    <button type="button" wire:click="destroy({{ $user->id }})"
                                                        wire:confirm="{{ $hasData ? __('Anonimkan pengguna ini? Identitas dihapus, dokumen & verifikasi tetap utuh.') : __('Hapus pengguna ini permanen?') }}"
                                                        class="text-red-600 dark:text-red-400 hover:underline">{{ __('Hapus') }}</button>
                                                @else
                                                    <span class="text-gray-400">{{ __('(Anda)') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('Tidak ada pengguna ditemukan.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
