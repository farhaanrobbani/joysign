<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Providers\AppServiceProvider;
use App\Services\Audit\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pengaturan website (full-page Livewire): nama web, logo, tagline, SEO, dan
 * seluruh teks beranda. Nilai dipegang properti $data (array bersarang), disimpan
 * ke tabel settings (key-value) saat save().
 */
#[Layout('layouts.admin')]
class SettingsPage extends Component
{
    use WithFileUploads;

    /** Nilai pengaturan dalam struktur bersarang (mis. data.site.name). */
    public array $data = [];

    /** File logo baru (TemporaryUploadedFile) bila diunggah. */
    public $logo = null;

    public ?string $logoUrl = null;

    public ?string $status = null;

    public function mount(): void
    {
        $flat = [];
        foreach (config('settings.defaults', []) as $key => $default) {
            $flat[$key] = setting($key) ?? $default;
        }

        $this->data = Arr::undot($flat);
        $this->logoUrl = $this->currentLogoUrl();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->role === UserRole::Admin, 403);

        $this->validate($this->rules());

        $flat = Arr::dot($this->data);

        if ($this->logo !== null) {
            $flat['site.logo_path'] = $this->storeLogo($this->logo);
            $this->logoUrl = $this->currentLogoUrl();
        }

        Setting::setMany($flat);

        // Terapkan ulang nama web ke config untuk permintaan berjalan.
        AppServiceProvider::applySiteNameOverride();

        app(AuditService::class)->log(null, auth()->user(), 'admin.settings.updated', [
            'keys' => implode(', ', array_keys($flat)),
        ]);

        $this->status = 'Pengaturan berhasil disimpan.';
        $this->logo = null;
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = [
            'data.site.name' => ['required', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ];

        foreach (array_keys(config('settings.defaults', [])) as $key) {
            if ($key === 'site.name') {
                continue;
            }

            $rules['data.'.$key] = ['nullable', 'string', 'max:4000'];
        }

        return $rules;
    }

    private function currentLogoUrl(): ?string
    {
        $path = setting('site.logo_path');

        if ($path === null || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return route('site.logo');
    }

    private function storeLogo(mixed $file): string
    {
        $extension = $file->extension() ?: 'png';
        $filename = 'logo-'.Str::ulid().'.'.$extension;

        $file->storeAs('logos', $filename, 'local');

        $old = setting('site.logo_path');
        if ($old !== null && Storage::disk('local')->exists($old)) {
            Storage::disk('local')->delete($old);
        }

        return 'logos/'.$filename;
    }

    /**
     * Definisi form pengaturan per section: [key, label, tipe].
     *
     * @return array<int, array{title: string, fields: array<int, array{0: string, 1: string, 2: string}>}>
     */
    private function sections(): array
    {
        $card = function (string $prefix, int $count, string $titleLabel = 'Judul', string $descLabel = 'Deskripsi'): array {
            $fields = [];
            for ($i = 1; $i <= $count; $i++) {
                $fields[] = [$prefix.'.'.$i.'.title', "Item $i — $titleLabel", 'text'];
                $fields[] = [$prefix.'.'.$i.'.desc', "Item $i — $descLabel", 'textarea'];
            }

            return $fields;
        };

        $faq = function (): array {
            $fields = [];
            for ($i = 1; $i <= 5; $i++) {
                $fields[] = ['landing.faq.'.$i.'.q', "Pertanyaan $i", 'text'];
                $fields[] = ['landing.faq.'.$i.'.a', "Jawaban $i", 'textarea'];
            }

            return $fields;
        };

        return [
            [
                'title' => 'Brand & SEO',
                'fields' => [
                    ['site.name', 'Nama Web', 'text'],
                    ['site.tagline', 'Tagline', 'text'],
                    ['seo.title', 'Judul SEO (Meta Title)', 'text'],
                    ['seo.description', 'Deskripsi SEO', 'textarea'],
                ],
            ],
            [
                'title' => 'Hero (Beranda)',
                'fields' => [
                    ['landing.hero.badge', 'Teks Badge', 'text'],
                    ['landing.hero.title', 'Judul Utama', 'text'],
                    ['landing.hero.title_accent', 'Judul — bagian terhighlight', 'text'],
                    ['landing.hero.subtitle', 'Subjudul', 'textarea'],
                    ['landing.hero.stat1_value', 'Statistik 1 — Nilai', 'text'],
                    ['landing.hero.stat1_label', 'Statistik 1 — Label', 'text'],
                    ['landing.hero.stat2_value', 'Statistik 2 — Nilai', 'text'],
                    ['landing.hero.stat2_label', 'Statistik 2 — Label', 'text'],
                    ['landing.hero.stat3_value', 'Statistik 3 — Nilai', 'text'],
                    ['landing.hero.stat3_label', 'Statistik 3 — Label', 'text'],
                ],
            ],
            [
                'title' => 'Fitur Unggulan',
                'fields' => array_merge([
                    ['landing.fitur.badge', 'Eyebrow', 'text'],
                    ['landing.fitur.title', 'Judul Section', 'text'],
                    ['landing.fitur.subtitle', 'Subjudul', 'textarea'],
                ], $card('landing.fitur', 6)),
            ],
            [
                'title' => 'Cara Kerja',
                'fields' => array_merge([
                    ['landing.steps.badge', 'Eyebrow', 'text'],
                    ['landing.steps.title', 'Judul Section', 'text'],
                ], $card('landing.steps', 4, 'Judul Langkah', 'Deskripsi')),
            ],
            [
                'title' => 'Portal Verifikasi',
                'fields' => [
                    ['landing.verify.badge', 'Eyebrow', 'text'],
                    ['landing.verify.title', 'Judul', 'text'],
                    ['landing.verify.subtitle', 'Deskripsi', 'textarea'],
                ],
            ],
            [
                'title' => 'Keamanan & Kepatuhan',
                'fields' => array_merge([
                    ['landing.security.badge', 'Eyebrow', 'text'],
                    ['landing.security.title', 'Judul Section', 'text'],
                    ['landing.security.subtitle', 'Subjudul', 'textarea'],
                ], $card('landing.security', 6, 'Nama', 'Deskripsi')),
            ],
            [
                'title' => 'FAQ',
                'fields' => array_merge([
                    ['landing.faq.badge', 'Eyebrow', 'text'],
                    ['landing.faq.title', 'Judul Section', 'text'],
                ], $faq()),
            ],
            [
                'title' => 'CTA Penutup',
                'fields' => [
                    ['landing.cta.title', 'Judul', 'text'],
                    ['landing.cta.subtitle', 'Deskripsi', 'textarea'],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.settings-page', [
            'sections' => $this->sections(),
        ])->layout('layouts.admin', [
            'header' => new HtmlString(
                '<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'
                .__('Pengaturan Website').'</h2>'
            ),
        ]);
    }
}
