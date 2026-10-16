<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use App\Services\Document\DocumentSignerService;
use App\Services\Document\DocumentUploadService;
use App\Services\Pdf\SignaturePositionDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait CreatesSignedDocument
{
    private const SIGNING_PASSPHRASE = 'passphrase-rahasia';

    /**
     * @return array{0: User, 1: Document, 2: DocumentSignature}
     */
    protected function makeSignedDocument(): array
    {
        $user = User::factory()->create();
        app(CertificateService::class)->activate($user, self::SIGNING_PASSPHRASE);

        $document = app(DocumentUploadService::class)->upload(
            $user,
            UploadedFile::fake()->createWithContent('draft.pdf', $this->createTestPdfBinary()),
            'Dokumen Uji Signing',
        );
        $document->refresh();

        $signature = app(DocumentSignerService::class)->sign(
            $document,
            $user,
            new SignaturePositionDTO(pageNumber: 1, x: 72.0, y: 700.0, width: 150.0, height: 50.0),
            self::SIGNING_PASSPHRASE,
        );

        return [$user, $document->refresh(), $signature];
    }

    protected function makeDraftDocumentFor(User $user): Document
    {
        return app(DocumentUploadService::class)->upload(
            $user,
            UploadedFile::fake()->createWithContent('draft.pdf', $this->createTestPdfBinary()),
            'Dokumen Draft',
        )->refresh();
    }

    protected function makeStorageFakes(): void
    {
        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');
    }
}
