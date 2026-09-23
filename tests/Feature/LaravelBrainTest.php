<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/**
 * Laravel Brain serves the application's source and call graph with no auth
 * of its own. Its one guard is that the provider registers nothing outside
 * APP_ENV=local, so these prove that guard still holds -- the suite runs as
 * `testing`, which is exactly a "not local" environment.
 */
it('registers no Laravel Brain routes outside the local environment', function (): void {
    $brainRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), '_laravel-brain'));

    expect($brainRoutes)->toBeEmpty();

    $this->get('/_laravel-brain')->assertNotFound();
    $this->get('/_laravel-brain/api/source')->assertNotFound();
});

it('registers no Laravel Brain commands outside the local environment', function (): void {
    expect(Artisan::all())->not->toHaveKey('brain:scan');
});
