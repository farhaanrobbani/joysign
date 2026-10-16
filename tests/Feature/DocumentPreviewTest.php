<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\Document\DocumentUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class DocumentPreviewTest extends TestCase
{
    use CreatesTestPdf, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');
    }

    public function test_owner_can_preview_original_pdf_inline(): void
    {
        $user = User::factory()->create();
        $document = $this->makeDocument($user);

        $response = $this->actingAs($user)->get(route('documents.preview', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    public function test_non_owner_cannot_preview_document(): void
    {
        $owner = User::factory()->create();
        $document = $this->makeDocument($owner);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('documents.preview', $document))
            ->assertForbidden();
    }

    public function test_guest_cannot_preview_document(): void
    {
        $owner = User::factory()->create();
        $document = $this->makeDocument($owner);

        $this->get(route('documents.preview', $document))
            ->assertRedirect(route('login'));
    }

    public function test_preview_returns_not_found_when_file_is_missing(): void
    {
        $user = User::factory()->create();
        $document = $this->makeDocument($user);

        Storage::disk('documents')->delete($document->original_path);

        $this->actingAs($user)
            ->get(route('documents.preview', $document))
            ->assertNotFound();
    }

    private function makeDocument(User $user): Document
    {
        return app(DocumentUploadService::class)->upload(
            $user,
            UploadedFile::fake()->createWithContent('preview.pdf', $this->createTestPdfBinary(2)),
            'Dokumen Uji Preview',
        );
    }
}
