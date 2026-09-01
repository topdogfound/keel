# 0002 — Two permission vocabularies, split by who owns them

**Status:** accepted

## Context

Permissions arrive from two directions:

- `App\Enums\TeamPermission` — `team:update`, `member:add`, … Hand-written,
  typed, referenced directly from application code and policies.
- Filament Shield — `Update:Team`, `ViewAny:User`, … Generated, one per resource
  action, regenerated whenever a resource is added.

Briefly the project had both covering the same operations: 36 Shield names
beside 15 near-duplicate enum cases. That is the kind of duplication that
quietly diverges.

## Decision

Split by ownership rather than picking one:

- **Tenant permissions stay typed** in `TeamPermission`. They are product
  behaviour, referenced from code, and benefit from the compiler.
- **Staff permissions belong to Shield.** They track the resource set, and a
  hand-maintained mirror would drift the first time anyone adds a resource.

`App\Enums\StaffPermission` was therefore deleted. `StaffRole` keeps only the
role names (`super_admin`, `support`), which are referenced by `isStaff()`,
`canAccessPanel()` and the dashboard gates.

`RolesAndPermissionsSeeder` seeds both: the tenant half from the enums, the
staff half by invoking `shield:generate`.

## Consequences

- `TeamPolicy` checks Shield's names as **plain strings** in its staff branches.
  That is deliberate — they are Shield's to regenerate, not ours to declare.
- The seeder must call `shield:generate` with `--option=permissions` and
  `--ignore-existing-policies`. The bare command also regenerates *policies*,
  and it will happily overwrite a hand-written one — see ADR 0003.
- Adding a Filament resource automatically adds its staff permissions on the
  next seed. Adding a tenant capability means adding an enum case.
