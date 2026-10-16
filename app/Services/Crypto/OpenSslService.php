<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Contracts\CryptoServiceInterface;
use App\Exceptions\CryptoException;

/**
 * Implementasi kriptografi berbasis OpenSSL.
 *
 * - Pasangan kunci: RSA-2048 (default).
 * - Enkripsi private key saat disimpan: AES-256-GCM.
 * - Key derivation dari passphrase pengguna: Argon2id (sodium_crypto_pwhash).
 *
 * Format payload kunci terenkripsi:
 * argon2id-v1$<base64 salt>$<base64 iv>$<base64 tag>$<base64 ciphertext>
 *
 * ATURAN: material kunci (private key, passphrase, derived key) TIDAK BOLEH
 * dicetak ke log/console/balikan response — hanya lempar CryptoException generik.
 */
final class OpenSslService implements CryptoServiceInterface
{
    private const PAYLOAD_VERSION = 'argon2id-v1';

    private const CIPHER = 'aes-256-gcm';

    private const CIPHER_KEY_BYTES = 32;

    public function generateKeyPair(string $passphrase): KeyPairDTO
    {
        $this->guardPassphrase($passphrase);

        $keyResource = openssl_pkey_new([
            'private_key_bits' => (int) config('crypto.key_bits', 2048),
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($keyResource === false) {
            openssl_error_string();

            throw CryptoException::keyGenerationFailed();
        }

        if (! openssl_pkey_export($keyResource, $privateKeyPem)) {
            openssl_error_string();

            throw CryptoException::keyGenerationFailed();
        }

        $details = openssl_pkey_get_details($keyResource);

        if ($details === false || ! isset($details['key'])) {
            openssl_error_string();

            throw CryptoException::keyGenerationFailed();
        }

        return new KeyPairDTO(
            publicKey: (string) $details['key'],
            encryptedPrivateKey: $this->encryptPrivateKey((string) $privateKeyPem, $passphrase),
            algorithm: (string) config('crypto.key_algorithm', 'RSA-2048'),
        );
    }

    public function sign(string $data, string $encryptedPrivateKey, string $passphrase): string
    {
        $privateKeyPem = $this->decryptPrivateKey($encryptedPrivateKey, $passphrase);

        $keyResource = openssl_pkey_get_private($privateKeyPem);

        if ($keyResource === false) {
            openssl_error_string();

            throw CryptoException::signingFailed();
        }

        if (! openssl_sign($data, $signature, $keyResource, OPENSSL_ALGO_SHA256)) {
            openssl_error_string();

            throw CryptoException::signingFailed();
        }

        return base64_encode($signature);
    }

    public function verify(string $data, string $signature, string $publicKey): bool
    {
        $decodedSignature = base64_decode($signature, true);

        if ($decodedSignature === false) {
            return false;
        }

        $keyResource = openssl_pkey_get_public($publicKey);

        if ($keyResource === false) {
            openssl_error_string();

            return false;
        }

        return openssl_verify($data, $decodedSignature, $keyResource, OPENSSL_ALGO_SHA256) === 1;
    }

    private function encryptPrivateKey(string $privateKeyPem, string $passphrase): string
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $derivedKey = $this->deriveKey($passphrase, $salt);

        try {
            $tag = '';
            $ciphertext = openssl_encrypt(
                $privateKeyPem,
                self::CIPHER,
                $derivedKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
            );

            if ($ciphertext === false) {
                openssl_error_string();

                throw CryptoException::signingFailed();
            }
        } finally {
            sodium_memzero($derivedKey);
        }

        return implode('$', [
            self::PAYLOAD_VERSION,
            base64_encode($salt),
            base64_encode($iv),
            base64_encode($tag),
            base64_encode($ciphertext),
        ]);
    }

    private function decryptPrivateKey(string $payload, string $passphrase): string
    {
        $this->guardPassphrase($passphrase);

        $parts = explode('$', $payload);

        if (count($parts) !== 5 || $parts[0] !== self::PAYLOAD_VERSION) {
            throw CryptoException::malformedKeyPayload();
        }

        [, $saltB64, $ivB64, $tagB64, $ciphertextB64] = $parts;

        $salt = base64_decode($saltB64, true);
        $iv = base64_decode($ivB64, true);
        $tag = base64_decode($tagB64, true);
        $ciphertext = base64_decode($ciphertextB64, true);

        if ($salt === false || $iv === false || $tag === false || $ciphertext === false) {
            throw CryptoException::malformedKeyPayload();
        }

        if (strlen($salt) !== SODIUM_CRYPTO_PWHASH_SALTBYTES || strlen($tag) !== 16) {
            throw CryptoException::malformedKeyPayload();
        }

        $derivedKey = $this->deriveKey($passphrase, $salt);

        try {
            $privateKeyPem = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $derivedKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
            );
        } finally {
            sodium_memzero($derivedKey);
        }

        if ($privateKeyPem === false) {
            openssl_error_string();

            throw CryptoException::invalidPassphrase();
        }

        return $privateKeyPem;
    }

    private function deriveKey(string $passphrase, string $salt): string
    {
        return sodium_crypto_pwhash(
            self::CIPHER_KEY_BYTES,
            $passphrase,
            $salt,
            (int) config('crypto.argon2.opslimit', SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE),
            (int) config('crypto.argon2.memlimit', SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE),
        );
    }

    private function guardPassphrase(string $passphrase): void
    {
        if (trim($passphrase) === '') {
            throw CryptoException::invalidPassphrase();
        }
    }
}
