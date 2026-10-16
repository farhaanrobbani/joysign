<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\SettingsPage;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $signer = User::factory()->create(['role' => UserRole::Signer]);

        // Halaman diblokir middleware route.
        $this->actingAs($signer)->get(route('admin.settings'))->assertForbidden();

        // Aksi save diblokir di level komponen (defense-in-depth).
        Livewire::actingAs($signer)
            ->test(SettingsPage::class)
            ->set('data.site.name', 'X')
            ->call('save');

        $this->assertNull(Setting::get('site.name'));
    }

    public function test_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Pengaturan Website')
            ->assertSee('Nama Web');
    }

    public function test_site_name_is_required(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->set('data.site.name', '')
            ->call('save')
            ->assertHasErrors('data.site.name');
    }

    public function test_admin_can_update_site_name_and_override_applies(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->set('data.site.name', 'TTE Kustom')
            ->call('save')
            ->assertSet('status', 'Pengaturan berhasil disimpan.');

        $this->assertSame('TTE Kustom', Setting::get('site.name'));

        // Terapkan ulang override ke config seperti pada boot provider.
        AppServiceProvider::applySiteNameOverride();
        $this->assertSame('TTE Kustom', config('app.name'));
    }

    public function test_seo_title_shows_on_welcome_immediately(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->set('data.site.name', 'TTE Kustom')
            ->set('data.seo.title', 'Judul SEO Kustom')
            ->call('save');

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Judul SEO Kustom', false);
    }

    public function test_hero_text_shows_on_welcome(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->set('data.site.name', 'TTE Kustom')
            ->set('data.landing.hero.title', 'Judul Hero Baru')
            ->set('data.landing.hero.subtitle', 'Subjudul hero yang disesuaikan.')
            ->call('save');

        $response = $this->get('/');
        $response->assertSee('Judul Hero Baru');
        $response->assertSee('Subjudul hero yang disesuaikan.');
    }

    public function test_admin_can_upload_logo_and_it_is_served(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 40, 40);

        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->set('data.site.name', 'TTE Kustom')
            ->set('logo', $file)
            ->call('save');

        $path = Setting::get('site.logo_path');
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        // Disajikan lewat route publik /logo.
        $this->get(route('site.logo'))->assertOk();

        // Halaman beranda menampilkan <img> logo, bukan SVG bawaan.
        $welcome = $this->get('/');
        $welcome->assertOk();
        $welcome->assertSee(route('site.logo'), false);
    }

    public function test_logo_defaults_to_svg_when_not_uploaded(): void
    {
        // Tidak ada logo → route /logo 404 dan welcome pakai SVG bawaan.
        $this->get(route('site.logo'))->assertNotFound();

        $welcome = $this->get('/');
        $welcome->assertOk();
        $welcome->assertDontSee('src="'.route('site.logo').'"', false);
    }

    public function test_invalid_logo_rejected(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test(SettingsPage::class)
            ->set('data.site.name', 'TTE Kustom')
            ->set('logo', $file)
            ->call('save')
            ->assertHasErrors('logo');

        $this->assertNull(Setting::get('site.logo_path'));
    }
}
