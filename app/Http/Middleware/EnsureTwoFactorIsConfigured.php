<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user &&
            $user->requires_two_factor &&
            ! $user->hasEnabledTwoFactorAuthentication() &&
            ! $request->is('two-factor*', 'logout', 'sanctum*')
        ) {
            return redirect()->route('two-factor.show')
                ->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
