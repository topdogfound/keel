<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

/**
 * Registers the health checks behind /up.
 *
 * The framework's default /up only proves PHP is executing. These prove the
 * things that actually go wrong: the database is reachable, Redis is answering,
 * the queue worker is consuming, and the scheduler is still ticking.
 *
 * The scheduler and queue checks depend on the `scheduler` and `horizon`
 * Compose services. Sail ships neither, which is why they exist in compose.yaml
 * -- without them these checks correctly report failure.
 */
class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Health::checks([
            DatabaseCheck::new(),
            CacheCheck::new(),
            // Requires `php artisan schedule:work` -- the scheduler service.
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(5),
            // Requires a worker consuming the queue -- the horizon service.
            QueueCheck::new()->onQueue('default'),
            UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(80),
            // These only make sense in a deployed environment. The base Check
            // offers if()/unless(), not the unlessEnvironmentIs() you might
            // expect, and the closure keeps evaluation lazy.
            DebugModeCheck::new()
                ->expectedToBe(false)
                ->unless(fn (): bool => app()->environment(['local', 'testing'])),
            EnvironmentCheck::new()
                ->expectEnvironment('production')
                ->unless(fn (): bool => app()->environment(['local', 'testing'])),
            OptimizedAppCheck::new()
                ->unless(fn (): bool => app()->environment(['local', 'testing'])),
        ]);
    }
}
