<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\CryptoServiceInterface;
use App\Exceptions\CryptoException;
use App\Services\Crypto\OpenSslService;
use Tests\TestCase;

class CryptoServiceTest extends TestCase
{
    private CryptoServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CryptoServiceInterface::class);
    }

    public function test_interface_is_bound_to_openssl_service(): void
    {
        $this->assertInstanceOf(OpenSslService::class, $this->service);
    }

    public function test_generate_key_pair_returns_public_pem_and_encrypted_private_key(): void
    {
        $keyPair = $this->service->generateKeyPair('passphrase-rahasia-123');

        $this->assertStringContainsString('BEGIN PUBLIC KEY', $keyPair->publicKey);
        $this->assertStringContainsString('END PUBLIC KEY', $keyPair->publicKey);
        $this->assertSame('RSA-2048', $keyPair->algorithm);

        $this->assertStringStartsWith('argon2id-v1$', $keyPair->encryptedPrivateKey);
        $this->assertStringNotContainsString('PRIVATE KEY', $keyPair->encryptedPrivateKey);
        $this->assertStringNotContainsString('passphrase-rahasia-123', $keyPair->encryptedPrivateKey);
    }

    public function test_generated_encrypted_key_is_not_deterministic(): void
    {
        $first = $this->service->generateKeyPair('passphrase-sama');
        $second = $this->service->generateKeyPair('passphrase-sama');

        $this->assertNotSame($first->encryptedPrivateKey, $second->encryptedPrivateKey);
    }

    public function test_sign_and_verify_roundtrip_succeeds(): void
    {
        $keyPair = $this->service->generateKeyPair('passphrase-kuat');
        $hash = hash('sha256', 'isi dokumen contoh');

        $signature = $this->service->sign($hash, $keyPair->encryptedPrivateKey, 'passphrase-kuat');

        $this->assertNotEmpty($signature);
        $this->assertTrue($this->service->verify($hash, $signature, $keyPair->publicKey));
    }

    public function test_verify_fails_for_tampered_data(): void
    {
        $keyPair = $this->service->generateKeyPair('passphrase-kuat');
        $signature = $this->service->sign('hash-asli', $keyPair->encryptedPrivateKey, 'passphrase-kuat');

        $this->assertFalse($this->service->verify('hash-dimanipulasi', $signature, $keyPair->publicKey));
    }

    public function test_verify_fails_for_signature_from_other_key(): void
    {
        $keyPairA = $this->service->generateKeyPair('passphrase-a');
        $keyPairB = $this->service->generateKeyPair('passphrase-b');

        $signature = $this->service->sign('data', $keyPairA->encryptedPrivateKey, 'passphrase-a');

        $this->assertFalse($this->service->verify('data', $signature, $keyPairB->publicKey));
    }

    public function test_verify_returns_false_for_invalid_inputs(): void
    {
        $keyPair = $this->service->generateKeyPair('passphrase-kuat');

        $this->assertFalse($this->service->verify('data', 'bukan-base64!!!', $keyPair->publicKey));
        $this->assertFalse($this->service->verify('data', base64_encode('acak'), 'bukan-public-key'));
    }

    public function test_sign_throws_on_wrong_passphrase(): void
    {
        $keyPair = $this->service->generateKeyPair('passphrase-benar');

        $this->expectException(CryptoException::class);

        $this->service->sign('data', $keyPair->encryptedPrivateKey, 'passphrase-salah');
    }

    public function test_sign_throws_on_malformed_key_payload(): void
    {
        $this->expectException(CryptoException::class);

        $this->service->sign('data', 'payload-tidak-valid', 'passphrase');
    }

    public function test_generate_key_pair_rejects_empty_passphrase(): void
    {
        $this->expectException(CryptoException::class);

        $this->service->generateKeyPair('   ');
    }
}
