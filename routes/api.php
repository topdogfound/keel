<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Middleware\SetPermissionsTeamContext;
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
| SetPermissionsTeamContext is applied here as well as on web routes: token
| requests carry no session, so without it a user's team roles simply do not
| resolve and every policy check quietly fails closed.
|
*/

Route::prefix('v1')
    ->as('api.v1.')
    ->middleware(['auth:sanctum', SetPermissionsTeamContext::class])
    ->group(function (): void {
        Route::get('/user', fn (Request $request) => $request->user())->name('user');

        Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
        Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    });
