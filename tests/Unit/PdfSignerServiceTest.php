<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\PdfSignerInterface;
use App\Exceptions\PdfException;
use App\Services\Pdf\SignaturePositionDTO;
use App\Services\Pdf\SignatureVisualDTO;
use App\Services\Qr\QrCodeService;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tests\Concerns\ExtractsPdfText;
use Tests\TestCase;

class PdfSignerServiceTest extends TestCase
{
    use ExtractsPdfText;

    private PdfSignerInterface $service;

    private QrCodeService $qrService;

    private string $tempDir;

    private string $fixturePdf;

    private string $twoPagePdf;

    private string $qrPath;

    private string $logoPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PdfSignerInterface::class);
        $this->qrService = app(QrCodeService::class);
        $this->tempDir = sys_get_temp_dir().'/tte_pdf_'.uniqid();
        mkdir($this->tempDir);

        $this->fixturePdf = $this->tempDir.'/fixture.pdf';
        $this->createPdf($this->fixturePdf, 1);

        $this->twoPagePdf = $this->tempDir.'/two-page.pdf';
        $this->createPdf($this->twoPagePdf, 2);

        $this->qrPath = $this->tempDir.'/qr.png';
        $this->qrService->save('http://localhost:7051/verify/TESTCODE', $this->qrPath);

        $this->logoPath = $this->tempDir.'/logo.png';
        $this->createLogoImage($this->logoPath);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir.'/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tempDir);

        parent::tearDown();
    }

    public function test_calculate_pdf_hash_returns_sha256_hex(): void
    {
        $hash = $this->service->calculatePdfHash($this->fixturePdf);

        $this->assertSame(hash('sha256', (string) file_get_contents($this->fixturePdf)), $hash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_calculate_pdf_hash_throws_for_missing_file(): void
    {
        $this->expectException(PdfException::class);

        $this->service->calculatePdfHash($this->tempDir.'/tidak-ada.pdf');
    }

    public function test_embed_visual_signature_produces_pdf_with_embedded_image(): void
    {
        $outputPath = $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 72.0, 500.0, 150.0, 50.0),
            $this->visual(),
        );

        $this->assertFileExists($outputPath);

        $content = (string) file_get_contents($outputPath);

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertTrue(
            str_contains($content, '/Subtype /Image') || str_contains($content, '/Subtype/Image'),
            'PDF hasil harus memuat image XObject (visual signature).',
        );
        $this->assertNotSame(
            $this->service->calculatePdfHash($this->fixturePdf),
            $this->service->calculatePdfHash($outputPath),
        );

        $source = new Fpdi('P', 'pt');
        $this->assertSame(1, $source->setSourceFile($outputPath));
    }

    public function test_embed_visual_signature_uses_explicit_output_path(): void
    {
        $outputPath = $this->tempDir.'/hasil-tte.pdf';

        $returned = $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 100.0, 600.0, 160.0, 55.0),
            $this->visual(),
            $outputPath,
        );

        $this->assertSame($outputPath, $returned);
        $this->assertFileExists($outputPath);
    }

    public function test_embed_can_target_second_page(): void
    {
        $outputPath = $this->service->embedVisualSignature(
            $this->twoPagePdf,
            new SignaturePositionDTO(2, 72.0, 700.0, 150.0, 50.0),
            $this->visual(),
        );

        $source = new Fpdi('P', 'pt');
        $this->assertSame(2, $source->setSourceFile($outputPath));
    }

    public function test_embed_throws_on_page_out_of_range(): void
    {
        $this->expectException(PdfException::class);

        $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(5, 72.0, 500.0, 150.0, 50.0),
            $this->visual(),
        );
    }

    public function test_embed_throws_on_position_outside_page(): void
    {
        $this->expectException(PdfException::class);

        $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 500.0, 800.0, 150.0, 50.0),
            $this->visual(),
        );
    }

    public function test_embed_throws_on_too_small_visual_box(): void
    {
        $this->expectException(PdfException::class);

        $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 72.0, 500.0, 20.0, 10.0),
            $this->visual(),
        );
    }

    public function test_embed_throws_on_invalid_pdf_content(): void
    {
        $corrupt = $this->tempDir.'/rusak.pdf';
        file_put_contents($corrupt, 'ini bukan pdf');

        $this->expectException(PdfException::class);

        $this->service->embedVisualSignature(
            $corrupt,
            new SignaturePositionDTO(1, 72.0, 500.0, 150.0, 50.0),
            $this->visual(),
        );
    }

    public function test_embed_throws_when_visual_assets_missing(): void
    {
        $visual = new SignatureVisualDTO(
            qrCodePath: $this->tempDir.'/hilang.png',
            logoImagePath: $this->logoPath,
        );

        $this->expectException(PdfException::class);

        $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 72.0, 500.0, 150.0, 50.0),
            $visual,
        );
    }

    public function test_embed_without_logo_produces_valid_pdf(): void
    {
        $outputPath = $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 72.0, 500.0, 150.0, 50.0),
            new SignatureVisualDTO(qrCodePath: $this->qrPath, logoImagePath: null),
        );

        $this->assertFileExists($outputPath);

        $content = (string) file_get_contents($outputPath);

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertTrue(
            str_contains($content, '/Subtype /Image') || str_contains($content, '/Subtype/Image'),
            'PDF hasil tetap memuat image XObject QR Code meski tanpa logo.',
        );
    }

    public function test_embed_with_iccp_logo_succeeds_without_libpng_warning(): void
    {
        $logoPath = $this->tempDir.'/logo-iccp.png';
        copy((string) base_path('tests/Fixtures/logo-iccp.png'), $logoPath);

        $warnings = [];
        $outputPath = null;

        set_error_handler(function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });

        try {
            $outputPath = $this->service->embedVisualSignature(
                $this->fixturePdf,
                new SignaturePositionDTO(1, 72.0, 500.0, 150.0, 50.0),
                new SignatureVisualDTO(qrCodePath: $this->qrPath, logoImagePath: $logoPath),
            );
        } finally {
            restore_error_handler();
        }

        $this->assertFileExists($outputPath);

        $content = (string) file_get_contents($outputPath);

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertStringNotContainsString('TCPDF ERROR', $content);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($content, '/Subtype /Image') + substr_count($content, '/Subtype/Image'),
            'QR + logo harus menghasilkan minimal 2 image XObject.',
        );

        $libpngWarnings = array_values(array_filter(
            $warnings,
            fn (string $message): bool => str_contains($message, 'libpng'),
        ));

        $this->assertSame(
            [],
            $libpngWarnings,
            'Logo iCCP wajib disanitasi sebelum masuk jalur GD/TCPDF (tidak boleh ada warning libpng).',
        );
    }

    public function test_iccp_fixture_triggers_libpng_warning_on_decode(): void
    {
        $fixture = (string) base_path('tests/Fixtures/logo-iccp.png');
        $warnings = [];
        $image = false;

        set_error_handler(function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });

        try {
            $image = imagecreatefrompng($fixture);
        } finally {
            restore_error_handler();
        }

        $this->assertNotFalse($image, 'Fixture harus bisa didekode GD (decode sebenarnya sukses).');
        $this->assertStringContainsString(
            'iCCP: known incorrect sRGB profile',
            implode("\n", $warnings),
            'Fixture harus memicu warning iCCP produksi agar tes sanitasi bermakna.',
        );
    }

    public function test_embed_throws_pdf_exception_for_corrupt_png_logo_instead_of_dying(): void
    {
        $corrupt = $this->tempDir.'/logo-rusak.png';
        $valid = (string) file_get_contents($this->logoPath);
        file_put_contents($corrupt, substr($valid, 0, 40));

        try {
            $this->service->embedVisualSignature(
                $this->fixturePdf,
                new SignaturePositionDTO(1, 72.0, 500.0, 150.0, 50.0),
                new SignatureVisualDTO(qrCodePath: $this->qrPath, logoImagePath: $corrupt),
            );

            $this->fail('Embed logo PNG korup harus melempar PdfException, bukan die().');
        } catch (PdfException $exception) {
            $this->assertSame('Proses pembubuhan tanda tangan visual ke PDF gagal.', $exception->getMessage());
        }
    }

    public function test_embed_prints_footer_token_statement(): void
    {
        $token = str_repeat('ab12', 16);
        $outputPath = $this->tempDir.'/footer.pdf';

        $returned = $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 72.0, 700.0, 150.0, 50.0),
            new SignatureVisualDTO(
                qrCodePath: $this->qrPath,
                logoImagePath: null,
                verificationToken: $token,
            ),
            $outputPath,
        );

        $text = $this->extractPdfText($returned);

        $this->assertStringContainsString($token, $text);
        $this->assertStringContainsString('Token: '.$token, $text);
        $this->assertStringContainsString(
            'Dokumen ini telah ditandatangani secara elektronik',
            $text,
        );
    }

    public function test_embed_without_token_prints_no_footer_statement(): void
    {
        $returned = $this->service->embedVisualSignature(
            $this->fixturePdf,
            new SignaturePositionDTO(1, 72.0, 700.0, 150.0, 50.0),
            $this->visual(),
            $this->tempDir.'/no-footer.pdf',
        );

        $text = $this->extractPdfText($returned);

        $this->assertStringNotContainsString(
            'Dokumen ini telah ditandatangani secara elektronik',
            $text,
        );
    }

    public function test_embed_prints_footer_on_every_page(): void
    {
        $token = str_repeat('cc33', 16);
        $returned = $this->service->embedVisualSignature(
            $this->twoPagePdf,
            new SignaturePositionDTO(1, 72.0, 700.0, 150.0, 50.0),
            new SignatureVisualDTO(
                qrCodePath: $this->qrPath,
                logoImagePath: null,
                verificationToken: $token,
            ),
            $this->tempDir.'/every-page.pdf',
        );

        $text = $this->extractPdfText($returned);

        $this->assertSame(
            2,
            substr_count($text, 'Dokumen ini telah ditandatangani secara elektronik'),
        );
        $this->assertSame(2, substr_count($text, $token));
    }

    private function visual(): SignatureVisualDTO
    {
        return new SignatureVisualDTO(
            qrCodePath: $this->qrPath,
            logoImagePath: $this->logoPath,
        );
    }

    private function createPdf(string $path, int $pages): void
    {
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false, 0);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetXY(20, 30);
            $pdf->Write(0, sprintf('Dokumen uji TTE - halaman %d', $page));
        }

        $pdf->Output($path, 'F');
    }

    private function createLogoImage(string $path): void
    {
        $image = imagecreatetruecolor(200, 80);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        $ink = imagecolorallocate($image, 25, 25, 160);
        imageellipse($image, 100, 40, 150, 50, $ink);
        imageline($image, 30, 55, 170, 25, $ink);
        imagepng($image, $path);
        imagedestroy($image);
    }
}
