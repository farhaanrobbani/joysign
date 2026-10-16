<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_forwarded_for_header_is_trusted_from_the_proxy(): void
    {
        Route::get('/_proxy-ip', fn (Request $request) => $request->ip());

        $this->get('/_proxy-ip', ['X-Forwarded-For' => '203.0.113.7'])
            ->assertOk()
            ->assertContent('203.0.113.7');
    }

    public function test_forwarded_proto_header_marks_request_as_secure(): void
    {
        Route::get('/_proxy-scheme', fn (Request $request) => $request->isSecure() ? 'secure' : 'insecure');

        $this->get('/_proxy-scheme', ['X-Forwarded-Proto' => 'https'])
            ->assertOk()
            ->assertContent('secure');
    }
}
