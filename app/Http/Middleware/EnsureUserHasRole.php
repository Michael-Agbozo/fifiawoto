<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Reject the request unless the authenticated user has one of the given roles.
     *
     * Usage in routes:
     *   ->middleware('role:super_admin,foundation_staff')
     *
     * If no roles are passed, falls back to "any role allowed into /admin".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $required = collect($roles)
            ->map(fn (string $value) => UserRole::tryFrom($value))
            ->filter()
            ->values();

        if ($required->isEmpty()) {
            abort_unless($user->canAccessAdmin(), 403);

            return $next($request);
        }

        abort_unless($user->hasRole(...$required->all()), 403);

        return $next($request);
    }
}
