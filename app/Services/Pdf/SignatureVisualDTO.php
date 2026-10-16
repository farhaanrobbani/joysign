<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Asset visual tanda tangan (DESIGN.md §3): QR Code verifikasi di kiri +
 * logo perusahaan (opsional) di kanan. Footer pernyataan dua baris rata kiri
 * (logo situs opsional di kiri, lalu "Dokumen ini telah ditandatangani
 * secara elektronik." + "Token {kode}") dicetak di setiap halaman PDF —
 * hanya pada embed tanda tangan pertama (co-sign mewarisi footer dari PDF
 * sumber).
 */
final readonly class SignatureVisualDTO
{
    public function __construct(
        public string $qrCodePath,
        public ?string $logoImagePath = null,
        public ?string $verificationToken = null,
    ) {}
}
