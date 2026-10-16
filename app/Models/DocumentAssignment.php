<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentStatus;
use Database\Factories\DocumentAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'signer_id', 'assigned_by', 'status', 'position'])]
class DocumentAssignment extends Model
{
    /** @use HasFactory<DocumentAssignmentFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'signed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return BelongsTo<User, $this> */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isPending(): bool
    {
        return $this->status === AssignmentStatus::Pending;
    }
}
