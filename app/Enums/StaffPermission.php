<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Staff-panel permissions, named with the same `resource:action` convention the
 * kit already uses for TeamPermission so both read alike.
 */
enum StaffPermission: string
{
    case ViewUsers = 'staff.user:view';
    case UpdateUsers = 'staff.user:update';
    case DeleteUsers = 'staff.user:delete';

    case ViewTeams = 'staff.team:view';
    case UpdateTeams = 'staff.team:update';
    case DeleteTeams = 'staff.team:delete';

    case ViewActivity = 'staff.activity:view';
    case ManageRoles = 'staff.role:manage';
}
