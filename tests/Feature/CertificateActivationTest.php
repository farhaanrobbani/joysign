<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DigitalCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Storage::fake('temp');
        Storage::fake('local');
    }

    public function test_guest_cannot_activate_certificate(): void
    {
        $response = $this->post(route('certificates.store'), [
            'passphrase' => 'rahasia123',
            'passphrase_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_activate_certificate(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('certificates.store'), [
            'passphrase' => 'rahasia123',
            'passphrase_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('certificates.show'));

        $certificate = DigitalCertificate::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($certificate->is_active);
        $this->assertSame('RSA-2048', $certificate->key_algorithm);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $certificate->public_key);
        $this->assertStringStartsWith('argon2id-v1$', $certificate->encrypted_private_key);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'certificate.activated',
        ]);
    }

    public function test_reactivation_rotates_previous_active_keypair(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('certificates.store'), [
            'passphrase' => 'passphrase-satu',
            'passphrase_confirmation' => 'passphrase-satu',
        ]);

        $this->actingAs($user)->post(route('certificates.store'), [
            'passphrase' => 'passphrase-dua',
            'passphrase_confirmation' => 'passphrase-dua',
        ]);

        $certificates = DigitalCertificate::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $certificates);
        $this->assertFalse($certificates[0]->is_active);
        $this->assertTrue($certificates[1]->is_active);
        $this->assertSame(1, $user->certificates()->where('is_active', true)->count());
    }

    public function test_activation_requires_confirmed_passphrase_of_minimum_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('certificates.store'), [
            'passphrase' => 'pendek',
            'passphrase_confirmation' => 'berbeda',
        ]);

        $response->assertSessionHasErrors('passphrase');
        $this->assertSame(0, DigitalCertificate::query()->count());
    }

    public function test_certificate_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('certificates.show'))
            ->assertOk()
            ->assertSee('Sertifikat Digital');
    }
}
