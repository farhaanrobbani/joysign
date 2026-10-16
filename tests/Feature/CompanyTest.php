<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Company\DashboardPage;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->user = User::factory()->create();
    }

    private function makeOperator(): Company
    {
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Operator])->save();

        return $company;
    }

    public function test_user_without_company_sees_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertSee('Buat Perusahaan');
        $response->assertSee('Nama Perusahaan');
    }

    public function test_user_can_create_company_and_becomes_operator(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('name', 'PT Maju Jaya')
            ->call('createCompany')
            ->assertSet('status', 'Perusahaan berhasil dibuat. Anda menjadi operator.');

        $this->user->refresh();
        $this->assertNotNull($this->user->company_id);
        $this->assertSame(UserRole::Operator, $this->user->role);
        $this->assertSame('PT Maju Jaya', $this->user->company->name);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.created',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_in_company_cannot_create_another(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();

        // Abort 403 menghentikan aksi sebelum mutasi; Livewire menelan exception.
        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('createCompany');

        $this->assertSame($company->id, $this->user->refresh()->company_id);
        $this->assertSame('signer', $this->user->role->value);
    }

    public function test_company_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('name', '')
            ->call('createCompany')
            ->assertHasErrors('name');
    }

    public function test_operator_sees_members_and_add_form(): void
    {
        $company = $this->makeOperator();

        $response = $this->actingAs($this->user)->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertSee($company->name);
        $response->assertSee('Tambah Penandatangan');
        $response->assertSee('Operator');
    }

    public function test_signer_member_does_not_see_add_form(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();

        $response = $this->actingAs($this->user)->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertSee('Penandatangan');
        $response->assertDontSee('Tambah Penandatangan');
    }

    public function test_operator_can_add_signer_member(): void
    {
        $company = $this->makeOperator();

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('memberName', 'Penandatangan Satu')
            ->set('memberEmail', 'signer1@example.com')
            ->set('memberNik', '3275012345670010')
            ->set('memberPassword', 'rahasia123')
            ->call('addMember')
            ->assertSet('status', 'Penandatangan berhasil ditambahkan.');

        $member = User::query()->where('email', 'signer1@example.com')->firstOrFail();
        $this->assertSame($company->id, $member->company_id);
        $this->assertSame(UserRole::Signer, $member->role);
        $this->assertSame('signer', $member->role->value);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.member_added',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_non_operator_cannot_add_member(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('memberName', 'X')
            ->set('memberEmail', 'x@example.com')
            ->set('memberNik', '3275012345670011')
            ->set('memberPassword', 'rahasia123')
            ->call('addMember');

        $this->assertNull(User::query()->where('email', 'x@example.com')->first());
    }

    public function test_add_member_rejects_duplicate_email_and_nik(): void
    {
        $this->makeOperator();

        User::factory()->create(['email' => 'taken@example.com', 'nik' => '3275012345670012']);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('memberName', 'Dup')
            ->set('memberEmail', 'taken@example.com')
            ->set('memberNik', '3275012345670012')
            ->set('memberPassword', 'rahasia123')
            ->call('addMember')
            ->assertHasErrors(['memberEmail', 'memberNik']);
    }

    public function test_operator_can_remove_member(): void
    {
        $company = $this->makeOperator();

        $member = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('removeMember', $member->id)
            ->assertSet('status', 'Anggota dikeluarkan dari perusahaan.');

        $this->assertNull($member->refresh()->company_id);
        $this->assertSame(UserRole::Signer, $member->role);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.member_removed',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_operator_cannot_remove_self(): void
    {
        $this->makeOperator();

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('removeMember', $this->user->id);

        $this->assertNotNull($this->user->refresh()->company_id);
    }

    public function test_non_operator_cannot_remove_member(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();

        $member = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('removeMember', $member->id);

        $this->assertSame($company->id, $member->refresh()->company_id);
    }

    public function test_nav_shows_company_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertSee(route('company.dashboard'), false);
        $response->assertSee('Perusahaan');
    }

    public function test_operator_can_attach_existing_user(): void
    {
        $company = $this->makeOperator();

        $existing = User::factory()->create(['role' => UserRole::Signer]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('email', $existing->email)
            ->call('attachMember')
            ->assertSet('status', 'Anggota berhasil dilampirkan ke perusahaan.');

        $existing->refresh();
        $this->assertSame($company->id, $existing->company_id);
        $this->assertSame(UserRole::Signer, $existing->role);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.member_added',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_attach_rejects_unknown_email(): void
    {
        $this->makeOperator();

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('email', 'tidakada@example.com')
            ->call('attachMember')
            ->assertHasErrors('email');
    }

    public function test_attach_rejects_user_already_in_company(): void
    {
        $this->makeOperator();

        $other = Company::factory()->create();
        $member = User::factory()->create([
            'role' => UserRole::Signer,
            'company_id' => $other->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('email', $member->email)
            ->call('attachMember')
            ->assertHasErrors('email');

        $this->assertSame($other->id, $member->refresh()->company_id);
    }

    public function test_attach_rejects_admin_user(): void
    {
        $this->makeOperator();

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('email', $admin->email)
            ->call('attachMember')
            ->assertHasErrors('email');

        $this->assertNull($admin->refresh()->company_id);
    }

    public function test_non_operator_cannot_attach_member(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();

        $existing = User::factory()->create(['role' => UserRole::Signer]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('email', $existing->email)
            ->call('attachMember');

        $this->assertNull($existing->refresh()->company_id);
    }

    public function test_operator_can_delete_signer_member_without_data(): void
    {
        $this->makeOperator();

        $member = User::factory()->create([
            'company_id' => $this->user->company_id,
            'role' => UserRole::Signer,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('destroyMember', $member->id)
            ->assertSet('status', 'Anggota berhasil dihapus.');

        $this->assertNull(User::query()->find($member->id));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.member_deleted',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_operator_anonymizes_signer_member_with_data(): void
    {
        $this->makeOperator();

        $member = User::factory()->create([
            'company_id' => $this->user->company_id,
            'role' => UserRole::Signer,
        ]);
        $document = Document::factory()->create(['user_id' => $member->id]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('destroyMember', $member->id)
            ->assertSet('status', 'Anggota dianonimkan. Dokumen & verifikasi tetap utuh.');

        $member->refresh();
        $this->assertSame('Pengguna Dihapus', $member->name);
        $this->assertNotNull($member->disabled_at);
        $this->assertNull($member->company_id);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'user_id' => $member->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.member_anonymized',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_operator_cannot_delete_self(): void
    {
        $this->makeOperator();

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('destroyMember', $this->user->id);

        $this->assertNotNull(User::query()->find($this->user->id));
    }

    public function test_operator_cannot_delete_other_operator(): void
    {
        $company = $this->makeOperator();

        $other = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Operator,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('destroyMember', $other->id);

        $this->assertNotNull(User::query()->find($other->id));
    }

    public function test_operator_cannot_delete_member_outside_company(): void
    {
        $this->makeOperator();

        $outsider = User::factory()->create(['role' => UserRole::Signer]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('destroyMember', $outsider->id);

        $this->assertNotNull(User::query()->find($outsider->id));
    }

    public function test_non_operator_cannot_delete_member(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();

        $member = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('destroyMember', $member->id);

        $this->assertNotNull(User::query()->find($member->id));
    }

    public function test_operator_can_upload_company_logo(): void
    {
        $company = $this->makeOperator();
        $file = UploadedFile::fake()->image('logo.png', 40, 40);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('logo', $file)
            ->call('saveLogo')
            ->assertSet('status', 'Logo perusahaan berhasil diperbarui.');

        $company->refresh();
        $this->assertNotNull($company->logo_path);
        Storage::disk('local')->assertExists($company->logo_path);

        // Route preview melayani logo perusahaan (disk local).
        $this->actingAs($this->user)->get(route('company.logo'))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.logo_updated',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_non_operator_cannot_upload_logo(): void
    {
        $company = Company::factory()->create();
        $this->user->forceFill(['company_id' => $company->id, 'role' => UserRole::Signer])->save();
        $file = UploadedFile::fake()->image('logo.png', 40, 40);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('logo', $file)
            ->call('saveLogo');

        // Guard 403 menahan aksi: logo tidak berubah.
        $this->assertNull($company->refresh()->logo_path);
    }

    public function test_invalid_company_logo_rejected(): void
    {
        $company = $this->makeOperator();
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('logo', $file)
            ->call('saveLogo')
            ->assertHasErrors('logo');

        $this->assertNull($company->refresh()->logo_path);
    }

    public function test_operator_can_remove_company_logo(): void
    {
        $company = $this->makeOperator();
        $file = UploadedFile::fake()->image('logo.png', 40, 40);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->set('logo', $file)
            ->call('saveLogo');

        $path = $company->refresh()->logo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        Livewire::actingAs($this->user)
            ->test(DashboardPage::class)
            ->call('removeLogo')
            ->assertSet('status', 'Logo perusahaan dihapus.');

        $this->assertNull($company->refresh()->logo_path);
        Storage::disk('local')->assertMissing($path);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'company.logo_removed',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_company_logo_route_404_when_not_uploaded(): void
    {
        $this->makeOperator();

        $this->actingAs($this->user)->get(route('company.logo'))->assertNotFound();
    }
}
