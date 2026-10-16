<?php

declare(strict_types=1);

namespace App\Services\Crypto;

final readonly class KeyPairDTO
{
    public function __construct(
        public string $publicKey,
        public string $encryptedPrivateKey,
        public string $algorithm = 'RSA-2048',
    ) {}
}
