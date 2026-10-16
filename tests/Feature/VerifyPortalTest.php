<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use App\Services\Document\DocumentSignerService;
use App\Services\Document\DocumentUploadService;
use App\Services\Pdf\SignaturePositionDTO;
use App\Services\Verification\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class VerifyPortalTest extends TestCase
{
    use CreatesTestPdf, RefreshDatabase;

    private const PASSPHRASE = 'passphrase-rahasia';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');
    }

    public function test_verify_portal_is_accessible_without_login(): void
    {
        $this->get(route('verify.index'))->assertOk()->assertSee('Portal Verifikasi Dokumen');
    }

    public function test_verify_by_code_returns_valid_for_signed_document(): void
    {
        [, $document, $signature] = $this->makeSignedDocument();

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSee('VALID');
        $response->assertSee($document->title);
        $response->assertSee('Token');
        $response->assertSee(VerificationCode::display($signature->verification_code));
    }

    public function test_verify_by_code_returns_not_found_for_unknown_code(): void
    {
        $response = $this->get(route('verify.show', ['code' => str_repeat('a', 64)]));

        $response->assertOk();
        $response->assertSee('NOT FOUND');
    }

    public function test_verify_by_code_returns_revoked_for_revoked_document(): void
    {
        [, $document, $signature] = $this->makeSignedDocument();

        $document->forceFill([
            'status' => DocumentStatus::Revoked,
            'revoked_at' => now(),
        ])->save();

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSee('REVOKED');
    }

    public function test_verify_by_code_returns_expired_for_expired_certificate(): void
    {
        [$user, , $signature] = $this->makeSignedDocument();

        $user->activeCertificate()->forceFill(['expires_at' => now()->subDay()])->save();

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSee('EXPIRED');
    }

    public function test_verify_by_code_returns_tampered_when_signature_invalid(): void
    {
        [, , $signature] = $this->makeSignedDocument();

        $signature->forceFill([
            'cryptographic_signature' => base64_encode(random_bytes(256)),
        ])->save();

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSee('TAMPERED');
    }

    public function test_verification_is_audited(): void
    {
        [, , $signature] = $this->makeSignedDocument();

        $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'document.verified',
            'user_id' => null,
        ]);
    }

    public function test_verify_page_shows_verification_code_before_identity_block(): void
    {
        [$user, , $signature] = $this->makeSignedDocument();

        $user->forceFill([
            'jabatan' => 'Kepala Bagian',
            'unit_kerja' => 'Sekretariat Umum',
            'instansi' => 'Dinas Contoh',
        ])->save();

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Token',
            VerificationCode::display($signature->verification_code),
            'Judul Dokumen',
            'Penandatangan',
            'Kepala Bagian',
            'Sekretariat Umum',
            'Dinas Contoh',
            'Waktu Tanda Tangan',
        ]);
        $response->assertDontSee('Hash SHA-256');
    }

    public function test_verify_page_hides_identity_fields_when_empty(): void
    {
        [, , $signature] = $this->makeSignedDocument();

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSee('Penandatangan');
        $response->assertDontSee('Jabatan');
        $response->assertDontSee('Unit Kerja');
        $response->assertDontSee('Instansi');
    }

    public function test_verify_page_shows_all_signatories_for_cosigned_document(): void
    {
        [$signerA, $signerB, , $signatureB] = $this->makeCosignedDocument();

        $response = $this->get(route('verify.show', ['code' => $signatureB->verification_code]));

        $response->assertOk();
        $response->assertSee('VALID');
        $response->assertSee('Penandatangan 1');
        $response->assertSee('Penandatangan 2');
        $response->assertSeeInOrder([$signerA->name, $signerB->name]);
        $response->assertSee('Kepala Seksi');
        $response->assertSee('Staf Pelaksana');
    }

    public function test_verify_document_route_serves_signed_pdf_inline(): void
    {
        [, , $signature] = $this->makeSignedDocument();

        $response = $this->get(route('verify.document', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader(
            'Content-Disposition',
            'inline; filename="dokumen-tte-'.substr($signature->verification_code, 0, 8).'.pdf"',
        );

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($file->getPathname()));
    }

    public function test_verify_document_route_returns_404_for_unknown_code(): void
    {
        $this->get(route('verify.document', ['code' => str_repeat('a', 64)]))->assertNotFound();
    }

    public function test_verify_document_view_is_audited(): void
    {
        [, , $signature] = $this->makeSignedDocument();

        $this->get(route('verify.document', ['code' => $signature->verification_code]))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'document.viewed',
            'user_id' => null,
        ]);
    }

    public function test_verify_accepts_dashed_and_lowercase_input(): void
    {
        [, , $signature] = $this->makeSignedDocument();
        $signature->update(['verification_code' => 'ABCDEFGHIJKL']);

        $this->get(route('verify.show', ['code' => 'ABCD-EFGH-IJKL']))
            ->assertOk()
            ->assertSee('VALID');

        $this->get(route('verify.show', ['code' => 'abcdefghijkl']))
            ->assertOk()
            ->assertSee('VALID');
    }

    /**
     * @return array{0: User, 1: Document, 2: DocumentSignature}
     */
    private function makeSignedDocument(): array
    {
        $user = User::factory()->create();
        app(CertificateService::class)->activate($user, self::PASSPHRASE);

        $document = app(DocumentUploadService::class)->upload(
            $user,
            UploadedFile::fake()->createWithContent('draft.pdf', $this->createTestPdfBinary()),
            'Dokumen Uji Verifikasi',
        );
        $document->refresh();

        $signature = app(DocumentSignerService::class)->sign(
            $document,
            $user,
            new SignaturePositionDTO(pageNumber: 1, x: 72.0, y: 700.0, width: 150.0, height: 50.0),
            self::PASSPHRASE,
        );

        return [$user, $document, $signature];
    }

    /**
     * @return array{0: User, 1: User, 2: Document, 3: DocumentSignature}
     */
    private function makeCosignedDocument(): array
    {
        $owner = User::factory()->create();
        app(CertificateService::class)->activate($owner, self::PASSPHRASE);

        $signerA = User::factory()->create();
        $signerA->forceFill([
            'jabatan' => 'Kepala Seksi',
            'unit_kerja' => 'Bidang Pelayanan',
            'instansi' => 'Instansi A',
        ])->save();
        app(CertificateService::class)->activate($signerA, self::PASSPHRASE);

        $signerB = User::factory()->create();
        $signerB->forceFill([
            'jabatan' => 'Staf Pelaksana',
            'unit_kerja' => 'Bidang Persyaratan',
            'instansi' => 'Instansi B',
        ])->save();
        app(CertificateService::class)->activate($signerB, self::PASSPHRASE);

        $document = app(DocumentUploadService::class)->upload(
            $owner,
            UploadedFile::fake()->createWithContent('draft.pdf', $this->createTestPdfBinary()),
            'Dokumen CoSign Verifikasi',
        );
        $document->refresh();

        foreach ([$signerA, $signerB] as $signer) {
            $document->assignments()->create([
                'signer_id' => $signer->id,
                'assigned_by' => $owner->id,
                'status' => AssignmentStatus::Pending,
            ]);
        }

        $service = app(DocumentSignerService::class);

        $first = $service->sign(
            $document,
            $signerA,
            new SignaturePositionDTO(pageNumber: 1, x: 72.0, y: 700.0, width: 150.0, height: 50.0),
            self::PASSPHRASE,
        );
        $second = $service->sign(
            $document->refresh(),
            $signerB,
            new SignaturePositionDTO(pageNumber: 1, x: 72.0, y: 620.0, width: 150.0, height: 50.0),
            self::PASSPHRASE,
        );

        $first->forceFill(['signed_at' => now()->subMinute()])->save();
        $second->forceFill(['signed_at' => now()])->save();

        return [$signerA, $signerB, $document->refresh(), $second];
    }
}
