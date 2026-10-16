<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Pembuatan perusahaan & pengelolaan anggota (operator / penandatangan).
 * Sertifikat digital tetap melekat per-orangan, bukan perusahaan.
 */
final class CompanyService
{
    public function __construct(private AuditService $audit) {}

    public function create(User $creator, string $name): Company
    {
        $company = Company::query()->create([
            'name' => $name,
            'created_by' => $creator->id,
        ]);

        $creator->forceFill([
            'company_id' => $company->id,
            'role' => UserRole::Operator,
        ])->save();

        $this->audit->log(null, $creator, 'company.created', [
            'company_id' => $company->id,
            'name' => $name,
        ]);

        return $company;
    }

    public function addSigner(User $operator, string $name, string $email, string $nik, string $password): User
    {
        $member = User::query()->create([
            'name' => $name,
            'email' => $email,
            'nik' => $nik,
            'password' => Hash::make($password),
        ]);

        $member->forceFill([
            'company_id' => $operator->company_id,
            'role' => UserRole::Signer,
        ])->save();

        $this->audit->log(null, $operator, 'company.member_added', [
            'company_id' => $operator->company_id,
            'member_id' => $member->id,
            'member_email' => $member->email,
            'source' => 'created',
        ]);

        return $member;
    }

    /**
     * Melampirkan pengguna yang sudah terdaftar (belum punya perusahaan)
     * ke perusahaan operator sebagai penandatangan.
     */
    public function attachExistingMember(User $operator, string $email): User
    {
        $member = User::query()->where('email', $email)->first();

        if ($member === null) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar pada sistem.',
            ]);
        }

        if ($member->role !== UserRole::Signer) {
            throw ValidationException::withMessages([
                'email' => 'Hanya pengguna dengan peran penandatangan yang dapat dilampirkan.',
            ]);
        }

        if ($member->company_id !== null) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna sudah berada di perusahaan lain.',
            ]);
        }

        $member->forceFill([
            'company_id' => $operator->company_id,
            'role' => UserRole::Signer,
        ])->save();

        $this->audit->log(null, $operator, 'company.member_added', [
            'company_id' => $operator->company_id,
            'member_id' => $member->id,
            'member_email' => $member->email,
            'source' => 'existing',
        ]);

        return $member;
    }

    public function removeMember(User $operator, User $member): void
    {
        $member->forceFill([
            'company_id' => null,
        ])->save();

        $this->audit->log(null, $operator, 'company.member_removed', [
            'company_id' => $operator->company_id,
            'member_id' => $member->id,
            'member_email' => $member->email,
        ]);
    }
}
