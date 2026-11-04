<?php

namespace App\Http\Middleware;

use App\Support\RequestOrigin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->force_password_change && ! RequestOrigin::isWithin($request, ['settings/password', 'logout'])) {
            return redirect()->route('user-password.edit')
                ->with('force_password_change', true);
        }

        return $next($request);
    }
}
