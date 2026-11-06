<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureTwoFactorIsConfigured;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RecordPageVisit;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // One-click unsubscribe from mail clients: these POSTs carry a signed URL, not a CSRF token.
        $middleware->validateCsrfTokens(except: ['newsletter/unsubscribe/*']);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => EnsureUserHasPermission::class,
        ]);
        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
        $middleware->appendToGroup('web', EnsurePasswordIsChanged::class);
        $middleware->appendToGroup('web', EnsureTwoFactorIsConfigured::class);
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->appendToGroup('web', RecordPageVisit::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
