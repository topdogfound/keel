<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Rules\NoSolelyOwnedTeams;
use App\Support\PermissionScope;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

/**
 * Deleting the sole owner of a shared team used to succeed and leave the team
 * standing with zero owners: the foreign key cascade removed the membership,
 * and nobody remaining could update, delete or manage it. The team and its
 * data were stranded permanently.
 */
function ownerOfSharedTeam(): array
{
    $owner = User::factory()->create();
    $team = resolve(CreateTeam::class)->handle($owner, 'Shared Team');

    PermissionScope::team($team->id);
    $owner->assignRole(TeamRole::Owner->value);

    $member = User::factory()->create();
    $team->memberships()->create(['user_id' => $member->id, 'role' => TeamRole::Member]);

    return [$owner, $member, $team];
}

it('refuses to delete an account that would strand a team', function (): void {
    [$owner, , $team] = ownerOfSharedTeam();

    $this->actingAs($owner)
        ->from('/settings/profile')
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasErrors('password');

    expect(User::find($owner->id))->not->toBeNull()
        ->and(Team::find($team->id))->not->toBeNull();
});

it('names the teams blocking the deletion so the user can act', function (): void {
    [$owner] = ownerOfSharedTeam();

    // assertInvalid does a substring match on the error message.
    $this->actingAs($owner)
        ->from('/settings/profile')
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertInvalid(['password' => 'Shared Team']);
});

/**
 * The rule must not block legitimate deletions, or it just moves the problem.
 */
it('allows deletion when someone else can still own the team', function (): void {
    [$owner, $member, $team] = ownerOfSharedTeam();

    // A second owner means nothing is stranded.
    $team->memberships()->where('user_id', $member->id)->update(['role' => TeamRole::Owner]);

    expect(NoSolelyOwnedTeams::strandedTeams($owner->fresh()))->toBeEmpty();

    $this->actingAs($owner)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors();

    expect(User::find($owner->id))->toBeNull();
});

it('allows deletion when the user only owns a personal team', function (): void {
    $user = User::factory()->create();

    expect(NoSolelyOwnedTeams::strandedTeams($user))->toBeEmpty();

    $this->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors();

    expect(User::find($user->id))->toBeNull();
});

it('allows deletion for a plain member of someone else team', function (): void {
    [, $member] = ownerOfSharedTeam();

    expect(NoSolelyOwnedTeams::strandedTeams($member))->toBeEmpty();

    $this->actingAs($member)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors();
});
