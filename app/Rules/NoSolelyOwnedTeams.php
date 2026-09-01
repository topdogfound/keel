<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

/**
 * Refuses account deletion while the user is the only owner of a shared team.
 *
 * Without this the account deletes cleanly, the foreign key cascade removes the
 * membership, and the team is left standing with zero owners: the remaining
 * members cannot update it, delete it, invite anyone or manage it in any way.
 * The team and its data are stranded permanently.
 *
 * Blocking is deliberate. Auto-transferring hands someone control of a team
 * without asking them, and cascading destroys data belonging to members who had
 * no say -- so the user is asked to make that call explicitly instead.
 *
 * Personal teams are exempt: nobody else can be affected, so they go with the
 * account.
 */
class NoSolelyOwnedTeams implements ValidationRule
{
    public function __construct(private readonly User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $stranded = self::strandedTeams($this->user);

        if ($stranded->isEmpty()) {
            return;
        }

        $fail(sprintf(
            'You are the only owner of %s. Transfer ownership or delete %s before deleting your account: %s',
            $stranded->count() === 1 ? 'a team' : 'these teams',
            $stranded->count() === 1 ? 'it' : 'them',
            $stranded->pluck('name')->implode(', '),
        ));
    }

    /**
     * Shared teams this user solely owns, which would be orphaned by deletion.
     *
     * @return Collection<int, Team>
     */
    public static function strandedTeams(User $user): Collection
    {
        return $user->ownedTeams()
            ->where('teams.is_personal', false)
            ->get()
            ->filter(fn (Team $team): bool => $team->memberships()
                ->where('role', TeamRole::Owner->value)
                ->count() === 1
            )
            ->values();
    }
}
