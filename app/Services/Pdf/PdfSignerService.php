<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Contracts\PdfSignerInterface;
use App\Exceptions\PdfException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

/**
 * PDF Signing Engine berbasis FPDI (setasign) + TCPDF.
 *
 * - Semua koordinat dalam satuan Point dengan origin KIRI-ATAS halaman
 *   (konsisten dengan FPDI/TCPDF) — lihat SignaturePositionDTO.
 * - Layout visual mengikuti DESIGN.md §3: border tipis, QR Code di kiri,
 *   logo perusahaan (opsional) di kanan. Footer pernyataan + token verifikasi
 *   penanda tangan pertama dicetak di bawah setiap halaman PDF.
 */
final class PdfSignerService implements PdfSignerInterface
{
    private const MIN_VISUAL_WIDTH = 40.0;

    private const MIN_VISUAL_HEIGHT = 22.0;

    private const FOOTER_BOTTOM_MARGIN = 10.0;

    public function calculatePdfHash(string $pdfPath): string
    {
        $this->guardReadableFile($pdfPath);

        $hash = hash_file('sha256', $pdfPath);

        if ($hash === false) {
            throw PdfException::fileNotFound();
        }

        return $hash;
    }

    public function embedVisualSignature(
        string $pdfPath,
        SignaturePositionDTO $position,
        SignatureVisualDTO $visual,
        ?string $outputPath = null,
    ): string {
        $this->guardReadableFile($pdfPath);
        $this->guardVisualAssets($visual);

        $sanitized = [];

        try {
            $visual = $this->withoutIccp($visual, $sanitized);

            $outputPath ??= Storage::disk('temp')->path((string) Str::uuid().'.pdf');

            $pdf = new class('P', 'pt') extends Fpdi
            {
                public function Header() {}

                public function Footer() {}
            };
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetCreator('TTE Web');
            $pdf->SetTitle('Dokumen Bertanda Tangan Elektronik');

            try {
                $pageCount = $pdf->setSourceFile($pdfPath);
            } catch (Throwable) {
                throw PdfException::invalidPdf();
            }

            if ($position->pageNumber < 1 || $position->pageNumber > $pageCount) {
                throw PdfException::pageOutOfRange($position->pageNumber, $pageCount);
            }

            try {
                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $templateId = $pdf->importPage($pageNumber);
                    $size = $pdf->getTemplateSize($templateId);

                    $pageWidth = (float) $size['width'];
                    $pageHeight = (float) $size['height'];

                    $pdf->AddPage($size['orientation'], [$pageWidth, $pageHeight]);
                    $pdf->useTemplate($templateId);

                    $this->renderFooter($pdf, $pageWidth, $pageHeight, $visual);

                    if ($pageNumber === $position->pageNumber) {
                        $this->guardPosition($position, $pageWidth, $pageHeight);

                        $this->renderSignature($pdf, $position, $visual);
                    }
                }

                $pdf->Output($outputPath, 'F');
            } catch (PdfException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                report($exception);

                throw PdfException::embedFailed();
            }

            if (! is_file($outputPath)) {
                throw PdfException::embedFailed();
            }

            return $outputPath;
        } finally {
            foreach ($sanitized as $tempPath) {
                @unlink($tempPath);
            }
        }
    }

    private function renderSignature(Fpdi $pdf, SignaturePositionDTO $position, SignatureVisualDTO $visual): void
    {
        $x = $position->x;
        $y = $position->y;
        $w = $position->width;
        $h = $position->height;
        $pad = min(2.0, $h * 0.08);

        $pdf->SetLineStyle(['width' => 0.6, 'cap' => 'round', 'color' => [80, 80, 80]]);
        $pdf->Rect($x, $y, $w, $h);

        // QR Code di kiri (persegi penuh tinggi area dalam).
        $qrSize = $h - (2 * $pad);
        $pdf->Image($visual->qrCodePath, $x + $pad, $y + $pad, $qrSize, $qrSize);

        // Logo perusahaan di kanan (opsional) — fit proporsional, center H+V.
        if ($visual->logoImagePath === null) {
            return;
        }

        $rightX = $x + $pad + $qrSize + $pad;
        $rightW = $w - ($rightX - $x) - $pad;
        $areaHeight = $h - (2 * $pad);

        [$imageWidth, $imageHeight] = $this->intrinsicSize($visual->logoImagePath);

        $scale = min($rightW / $imageWidth, $areaHeight / $imageHeight);
        $drawWidth = $imageWidth * $scale;
        $drawHeight = $imageHeight * $scale;

        $pdf->Image(
            $visual->logoImagePath,
            $rightX + (($rightW - $drawWidth) / 2),
            $y + $pad + (($areaHeight - $drawHeight) / 2),
            $drawWidth,
            $drawHeight,
        );
    }

    private function renderFooter(Fpdi $pdf, float $pageWidth, float $pageHeight, SignatureVisualDTO $visual): void
    {
        if ($visual->verificationToken === null || $visual->verificationToken === '') {
            return;
        }

        $line1 = 'Dokumen ini telah ditandatangani secara elektronik.';
        $line2 = 'Token: '.$visual->verificationToken;

        $fontHeight = 10.0;
        $lineHeight = $fontHeight + 2.0;
        $marginX = 12.0;
        $logo = $this->resolveSiteLogo();

        $logoWidth = 0.0;
        $logoHeight = 14.0;

        if ($logo !== null) {
            $size = @getimagesize($logo);

            if ($size !== false && $size[0] > 0 && $size[1] > 0) {
                $logoWidth = $logoHeight * ($size[0] / $size[1]);
            }
        }

        $gap = $logoWidth > 0 ? 4.0 : 0.0;
        $textX = $marginX + $logoWidth + $gap;
        $width = $pageWidth - $marginX - $textX;

        $separatorGap = 4.0;
        $blockHeight = $separatorGap + (2 * $lineHeight);
        $y = $pageHeight - self::FOOTER_BOTTOM_MARGIN - $blockHeight;
        $textY = $y + $separatorGap;

        $pdf->SetLineStyle(['width' => 0.3, 'cap' => 'butt', 'dash' => 0, 'color' => [80, 80, 80]]);
        $pdf->Line($marginX, $y, $pageWidth - $marginX, $y);

        if ($logoWidth > 0 && $logo !== null) {
            $pdf->Image($logo, $marginX, $textY + ((2 * $lineHeight - $logoHeight) / 2), $logoWidth, $logoHeight);
        }

        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFont('helvetica', '', $fontHeight);
        $pdf->SetXY($textX, $textY);
        $pdf->MultiCell($width, $lineHeight, $line1, '', 'L', false);
        $pdf->SetFont('helvetica', 'B', $fontHeight);
        $pdf->SetXY($textX, $textY + $lineHeight);
        $pdf->MultiCell($width, $lineHeight, $line2, '', 'L', false);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', $fontHeight);
    }

    private function resolveSiteLogo(): ?string
    {
        try {
            $path = setting('site.logo_path');
        } catch (Throwable) {
            return null;
        }

        if ($path === null || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

    private function withoutIccp(SignatureVisualDTO $visual, array &$sanitized): SignatureVisualDTO
    {
        return new SignatureVisualDTO(
            qrCodePath: $this->stripIccp($visual->qrCodePath, $sanitized),
            logoImagePath: $visual->logoImagePath === null
                ? null
                : $this->stripIccp($visual->logoImagePath, $sanitized),
            verificationToken: $visual->verificationToken,
        );
    }

    private function stripIccp(string $path, array &$sanitized): string
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'png') {
            return $path;
        }

        $bytes = @file_get_contents($path);

        if ($bytes === false || ! str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return $path;
        }

        $output = substr($bytes, 0, 8);
        $offset = 8;
        $hasIccp = false;
        $total = strlen($bytes);

        while ($offset + 12 <= $total) {
            $chunkLength = (int) unpack('N', substr($bytes, $offset, 4))[1];
            $chunkEnd = $offset + 12 + $chunkLength;

            if ($chunkEnd > $total) {
                return $path;
            }

            $type = substr($bytes, $offset + 4, 4);

            if ($type === 'iCCP') {
                $hasIccp = true;
            } else {
                $output .= substr($bytes, $offset, 12 + $chunkLength);
            }

            $offset = $chunkEnd;

            if ($type === 'IEND') {
                break;
            }
        }

        if (! $hasIccp) {
            return $path;
        }

        $relativePath = 'sanitized/'.(string) Str::uuid().'.png';

        try {
            Storage::disk('temp')->makeDirectory('sanitized');
            Storage::disk('temp')->put($relativePath, $output);
        } catch (Throwable) {
            return $path;
        }

        $absolutePath = Storage::disk('temp')->path($relativePath);
        $sanitized[] = $absolutePath;

        return $absolutePath;
    }

    /**
     * Dimensi intrinsik gambar (raster via getimagesize; SVG via parse
     * viewBox/width/height karena getimagesize gagal untuk SVG).
     *
     * @return array{0: float, 1: float}
     */
    private function intrinsicSize(string $path): array
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
            return $this->svgSize($path);
        }

        $size = @getimagesize($path);

        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            throw PdfException::missingVisualAsset();
        }

        return [(float) $size[0], (float) $size[1]];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function svgSize(string $path): array
    {
        $content = (string) file_get_contents($path);

        // viewBox="minX minY width height"
        if (preg_match('/viewBox\s*=\s*["\']?[-\d.]+[,\s]+[-\d.]+[,\s]+([\d.]+)[,\s]+([\d.]+)/i', $content, $matches) === 1) {
            return [(float) $matches[1], (float) $matches[2]];
        }

        // width="…" height="…"
        if (
            preg_match('/\bwidth\s*=\s*["\']([\d.]+)/i', $content, $w) === 1
            && preg_match('/\bheight\s*=\s*["\']([\d.]+)/i', $content, $h) === 1
        ) {
            return [(float) $w[1], (float) $h[1]];
        }

        // Fallback: anggap kotak 1:1 (TCPDF tetap bisa merender SVG).
        return [1.0, 1.0];
    }

    private function guardReadableFile(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw PdfException::fileNotFound();
        }
    }

    private function guardVisualAssets(SignatureVisualDTO $visual): void
    {
        if (! is_file($visual->qrCodePath)) {
            throw PdfException::missingVisualAsset();
        }

        if ($visual->logoImagePath !== null && ! is_file($visual->logoImagePath)) {
            throw PdfException::missingVisualAsset();
        }
    }

    private function guardPosition(SignaturePositionDTO $position, float $pageWidth, float $pageHeight): void
    {
        if (
            $position->width < self::MIN_VISUAL_WIDTH
            || $position->height < self::MIN_VISUAL_HEIGHT
            || $position->x < 0
            || $position->y < 0
            || $position->x + $position->width > $pageWidth + 0.5
            || $position->y + $position->height > $pageHeight + 0.5
        ) {
            throw PdfException::invalidPosition();
        }
    }
}
