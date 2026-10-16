<?php

declare(strict_types=1);

namespace App\Services\Verification;

use App\Enums\VerificationStatus;
use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\DocumentSignature;

/**
 * Hasil verifikasi publik (FR-04 + BUSINESS-RULES §4). Membawa status
 * akhir beserta metadata dokumen yang relevan untuk ditampilkan di portal.
 */
final readonly class VerificationResult
{
    public function __construct(
        public VerificationStatus $status,
        public ?string $message = null,
        public ?Document $document = null,
        public ?DocumentSignature $signature = null,
        public ?DigitalCertificate $certificate = null,
    ) {}
}
