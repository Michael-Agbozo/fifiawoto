<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Reject the request unless the user holds the permission key, whether it comes
     * from their role or was granted to them individually. This is the same check the
     * admin navigation and Livewire components make, so the three never disagree.
     *
     * Usage in routes:
     *   ->middleware('permission:events.view')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user()?->hasPermission($permission), 403);

        return $next($request);
    }
}
