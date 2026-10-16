<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;

/**
 * Pencatat jejak audit permanen (BUSINESS-RULES §5): setiap aksi penting
 * (unggah, aktivasi kunci, penandatanganan, verifikasi, unduh) dicatat
 * lengkap dengan waktu UTC, IP address, dan User-Agent pelaku.
 */
final class AuditService
{
    public function log(
        ?Document $document,
        ?User $user,
        string $event,
        array $payload = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'document_id' => $document?->id,
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent(),
            'payload' => $payload === [] ? null : $payload,
        ]);
    }
}
