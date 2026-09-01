<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('seeds staff permissions from Filament Shield', function (): void {
    expect(Permission::query()->whereRaw("name ~ '^[A-Z]'")->count())->toBeGreaterThan(0);
});

it('gives super admin every staff permission', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole(StaffRole::SuperAdmin->value);

    expect($staff->can('ViewAny:User'))->toBeTrue()
        ->and($staff->can('Delete:User'))->toBeTrue();
});

it('gives support read-only staff permissions by default', function (): void {
    $support = User::factory()->create();
    $support->assignRole(StaffRole::Support->value);

    expect($support->can('ViewAny:User'))->toBeTrue()
        ->and($support->can('Delete:User'))->toBeFalse();
});

it('gives an ordinary user no staff permissions', function (): void {
    $user = User::factory()->create();

    expect($user->can('ViewAny:User'))->toBeFalse();
});
