<?php

declare(strict_types=1);

namespace App\Services\Certificate;

use App\Models\Document;
use App\Services\Verification\VerificationCode;
use Illuminate\Support\Facades\Route;
use TCPDF;

/**
 * Generator Sertifikat Pembuktian Penandatanganan Elektronik (FR-05).
 * Menghasilkan PDF ringkas berisi metadata dokumen, penandatangan,
 * sertifikat digital, token verifikasi, dan ringkasan jejak audit —
 * untuk keperluan pembuktian/arsip.
 */
final class AuditCertificateService
{
    public function generate(Document $document): string
    {
        $document->loadMissing([
            'signatures.signer',
            'auditLogs' => fn ($query) => $query->latest('created_at')->limit(20),
        ]);

        $signature = $document->signatures->first();
        $signer = $signature?->signer;
        $certificate = $signer?->activeCertificate();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TTE Web');
        $pdf->SetTitle('Sertifikat Pembuktian TTE - '.$document->title);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'SERTIFIKAT PEMBUKTIAN', 0, 1, 'C');
        $pdf->Cell(0, 8, 'Tanda Tangan Elektronik', 0, 1, 'C');
        $pdf->Ln(4);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100);
        $pdf->Cell(0, 6, 'Dokumen ini merupakan bukti elektronik yang dihasilkan oleh sistem TTE Web.', 0, 1, 'C');
        $pdf->Ln(6);

        $pdf->SetTextColor(0);

        $this->sectionTitle($pdf, '1. Informasi Dokumen');
        $this->row($pdf, 'Judul', $document->title);
        $this->row($pdf, 'Status', strtoupper($document->status->value));
        $this->row($pdf, 'Hash SHA-256 Asli', $document->original_hash);
        if ($document->signed_hash !== null) {
            $this->row($pdf, 'Hash SHA-256 Hasil TTE', $document->signed_hash);
        }
        if ($document->revoked_at !== null) {
            $this->row($pdf, 'Dicabut Pada', $document->revoked_at->format('Y-m-d H:i:s'));
            $this->row($pdf, 'Alasan Pencabutan', (string) $document->revoke_reason);
        }
        $pdf->Ln(4);

        if ($signature !== null && $signer !== null) {
            $this->sectionTitle($pdf, '2. Penandatangan');
            $this->row($pdf, 'Nama', (string) ($signature->signer_name ?? $signer->name));
            $this->row($pdf, 'Email', $signer->email);
            $this->row($pdf, 'NIK', (string) $signer->nik);
            $this->row($pdf, 'Waktu Tanda Tangan', $signature->signed_at?->format('Y-m-d H:i:s'));
            $this->row($pdf, 'Halaman', (string) $signature->page_number);
            $this->row($pdf, 'Token', VerificationCode::display($signature->verification_code));
            $pdf->Ln(4);
        }

        if ($certificate !== null) {
            $this->sectionTitle($pdf, '3. Sertifikat Digital');
            $this->row($pdf, 'Algoritma Kunci', $certificate->key_algorithm);
            $this->row($pdf, 'Berlaku Hingga', $certificate->expires_at?->format('Y-m-d') ?? 'Tidak kedaluwarsa');
            $pdf->Ln(4);
        }

        $this->sectionTitle($pdf, '4. Ringkasan Jejak Audit');
        if ($document->auditLogs->isEmpty()) {
            $pdf->Cell(0, 6, 'Tidak ada catatan audit.', 0, 1);
        } else {
            foreach ($document->auditLogs as $log) {
                $line = sprintf(
                    '%s | %s | %s',
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->event,
                    $log->user?->name ?? 'Sistem/Publik',
                );
                $pdf->Cell(0, 6, $line, 0, 1);
            }
        }
        $pdf->Ln(6);

        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(120);
        $pdf->Cell(0, 5, 'Sertifikat ini dihasilkan pada '.now()->format('Y-m-d H:i:s').' oleh TTE Web.', 0, 1, 'C');

        if ($signature !== null) {
            $verificationUrl = Route::has('verify.show')
                ? route('verify.show', ['code' => $signature->verification_code])
                : '';
            $pdf->Cell(0, 5, 'Verifikasi keaslian: '.$verificationUrl, 0, 1, 'C');
        }

        return (string) $pdf->Output('sertifikat.pdf', 'S');
    }

    private function sectionTitle(TCPDF $pdf, string $title): void
    {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, $title, 0, 1);
        $pdf->SetFont('helvetica', '', 10);
    }

    private function row(TCPDF $pdf, string $label, string $value): void
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 6, $label.':', 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 6, $value, 0, 'L');
    }
}
