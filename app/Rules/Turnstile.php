<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('turnstile.enabled')) {
            return;
        }

        $token = is_string($value) ? $value : '';

        if ($token === '') {
            $fail('Verifikasi keamanan wajib diselesaikan.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => (string) config('turnstile.secret'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable) {
            $fail('Verifikasi keamanan gagal. Coba lagi.');

            return;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            $fail('Verifikasi keamanan gagal. Coba lagi.');
        }
    }
}
