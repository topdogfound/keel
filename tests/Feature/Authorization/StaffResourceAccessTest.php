<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole(StaffRole::SuperAdmin->value);
});

it('reaches the users resource', function (): void {
    User::factory()->count(2)->create();

    $this->actingAs($this->staff)
        ->get('/admin/users')
        ->assertSuccessful();
});

it('refuses staff resources to an ordinary user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
});
