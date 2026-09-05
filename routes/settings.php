<?php

use App\Http\Controllers\Settings\EmailController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SocialConnectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('settings/profile/email', [EmailController::class, 'store'])
        ->middleware('throttle:login')
        ->name('profile.email.store');
    Route::put('settings/profile/email', [EmailController::class, 'update'])
        ->middleware('throttle:login-otp-verify')
        ->name('profile.email.update');

    Route::get('settings/profile/connections/{provider}/redirect', [SocialConnectionController::class, 'redirect'])
        ->middleware('throttle:login')
        ->name('settings.connections.redirect');
    Route::delete('settings/profile/connections/{provider}', [SocialConnectionController::class, 'destroy'])
        ->name('settings.connections.destroy');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});
