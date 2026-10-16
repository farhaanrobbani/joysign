<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Key Algorithm
    |--------------------------------------------------------------------------
    |
    | Algoritma pasangan kunci yang diterbitkan untuk pengguna. Saat ini
    | implementasi OpenSSL service menghasilkan RSA dengan ukuran key_bits.
    |
    */

    'key_algorithm' => env('CRYPTO_KEY_ALGORITHM', 'RSA-2048'),

    'key_bits' => (int) env('CRYPTO_KEY_BITS', 2048),

    /*
    |--------------------------------------------------------------------------
    | Argon2id Parameters
    |--------------------------------------------------------------------------
    |
    | Parameter key derivation (sodium_crypto_pwhash) untuk menurunkan kunci
    | enkripsi private key dari passphrase pengguna. Default memakai profil
    | INTERACTIVE agar hemat memori; naikkan ke MODERATE/STRONG di produksi.
    |
    */

    'argon2' => [
        'opslimit' => (int) env('CRYPTO_ARGON2_OPSLIMIT', SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE),
        'memlimit' => (int) env('CRYPTO_ARGON2_MEMLIMIT', SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE),
    ],

];
