# 0001 — Spatie permissions with teams, and the reserved global scope

**Status:** accepted

## Context

The application has two kinds of authority:

- **Tenant authority** — what a member may do inside *their* team.
- **Staff authority** — what the people running the product may do in `/admin`,
  across every tenant.

`spatie/laravel-permission` is configured with `'teams' => true`, so every role
assignment is scoped to a team. The obvious way to express staff roles is then
to assign them with `team_id = null`, meaning "not any team".

## The problem

That is not possible. In the published schema:

- `roles.team_id` **is** nullable — a *role definition* can be global, meaning
  it is available to every team.
- `model_has_roles.team_id` is **NOT NULL and part of the primary key** — every
  *assignment* must name a team.

So a null-scoped assignment cannot be stored at all.

## Decision

Use a reserved scope id for global assignments: `PermissionScope::GLOBAL` (`0`).

This works because `model_has_roles.team_id` carries **no foreign key** to
`teams`, so the sentinel needs no placeholder team row, and real team ids start
at 1 so it can never collide with a tenant.

Two middleware set the scope, and getting them backwards is the most likely
authorisation bug in the app:

- `SetPermissionsTeamContext` → the current team, on web and API routes.
- `SetGlobalPermissionsContext` → `PermissionScope::GLOBAL`, on the Filament panel.

## Consequences

- A team owner is **not** staff, and a staff user browsing the product UI gets
  no elevated powers there. Both directions are covered by tests in
  `tests/Feature/Authorization/`.
- API routes need the team middleware too. Token requests carry no session, so
  without it team roles never resolve and every policy check fails closed — in a
  way that looks like a permissions bug rather than a missing middleware.
- `App\Concerns\HasTeams::teams()` collides with `HasRoles::teams()`. The
  application's meaning wins via `insteadof`; Spatie's is aliased to
  `permissionTeams()`.

## Do not

Replace the sentinel with `null` "to clean it up". It cannot be stored.
