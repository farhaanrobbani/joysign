<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Unggah Dokumen PDF') }}</h3>

                    <form wire:submit="store" class="space-y-4">
                        <div>
                            <x-input-label for="title" :value="__('Judul Dokumen')" />
                            <x-text-input id="title" type="text" class="mt-1 block w-full" wire:model="title" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="document" :value="__('File PDF (maks. '.config('documents.max_upload_mb').' MB)')" />
                            <input id="document" type="file" accept="application/pdf" wire:model="document" required
                                class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-400 file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-800 file:text-white hover:file:bg-gray-700" />
                            <x-input-error :messages="$errors->get('document')" class="mt-2" />
                        </div>

                        @if ($companySigners->isNotEmpty())
                            <div class="border border-indigo-100 bg-indigo-50 dark:border-indigo-500/30 dark:bg-indigo-500/10 rounded-md p-4"
                                 x-data="{ order: @js($signers) }">
                                <x-input-label :value="__('Tugaskan ke Penandatangan (opsional)')" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Jika dipilih, dokumen menjadi dokumen perusahaan yang harus ditandatangani semua penandatangan terpilih. Jika dikosongkan, dokumen menjadi milik pribadi Anda. Urutan pengecekan menentukan posisi: Penandatangan 1, 2, dst.') }}</p>
                                <div class="mt-3 space-y-2">
                                    @foreach ($companySigners as $signer)
                                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model="signers" value="{{ $signer->id }}"
                                                x-on:change="$event.target.checked ? (order.includes($event.target.value) || order.push($event.target.value)) : order = order.filter(id => id !== $event.target.value)"
                                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <span x-show="order.includes('{{ $signer->id }}')"
                                                  x-text="order.indexOf('{{ $signer->id }}') + 1"
                                                  class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[10px] font-semibold text-white"></span>
                                            {{ $signer->name }}
                                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $signer->email }})</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('signers')" class="mt-2" />
                            </div>
                        @endif

                        <x-primary-button>{{ __('Unggah') }}</x-primary-button>
                    </form>
                </div>
            </div>

            <livewire:documents.documents-list />
        </div>
    </div>
</div>
