<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ActivateCertificateRequest;
use App\Services\Certificate\CertificateService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CertificateService $certificates,
    ) {}

    public function show(Request $request): View
    {
        $certificate = $request->user()->activeCertificate();

        return view('certificates.show', compact('certificate'));
    }

    public function store(ActivateCertificateRequest $request): RedirectResponse
    {
        $this->certificates->activate(
            $request->user(),
            $request->string('passphrase')->toString(),
        );

        return redirect()
            ->route('certificates.show')
            ->with('status', 'Sertifikat digital baru diaktivasi. Pasangan kunci sebelumnya dinonaktifkan.');
    }
}
