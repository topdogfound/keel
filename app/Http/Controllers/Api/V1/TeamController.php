<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Teams the authenticated user belongs to.
 *
 * Filtering, sorting and includes come from spatie/laravel-query-builder so
 * every collection endpoint shares one query-string vocabulary rather than each
 * inventing its own `?sort=`/`?filter=` dialect.
 */
class TeamController extends ApiController
{
    public function index(): JsonResponse
    {
        $teams = QueryBuilder::for(auth()->user()->teams()->getQuery())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('is_personal'),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate(perPage: min((int) request()->integer('per_page', 15), 100))
            ->appends(request()->query());

        return $this->ok(
            TeamResource::collection($teams->items()),
            [
                'page' => $teams->currentPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
            ],
        );
    }

    public function show(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        return $this->ok(new TeamResource($team));
    }
}
