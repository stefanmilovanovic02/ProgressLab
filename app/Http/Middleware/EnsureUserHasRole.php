<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $allowedRoles = collect($roles)
            ->map(fn (string $role) => UserRole::tryFrom(strtolower($role)))
            ->filter()
            ->values()
            ->all();

        if (!$allowedRoles || !$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Your account role does not have access to this feature.');
        }

        return $next($request);
    }
}
