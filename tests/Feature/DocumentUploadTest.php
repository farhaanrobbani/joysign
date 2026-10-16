<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Documents\DocumentsList;
use App\Livewire\Documents\IndexPage;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use CreatesTestPdf, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');
    }

    public function test_guest_cannot_upload_documents(): void
    {
        // Halaman daftar + unggah di balik middleware auth.
        $this->get(route('documents.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_embeds_polling_documents_list(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->assertSeeLivewire(DocumentsList::class);
    }

    public function test_user_can_upload_pdf_document(): void
    {
        $user = User::factory()->create();
        $pdfBinary = $this->createTestPdfBinary();

        $component = Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('title', 'Kontrak Kerja')
            ->set('document', UploadedFile::fake()->createWithContent('kontrak.pdf', $pdfBinary))
            ->call('store');

        $document = Document::query()->firstOrFail();
        $component->assertRedirect(route('documents.show', $document));

        $this->assertSame('Kontrak Kerja', $document->title);
        $this->assertSame($user->id, $document->user_id);
        $this->assertStringStartsWith('drafts/', $document->original_path);
        $this->assertSame(hash('sha256', $pdfBinary), $document->original_hash);
        $this->assertNull($document->signed_path);

        Storage::disk('documents')->assertExists($document->original_path);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'event' => 'document.uploaded',
        ]);
    }

    public function test_upload_rejects_non_pdf_content(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('title', 'Bukan PDF')
            ->set('document', UploadedFile::fake()->createWithContent('bukan.pdf', 'ini bukan pdf sama sekali'))
            ->call('store')
            ->assertHasErrors('document');

        $this->assertSame(0, Document::query()->count());
    }

    public function test_upload_rejects_files_over_size_limit(): void
    {
        $user = User::factory()->create();

        $oversized = UploadedFile::fake()
            ->createWithContent('besar.pdf', $this->createTestPdfBinary().str_repeat('A', (21 * 1024 * 1024)));

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('title', 'Terlalu Besar')
            ->set('document', $oversized)
            ->call('store')
            ->assertHasErrors('document');

        $this->assertSame(0, Document::query()->count());
    }

    public function test_user_can_delete_own_draft(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('title', 'Draft Sementara')
            ->set('document', $this->fakePdfFile())
            ->call('store');

        $document = Document::query()->firstOrFail();

        // Hapus lewat route controller (delete tetap di show/controller).
        $response = $this->actingAs($user)->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('documents')->assertMissing($document->original_path);

        $audit = AuditLog::query()
            ->where('event', 'document.deleted')
            ->where('user_id', $user->id)
            ->whereNull('document_id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($document->title, data_get($audit->payload, 'title'));
    }
}
