<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\PermissionScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes permission checks to the team the request is acting within.
 *
 * Every product route needs this: spatie/laravel-permission resolves roles
 * against the current team id, so without it a user's team roles simply do not
 * resolve. The staff panel uses SetGlobalPermissionsContext instead.
 */
class SetPermissionsTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->user()?->currentTeam;

        if ($team !== null) {
            PermissionScope::team($team->id);
        }

        return $next($request);
    }
}
