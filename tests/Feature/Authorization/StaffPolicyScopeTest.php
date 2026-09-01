<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Support\PermissionScope;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->staff = User::factory()->create();
    PermissionScope::global();
    $this->staff->assignRole(StaffRole::SuperAdmin->value);

    $this->owner = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->team->memberships()->create(['user_id' => $this->owner->id, 'role' => TeamRole::Owner]);
    PermissionScope::team($this->team->id);
    $this->owner->assignRole(TeamRole::Owner->value);

    $this->otherTeam = Team::factory()->create();
});

/**
 * TeamPolicy serves both the staff panel and the product UI, told apart by the
 * active permission scope. All four combinations matter; the last two are the
 * ones that would quietly grant too much.
 */
it('lets staff update any team in the global scope', function (): void {
    PermissionScope::global();
    $this->staff->unsetRelation('roles');

    expect($this->staff->can('update', $this->otherTeam))->toBeTrue();
});

it('lets an owner update their own team', function (): void {
    PermissionScope::team($this->team->id);
    $this->owner->unsetRelation('roles');

    expect($this->owner->can('update', $this->team))->toBeTrue();
});

it('refuses an owner on a team they do not belong to', function (): void {
    PermissionScope::team($this->team->id);
    $this->owner->unsetRelation('roles');

    expect($this->owner->can('update', $this->otherTeam))->toBeFalse();
});

/**
 * Staff powers must not follow the user into the product UI: inside a team
 * scope they are an ordinary member of that team, or not a member at all.
 */
it('does not let staff powers leak into the product surface', function (): void {
    PermissionScope::team($this->team->id);
    $this->staff->unsetRelation('roles');

    expect($this->staff->can('update', $this->team))->toBeFalse();
});

it('seeds both permission vocabularies without them colliding', function (): void {
    $shield = Permission::query()->whereRaw("name ~ '^[A-Z]'")->count();
    $tenant = Permission::query()->whereRaw("name !~ '^[A-Z]'")->count();

    expect($shield)->toBeGreaterThan(0, 'Shield generates the staff panel permissions')
        ->and($tenant)->toBeGreaterThan(0, 'TeamPermission provides the tenant permissions');
});

it('gives support read-only staff permissions by default', function (): void {
    PermissionScope::global();

    $support = User::factory()->create();
    $support->assignRole(StaffRole::Support->value);
    $support->unsetRelation('roles');

    expect($support->can('ViewAny:Team'))->toBeTrue()
        ->and($support->can('Delete:Team'))->toBeFalse();
});
