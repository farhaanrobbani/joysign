<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi file PDF standar (BUSINESS-RULES §2): memeriksa magic bytes
 * "%PDF-" dan versi 1.4–2.0 dari isi file, bukan sekadar MIME client.
 */
final class IsPdfFile implements ValidationRule
{
    private const HEADER_PATTERN = '/^%PDF-1\.[4-9]|^%PDF-2\./';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_object($value) || ! method_exists($value, 'getRealPath')) {
            $fail('Field :attribute harus berupa file PDF.');

            return;
        }

        $handle = @fopen($value->getRealPath(), 'rb');

        if ($handle === false) {
            $fail('File :attribute tidak dapat dibaca.');

            return;
        }

        try {
            $header = (string) fread($handle, 16);
        } finally {
            fclose($handle);
        }

        if (preg_match(self::HEADER_PATTERN, $header) !== 1) {
            $fail('Field :attribute harus berupa dokumen PDF standar (versi 1.4–2.0).');
        }
    }
}
