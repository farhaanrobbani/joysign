<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentSignatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'user_id',
    'signer_name',
    'verification_code',
    'page_number',
    'coord_x',
    'coord_y',
    'width',
    'height',
    'cryptographic_signature',
])]
class DocumentSignature extends Model
{
    /** @use HasFactory<DocumentSignatureFactory> */
    use HasFactory, HasUlids;

    public const CREATED_AT = 'signed_at';

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'coord_x' => 'float',
            'coord_y' => 'float',
            'width' => 'float',
            'height' => 'float',
            'signed_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'user_id');
    }
}
