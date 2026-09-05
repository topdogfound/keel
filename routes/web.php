<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
|
| One page serves both sides of the auth line: a landing hero for visitors,
| the dashboard for signed-in users. The page decides which to render from the
| shared `auth.user` prop, so the route stays public.
|
*/

Route::get('/', HomeController::class)->name('home');

require __DIR__.'/settings.php';

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
|
| The framework's /up only proves PHP is executing. This reports what actually
| tends to break -- database, cache, queue worker, scheduler -- as JSON for an
| uptime monitor, and as a readable page for a human.
|
| Both are gated on staff, since check output describes the infrastructure.
|
*/

Route::middleware(['auth', 'can:viewPulse'])->group(function (): void {
    Route::get('/health', HealthCheckResultsController::class)->name('health');
    Route::get('/health.json', HealthCheckJsonResultsController::class)->name('health.json');
});
