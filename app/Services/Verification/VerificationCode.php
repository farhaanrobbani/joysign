<?php

declare(strict_types=1);

namespace App\Services\Verification;

use App\Models\DocumentSignature;

/**
 * Kode verifikasi dokumen: 12 karakter Base32 (tanpa 0/O/1/I), entropi
 * 60 bit dari random_bytes — cukup kuat untuk token lookup online,
 * dipadukan dengan rate limiting pada endpoint verifikasi.
 *
 * - generate(): kode compactuppercase (tanpa dash), unik di DB.
 * - normalize(): input pengguna → bentuk lookup (kapital, tanpa dash/spasi).
 * - display(): bentuk tampilan berkelompok XXXX-XXXX-XXXX (kode lama
 *   non-12-karakter ditampilkan apa adanya).
 */
final class VerificationCode
{
    public const LENGTH = 12;

    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function generate(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $bytes = random_bytes(self::LENGTH);

            $code = '';
            for ($i = 0; $i < self::LENGTH; $i++) {
                $code .= self::ALPHABET[ord($bytes[$i]) % 32];
            }

            if (! DocumentSignature::query()->where('verification_code', $code)->exists()) {
                return $code;
            }
        }

        return $code;
    }

    public static function normalize(string $input): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $input));
    }

    public static function display(string $code): string
    {
        if (strlen($code) !== self::LENGTH) {
            return $code;
        }

        return implode('-', str_split($code, 4));
    }
}
