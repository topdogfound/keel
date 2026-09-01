<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use App\Support\PermissionScope;
use Database\Seeders\RolesAndPermissionsSeeder;

it('renders Shield role management for staff and refuses everyone else', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $staff = User::factory()->create();
    PermissionScope::global();
    $staff->assignRole(StaffRole::SuperAdmin->value);

    $this->actingAs($staff)->get('/admin/shield/roles')->assertSuccessful();
    $this->actingAs(User::factory()->create())->get('/admin/shield/roles')->assertForbidden();
});
