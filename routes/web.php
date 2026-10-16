<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VerificationController;
use App\Livewire\Admin\AuditLogsPage;
use App\Livewire\Admin\DashboardPage;
use App\Livewire\Admin\SettingsPage;
use App\Livewire\Admin\UsersPage;
use App\Livewire\Company\DashboardPage as CompanyDashboardPage;
use App\Livewire\Documents\IndexPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/logo', [SettingController::class, 'logo'])->name('site.logo');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/verify', [VerificationController::class, 'index'])->name('verify.index');
Route::get('/verify/{code}', [VerificationController::class, 'show'])
    ->middleware('throttle:verify')->name('verify.show');
Route::get('/verify/{code}/document', [VerificationController::class, 'document'])
    ->middleware('throttle:verify')->name('verify.document');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/documents', IndexPage::class)->name('documents.index');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/documents/{document}/sign', [DocumentController::class, 'sign'])
        ->name('documents.sign');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])
        ->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::post('/documents/{document}/revoke', [DocumentController::class, 'revoke'])
        ->name('documents.revoke');
    Route::get('/documents/{document}/audit-certificate', [DocumentController::class, 'auditCertificate'])
        ->name('documents.audit-certificate');

    Route::get('/certificates', [CertificateController::class, 'show'])->name('certificates.show');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');

    Route::get('/company', CompanyDashboardPage::class)->name('company.dashboard');
    Route::get('/company/logo', function (Request $request) {
        $company = $request->user()?->company;
        $path = $company?->logo_path;

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    })->name('company.logo');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardPage::class)->name('dashboard');
    Route::get('/audit-logs', AuditLogsPage::class)->name('audit-logs');
    Route::get('/settings', SettingsPage::class)->name('settings');
});

Route::middleware(['auth', 'admin-or-operator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', UsersPage::class)->name('users');
});

require __DIR__.'/auth.php';
