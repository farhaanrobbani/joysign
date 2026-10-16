<?php

declare(strict_types=1);

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Baca pengaturan website: nilai tersimpan (DB) → $default → default
     * bawaan di config('settings.defaults').
     */
    function setting(?string $key, ?string $default = null): ?string
    {
        if ($key === null) {
            return $default;
        }

        $value = Setting::get($key);

        if ($value !== null) {
            return $value;
        }

        if ($default !== null) {
            return $default;
        }

        $defaults = config('settings.defaults', []);

        return $defaults[$key] ?? null;
    }
}
