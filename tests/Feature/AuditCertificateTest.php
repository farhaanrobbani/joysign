<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSignedDocument;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class AuditCertificateTest extends TestCase
{
    use CreatesSignedDocument, CreatesTestPdf, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeStorageFakes();
    }

    public function test_owner_can_download_audit_certificate_for_signed_document(): void
    {
        [, $document] = $this->makeSignedDocument();

        $response = $this->actingAs($document->user)
            ->get(route('documents.audit-certificate', $document));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment; filename="sertifikat-'.$document->id.'.pdf"',
            (string) $response->headers->get('Content-Disposition'),
        );

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_admin_can_download_audit_certificate(): void
    {
        [, $document] = $this->makeSignedDocument();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)
            ->get(route('documents.audit-certificate', $document));

        $response->assertOk();
    }

    public function test_non_owner_cannot_download_audit_certificate(): void
    {
        [, $document] = $this->makeSignedDocument();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)
            ->get(route('documents.audit-certificate', $document));

        $response->assertForbidden();
    }

    public function test_draft_document_returns_404(): void
    {
        $user = User::factory()->create();
        $document = $this->makeDraftDocumentFor($user);

        $response = $this->actingAs($user)
            ->get(route('documents.audit-certificate', $document));

        $response->assertNotFound();
    }

    public function test_guest_cannot_download_audit_certificate(): void
    {
        [, $document] = $this->makeSignedDocument();

        $response = $this->get(route('documents.audit-certificate', $document));

        $response->assertRedirect(route('login'));
    }

    public function test_download_is_audited(): void
    {
        [, $document] = $this->makeSignedDocument();

        $this->actingAs($document->user)
            ->get(route('documents.audit-certificate', $document));

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'event' => 'document.audit_certificate_downloaded',
        ]);
    }
}
