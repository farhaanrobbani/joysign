<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sajian logo kustom (publik). Form pengaturan telah dipindah ke komponen
 * full-page Livewire App\Livewire\Admin\SettingsPage.
 */
class SettingController extends Controller
{
    /** Sajikan logo kustom (publik). Bila tidak ada, 404 (tampilan pakai SVG bawaan). */
    public function logo(): BinaryFileResponse
    {
        $path = setting('site.logo_path');

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="logo.'.$extension.'"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
