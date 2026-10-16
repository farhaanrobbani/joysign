<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Dashboard pengguna (PRD §3 FR-02): ringkasan statistik dokumen milik
 * pengguna aktif, status sertifikat, dan daftar dokumen terbaru.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $documents = $user->documents();
        $activeCertificate = $user->activeCertificate();

        $stats = [
            'documents_total' => (clone $documents)->count(),
            'documents_draft' => (clone $documents)->where('status', DocumentStatus::Draft->value)->count(),
            'documents_signed' => (clone $documents)->where('status', DocumentStatus::Signed->value)->count(),
            'documents_revoked' => (clone $documents)->where('status', DocumentStatus::Revoked->value)->count(),
            'signatures_given' => $user->signatures()->count(),
            'certificate_active' => $activeCertificate !== null,
        ];

        $recentDocuments = $documents->latest()->limit(5)->get();

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
            'activeCertificate' => $activeCertificate,
        ]);
    }
}
