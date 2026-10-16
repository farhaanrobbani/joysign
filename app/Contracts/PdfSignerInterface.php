<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\PdfException;
use App\Services\Pdf\SignaturePositionDTO;
use App\Services\Pdf\SignatureVisualDTO;

interface PdfSignerInterface
{
    /**
     * Hitung Hash SHA-256 (hex lowercase) dari file PDF.
     *
     * @throws PdfException
     */
    public function calculatePdfHash(string $pdfPath): string;

    /**
     * Bubuhkan visual tanda tangan (QR + logo perusahaan opsional) ke PDF pada
     * posisi (halaman, X, Y, width, height dalam Point), cetak footer
     * pernyataan + token verifikasi di bawah setiap halaman PDF, dan
     * hasilkan file PDF baru. Mengembalikan path absolut file hasil.
     *
     * @param  string|null  $outputPath  Path output absolut; jika null, file
     *                                   sementara dibuat pada disk "temp".
     *
     * @throws PdfException
     */
    public function embedVisualSignature(
        string $pdfPath,
        SignaturePositionDTO $position,
        SignatureVisualDTO $visual,
        ?string $outputPath = null,
    ): string;
}
