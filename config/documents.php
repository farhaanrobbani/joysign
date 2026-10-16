<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Document Upload Limits
    |--------------------------------------------------------------------------
    |
    | Batas ukuran unggahan dokumen (BUSINESS-RULES §2): maksimal 20 MB
    | per file untuk menjaga performa rendering dan pemrosesan kriptografi.
    |
    */

    'max_upload_mb' => (int) env('DOCUMENTS_MAX_UPLOAD_MB', 20),

];
