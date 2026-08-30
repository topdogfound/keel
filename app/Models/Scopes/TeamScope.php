<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\CurrentTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query on a tenant-owned model to the current team.
 *
 * Route guards like EnsureTeamMembership control which *pages* a user reaches;
 * they do nothing about what a query returns. This does, so the safe path is
 * the default path rather than something each developer has to remember.
 *
 * @implements Scope<Model>
 */
class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $teamId = CurrentTeam::id();

        if ($teamId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('team_id'), $teamId);
    }
}
