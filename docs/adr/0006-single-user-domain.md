# 0006 — A single-user domain; teams and tenancy removed

**Status:** accepted

Supersedes [0001](0001-spatie-permissions-with-teams.md) and
[0003](0003-tenant-isolation-by-global-scope.md).

## Context

Keel shipped a full multi-tenant feature: `Team`, `Membership`,
`TeamInvitation`, a `BelongsToTeam` global scope, a `CurrentTeam` resolver,
team-scoped Spatie roles with a reserved global scope for staff, a
`make:tenant-model` generator, team middleware, team-aware policies, a Filament
Team resource and the React pages for all of it — roughly 120 files.

It worked. The problem is what it costs a **template**.

Every project started from Keel inherited an opinion about tenancy before it had
a domain: teams are the tenant, membership is a pivot with roles, a personal team
is created on registration, invitations are emailed and accepted by URL. A
project whose tenant is an organisation with sub-accounts, or a project with no
tenancy at all, does not get to skip that — it has to unpick it, and unpicking
tenancy after the fact is exactly the change most likely to leave a query
unscoped.

The tenancy code was also the largest single source of documentation debt: five
of the fifteen manual-testing sections, two of the five ADRs and two architecture
tests existed to describe machinery a given project might never want.

## Decision

Remove it. The domain is `User` plus `StaffRole`.

What stays is the part that is true of nearly every application and is genuinely
awkward to add later:

- **The `/admin` split.** Two presentations over one domain, with staff authority
  as a property of the user rather than something the product UI can grant. That
  boundary is architectural; retrofitting it means revisiting every policy.
- **Shield RBAC** for the staff panel ([0002](0002-two-permission-vocabularies.md)).
- Everything infrastructural: request ids, the API error envelope, health checks,
  the PHP→TypeScript pipeline, strict runtime defaults, the Docker workflow.

## Consequences

- The seeded demo is three users, not two organisations and five people. A fresh
  `./keel setup` lands on something you can read in a minute.
- `spatie/laravel-permission` runs **without** team scoping. The team keys were
  dropped from `config/permission.php` entirely, so the package default (off)
  applies and role assignments carry no scope id.
- ADRs 0001 and 0003 are kept rather than deleted. They are the map for adding
  tenancy back: 0001 documents a schema constraint in `spatie/laravel-permission`
  that is still there, and 0003 makes the case for isolating at the query layer
  rather than the route layer. Both are reasoning you want _before_ writing the
  first tenant-owned model, and neither is discoverable from the code once the
  code is gone.
- `spatie/laravel-query-builder` remains a dependency with no current caller. It
  was the shared filter/sort vocabulary for the team endpoints. Left installed
  because the first real collection endpoint will want it; remove it if you
  disagree.

## If you are adding tenancy back

Read 0003 first, then 0001. The order matters: 0003 tells you to isolate with a
global scope and an architecture test rather than middleware, and 0001 tells you
why the staff role assignment cannot simply be `team_id = null`.
