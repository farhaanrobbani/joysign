<div>
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($status)
                <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ $status }}
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">
                {{-- Logo + Brand --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Logo &amp; Nama</h3>

                        <div class="flex items-center gap-6">
                            <div class="shrink-0">
                                <div class="h-20 w-20 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-center overflow-hidden">
                                    @if ($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="Logo" class="h-full w-full object-contain">
                                    @else
                                        <x-application-logo class="h-12 w-12 fill-current text-indigo-600 dark:text-indigo-400" />
                                    @endif
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">Saat ini</p>
                            </div>

                            <div class="flex-1">
                                <x-input-label for="logo" value="{{ __('Upload Logo Baru (PNG/JPG/SVG, maks 2 MB) — kosongkan untuk memakai logo bawaan') }}" />
                                <input id="logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/svg+xml"
                                    class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-500/20 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-500/30" />
                                <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6">
                            <x-input-label for="site-name" value="{{ __('Nama Web') }}" />
                            <x-text-input id="site-name" type="text" class="mt-1 block w-full" wire:model="data.site.name" required />
                            <x-input-error :messages="$errors->get('data.site.name')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="site-tagline" value="{{ __('Tagline') }}" />
                            <x-text-input id="site-tagline" type="text" class="mt-1 block w-full" wire:model="data.site.tagline" />
                            <x-input-error :messages="$errors->get('data.site.tagline')" class="mt-2" />
                        </div>
                    </div>
                </div>

                @foreach ($sections as $sectionIndex => $section)
                    @continue($sectionIndex === 0)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                        <details {{ $sectionIndex === 1 ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">
                                {{ $section['title'] }}
                                <svg class="h-5 w-5 text-gray-400 transform transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </summary>
                            <div class="px-6 pb-6 space-y-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                                @foreach ($section['fields'] as [$key, $label, $type])
                                    @php
                                        $fieldId = str_replace('.', '-', $key);
                                    @endphp
                                    <div>
                                        @if ($type === 'textarea')
                                            <x-input-label :for="$fieldId" :value="$label" />
                                            <textarea id="{{ $fieldId }}" wire:model="data.{{ $key }}" rows="2"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"></textarea>
                                        @else
                                            <x-input-label :for="$fieldId" :value="$label" />
                                            <x-text-input :id="$fieldId" type="text" class="mt-1 block w-full" wire:model="data.{{ $key }}" />
                                        @endif
                                        <x-input-error :messages="$errors->get('data.'.$key)" class="mt-2" />
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        {{ __('Simpan Pengaturan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
