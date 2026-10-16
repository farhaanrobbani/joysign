<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SigningException extends RuntimeException
{
    public static function documentNotDraft(): self
    {
        return new self('Hanya dokumen berstatus draft atau belum lengkap yang dapat ditandatangani.');
    }

    public static function notDocumentOwner(): self
    {
        return new self('Hanya pemilik dokumen yang dapat menandatangani dokumen tersebut.');
    }

    public static function notAssignedToSign(): self
    {
        return new self('Anda tidak ditugaskan untuk menandatangani dokumen ini.');
    }

    public static function certificateRequired(): self
    {
        return new self('Aktivasi sertifikat digital (pasangan kunci) wajib dilakukan sebelum menandatangani dokumen.');
    }

    public static function fileOperationFailed(): self
    {
        return new self('Operasi file dokumen gagal diproses.');
    }
}
