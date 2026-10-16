<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Penghapusan akun pengguna tanpa merusak verifikasi TTE:
 * - Akun tanpa data  -> hard-delete (baris dihapus).
 * - Akun ber-data     -> anonimkan di tempat: PII (nama/email/NIK/password)
 *                        dihapus, tetapi baris user, sertifikat (tetap aktif),
 *                        dokumen, & tanda tangan dipertahankan agar dokumen masih
 *                        bisa diverifikasi secara kriptografis.
 */
final class UserDeletionService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Hapus akun $target. Mengembalikan 'anonymized' bila ber-data, 'deleted' bila bersih.
     */
    public function remove(User $actor, User $target, string $event): string
    {
        if ($target->hasAnyDocuments()) {
            $this->anonymize($target);

            $this->audit->log(null, $actor, $event.'_anonymized', [
                'target_user_id' => $target->id,
                'email' => $target->email,
            ]);

            return 'anonymized';
        }

        $email = $target->email;
        $target->delete();

        $this->audit->log(null, $actor, $event.'_deleted', [
            'target_user_id' => $target->id,
            'email' => $email,
        ]);

        return 'deleted';
    }

    private function anonymize(User $target): void
    {
        $target->forceFill([
            'name' => 'Pengguna Dihapus',
            'email' => 'deleted+'.strtolower($target->id).'@example.invalid',
            'nik' => null,
            'jabatan' => null,
            'unit_kerja' => null,
            'instansi' => null,
            'password' => Hash::make(Str::random(40)),
            'disabled_at' => now(),
            'company_id' => null,
        ])->save();
    }
}
