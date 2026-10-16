<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\CryptoException;
use App\Services\Crypto\KeyPairDTO;

interface CryptoServiceInterface
{
    /**
     * Generate pasangan kunci RSA baru. Private key dikembalikan dalam bentuk
     * terenkripsi (AES-256-GCM, key derivation Argon2id) — tidak pernah plaintext.
     *
     * @throws CryptoException
     */
    public function generateKeyPair(string $passphrase): KeyPairDTO;

    /**
     * Tandatangani $data (mis. hash SHA-256 dokumen) dengan private key terenkripsi.
     * Mengembalikan signature base64-encoded.
     *
     * @throws CryptoException Jika passphrase salah atau payload kunci rusak.
     */
    public function sign(string $data, string $encryptedPrivateKey, string $passphrase): string;

    /**
     * Verifikasi signature terhadap $data menggunakan public key PEM.
     * Tidak pernah melempar exception untuk data/signature yang tidak valid — cukup false.
     */
    public function verify(string $data, string $signature, string $publicKey): bool;
}
