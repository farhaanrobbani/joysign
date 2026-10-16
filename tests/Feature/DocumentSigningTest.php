<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CryptoServiceInterface;
use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\Setting;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use App\Services\Document\DocumentUploadService;
use App\Services\Verification\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestPdf;
use Tests\Concerns\ExtractsPdfText;
use Tests\TestCase;

class DocumentSigningTest extends TestCase
{
    use CreatesTestPdf, ExtractsPdfText, RefreshDatabase;

    private const PASSPHRASE = 'passphrase-rahasia';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');
    }

    public function test_full_signing_flow_produces_signed_document_and_audit_trail(): void
    {
        $user = $this->makeReadySigner();
        $document = $this->makeDraftDocument($user);

        $response = $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $response->assertRedirect(route('documents.show', $document));

        $document->refresh();

        $this->assertSame(DocumentStatus::Signed, $document->status);
        $this->assertStringStartsWith('signed/', (string) $document->signed_path);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $document->signed_hash);
        Storage::disk('documents')->assertExists($document->signed_path);

        $signature = DocumentSignature::query()->where('document_id', $document->id)->firstOrFail();

        $this->assertSame(1, $signature->page_number);
        $this->assertSame(72.0, $signature->coord_x);
        $this->assertSame(12, strlen($signature->verification_code));
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{12}$/', $signature->verification_code);
        $this->assertNotEmpty($signature->cryptographic_signature);
        $this->assertNotNull($signature->signed_at);

        $crypto = app(CryptoServiceInterface::class);
        $certificate = $user->activeCertificate();

        $this->assertTrue($crypto->verify(
            $document->original_hash,
            $signature->cryptographic_signature,
            $certificate->public_key,
        ));

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'event' => 'document.signed',
        ]);
    }

    public function test_signing_with_wrong_passphrase_keeps_document_draft(): void
    {
        $user = $this->makeReadySigner();
        $document = $this->makeDraftDocument($user);

        $response = $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => 'passphrase-salah-banget',
        ]);

        $response->assertSessionHasErrors('passphrase');

        $document->refresh();

        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertNull($document->signed_path);
        $this->assertSame(0, DocumentSignature::query()->where('document_id', $document->id)->count());
    }

    public function test_non_owner_cannot_sign_document(): void
    {
        $owner = $this->makeReadySigner();
        $document = $this->makeDraftDocument($owner);
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $response->assertForbidden();
        $this->assertSame(DocumentStatus::Draft, $document->refresh()->status);
    }

    public function test_signing_requires_active_certificate(): void
    {
        $user = User::factory()->create();
        $document = $this->makeDraftDocument($user);

        $response = $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $response->assertSessionHasErrors('sign');
        $this->assertSame(DocumentStatus::Draft, $document->refresh()->status);
    }

    public function test_already_signed_document_cannot_be_signed_again(): void
    {
        $user = $this->makeReadySigner();
        $document = $this->makeDraftDocument($user);

        $payload = [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ];

        $this->actingAs($user)->post(route('documents.sign', $document), $payload)->assertRedirect();

        $response = $this->actingAs($user)->post(route('documents.sign', $document), $payload);

        $response->assertSessionHasErrors('sign');
        $this->assertSame(1, DocumentSignature::query()->where('document_id', $document->id)->count());
    }

    public function test_owner_can_download_signed_document_with_audit(): void
    {
        $user = $this->makeReadySigner();
        $document = $this->makeDraftDocument($user);

        $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $response = $this->actingAs($user)->get(route('documents.download', $document));

        $response->assertOk();
        $response->assertDownload($document->title.'-signed.pdf');

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'event' => 'document.downloaded',
        ]);
    }

    public function test_draft_document_cannot_be_downloaded(): void
    {
        $user = User::factory()->create();
        $document = $this->makeDraftDocument($user);

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertNotFound();
    }

    private function makeReadySigner(): User
    {
        $user = User::factory()->create();
        app(CertificateService::class)->activate($user, self::PASSPHRASE);

        return $user;
    }

    public function test_signing_embeds_company_logo_beside_qr(): void
    {
        $company = Company::factory()->create();
        Storage::disk('local')->put('company-logos/test-logo.png', $this->pngBinary());
        $company->update(['logo_path' => 'company-logos/test-logo.png']);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);
        app(CertificateService::class)->activate($user, self::PASSPHRASE);
        $document = $this->makeDraftDocument($user);

        $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $document->refresh();
        Storage::disk('documents')->assertExists($document->signed_path);

        // QR + logo perusahaan = 2 image XObject.
        $this->assertSame(2, $this->countPdfImages($document));
    }

    public function test_signing_without_company_logo_prints_site_logo_in_footer(): void
    {
        // Logo global situs terpasang, tapi perusahaan penandatangan belum punya
        // logo → kotak tanda tangan hanya QR; logo situs muncul di footer.
        Storage::disk('local')->put('site/logo-global.png', $this->pngBinary());
        Setting::set('site.logo_path', 'site/logo-global.png');

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);
        app(CertificateService::class)->activate($user, self::PASSPHRASE);
        $document = $this->makeDraftDocument($user);

        $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $document->refresh();
        Storage::disk('documents')->assertExists($document->signed_path);

        // QR (kotak tanda tangan) + logo situs (footer) = 2 image XObject.
        $this->assertSame(2, $this->countPdfImages($document));
    }

    public function test_signing_with_company_logo_iccp_profile_succeeds(): void
    {
        $company = Company::factory()->create();
        Storage::disk('local')->put(
            'company-logos/iccp-logo.png',
            (string) file_get_contents((string) base_path('tests/Fixtures/logo-iccp.png')),
        );
        $company->update(['logo_path' => 'company-logos/iccp-logo.png']);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);
        app(CertificateService::class)->activate($user, self::PASSPHRASE);
        $document = $this->makeDraftDocument($user);

        $response = $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $response->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertSame(DocumentStatus::Signed, $document->status);
        Storage::disk('documents')->assertExists($document->signed_path);
        $this->assertSame(
            3,
            $this->countPdfImages($document),
            'QR + logo alpha + mask = 3 image XObject.',
        );
    }

    public function test_signing_with_corrupt_company_logo_returns_clean_error(): void
    {
        $company = Company::factory()->create();
        $valid = (string) file_get_contents((string) base_path('tests/Fixtures/logo-iccp.png'));
        Storage::disk('local')->put('company-logos/corrupt-logo.png', substr($valid, 0, 40));
        $company->update(['logo_path' => 'company-logos/corrupt-logo.png']);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Signer,
        ]);
        app(CertificateService::class)->activate($user, self::PASSPHRASE);
        $document = $this->makeDraftDocument($user);

        $response = $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ]);

        $response->assertSessionHasErrors('sign');

        $errors = $response->getSession()->get('errors');
        $this->assertNotNull($errors);
        $this->assertSame(
            'Proses pembubuhan tanda tangan visual ke PDF gagal.',
            $errors->get('sign')[0],
        );

        $document->refresh();
        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertNull($document->signed_path);
    }

    public function test_qr_temp_file_is_deleted_after_successful_signing(): void
    {
        $user = $this->makeReadySigner();
        $document = $this->makeDraftDocument($user);

        $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ])->assertRedirect(route('documents.show', $document));

        $signature = DocumentSignature::query()->where('document_id', $document->id)->firstOrFail();

        Storage::disk('temp')->assertMissing('qrcodes/'.$signature->verification_code.'.png');
    }

    public function test_signed_pdf_contains_footer_token_statement(): void
    {
        $user = $this->makeReadySigner();
        $document = $this->makeDraftDocument($user);

        $this->actingAs($user)->post(route('documents.sign', $document), [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ])->assertRedirect(route('documents.show', $document));

        $signature = DocumentSignature::query()->where('document_id', $document->id)->firstOrFail();
        $document->refresh();

        $text = $this->extractPdfText(Storage::disk('documents')->path((string) $document->signed_path));

        $this->assertStringContainsString('Dokumen ini telah ditandatangani secara elektronik.', $text);
        $this->assertStringContainsString(
            'Token: '.VerificationCode::display($signature->verification_code),
            $text,
        );
    }

    private function pngBinary(): string
    {
        $image = imagecreatetruecolor(40, 40);
        imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 255, 0, 0));

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function countPdfImages(Document $document): int
    {
        $content = (string) file_get_contents(Storage::disk('documents')->path($document->signed_path));

        return substr_count($content, '/Subtype /Image') + substr_count($content, '/Subtype/Image');
    }

    private function makeDraftDocument(User $user): Document
    {
        return app(DocumentUploadService::class)->upload(
            $user,
            UploadedFile::fake()->createWithContent('draft.pdf', $this->createTestPdfBinary()),
            'Dokumen Uji Signing',
        );
    }
}
