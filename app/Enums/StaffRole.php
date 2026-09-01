<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles that grant access to the /admin staff panel.
 *
 * These are assigned under PermissionScope::GLOBAL rather than to any team --
 * they describe who runs the product, not who belongs to a tenant.
 *
 * Their *permissions* are deliberately not listed here. Filament Shield
 * generates one permission per resource action (View:Team, Update:User, ...)
 * and regenerates them as resources are added, so a hand-maintained enum
 * mirroring that set would drift the first time someone adds a resource.
 * Staff permissions therefore live in Shield's vocabulary and are managed in
 * its UI; StaffRoleSeeder sets the defaults.
 *
 * Tenant permissions are the opposite case and stay typed in TeamPermission:
 * they are product behaviour, referenced from application code.
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
