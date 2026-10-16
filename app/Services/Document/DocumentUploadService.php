<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Contracts\PdfSignerInterface;
use App\Enums\AssignmentStatus;
use App\Exceptions\SigningException;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Unggah dokumen PDF draft (FR-02 + BUSINESS-RULES §2): maksimal 20 MB,
 * PDF 1.4–2.0, hash SHA-256 dihitung sejak awal dan diikat pada dokumen.
 *
 * Dokumen perorangan otomatis "ditugaskan ke diri sendiri" (self-assignment)
 * agar alur penandatanganan seragam lewat mesin assignment. Dokumen perusahaan
 * (operator) tidak di-self-assign — penandatangan ditentukan terpisah.
 */
final class DocumentUploadService
{
    public function __construct(
        private PdfSignerInterface $pdfSigner,
        private AuditService $audit,
    ) {}

    public function upload(User $user, UploadedFile $file, string $title, ?Company $company = null): Document
    {
        $filename = (string) Str::ulid().'.pdf';
        $relativePath = $file->storeAs('drafts', $filename, 'documents');
        $absolutePath = Storage::disk('documents')->path($relativePath);

        try {
            $originalHash = $this->pdfSigner->calculatePdfHash($absolutePath);
        } catch (Throwable) {
            Storage::disk('documents')->delete($relativePath);

            throw SigningException::fileOperationFailed();
        }

        $document = Document::query()->create([
            'user_id' => $user->id,
            'company_id' => $company?->id,
            'title' => $title,
            'original_path' => $relativePath,
            'original_hash' => $originalHash,
        ]);

        if ($company === null) {
            $document->assignments()->create([
                'signer_id' => $user->id,
                'assigned_by' => $user->id,
                'status' => AssignmentStatus::Pending,
            ]);
        }

        $this->audit->log($document, $user, 'document.uploaded', [
            'title' => $title,
            'original_hash' => $originalHash,
            'size_bytes' => $file->getSize(),
            'company_id' => $company?->id,
        ]);

        return $document;
    }
}
