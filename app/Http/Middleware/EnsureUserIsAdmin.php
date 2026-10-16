<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses hanya untuk user dengan role admin (PRD §3,
 * BUSINESS-RULES §5: log audit hanya dikelola administrator).
 */
final class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null || $request->user()->role !== UserRole::Admin) {
            abort(403, 'Akses hanya untuk administrator sistem.');
        }

        return $next($request);
    }
}
