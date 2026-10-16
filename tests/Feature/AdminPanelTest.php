<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSignedDocument;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use CreatesSignedDocument, CreatesTestPdf, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeStorageFakes();
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Total Pengguna');
    }

    public function test_admin_can_access_users_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users'));

        $response->assertOk();
        $response->assertSee('Manajemen Pengguna');
    }

    public function test_admin_can_access_audit_logs_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.audit-logs'));

        $response->assertOk();
        $response->assertSee('Log Audit');
    }

    public function test_non_admin_cannot_access_admin_pages(): void
    {
        $signer = User::factory()->create(['role' => UserRole::Signer]);

        $this->actingAs($signer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($signer)->get(route('admin.users'))->assertForbidden();
        $this->actingAs($signer)->get(route('admin.audit-logs'))->assertForbidden();
    }

    public function test_guest_redirected_from_admin_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_users_page_shows_search_result(): void
    {
        $target = User::factory()->create(['name' => 'Budi Santoso', 'nik' => '1234567890']);

        $response = $this->actingAs($this->admin)->get(route('admin.users', ['q' => 'Budi']));

        $response->assertOk();
        $response->assertSee('Budi Santoso');
    }

    public function test_audit_logs_page_filters_by_event(): void
    {
        [, $document] = $this->makeSignedDocument();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs', ['event' => 'document.signed']));

        $response->assertOk();
        $response->assertSee('document.signed');
    }

    public function test_dashboard_no_longer_shows_recent_activity(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Dokumen');
        $response->assertDontSee('Aktivitas Terbaru');
    }
}
