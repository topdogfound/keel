<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        $this->configureDefaults();
        $this->configureAuthorizationGates();
        $this->configureRateLimiting();
    }

    /**
     * Rate limit the passwordless login flow: requesting a code and verifying
     * one. Moved here from the now-removed Fortify service provider.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('login-otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by($request->session()->getId().'|'.$request->ip());
        });
    }

    /**
     * Dashboards ship with gates that only permit the local environment, so
     * they 403 everywhere else unless wired explicitly. Staff roles already
     * describe who runs the product.
     */
    protected function configureAuthorizationGates(): void
    {
        Gate::define('viewPulse', fn (?User $user = null): bool => $user?->isStaff() ?? false);

        // Scramble allows the local environment outright and falls back to this
        // gate elsewhere. Undefined it would deny everyone, which is safe but
        // makes the docs unreachable in staging.
        Gate::define('viewApiDocs', fn (?User $user = null): bool => $user?->isStaff() ?? false);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Turn silent N+1 queries, missing attributes and mass-assignment
        // mistakes into immediate exceptions. Off in production, where an
        // exception is worse than a slow page.
        Model::shouldBeStrict(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }
}
