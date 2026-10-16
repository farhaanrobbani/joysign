<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Contracts\CryptoServiceInterface;
use App\Contracts\PdfSignerInterface;
use App\Enums\AssignmentStatus;
use App\Enums\DocumentStatus;
use App\Exceptions\CryptoException;
use App\Exceptions\PdfException;
use App\Exceptions\SigningException;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Pdf\SignaturePositionDTO;
use App\Services\Pdf\SignatureVisualDTO;
use App\Services\Qr\QrCodeService;
use App\Services\Verification\VerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orkestrasi penandatanganan kriptografis multi-penandatangan (co-sign):
 *
 * 1. Validasi penandatangan punya assignment pending & dokumen draft/parsial.
 * 2. Sertifikat aktif penandatangan wajib ada.
 * 3. Visual (QR + logo perusahaan opsional) dibubuhkan ke PDF berjalan
 *    (original untuk penandatangan pertama, PDF hasil penandatangan sebelumnya
 *    untuk penandatangan berikutnya).
 * 4. Hash dokumen ASLI ditandatangani private key penandatangan ( tiap tanda tangan
 *    menutup konten asli yang sama).
 * 5. Assignment ditandai signed; status dokumen menjadi partially_signed bila masih
 *    ada yang pending, atau signed bila semua selesai.
 */
final class DocumentSignerService
{
    public function __construct(
        private CryptoServiceInterface $crypto,
        private PdfSignerInterface $pdfSigner,
        private QrCodeService $qrCode,
        private AuditService $audit,
    ) {}

    /**
     * @throws CryptoException Jika passphrase salah.
     * @throws PdfException Jika PDF/posisi tidak valid.
     * @throws SigningException Jika prasyarat bisnis tidak terpenuhi.
     */
    public function sign(
        Document $document,
        User $signer,
        SignaturePositionDTO $position,
        string $passphrase,
    ): DocumentSignature {
        $assignment = $document->assignments()
            ->where('signer_id', $signer->id)
            ->where('status', AssignmentStatus::Pending->value)
            ->first();

        if ($assignment === null) {
            throw SigningException::notAssignedToSign();
        }

        if (! in_array($document->status, [DocumentStatus::Draft, DocumentStatus::PartiallySigned], true)) {
            throw SigningException::documentNotDraft();
        }

        $certificate = $signer->activeCertificate();

        if ($certificate === null) {
            throw SigningException::certificateRequired();
        }

        $originalHash = $document->original_hash;

        // Penandatangan pertama menandatangani PDF asli; berikutnya di atas PDF berjalan.
        $sourceRelativePath = $document->signed_path ?? $document->original_path;
        $sourceAbsolute = Storage::disk('documents')->path($sourceRelativePath);

        $verificationCode = VerificationCode::generate();
        $verificationUrl = route('verify.show', ['code' => $verificationCode]);

        $qrRelativePath = 'qrcodes/'.$verificationCode.'.png';
        Storage::disk('temp')->makeDirectory('qrcodes');
        $qrAbsolutePath = Storage::disk('temp')->path($qrRelativePath);
        $this->qrCode->save($verificationUrl, $qrAbsolutePath);

        $signedRelativePath = 'signed/'.(string) Str::ulid().'.pdf';
        Storage::disk('documents')->makeDirectory('signed');
        $signedAbsolutePath = Storage::disk('documents')->path($signedRelativePath);

        $footerToken = $document->signatures()
            ->orderBy('signed_at')
            ->orderBy('id')
            ->exists()
            ? null
            : VerificationCode::display($verificationCode);

        try {
            $embeddedPath = $this->pdfSigner->embedVisualSignature(
                $sourceAbsolute,
                $position,
                new SignatureVisualDTO(
                    qrCodePath: $qrAbsolutePath,
                    logoImagePath: $this->resolveLogoPath($signer),
                    verificationToken: $footerToken,
                ),
                $signedAbsolutePath,
            );

            $cryptographicSignature = $this->crypto->sign(
                $originalHash,
                $certificate->encrypted_private_key,
                $passphrase,
            );

            $signedHash = $this->pdfSigner->calculatePdfHash($embeddedPath);
        } catch (Throwable $exception) {
            Storage::disk('documents')->delete($signedRelativePath);
            Storage::disk('temp')->delete($qrRelativePath);

            throw $exception;
        }

        Storage::disk('temp')->delete($qrRelativePath);

        return DB::transaction(function () use (
            $document,
            $signer,
            $assignment,
            $position,
            $verificationCode,
            $signedRelativePath,
            $signedHash,
            $cryptographicSignature,
        ): DocumentSignature {
            $assignment->forceFill([
                'status' => AssignmentStatus::Signed,
                'signed_at' => now(),
            ])->save();

            $pendingCount = $document->assignments()
                ->where('status', AssignmentStatus::Pending->value)
                ->count();

            $document->forceFill([
                'signed_path' => $signedRelativePath,
                'signed_hash' => $signedHash,
                'status' => $pendingCount > 0
                    ? DocumentStatus::PartiallySigned
                    : DocumentStatus::Signed,
            ])->save();

            $signature = DocumentSignature::query()->create([
                'document_id' => $document->id,
                'user_id' => $signer->id,
                'signer_name' => $signer->name,
                'verification_code' => $verificationCode,
                'position' => $assignment->position,
                'page_number' => $position->pageNumber,
                'coord_x' => $position->x,
                'coord_y' => $position->y,
                'width' => $position->width,
                'height' => $position->height,
                'cryptographic_signature' => $cryptographicSignature,
            ]);

            $this->audit->log($document, $signer, 'document.signed', [
                'verification_code' => $verificationCode,
                'page_number' => $position->pageNumber,
                'original_hash' => $document->original_hash,
                'signed_hash' => $signedHash,
                'assignment_id' => $assignment->id,
                'pending_remaining' => $pendingCount,
            ]);

            return $signature;
        });
    }

    /**
     * Path absolut logo perusahaan milik penandatangan bila sudah diunggah
     * operator & file ada di disk local; null bila belum ada (visual hanya QR).
     * Tanpa fallback ke logo global situs.
     */
    private function resolveLogoPath(User $signer): ?string
    {
        $relativePath = $signer->company?->logo_path;

        if ($relativePath === null || ! Storage::disk('local')->exists($relativePath)) {
            return null;
        }

        return Storage::disk('local')->path($relativePath);
    }
}
