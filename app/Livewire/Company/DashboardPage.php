<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Company\CompanyService;
use App\Services\User\UserDeletionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Halaman perusahaan (full-page Livewire): mode "create" bila pengguna belum
 * punya perusahaan, mode "show" bila sudah (logo perusahaan, detail anggota +
 * tambah/lampir/keluarkan/hapus penandatangan). Semua mutasi jadi aksi Livewire.
 */
#[Layout('layouts.app')]
class DashboardPage extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $memberName = '';

    public string $memberEmail = '';

    public string $memberNik = '';

    public string $memberPassword = '';

    /** Email akun terdaftar yang dilampirkan (kunci error 'email' = service). */
    public string $email = '';

    /** File logo perusahaan sementara (TemporaryUploadedFile). */
    public $logo = null;

    public ?string $status = null;

    public function createCompany(): void
    {
        $user = auth()->user();
        abort_unless(! $user->inCompany() && $user->role !== UserRole::Admin, 403);

        $this->validate(['name' => ['required', 'string', 'max:255']]);

        app(CompanyService::class)->create($user, $this->name);

        $this->status = 'Perusahaan berhasil dibuat. Anda menjadi operator.';
        $this->reset('name');
        $this->resetValidation();
    }

    public function addMember(): void
    {
        $user = auth()->user();
        abort_unless($user->isCompanyOperator(), 403);

        $this->validate([
            'memberName' => ['required', 'string', 'max:255'],
            'memberEmail' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'memberNik' => ['required', 'string', 'regex:/^[0-9]{16}$/', 'unique:'.User::class.',nik'],
            'memberPassword' => ['required', 'string', 'min:8'],
        ], [
            'memberNik.regex' => 'NIK harus terdiri dari tepat 16 digit angka.',
            'memberNik.unique' => 'NIK ini sudah terdaftar pada akun lain.',
            'memberEmail.unique' => 'Email ini sudah terdaftar pada akun lain.',
        ]);

        app(CompanyService::class)->addSigner(
            $user,
            $this->memberName,
            $this->memberEmail,
            $this->memberNik,
            $this->memberPassword,
        );

        $this->status = 'Penandatangan berhasil ditambahkan.';
        $this->reset(['memberName', 'memberEmail', 'memberNik', 'memberPassword']);
        $this->resetValidation();
    }

    public function attachMember(): void
    {
        $user = auth()->user();
        abort_unless($user->isCompanyOperator(), 403);

        $this->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::exists(User::class, 'email')],
        ], [
            'email.exists' => 'Email tidak terdaftar pada sistem.',
        ]);

        // Service dapat melempar ValidationException (kunci 'email') bila
        // peran/sudah berperusahaan — diteruskan sebagai error validasi.
        app(CompanyService::class)->attachExistingMember($user, $this->email);

        $this->status = 'Anggota berhasil dilampirkan ke perusahaan.';
        $this->reset('email');
        $this->resetValidation();
    }

    public function saveLogo(): void
    {
        $user = auth()->user();
        abort_unless($user->isCompanyOperator(), 403);

        $this->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ]);

        $company = $user->company()->firstOrFail();

        if ($company->logo_path !== null && Storage::disk('local')->exists($company->logo_path)) {
            Storage::disk('local')->delete($company->logo_path);
        }

        $extension = $this->logo->extension() ?: 'png';
        $filename = 'logo-'.Str::ulid().'.'.$extension;
        $this->logo->storeAs('company-logos', $filename, 'local');

        $relativePath = 'company-logos/'.$filename;
        $company->update(['logo_path' => $relativePath]);

        app(AuditService::class)->log(null, $user, 'company.logo_updated', [
            'company_id' => $company->id,
            'logo_path' => $relativePath,
        ]);

        $this->status = 'Logo perusahaan berhasil diperbarui.';
        $this->reset('logo');
        $this->resetValidation();
    }

    public function removeLogo(): void
    {
        $user = auth()->user();
        abort_unless($user->isCompanyOperator(), 403);

        $company = $user->company()->firstOrFail();

        if ($company->logo_path !== null && Storage::disk('local')->exists($company->logo_path)) {
            Storage::disk('local')->delete($company->logo_path);
        }

        $company->update(['logo_path' => null]);

        app(AuditService::class)->log(null, $user, 'company.logo_removed', [
            'company_id' => $company->id,
        ]);

        $this->status = 'Logo perusahaan dihapus.';
    }

    public function removeMember(int|string $memberId): void
    {
        $user = auth()->user();
        abort_unless($user->isCompanyOperator(), 403);

        $member = User::query()->findOrFail($memberId);
        abort_if($member->id === $user->id, 403, 'Tidak dapat mengeluarkan diri sendiri.');
        abort_unless($member->company_id === $user->company_id, 404);

        app(CompanyService::class)->removeMember($user, $member);

        $this->status = 'Anggota dikeluarkan dari perusahaan.';
    }

    public function destroyMember(int|string $memberId): void
    {
        $user = auth()->user();
        abort_unless($user->isCompanyOperator(), 403);

        $member = User::query()->findOrFail($memberId);
        abort_if($member->id === $user->id, 403, 'Tidak dapat menghapus diri sendiri.');
        abort_unless($member->company_id === $user->company_id, 404);
        abort_unless($member->role === UserRole::Signer, 403, 'Hanya penandatangan yang dapat dihapus.');

        $result = app(UserDeletionService::class)->remove($user, $member, 'company.member');

        $this->status = $result === 'anonymized'
            ? 'Anggota dianonimkan. Dokumen & verifikasi tetap utuh.'
            : 'Anggota berhasil dihapus.';
    }

    public function render()
    {
        $user = auth()->user();

        if (! $user->inCompany()) {
            $header = new HtmlString(
                '<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'
                .__('Buat Perusahaan').'</h2>'
            );

            return view('livewire.company.dashboard-page', ['mode' => 'create'])
                ->layout('layouts.app', ['header' => $header]);
        }

        $company = $user->company()->withCount('members')->firstOrFail();

        $members = $company->members()
            ->withCount(['documents', 'signatures', 'certificates'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $isOperator = $user->isCompanyOperator();
        $roleLabel = $isOperator ? __('Operator') : __('Penandatangan');

        $header = new HtmlString(
            '<div class="flex items-center justify-between">'
            .'<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'.e($company->name).'</h2>'
            .'<span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:text-indigo-300">'.$roleLabel.'</span>'
            .'</div>'
        );

        return view('livewire.company.dashboard-page', [
            'mode' => 'show',
            'company' => $company,
            'members' => $members,
            'isOperator' => $isOperator,
        ])->layout('layouts.app', ['header' => $header]);
    }
}
