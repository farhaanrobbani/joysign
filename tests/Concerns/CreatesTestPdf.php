<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use setasign\Fpdi\Tcpdf\Fpdi;

trait CreatesTestPdf
{
    protected function createTestPdfBinary(int $pages = 1): string
    {
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false, 0);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetXY(20, 30);
            $pdf->Write(0, sprintf('Dokumen uji TTE - halaman %d', $page));
        }

        return (string) $pdf->Output('dokumen.pdf', 'S');
    }

    protected function fakePdfFile(string $name = 'dokumen.pdf', int $pages = 1): File
    {
        return UploadedFile::fake()->createWithContent($name, $this->createTestPdfBinary($pages));
    }
}
