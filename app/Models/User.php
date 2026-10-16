<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'nik', 'jabatan', 'unit_kerja', 'instansi', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** @return HasMany<DigitalCertificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(DigitalCertificate::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<DocumentSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function activeCertificate(): ?DigitalCertificate
    {
        return $this->certificates()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('created_at')
            ->first();
    }

    public function hasActiveCertificate(): bool
    {
        return $this->activeCertificate() !== null;
    }

    public function inCompany(): bool
    {
        return $this->company_id !== null;
    }

    public function isCompanyOperator(): bool
    {
        return $this->inCompany() && $this->role === UserRole::Operator;
    }

    public function isCompanySigner(): bool
    {
        return $this->inCompany() && $this->role === UserRole::Signer;
    }

    public function isReadyToSign(): bool
    {
        return $this->hasActiveCertificate();
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    /** @param  Builder<self>  $query */
    public function scopeActive($query)
    {
        return $query->whereNull('disabled_at');
    }

    public function hasAnyDocuments(): bool
    {
        return $this->documents()->exists()
            || $this->signatures()->exists()
            || $this->certificates()->exists();
    }
}
