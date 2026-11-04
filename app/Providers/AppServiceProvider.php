<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserHasRole;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment(['production', 'staging'])) {
            URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }

        $this->configureDefaults();
        $this->configureSecurity();
    }

    /**
     * Security hardening that applies in every environment.
     */
    protected function configureSecurity(): void
    {
        // Escape `[` and `<` in values echoed into Markdown mailables so user-submitted
        // text (contact form, applications) cannot inject links or HTML into emails.
        Markdown::withSecuredEncoding();

        // Re-run the `role:` route middleware on every Livewire update request, so a user
        // whose role changed after the page loaded cannot keep calling admin actions.
        Livewire::addPersistentMiddleware([
            EnsureUserHasRole::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
