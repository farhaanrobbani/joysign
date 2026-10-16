<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Exceptions\CryptoException;
use App\Exceptions\PdfException;
use App\Exceptions\SigningException;
use App\Http\Requests\RevokeDocumentRequest;
use App\Http\Requests\SignDocumentRequest;
use App\Models\Document;
use App\Services\Audit\AuditService;
use App\Services\Certificate\AuditCertificateService;
use App\Services\Document\DocumentSignerService;
use App\Services\Pdf\SignaturePositionDTO;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private DocumentSignerService $signer,
        private AuditService $audit,
        private AuditCertificateService $auditCertificate,
    ) {}

    public function show(Request $request, Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('signatures.signer', 'assignments.signer');

        return view('documents.show', compact('document'));
    }

    public function sign(SignDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('sign', $document);

        try {
            $this->signer->sign(
                $document,
                $request->user(),
                new SignaturePositionDTO(
                    pageNumber: (int) $request->input('page_number'),
                    x: (float) $request->input('coord_x'),
                    y: (float) $request->input('coord_y'),
                    width: (float) $request->input('width'),
                    height: (float) $request->input('height'),
                ),
                $request->string('passphrase')->toString(),
            );
        } catch (CryptoException $exception) {
            return back()->withErrors(['passphrase' => $exception->getMessage()])->withInput();
        } catch (SigningException|PdfException $exception) {
            return back()->withErrors(['sign' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Dokumen berhasil ditandatangani secara kriptografis.');
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        if ($document->status !== DocumentStatus::Draft) {
            return back()->withErrors(['sign' => 'Hanya dokumen draft yang dapat dihapus.']);
        }

        Storage::disk('documents')->delete($document->original_path);

        $this->audit->log($document, $request->user(), 'document.deleted', [
            'title' => $document->title,
        ]);

        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('status', 'Dokumen draft dihapus.');
    }

    public function revoke(RevokeDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('revoke', $document);

        if ($document->status !== DocumentStatus::Signed) {
            return back()->withErrors(['revoke' => 'Hanya dokumen berstatus signed yang dapat dicabut.']);
        }

        $document->forceFill([
            'status' => DocumentStatus::Revoked,
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
            'revoke_reason' => $request->string('reason')->toString(),
        ])->save();

        $this->audit->log($document, $request->user(), 'document.revoked', [
            'reason' => $request->string('reason')->toString(),
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Dokumen berhasil dicabut (revoked).');
    }

    public function preview(Document $document): BinaryFileResponse
    {
        $this->authorize('view', $document);

        abort_unless(
            Storage::disk('documents')->exists($document->original_path),
            404,
        );

        return response()->file(
            Storage::disk('documents')->path($document->original_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.basename($document->original_path).'"',
            ],
        );
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless(
            $document->signed_path !== null && Storage::disk('documents')->exists($document->signed_path),
            404,
        );

        $this->audit->log($document, $request->user(), 'document.downloaded', [
            'signed_hash' => $document->signed_hash,
        ]);

        return Storage::disk('documents')->download(
            $document->signed_path,
            $document->title.'-signed.pdf',
        );
    }

    public function auditCertificate(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        abort_unless($document->isSigned() || $document->isRevoked(), 404);

        $pdfContent = $this->auditCertificate->generate($document);

        $this->audit->log($document, $request->user(), 'document.audit_certificate_downloaded', []);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sertifikat-'.$document->id.'.pdf"',
        ]);
    }
}
