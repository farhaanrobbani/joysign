<?php

declare(strict_types=1);

namespace App\Services\Certificate;

use App\Contracts\CryptoServiceInterface;
use App\Models\DigitalCertificate;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Manajemen sertifikat digital pengguna (FR-01 + BUSINESS-RULES §1):
 * setiap akun hanya boleh memiliki satu pasangan kunci aktif; aktivasi baru
 * otomatis menonaktifkan pasangan kunci aktif sebelumnya.
 */
final class CertificateService
{
    public function __construct(
        private CryptoServiceInterface $crypto,
        private AuditService $audit,
    ) {}

    public function activate(User $user, string $passphrase): DigitalCertificate
    {
        $keyPair = $this->crypto->generateKeyPair($passphrase);

        $certificate = DB::transaction(function () use ($user, $keyPair): DigitalCertificate {
            DigitalCertificate::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return DigitalCertificate::query()->create([
                'user_id' => $user->id,
                'public_key' => $keyPair->publicKey,
                'encrypted_private_key' => $keyPair->encryptedPrivateKey,
                'key_algorithm' => $keyPair->algorithm,
                'is_active' => true,
            ]);
        });

        $this->audit->log(null, $user, 'certificate.activated', [
            'certificate_id' => $certificate->id,
            'algorithm' => $keyPair->algorithm,
        ]);

        return $certificate;
    }
}
