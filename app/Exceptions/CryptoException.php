<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class CryptoException extends RuntimeException
{
    public static function keyGenerationFailed(): self
    {
        return new self('Generasi pasangan kunci kriptografis gagal.');
    }

    public static function invalidPassphrase(): self
    {
        return new self('Passphrase salah atau payload kunci terenkripsi rusak.');
    }

    public static function malformedKeyPayload(): self
    {
        return new self('Payload kunci terenkripsi tidak valid atau format tidak dikenal.');
    }

    public static function signingFailed(): self
    {
        return new self('Proses penandatanganan kriptografis gagal.');
    }
}
