<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Support\PermissionScope;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Creates a user holding $role in a fresh team, and returns both.
 *
 * @return array{0: User, 1: Team}
 */
function memberOfTeam(TeamRole $role): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->memberships()->create(['user_id' => $user->id, 'role' => $role]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    PermissionScope::team($team->id);
    $user->assignRole($role->value);

    return [$user, $team];
}

it('denies the staff panel to a user with no global role', function (): void {
    [$owner] = memberOfTeam(TeamRole::Owner);

    expect($owner->isStaff())->toBeFalse();

    $this->actingAs($owner)->get('/admin')->assertForbidden();
});

it('grants the staff panel to a user with a global role', function (): void {
    $user = User::factory()->create();

    PermissionScope::global();
    $user->assignRole(StaffRole::SuperAdmin->value);

    expect($user->isStaff())->toBeTrue()
        ->and($user->hasStaffRole(StaffRole::SuperAdmin))->toBeTrue();

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

/**
 * The direction test: a team role must never leak into the global scope, or
 * every team owner would reach the staff panel.
 */
it('does not treat a team role as a staff role', function (): void {
    [$owner, $team] = memberOfTeam(TeamRole::Owner);

    PermissionScope::team($team->id);
    expect($owner->can(TeamPermission::DeleteTeam->value))->toBeTrue();

    expect($owner->isStaff())->toBeFalse();
    $this->actingAs($owner)->get('/admin')->assertForbidden();
});

/**
 * And the reverse: a staff role must not confer team permissions.
 */
it('does not treat a staff role as a team role', function (): void {
    [$member, $team] = memberOfTeam(TeamRole::Member);

    PermissionScope::global();
    $member->assignRole(StaffRole::SuperAdmin->value);
    $member->unsetRelation('roles');

    PermissionScope::team($team->id);
    $member->unsetRelation('roles');

    expect($member->can(TeamPermission::DeleteTeam->value))->toBeFalse();
});

it('scopes team roles to their own team', function (): void {
    [$owner] = memberOfTeam(TeamRole::Owner);
    [, $otherTeam] = memberOfTeam(TeamRole::Owner);

    // The first owner has no standing in a team they don't belong to.
    PermissionScope::team($otherTeam->id);
    $owner->unsetRelation('roles');

    expect($owner->can(TeamPermission::DeleteTeam->value))->toBeFalse()
        ->and($owner->roles()->pluck('name')->all())->toBe([]);
});

it('guards the panel behind authentication', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});
