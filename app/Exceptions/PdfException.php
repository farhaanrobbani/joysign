<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class PdfException extends RuntimeException
{
    public static function fileNotFound(): self
    {
        return new self('File PDF tidak ditemukan atau tidak dapat dibaca.');
    }

    public static function invalidPdf(): self
    {
        return new self('File tidak merupakan PDF valid atau gagal di-parse.');
    }

    public static function pageOutOfRange(int $pageNumber, int $pageCount): self
    {
        return new self(sprintf('Halaman %d di luar rentang (dokumen memiliki %d halaman).', $pageNumber, $pageCount));
    }

    public static function invalidPosition(): self
    {
        return new self('Posisi/ukuran tanda tangan di luar batas halaman atau terlalu kecil.');
    }

    public static function missingVisualAsset(): self
    {
        return new self('Asset visual tanda tangan (QR Code/logo) tidak ditemukan.');
    }

    public static function embedFailed(): self
    {
        return new self('Proses pembubuhan tanda tangan visual ke PDF gagal.');
    }
}
