<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSignedDocument;
use Tests\Concerns\CreatesTestPdf;
use Tests\TestCase;

class DocumentRevokeTest extends TestCase
{
    use CreatesSignedDocument, CreatesTestPdf, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeStorageFakes();
    }

    public function test_owner_can_revoke_signed_document(): void
    {
        [, $document] = $this->makeSignedDocument();

        $response = $this->actingAs($document->user)->post(
            route('documents.revoke', $document),
            ['reason' => 'Dokumen ditarik kembali karena kesalahan data.'],
        );

        $response->assertRedirect(route('documents.show', $document));

        $document->refresh();

        $this->assertSame(DocumentStatus::Revoked, $document->status);
        $this->assertNotNull($document->revoked_at);
        $this->assertSame($document->user_id, $document->revoked_by);
        $this->assertSame('Dokumen ditarik kembali karena kesalahan data.', $document->revoke_reason);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'event' => 'document.revoked',
        ]);
    }

    public function test_admin_can_revoke_any_signed_document(): void
    {
        [, $document] = $this->makeSignedDocument();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(
            route('documents.revoke', $document),
            ['reason' => 'Dicabut oleh admin untuk investigasi.'],
        );

        $response->assertRedirect(route('documents.show', $document));

        $document->refresh();

        $this->assertSame(DocumentStatus::Revoked, $document->status);
        $this->assertSame($admin->id, $document->revoked_by);
    }

    public function test_non_owner_non_admin_cannot_revoke(): void
    {
        [, $document] = $this->makeSignedDocument();
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->post(
            route('documents.revoke', $document),
            ['reason' => 'Upaya tidak sah.'],
        );

        $response->assertForbidden();
        $this->assertSame(DocumentStatus::Signed, $document->refresh()->status);
    }

    public function test_draft_document_cannot_be_revoked(): void
    {
        $user = User::factory()->create();
        $document = $this->makeDraftDocumentFor($user);

        $response = $this->actingAs($user)->post(
            route('documents.revoke', $document),
            ['reason' => 'Alasan apapun.'],
        );

        $response->assertSessionHasErrors('revoke');
        $this->assertSame(DocumentStatus::Draft, $document->refresh()->status);
    }

    public function test_reason_is_required(): void
    {
        [, $document] = $this->makeSignedDocument();

        $response = $this->actingAs($document->user)->post(
            route('documents.revoke', $document),
            [],
        );

        $response->assertSessionHasErrors('reason');
        $this->assertSame(DocumentStatus::Signed, $document->refresh()->status);
    }

    public function test_revoked_document_shows_revoked_on_public_verification(): void
    {
        [, $document, $signature] = $this->makeSignedDocument();

        $this->actingAs($document->user)->post(
            route('documents.revoke', $document),
            ['reason' => 'Cabut untuk tes verifikasi.'],
        );

        $response = $this->get(route('verify.show', ['code' => $signature->verification_code]));

        $response->assertOk();
        $response->assertSee('REVOKED');
    }

    public function test_guest_cannot_revoke(): void
    {
        [, $document] = $this->makeSignedDocument();

        $response = $this->post(
            route('documents.revoke', $document),
            ['reason' => 'Guest attempt.'],
        );

        $response->assertRedirect(route('login'));
        $this->assertSame(DocumentStatus::Signed, $document->refresh()->status);
    }
}
