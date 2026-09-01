<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * Base for every v1 endpoint.
 *
 * The point is a single response envelope decided once, before the first
 * endpoint invents its own. Error shapes are handled centrally in
 * bootstrap/app.php so successes and failures stay symmetrical.
 */
abstract class ApiController extends Controller
{
    // Laravel 13's base controller no longer pulls this in, so $this->authorize()
    // is undefined unless a controller opts back in.
    use AuthorizesRequests;

    /**
     * @param  array<string, mixed>|list<mixed>  $meta
     */
    protected function ok(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json(
            $meta === [] ? ['data' => $data] : ['data' => $data, 'meta' => $meta],
            $status,
        );
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
