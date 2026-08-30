<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\Permission\PermissionRegistrar;

/**
 * Keel runs spatie/laravel-permission with `teams => true`, which gives every
 * role assignment a team scope.
 *
 * That covers tenant roles directly, but staff roles for the /admin panel are
 * not owned by any team. The obvious answer — assigning them with a null team —
 * is not available: `model_has_roles.team_id` is NOT NULL *and* part of the
 * primary key, so only `roles.team_id` (the role definition) may be null.
 *
 * So global assignments use a reserved scope id instead. `model_has_roles.team_id`
 * carries no foreign key to `teams`, so this needs no placeholder team row, and
 * because real team ids start at 1 it can never collide with a tenant.
 */
final class PermissionScope
{
    /**
     * The scope under which staff (non-tenant) roles are assigned.
     */
    public const GLOBAL = 0;

    /**
     * Scope permission checks to a specific team.
     */
    public static function team(int $teamId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    }

    /**
     * Scope permission checks to global staff roles.
     */
    public static function global(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(self::GLOBAL);
    }

    /**
     * The scope currently in effect, if any.
     */
    public static function current(): int|string|null
    {
        return app(PermissionRegistrar::class)->getPermissionsTeamId();
    }
}
