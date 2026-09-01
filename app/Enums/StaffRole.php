<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles that grant access to the /admin staff panel.
 *
 * Their *permissions* are deliberately not listed here. Filament Shield
 * generates one permission per resource action (View:User, Update:User, ...)
 * and regenerates them as resources are added, so a hand-maintained enum
 * mirroring that set would drift the first time someone adds a resource.
 * Staff permissions therefore live in Shield's vocabulary and are managed in
 * its UI; RolesAndPermissionsSeeder sets the defaults.
 */
enum StaffRole: string
{
    case SuperAdmin = 'super_admin';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Support => 'Support',
        };
    }
}
