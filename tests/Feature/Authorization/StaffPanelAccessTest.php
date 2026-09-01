<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies the staff panel to a user with no role', function (): void {
    $user = User::factory()->create();

    expect($user->isStaff())->toBeFalse();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('grants the staff panel to a user with a global role', function (): void {
    $user = User::factory()->create();
    $user->assignRole(StaffRole::SuperAdmin->value);

    expect($user->isStaff())->toBeTrue()
        ->and($user->hasStaffRole(StaffRole::SuperAdmin))->toBeTrue();

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('guards the panel behind authentication', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});
