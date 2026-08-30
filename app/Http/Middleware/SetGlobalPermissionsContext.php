<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\PermissionScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes permission checks to global staff roles for the /admin panel.
 *
 * Getting this backwards — leaving the panel in a team scope, or leaving product
 * routes in the global scope — is the most likely authorisation bug in the app,
 * so both directions are covered by tests.
 */
class SetGlobalPermissionsContext
{
    public function handle(Request $request, Closure $next): Response
    {
        PermissionScope::global();

        return $next($request);
    }
}
