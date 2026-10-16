<?php

declare(strict_types=1);

namespace App\Services\Qr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\Result\ResultInterface;
use InvalidArgumentException;

/**
 * Pembuat QR Code PNG (endroid/qr-code) untuk portal verifikasi publik.
 */
final class QrCodeService
{
    private const SIZE = 300;

    private const MARGIN = 4;

    /**
     * Hasilkan QR Code PNG sebagai binary string.
     */
    public function generate(string $content): string
    {
        return $this->buildQr($content)->getString();
    }

    /**
     * Hasilkan QR Code PNG dan simpan ke path absolut tertentu.
     */
    public function save(string $content, string $absolutePath): void
    {
        $this->buildQr($content)->saveToFile($absolutePath);
    }

    private function buildQr(string $content): ResultInterface
    {
        if (trim($content) === '') {
            throw new InvalidArgumentException('Konten QR Code tidak boleh kosong.');
        }

        return (new Builder)->build(
            data: $content,
            size: self::SIZE,
            margin: self::MARGIN,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
        );
    }
}
