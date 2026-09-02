# 0002 — Staff permissions belong to Shield, not to an enum

**Status:** accepted

_Revised after the team feature was removed ([0006](0006-single-user-domain.md)).
The original decision split permissions between a hand-written tenant enum and
Shield; only the Shield half survives, and the reasoning for it is unchanged._

## Context

`/admin` is a Filament panel with resources, and Filament Shield generates one
permission per resource action — `ViewAny:User`, `Update:User`, `Delete:Role` —
regenerating the set whenever a resource is added.

The tempting alternative is a typed `StaffPermission` enum: autocompletion, a
compiler that catches typos, permissions visible in the code rather than in a
database table. The project briefly had both, and they immediately described the
same operations twice.

## Decision

Staff permissions live in Shield's vocabulary. There is no enum mirroring them.

A hand-maintained mirror drifts the first time anyone adds a resource and
forgets the enum — and the failure mode is a permission that exists in code but
was never seeded, so the check fails closed and looks like a bug in the policy.

`App\Enums\StaffRole` keeps only the **role** names (`super_admin`, `support`).
Roles are a small, deliberate set that the application reasons about directly:
`isStaff()`, `canAccessPanel()` and the `viewPulse` / `viewApiDocs` /
`viewHorizon` / `viewTelescope` gates all resolve through them. Permissions are
the large generated set. The split is by who owns the list, not by taste.

## Consequences

- Policies check Shield's names as **plain strings** — `$user->can('Update:User')`
  in `UserPolicy` and `RolePolicy`. That is deliberate: they are Shield's to
  regenerate, not ours to declare.
- `RolesAndPermissionsSeeder` must call `shield:generate` with
  `--option=permissions` and `--ignore-existing-policies`. The bare command also
  regenerates _policies_, and it will happily overwrite a hand-written one.
- Adding a Filament resource adds its staff permissions on the next seed. Run
  `./keel artisan db:seed --class=RolesAndPermissionsSeeder`; it is idempotent.
- Default grants are set once in the seeder — `super_admin` gets everything,
  `support` gets the `View*` subset — and widened afterwards in Shield's UI at
  `/admin/shield/roles`, not by editing code.
