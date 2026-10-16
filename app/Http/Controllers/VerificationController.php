<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Audit\AuditService;
use App\Services\Verification\VerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Portal verifikasi publik (FR-04 + BUSINESS-RULES §4). Akses terbuka
 * tanpa login: lookup token (QR / ketik manual).
 */
class VerificationController extends Controller
{
    public function __construct(
        private VerificationService $verification,
        private AuditService $audit,
    ) {}

    public function index(): View
    {
        return view('verify.show');
    }

    public function show(string $code): View
    {
        $result = $this->verification->verifyByCode($code);

        return view('verify.show', [
            'result' => $result,
            'code' => $code,
        ]);
    }

    public function document(string $code): BinaryFileResponse
    {
        $signature = $this->verification->resolveSignature($code);

        abort_if($signature === null, 404);

        $document = $signature->document;

        abort_if(
            $document === null
                || $document->signed_path === null
                || ! Storage::disk('documents')->exists($document->signed_path),
            404,
        );

        $this->audit->log($document, null, 'document.viewed', [
            'lookup' => 'code',
            'code' => $code,
        ]);

        return response()->file(
            Storage::disk('documents')->path($document->signed_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="dokumen-tte-'.substr($code, 0, 8).'.pdf"',
            ],
        );
    }
}
