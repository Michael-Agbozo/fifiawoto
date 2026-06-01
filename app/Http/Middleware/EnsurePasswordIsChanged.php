<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->force_password_change) {
            $passwordRoute = route('user-password.edit');

            if ($request->url() !== $passwordRoute && ! $request->is('logout')) {
                return redirect($passwordRoute)
                    ->with('force_password_change', true);
            }
        }

        return $next($request);
    }
}
