<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Qr\QrCodeService;
use InvalidArgumentException;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    private QrCodeService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(QrCodeService::class);
        $this->tempDir = sys_get_temp_dir().'/tte_qr_'.uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir.'/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->tempDir);

        parent::tearDown();
    }

    public function test_generate_returns_png_binary(): void
    {
        $png = $this->service->generate('http://localhost:7051/verify/ABC123');

        $this->assertNotEmpty($png);
        $this->assertStringStartsWith("\x89PNG", $png);
    }

    public function test_save_writes_png_file(): void
    {
        $path = $this->tempDir.'/qr.png';

        $this->service->save('http://localhost:7051/verify/ABC123', $path);

        $this->assertFileExists($path);
        $this->assertStringStartsWith("\x89PNG", (string) file_get_contents($path));
    }

    public function test_empty_content_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->generate('   ');
    }
}
