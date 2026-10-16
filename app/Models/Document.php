<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'company_id', 'title', 'original_path', 'original_hash'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'created_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** @return HasMany<DocumentSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class);
    }

    /** @return HasMany<DocumentAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(DocumentAssignment::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isFullySigned(): bool
    {
        return $this->assignments()->count() > 0
            && $this->assignments()->where('status', AssignmentStatus::Pending->value)->count() === 0;
    }

    public function isSigned(): bool
    {
        return $this->status === DocumentStatus::Signed;
    }

    public function isRevoked(): bool
    {
        return $this->status === DocumentStatus::Revoked;
    }
}
