<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Scopes\TeamScope;
use App\Models\Team;
use App\Support\CurrentTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as owned by a team.
 *
 * Adds the global scope that filters every query to the current team, and fills
 * team_id on create so a forgotten assignment can't silently produce an orphan
 * or, worse, a row attributed to the wrong tenant.
 *
 * Any model with a team_id column should use this. An architecture test enforces
 * that, because a single model that opts out is a cross-tenant leak.
 *
 * @phpstan-require-extends Model
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope(new TeamScope);

        static::creating(function ($model): void {
            if ($model->team_id === null) {
                $model->team_id = CurrentTeam::id();
            }
        });
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Query across every team.
     *
     * This is the sanctioned way out of the scope, and it is deliberately loud:
     * an architecture test asserts nothing outside app/Filament calls it, so the
     * staff panel can be cross-tenant while the product never is.
     *
     * @return Builder<static>
     */
    public static function acrossAllTeams(): Builder
    {
        return static::query()->withoutGlobalScope(TeamScope::class);
    }
}
