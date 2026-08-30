<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Support\PermissionScope;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->staff = User::factory()->create();
    PermissionScope::global();
    $this->staff->assignRole(StaffRole::SuperAdmin->value);
});

it('lists every team regardless of tenant scope', function (): void {
    // Two teams owned by different people; staff must see both.
    $a = Team::factory()->create(['name' => 'Alpha']);
    $b = Team::factory()->create(['name' => 'Beta']);

    $this->actingAs($this->staff)
        ->get('/admin/teams')
        ->assertSuccessful()
        ->assertSee('Alpha')
        ->assertSee('Beta');
});

it('reaches the users resource', function (): void {
    $this->actingAs($this->staff)
        ->get('/admin/users')
        ->assertSuccessful();
});

it('refuses staff resources to an ordinary team member', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Owner]);

    $this->actingAs($user)->get('/admin/teams')->assertForbidden();
    $this->actingAs($user)->get('/admin/users')->assertForbidden();
});
