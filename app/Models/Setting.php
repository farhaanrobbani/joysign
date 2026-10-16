<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Penyimpanan pengaturan website berbasis key-value (nama web, logo,
 * teks landing, SEO, dll). Dibaca melalui helper setting() dengan cache
 * permanen yang di-flush setiap kali ada perubahan.
 */
class Setting extends Model
{
    public const CACHE_KEY = 'settings.map';

    protected $fillable = [
        'key',
        'value',
    ];

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    /** Peta semua setting (key => value) dari cache/DB. */
    public static function values(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => self::query()->pluck('value', 'key')->all(),
        );
    }

    /** Ambil satu nilai setting (null bila belum diatur). */
    public static function get(string $key): ?string
    {
        $value = self::values()[$key] ?? null;

        return $value === null ? null : (string) $value;
    }

    /** Simpan satu nilai setting lalu flush cache. */
    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        self::flushCache();
    }

    /** Simpan banyak nilai sekaligus lalu flush cache sekali. */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
