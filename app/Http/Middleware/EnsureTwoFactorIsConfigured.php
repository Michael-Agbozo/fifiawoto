<?php

namespace App\Http\Middleware;

use App\Support\RequestOrigin;
use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsConfigured
{
    /**
     * Paths a user must still reach while enrolling in 2FA: the settings page itself,
     * Fortify's 2FA + password-confirmation endpoints, email verification and logout.
     *
     * @var list<string>
     */
    protected array $allowedPaths = [
        'settings/two-factor',
        'user/two-factor-*',
        'user/confirmed-two-factor-authentication',
        'user/confirm-password',
        'user/confirmed-password-status',
        'email/verify*',
        'email/verification-notification',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! $user->requires_two_factor
            || $user->force_password_change
            || ! Features::canManageTwoFactorAuthentication()
            || $user->hasEnabledTwoFactorAuthentication()
            || RequestOrigin::isWithin($request, $this->allowedPaths)
        ) {
            return $next($request);
        }

        return redirect()->route('two-factor.show')
            ->with('status', 'two-factor-required');
    }
}
