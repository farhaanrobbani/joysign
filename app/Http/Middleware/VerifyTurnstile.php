<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Rules\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTurnstile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('turnstile.enabled')) {
            return $next($request);
        }

        $error = null;

        (new Turnstile)->validate(
            'cf-turnstile-response',
            $request->input('cf-turnstile-response'),
            function (string $message) use (&$error): void {
                $error = $message;
            }
        );

        if ($error !== null) {
            return back()->withErrors(['cf-turnstile-response' => $error]);
        }

        return $next($request);
    }
}
