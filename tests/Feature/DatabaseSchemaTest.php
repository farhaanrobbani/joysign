<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_domain_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('digital_certificates'));
        $this->assertTrue(Schema::hasTable('documents'));
        $this->assertTrue(Schema::hasTable('document_signatures'));
        $this->assertTrue(Schema::hasTable('audit_logs'));
    }

    public function test_user_receives_ulid_and_signer_role_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertSame(26, strlen($user->id));
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/i', $user->id);
        $this->assertSame(UserRole::Signer, $user->role);
        $this->assertSame(16, strlen((string) $user->nik));
    }

    public function test_admin_state_sets_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $admin->role);
    }

    public function test_document_factory_creates_draft_with_owner(): void
    {
        $document = Document::factory()->create();

        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertNull($document->signed_path);
        $this->assertNull($document->signed_hash);
        $this->assertSame(64, strlen($document->original_hash));
        $this->assertTrue($document->user->exists);
        $this->assertCount(0, $document->signatures);
    }

    public function test_document_signed_and_revoked_states(): void
    {
        $signed = Document::factory()->signed()->create();
        $revoked = Document::factory()->revoked()->create();

        $this->assertSame(DocumentStatus::Signed, $signed->status);
        $this->assertTrue($signed->isSigned());
        $this->assertSame(DocumentStatus::Revoked, $revoked->status);
        $this->assertTrue($revoked->isRevoked());
        $this->assertNotNull($revoked->revoked_at);
    }

    public function test_document_signature_auto_sets_signed_at_and_relations(): void
    {
        $signature = DocumentSignature::factory()->create();

        $this->assertNotNull($signature->signed_at);
        $this->assertTrue($signature->document->exists);
        $this->assertTrue($signature->signer->exists);
        $this->assertSame(64, strlen($signature->verification_code));
    }

    public function test_audit_log_persists_payload_and_relations(): void
    {
        $log = AuditLog::factory()->create([
            'payload' => ['hash' => str_repeat('a', 64)],
        ]);

        $this->assertSame('document.uploaded', $log->event);
        $this->assertSame(['hash' => str_repeat('a', 64)], $log->payload);
        $this->assertTrue($log->document->exists);
        $this->assertTrue($log->user->exists);
    }

    public function test_active_certificate_excludes_expired_and_inactive(): void
    {
        $user = User::factory()->create();

        $expired = DigitalCertificate::factory()->expired()->create(['user_id' => $user->id]);
        $inactive = DigitalCertificate::factory()->inactive()->create(['user_id' => $user->id]);
        $active = DigitalCertificate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($expired->isExpired());
        $this->assertSame($active->id, $user->activeCertificate()->id);
    }
}
