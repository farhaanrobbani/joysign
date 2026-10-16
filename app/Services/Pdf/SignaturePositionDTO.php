<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Posisi visual tanda tangan pada halaman PDF.
 *
 * Koordinat dalam satuan Point (1 pt = 1/72 inci), dengan origin (0,0)
 * di KIRI-ATAS halaman — konsisten dengan konvensi FPDI/TCPDF sehingga
 * hasil konversi dari canvas (DESIGN.md §1) dapat dipakai langsung.
 */
final readonly class SignaturePositionDTO
{
    public function __construct(
        public int $pageNumber,
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    ) {}
}
