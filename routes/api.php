<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Versioned from the first endpoint, so a breaking change later means adding
| v2 rather than negotiating with every existing client.
|
*/

Route::prefix('v1')
    ->as('api.v1.')
    ->middleware(['auth:sanctum'])
    ->group(function (): void {
        Route::get('/user', fn (Request $request) => $request->user())->name('user');
    });
