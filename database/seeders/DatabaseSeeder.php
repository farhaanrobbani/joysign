<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::query()->where('email', 'admin@tte.test')->exists()) {
            User::forceCreate([
                'name' => 'System Admin',
                'email' => 'admin@tte.test',
                'nik' => '0000000000000001',
                'role' => UserRole::Admin,
                'password' => 'password',
            ]);
        }

        $signer = User::query()->where('email', 'signer@tte.test')->first();

        if ($signer === null) {
            $signer = User::forceCreate([
                'name' => 'Demo Signer',
                'email' => 'signer@tte.test',
                'nik' => '0000000000000002',
                'role' => UserRole::Signer,
                'password' => 'password',
            ]);
        }

        if ($signer->activeCertificate() === null) {
            app(CertificateService::class)->activate($signer, 'password123');
        }
    }
}
