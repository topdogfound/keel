<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffPermission;
use App\Enums\StaffRole;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the Spatie roles and permissions from the enums that already describe
 * them, so the enum stays the single source of truth and this seeder never
 * drifts from it.
 *
 * Idempotent: safe to re-run after adding a case to either enum.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (TeamPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (StaffPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // team_id is null on the role *definition*, which makes each role
        // available to every team. The assignment in model_has_roles is what
        // carries the actual team scope.
        foreach (TeamRole::cases() as $role) {
            $this->role($role->value)->syncPermissions(
                array_map(fn (TeamPermission $p): string => $p->value, $role->permissions())
            );
        }

        foreach (StaffRole::cases() as $role) {
            $this->role($role->value)->syncPermissions(
                array_map(fn (StaffPermission $p): string => $p->value, $role->permissions())
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web', 'team_id' => null],
            ['name' => $name, 'guard_name' => 'web', 'team_id' => null],
        );
    }
}
