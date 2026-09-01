<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function (): void {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

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
