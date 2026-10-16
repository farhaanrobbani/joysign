<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\User\UserDeletionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manajemen pengguna (full-page Livewire): pencarian, pagination, dan mutasi
 * (buat/nonaktifkan/aktifkan/hapus) sebagai aksi Livewire tanpa reload.
 */
#[Layout('layouts.admin')]
class UsersPage extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    public bool $creating = false;

    public string $name = '';

    public string $email = '';

    public string $nik = '';

    public string $jabatan = '';

    public string $unitKerja = '';

    public string $instansi = '';

    public string $password = '';

    public string $role = 'signer';

    public $companyId = null;

    public ?string $status = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function toggleCreating(): void
    {
        $this->creating = ! $this->creating;
        $this->resetValidation();
    }

    public function store(): void
    {
        $this->ensureAdmin();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'nik' => ['required', 'string', 'regex:/^[0-9]{16}$/', 'unique:'.User::class.',nik'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'unitKerja' => ['nullable', 'string', 'max:255'],
            'instansi' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'companyId' => [
                Rule::requiredIf(fn () => $this->role === UserRole::Operator->value),
                'nullable',
                'exists:companies,id',
            ],
        ], [
            'nik.regex' => 'NIK harus terdiri dari tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar pada akun lain.',
            'email.unique' => 'Email ini sudah terdaftar pada akun lain.',
            'companyId.required' => 'Perusahaan wajib dipilih untuk peran Operator.',
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nik' => $validated['nik'],
            'jabatan' => $validated['jabatan'] ?: null,
            'unit_kerja' => $validated['unitKerja'] ?: null,
            'instansi' => $validated['instansi'] ?: null,
            'password' => Hash::make($validated['password']),
        ]);

        $attributes = [
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ];

        if ($validated['role'] !== UserRole::Admin->value) {
            $attributes['company_id'] = $validated['companyId'] ?? null;
        }

        $user->forceFill($attributes)->save();

        app(AuditService::class)->log(null, auth()->user(), 'admin.user_created', [
            'created_user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'company_id' => $user->company_id,
        ]);

        $this->status = 'Pengguna berhasil dibuat.';
        $this->reset(['name', 'email', 'nik', 'jabatan', 'unitKerja', 'instansi', 'password', 'role', 'companyId']);
        $this->role = 'signer';
        $this->creating = false;
        $this->resetValidation();
    }

    public function disable(int|string $userId): void
    {
        $this->ensureAdmin();

        $user = User::query()->findOrFail($userId);
        abort_if($user->id === auth()->id(), 403, 'Tidak dapat menonaktifkan diri sendiri.');

        $user->forceFill(['disabled_at' => now()])->save();

        app(AuditService::class)->log(null, auth()->user(), 'admin.user_disabled', [
            'target_user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->status = 'Pengguna berhasil dinonaktifkan.';
    }

    public function enable(int|string $userId): void
    {
        $this->ensureAdmin();

        $user = User::query()->findOrFail($userId);
        $user->forceFill(['disabled_at' => null])->save();

        app(AuditService::class)->log(null, auth()->user(), 'admin.user_enabled', [
            'target_user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->status = 'Pengguna berhasil diaktifkan kembali.';
    }

    public function destroy(int|string $userId): void
    {
        $this->ensureAdmin();

        $user = User::query()->findOrFail($userId);
        abort_if($user->id === auth()->id(), 403, 'Tidak dapat menghapus diri sendiri.');

        $result = app(UserDeletionService::class)->remove(auth()->user(), $user, 'admin.user');

        $this->status = $result === 'anonymized'
            ? 'Pengguna dianonimkan. Dokumen & verifikasi tetap utuh.'
            : 'Pengguna berhasil dihapus.';
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->role === UserRole::Admin, 403);
    }

    public function render()
    {
        $query = User::query()->with('company')->withCount(['certificates', 'documents', 'signatures']);

        if ($this->q !== '') {
            $search = $this->q;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%"));
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.users-page', compact('users', 'companies'))
            ->layout('layouts.admin', [
                'header' => new HtmlString(
                    '<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'
                    .__('Manajemen Pengguna').'</h2>'
                ),
            ]);
    }
}
