<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps every request with an id and puts it in the log context.
 *
 * Laravel's Context is propagated into queued jobs automatically, so one user
 * action stays traceable across web, queue and mail. Reconstructing what
 * happened from logs alone is the difference between a five minute and a
 * five hour incident.
 *
 * An inbound X-Request-Id is honoured so ids survive a proxy or gateway.
 */
class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header(self::HEADER) ?: (string) Str::uuid();

        Context::add('request_id', $requestId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
