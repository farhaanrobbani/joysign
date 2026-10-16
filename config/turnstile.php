<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('TURNSTILE_ENABLED', false),

    'sitekey' => env('TURNSTILE_SITEKEY'),

    'secret' => env('TURNSTILE_SECRET'),
];
