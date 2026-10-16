<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\UsersPage;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesSignedDocument;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use CreatesSignedDocument, CreatesTestPdf, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeStorageFakes();
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_admin_can_create_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'User Baru')
            ->set('email', 'baru@example.com')
            ->set('nik', '3275012345670020')
            ->set('password', 'password123')
            ->set('role', 'signer')
            ->call('store')
            ->assertSet('status', 'Pengguna berhasil dibuat.');

        $user = User::query()->where('email', 'baru@example.com')->firstOrFail();
        $this->assertSame('signer', $user->role->value);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->disabled_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'admin.user_created',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_operator_can_access_user_management_but_not_other_admin_pages(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)->get(route('admin.users'))->assertOk();
        $this->actingAs($operator)->get(route('admin.audit-logs'))->assertForbidden();
        $this->actingAs($operator)->get(route('admin.settings'))->assertForbidden();
        $this->actingAs($operator)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_signer_cannot_access_user_management(): void
    {
        $signer = User::factory()->create(['role' => UserRole::Signer]);

        $this->actingAs($signer)->get(route('admin.users'))->assertForbidden();
    }

    public function test_create_form_toggles_open_and_closed(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->assertSet('creating', false)
            ->assertDontSee('Simpan Pengguna')
            ->call('toggleCreating')
            ->assertSet('creating', true)
            ->assertSee('Simpan Pengguna')
            ->call('toggleCreating')
            ->assertSet('creating', false)
            ->assertDontSee('Simpan Pengguna');
    }

    public function test_company_field_visibility_follows_role(): void
    {
        Company::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('toggleCreating')
            ->set('role', 'signer')
            ->assertSee('Wajib untuk Operator')
            ->set('role', 'admin')
            ->assertDontSee('Wajib untuk Operator')
            ->set('role', 'operator')
            ->assertSee('Wajib untuk Operator');
    }

    public function test_admin_can_create_user_with_identity_fields(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Bergelar')
            ->set('email', 'bergelar@example.com')
            ->set('nik', '3275012345670099')
            ->set('jabatan', 'Kepala Subbag')
            ->set('unitKerja', 'Subbag Umum')
            ->set('instansi', 'Kantor Contoh')
            ->set('password', 'password123')
            ->set('role', 'signer')
            ->call('store')
            ->assertSet('status', 'Pengguna berhasil dibuat.');

        $this->assertDatabaseHas('users', [
            'email' => 'bergelar@example.com',
            'jabatan' => 'Kepala Subbag',
            'unit_kerja' => 'Subbag Umum',
            'instansi' => 'Kantor Contoh',
        ]);
    }

    public function test_admin_can_create_admin_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Admin Baru')
            ->set('email', 'admin2@example.com')
            ->set('nik', '3275012345670021')
            ->set('password', 'password123')
            ->set('role', 'admin')
            ->call('store');

        $this->assertDatabaseHas('users', [
            'email' => 'admin2@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_create_user_validates_unique_and_format(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'nik' => '3275012345670022']);

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Dup')
            ->set('email', 'taken@example.com')
            ->set('nik', '3275012345670022')
            ->set('password', 'password123')
            ->set('role', 'signer')
            ->call('store')
            ->assertHasErrors(['email', 'nik']);

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Bad')
            ->set('email', 'bad@example.com')
            ->set('nik', '123')
            ->set('password', 'password123')
            ->set('role', 'signer')
            ->call('store')
            ->assertHasErrors('nik');
    }

    public function test_admin_can_disable_user_and_they_cannot_login(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('disable', $user->id)
            ->assertSet('status', 'Pengguna berhasil dinonaktifkan.');

        $this->assertNotNull($user->refresh()->disabled_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'admin.user_disabled',
            'user_id' => $this->admin->id,
        ]);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_enable_user_and_they_can_login_again(): void
    {
        $user = User::factory()->create(['disabled_at' => now()]);

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('enable', $user->id)
            ->assertSet('status', 'Pengguna berhasil diaktifkan kembali.');

        $this->assertNull($user->refresh()->disabled_at);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_admin_cannot_disable_self(): void
    {
        // Abort 403 menghentikan aksi sebelum mutasi; Livewire menelan exception.
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('disable', $this->admin->id);

        $this->assertNull($this->admin->refresh()->disabled_at);
    }

    public function test_admin_can_delete_user_without_documents(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('destroy', $user->id)
            ->assertSet('status', 'Pengguna berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'admin.user_deleted',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_anonymizes_user_with_data(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'jabatan' => 'Kepala Bagian',
            'unit_kerja' => 'Sekretariat',
            'instansi' => 'Dinas Contoh',
        ])->save();
        $document = Document::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('destroy', $user->id)
            ->assertSet('status', 'Pengguna dianonimkan. Dokumen & verifikasi tetap utuh.');

        $user->refresh();
        $this->assertSame('Pengguna Dihapus', $user->name);
        $this->assertStringStartsWith('deleted+', $user->email);
        $this->assertNull($user->nik);
        $this->assertNull($user->jabatan);
        $this->assertNull($user->unit_kerja);
        $this->assertNull($user->instansi);
        $this->assertNotNull($user->disabled_at);

        // Dokumen tetap utuh (verifikasi tidak rusak).
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'admin.user_anonymized',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_anonymized_signer_document_still_verifies_with_name_snapshot(): void
    {
        [$signer, $document, $signature] = $this->makeSignedDocument();
        $signature->refresh();
        $originalName = $signature->signer_name;

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('destroy', $signer->id);

        // Nama user dihapus, tapi snapshot nama penandatangan tetap tersimpan.
        $this->assertSame('Pengguna Dihapus', $signer->refresh()->name);
        $this->assertSame($originalName, $signature->fresh()->signer_name);

        $response = $this->get('/verify/'.$signature->verification_code);
        $response->assertOk();
        $response->assertSee('VALID');
        $response->assertSee($originalName);
        $response->assertDontSee('Pengguna Dihapus');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $originalEmail = $this->admin->email;

        // Abort 403 menghentikan aksi sebelum mutasi; Livewire menelan exception.
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->call('destroy', $this->admin->id);

        $this->assertSame($originalEmail, $this->admin->refresh()->email);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $signer = User::factory()->create();
        $target = User::factory()->create();

        // Halaman tidak dapat diakses non-admin (middleware route).
        $this->actingAs($signer)->get(route('admin.users'))->assertForbidden();

        // Aksi mutasi juga diblokir di level komponen (defense-in-depth).
        Livewire::actingAs($signer)
            ->test(UsersPage::class)
            ->call('disable', $target->id);

        $this->assertNull($target->refresh()->disabled_at);
    }

    public function test_users_page_shows_status_and_actions(): void
    {
        User::factory()->create(['disabled_at' => now()]);
        User::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.users'));

        $response->assertOk();
        $response->assertSee('Buat Pengguna');
        $response->assertSee('Nonaktif');
        $response->assertSee('Aktif');
        $response->assertSee('Nonaktifkan');
    }

    public function test_user_management_is_reachable_from_admin_ui(): void
    {
        $dashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee(route('admin.users'), false);
        $dashboard->assertSee('User Management');

        $users = $this->actingAs($this->admin)->get(route('admin.users'));
        $users->assertOk();
        $users->assertSee('User Management');
        $users->assertSee('Log Audit');

        $signer = User::factory()->create();
        $this->actingAs($signer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_create_operator_with_company(): void
    {
        $company = Company::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Operator Baru')
            ->set('email', 'op@example.com')
            ->set('nik', '3275012345670040')
            ->set('password', 'password123')
            ->set('role', 'operator')
            ->set('companyId', $company->id)
            ->call('store');

        $user = User::query()->where('email', 'op@example.com')->firstOrFail();
        $this->assertSame('operator', $user->role->value);
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->isCompanyOperator());
    }

    public function test_admin_can_create_signer_with_company(): void
    {
        $company = Company::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Signer Perusahaan')
            ->set('email', 'signerc@example.com')
            ->set('nik', '3275012345670041')
            ->set('password', 'password123')
            ->set('role', 'signer')
            ->set('companyId', $company->id)
            ->call('store');

        $user = User::query()->where('email', 'signerc@example.com')->firstOrFail();
        $this->assertSame('signer', $user->role->value);
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->isCompanySigner());
    }

    public function test_operator_requires_company(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Op Tanpa Perusahaan')
            ->set('email', 'op2@example.com')
            ->set('nik', '3275012345670042')
            ->set('password', 'password123')
            ->set('role', 'operator')
            ->call('store')
            ->assertHasErrors('companyId');
    }

    public function test_admin_role_ignores_company(): void
    {
        $company = Company::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(UsersPage::class)
            ->set('name', 'Admin Dengan Perusahaan')
            ->set('email', 'adminc@example.com')
            ->set('nik', '3275012345670043')
            ->set('password', 'password123')
            ->set('role', 'admin')
            ->set('companyId', $company->id)
            ->call('store');

        $user = User::query()->where('email', 'adminc@example.com')->firstOrFail();
        $this->assertSame('admin', $user->role->value);
        $this->assertNull($user->company_id);
    }

    public function test_users_page_shows_company_column(): void
    {
        $company = Company::factory()->create(['name' => 'PT Kolom Uji']);
        User::factory()->create(['role' => UserRole::Signer, 'company_id' => $company->id]);
        User::factory()->create(['role' => UserRole::Signer]);

        $response = $this->actingAs($this->admin)->get(route('admin.users'));

        $response->assertOk();
        $response->assertSee('Perusahaan');
        $response->assertSee('PT Kolom Uji');
        $response->assertSee('Perorangan');
    }
}
