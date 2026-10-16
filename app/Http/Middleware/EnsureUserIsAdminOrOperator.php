<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdminOrOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (! in_array($role, [UserRole::Admin, UserRole::Operator], true)) {
            abort(403, 'Akses hanya untuk administrator atau operator.');
        }

        return $next($request);
    }
}
