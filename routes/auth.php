<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->controller(LoginController::class)->group(function (): void {
    Route::get('login', 'create')->name('login');
    Route::post('login', 'store')->middleware('throttle:login')->name('login.store');
    Route::post('login/verify', 'verify')->middleware('throttle:login-otp-verify')->name('login.verify');
    Route::post('login/resend', 'resend')->middleware('throttle:login')->name('login.resend');
    Route::delete('login/challenge', 'destroyChallenge')->name('login.challenge.destroy');
});

Route::middleware('guest')->controller(SocialLoginController::class)->group(function (): void {
    Route::get('auth/{provider}/redirect', 'redirect')->middleware('throttle:login')->name('login.social.redirect');
});

// Not guest-only: an authenticated user lands back here too when linking a
// provider from settings (see SocialConnectionController::redirect()).
Route::get('auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('login.social.callback');

// GET is intentionally also accepted (in addition to the UI's POST) so that
// navigating to /logout directly — typing it in, an old bookmark — works too.
Route::middleware('auth')->match(['get', 'post'], 'logout', [LoginController::class, 'destroy'])->name('logout');
