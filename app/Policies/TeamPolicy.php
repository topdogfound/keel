<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Support\PermissionScope;

/**
 * One policy, two callers.
 *
 * The product UI and the staff panel are two presentations over the same
 * domain, so they must share an authorisation surface rather than each
 * inventing one -- a Filament resource that answers "can this staff member
 * edit any team?" with different logic to the product's "can this member edit
 * their own team?" is exactly the drift this template exists to prevent.
 *
 * They are told apart by the active permission scope, which the middleware
 * already sets: PermissionScope::GLOBAL for /admin, the team id for product
 * routes.
 *
 * Staff branches check Filament Shield's generated permission names as plain
 * strings, because Shield owns and regenerates that vocabulary. Product
 * branches check the typed TeamPermission enum, which is application code.
 * StaffPermissionsMatchShieldTest guards the staff strings against drift.
 */
class TeamPolicy
{
    /**
     * Whether this request is the staff panel acting outside any tenant.
     */
    private function actingAsStaff(User $user): bool
    {
        return PermissionScope::current() === PermissionScope::GLOBAL && $user->isStaff();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($this->actingAsStaff($user)) {
            return $user->can('ViewAny:Team');
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        if ($this->actingAsStaff($user)) {
            return $user->can('View:Team');
        }

        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        if ($this->actingAsStaff($user)) {
            return $user->can('Update:Team');
        }

        return $user->hasTeamPermission($team, TeamPermission::UpdateTeam);
    }

    /**
     * Determine whether the user can leave the team.
     */
    public function leave(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && $user->belongsToTeam($team)
            && ! $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can add a member to the team.
     */
    public function addMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::AddMember);
    }

    /**
     * Determine whether the user can update a member's role in the team.
     */
    public function updateMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::UpdateMember);
    }

    /**
     * Determine whether the user can remove a member from the team.
     */
    public function removeMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::RemoveMember);
    }

    /**
     * Determine whether the user can invite members to the team.
     */
    public function inviteMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CreateInvitation);
    }

    /**
     * Determine whether the user can cancel invitations.
     */
    public function cancelInvitation(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CancelInvitation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        if ($this->actingAsStaff($user)) {
            return $user->can('Delete:Team');
        }

        return ! $team->is_personal && $user->hasTeamPermission($team, TeamPermission::DeleteTeam);
    }
}
