<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Exceptions\SigningException;
use App\Livewire\Documents\IndexPage;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use App\Services\Document\DocumentAssignmentService;
use App\Services\Document\DocumentUploadService;
use App\Services\Verification\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestPdf;
use Tests\Concerns\ExtractsPdfText;
use Tests\TestCase;

class MultiSignTest extends TestCase
{
    use CreatesTestPdf, ExtractsPdfText, RefreshDatabase;

    private const PASSPHRASE = 'passphrase-rahasia';

    private User $operator;

    private User $signerA;

    private User $signerB;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');

        $this->operator = User::factory()->create();
        $this->company = Company::factory()->create(['created_by' => $this->operator->id]);
        $this->operator->forceFill([
            'company_id' => $this->company->id,
            'role' => UserRole::Operator,
        ])->save();

        $this->signerA = $this->makeCompanySigner();
        $this->signerB = $this->makeCompanySigner();
    }

    public function test_two_signers_cosign_document_successfully(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id, $this->signerB->id],
        );

        $this->assertSame(DocumentStatus::Draft, $document->refresh()->status);
        $this->assertSame(2, $document->assignments()->count());

        // Penandatangan pertama.
        $this->actingAs($this->signerA)->post(route('documents.sign', $document), $this->signPayload())
            ->assertRedirect(route('documents.show', $document));

        $document->refresh();
        $this->assertSame(DocumentStatus::PartiallySigned, $document->status);
        $this->assertNotNull($document->signed_path);
        $firstSignedPath = $document->signed_path;

        // Penandatangan kedua.
        $this->actingAs($this->signerB)->post(route('documents.sign', $document), $this->signPayload())
            ->assertRedirect(route('documents.show', $document));

        $document->refresh();
        $this->assertSame(DocumentStatus::Signed, $document->status);
        $this->assertNotSame($firstSignedPath, $document->signed_path);
        $this->assertSame(2, $document->signatures()->count());
        $this->assertSame(0, $document->assignments()->where('status', 'pending')->count());
    }

    public function test_each_cosigner_signs_the_original_hash(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id, $this->signerB->id],
        );

        $originalHash = $document->original_hash;

        $this->actingAs($this->signerA)->post(route('documents.sign', $document), $this->signPayload());
        $this->actingAs($this->signerB)->post(route('documents.sign', $document), $this->signPayload());

        $signatures = $document->refresh()->signatures()->get();
        $this->assertCount(2, $signatures);

        $this->assertSame(2, $signatures->pluck('verification_code')->unique()->count());

        $signerIds = $signatures->pluck('user_id')->sort()->values()->all();
        $expected = collect([$this->signerA->id, $this->signerB->id])->sort()->values()->all();
        $this->assertSame($expected, $signerIds);

        foreach ($signatures as $signature) {
            $this->assertNotEmpty($signature->cryptographic_signature);
        }
    }

    public function test_unassigned_signer_cannot_sign_company_document(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id],
        );

        $stranger = $this->makeCompanySigner();

        $this->actingAs($stranger)->post(route('documents.sign', $document), $this->signPayload())
            ->assertForbidden();

        $this->assertSame(DocumentStatus::Draft, $document->refresh()->status);
    }

    public function test_assigned_signer_can_view_document(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id],
        );

        $this->actingAs($this->signerA)->get(route('documents.show', $document))->assertOk();
        $this->actingAs($this->signerB)->get(route('documents.show', $document))->assertForbidden();
    }

    public function test_signer_cannot_sign_same_document_twice(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id, $this->signerB->id],
        );

        $this->actingAs($this->signerA)->post(route('documents.sign', $document), $this->signPayload());

        $second = $this->actingAs($this->signerA)->post(route('documents.sign', $document), $this->signPayload());
        $second->assertSessionHasErrors('sign');

        $this->assertSame(1, $document->refresh()->signatures()->count());
    }

    public function test_assign_signers_requires_same_company(): void
    {
        $document = $this->makeCompanyDocument();
        $outsider = User::factory()->create();

        $this->expectException(SigningException::class);

        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$outsider->id],
        );
    }

    public function test_operator_upload_with_signers_creates_company_assignments(): void
    {
        Livewire::actingAs($this->operator)
            ->test(IndexPage::class)
            ->set('title', 'Kontrak Co-sign')
            ->set('document', UploadedFile::fake()->createWithContent('kontrak.pdf', $this->createTestPdfBinary()))
            ->set('signers', [$this->signerA->id, $this->signerB->id])
            ->call('store');

        $document = Document::query()->latest('created_at')->firstOrFail();

        $this->assertSame($this->company->id, $document->company_id);
        $this->assertSame(2, $document->assignments()->count());
        $this->assertSame(DocumentStatus::Draft, $document->status);
    }

    public function test_operator_upload_without_signers_creates_personal_doc(): void
    {
        Livewire::actingAs($this->operator)
            ->test(IndexPage::class)
            ->set('title', 'Dokumen Pribadi Operator')
            ->set('document', UploadedFile::fake()->createWithContent('pribadi.pdf', $this->createTestPdfBinary()))
            ->call('store');

        $document = Document::query()->latest('created_at')->firstOrFail();

        $this->assertNull($document->company_id);
        $this->assertSame(1, $document->assignments()->count());
        $this->assertSame($this->operator->id, $document->assignments()->first()->signer_id);
    }

    public function test_document_show_lists_assignment_progress(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id, $this->signerB->id],
        );

        $response = $this->actingAs($this->operator)->get(route('documents.show', $document));

        $response->assertOk();
        $response->assertSee('Penandatangan Ditugaskan');
        $response->assertSee($this->signerA->name);
        $response->assertSee($this->signerB->name);
        $response->assertSee('Menunggu');
    }

    public function test_cosigned_pdf_footer_contains_first_signatory_token_only(): void
    {
        $document = $this->makeCompanyDocument();
        app(DocumentAssignmentService::class)->assignSigners(
            $document,
            $this->operator,
            [$this->signerA->id, $this->signerB->id],
        );

        $this->actingAs($this->signerA)->post(route('documents.sign', $document), $this->signPayload());
        $this->actingAs($this->signerB)->post(route('documents.sign', $document), $this->signPayload());

        $document->refresh();
        $signatures = $document->signatures()->orderBy('signed_at')->get();
        $this->assertCount(2, $signatures);

        $text = $this->extractPdfText(Storage::disk('documents')->path((string) $document->signed_path));

        $this->assertStringContainsString(
            VerificationCode::display($signatures[0]->verification_code),
            $text,
        );
        $this->assertStringNotContainsString(
            VerificationCode::display($signatures[1]->verification_code),
            $text,
        );
    }

    private function makeCompanySigner(): User
    {
        $signer = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::Signer,
        ]);
        app(CertificateService::class)->activate($signer, self::PASSPHRASE);

        return $signer;
    }

    private function makeCompanyDocument(): Document
    {
        return app(DocumentUploadService::class)->upload(
            $this->operator,
            UploadedFile::fake()->createWithContent('cosign.pdf', $this->createTestPdfBinary()),
            'Dokumen Co-sign',
            $this->company,
        )->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function signPayload(): array
    {
        return [
            'page_number' => 1,
            'coord_x' => 72,
            'coord_y' => 700,
            'width' => 150,
            'height' => 50,
            'passphrase' => self::PASSPHRASE,
        ];
    }
}
