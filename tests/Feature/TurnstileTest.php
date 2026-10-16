<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private function enableTurnstile(string $secret = 'test-secret'): void
    {
        config([
            'turnstile.enabled' => true,
            'turnstile.sitekey' => 'test-sitekey',
            'turnstile.secret' => $secret,
        ]);
    }

    public function test_login_passes_when_siteverify_succeeds(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_is_blocked_when_siteverify_rejects_token(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_login_is_blocked_when_token_missing_and_turnstile_enabled(): void
    {
        $this->enableTurnstile();

        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_login_fails_closed_when_siteverify_unreachable(): void
    {
        $this->enableTurnstile();

        Http::preventStrayRequests();

        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'cf-turnstile-response' => 'any-token',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_forgot_password_is_blocked_when_siteverify_rejects_token(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'user@example.com',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('cf-turnstile-response');
    }
}
