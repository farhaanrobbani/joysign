<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Dashboard User',
            'nik' => '3275012345670003',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_sees_greeting_and_identity(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard User');
        $response->assertSee('3275012345670003');
        $response->assertSee($this->user->email);
    }

    public function test_dashboard_shows_document_statistics(): void
    {
        Document::factory()->count(2)->create(['user_id' => $this->user->id]);
        Document::factory()->signed()->create(['user_id' => $this->user->id]);
        Document::factory()->revoked()->create(['user_id' => $this->user->id]);

        Document::factory()->signed()->create();

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder(['Total Dokumen', '4', 'Draft', '2', 'Ditandatangani', 'Dicabut']);
    }

    public function test_dashboard_lists_recent_documents_only_for_user(): void
    {
        $own = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Dokumen Saya Sendiri',
        ]);
        Document::factory()->create(['title' => 'Dokumen Orang Lain']);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dokumen Saya Sendiri');
        $response->assertDontSee('Dokumen Orang Lain');
        $response->assertSee(route('documents.show', $own), false);
    }

    public function test_dashboard_shows_certificate_cta_when_missing(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Lengkapi identitas tanda tangan Anda');
        $response->assertSee('Buat / perbarui sekarang');
    }

    public function test_dashboard_hides_cta_when_certificate_ready(): void
    {
        DigitalCertificate::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Lengkapi identitas tanda tangan Anda');
        $response->assertSee('Sertifikat');
        $response->assertSee('Aktif');
    }

    public function test_expired_certificate_counts_as_missing(): void
    {
        DigitalCertificate::factory()->expired()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Lengkapi identitas tanda tangan Anda');
        $response->assertSee('Belum');
    }
}
