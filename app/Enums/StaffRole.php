<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles that grant access to the /admin staff panel.
 *
 * These are assigned under PermissionScope::GLOBAL rather than to any team —
 * they describe who runs the product, not who belongs to a tenant.
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

    /**
     * @return array<StaffPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => StaffPermission::cases(),
            self::Support => [
                StaffPermission::ViewUsers,
                StaffPermission::ViewTeams,
                StaffPermission::ViewActivity,
            ],
        };
    }
}
