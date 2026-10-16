<?php

declare(strict_types=1);

namespace App\Services\Verification;

use App\Contracts\CryptoServiceInterface;
use App\Enums\VerificationStatus;
use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Services\Audit\AuditService;

/**
 * Portal verifikasi publik (FR-04 + BUSINESS-RULES §4). Satu jalur lookup:
 * token 12 karakter (QR scan / ketik manual, dash/kase fleksibel, kode lama
 * 64 hex tetap diterima) — lookup DocumentSignature -> verifikasi integritas
 * kriptografis terhadap Public Key + cek status dokumen/sertifikat.
 *
 * Status akhir: VALID / TAMPERED / EXPIRED / REVOKED / NOT_FOUND.
 */
final class VerificationService
{
    public function __construct(
        private CryptoServiceInterface $crypto,
        private AuditService $audit,
    ) {}

    public function resolveSignature(string $code): ?DocumentSignature
    {
        $signature = DocumentSignature::query()
            ->where('verification_code', $code)
            ->first();

        if ($signature !== null) {
            return $signature;
        }

        return DocumentSignature::query()
            ->where('verification_code', VerificationCode::normalize($code))
            ->first();
    }

    public function verifyByCode(string $code): VerificationResult
    {
        $signature = $this->resolveSignature($code);

        if ($signature === null) {
            $this->audit->log(null, null, 'document.verified', [
                'lookup' => 'code',
                'code' => $code,
                'status' => VerificationStatus::NotFound->value,
            ]);

            return new VerificationResult(
                status: VerificationStatus::NotFound,
                message: 'Token tidak ditemukan dalam sistem.',
            );
        }

        $document = $signature->document;
        $certificate = $signature->signer?->activeCertificate();

        $result = $this->evaluate($document, $signature, $certificate);

        $this->audit->log($document, null, 'document.verified', [
            'lookup' => 'code',
            'code' => $code,
            'status' => $result->status->value,
        ]);

        return $result;
    }

    private function evaluate(
        Document $document,
        ?DocumentSignature $signature,
        ?DigitalCertificate $certificate,
    ): VerificationResult {
        if ($document->isRevoked()) {
            return new VerificationResult(
                status: VerificationStatus::Revoked,
                message: 'Dokumen telah dicabut (revoked) oleh pemilik atau sistem.',
                document: $document,
                signature: $signature,
                certificate: $certificate,
            );
        }

        if ($certificate === null || $certificate->isExpired()) {
            return new VerificationResult(
                status: VerificationStatus::Expired,
                message: 'Sertifikat digital penandatangan tidak aktif atau sudah kedaluwarsa.',
                document: $document,
                signature: $signature,
                certificate: $certificate,
            );
        }

        if ($signature === null) {
            return new VerificationResult(
                status: VerificationStatus::NotFound,
                message: 'Catatan tanda tangan tidak ditemukan untuk dokumen ini.',
                document: $document,
            );
        }

        $valid = $this->crypto->verify(
            $document->original_hash,
            $signature->cryptographic_signature,
            $certificate->public_key,
        );

        if (! $valid) {
            return new VerificationResult(
                status: VerificationStatus::Tampered,
                message: 'Tanda tangan digital tidak valid. Dokumen terindikasi dimanipulasi.',
                document: $document,
                signature: $signature,
                certificate: $certificate,
            );
        }

        return new VerificationResult(
            status: VerificationStatus::Valid,
            message: 'Dokumen ini telah tertandatangani oleh:',
            document: $document,
            signature: $signature,
            certificate: $certificate,
        );
    }
}
