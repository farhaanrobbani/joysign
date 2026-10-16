<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CryptoServiceInterface;
use App\Contracts\PdfSignerInterface;
use App\Services\Crypto\OpenSslService;
use App\Services\Pdf\PdfSignerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CryptoServiceInterface::class, OpenSslService::class);
        $this->app->bind(PdfSignerInterface::class, PdfSignerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        self::registerVerifyRateLimiter();
        self::applySiteNameOverride();
    }

    /**
     * Batasi pencobaan token verifikasi publik per IP (anti brute-force
     * token pendek pada endpoint /verify/{code}).
     */
    private static function registerVerifyRateLimiter(): void
    {
        RateLimiter::for('verify', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    /**
     * Terapkan nama web dari pengaturan admin ke config('app.name') sehingga
     * berlaku untuk semua tampilan (judul tab, nav, footer, landing).
     */
    public static function applySiteNameOverride(): void
    {
        try {
            $name = setting('site.name');

            if ($name !== null && $name !== '') {
                config(['app.name' => $name]);
            }
        } catch (\Throwable) {
            // Tabel settings belum tersedia (mis. sebelum migrate) — pakai APP_NAME.
        }
    }
}
