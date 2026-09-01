# Conventions

How this codebase is organised, and the rules that keep it that way.

## Three layers, one domain

Inertia and Filament are **two presentations over one domain**, never two
applications:

```
app/Models, app/Actions, app/Policies   the shared domain
app/Http/Controllers + resources/js     the product UI (tenant-scoped)
app/Filament                            the staff back-office (cross-tenant)
```

Both layers call the same Actions and the same Policies. A Filament resource
that reimplements an Action, or answers an authorisation question differently
from the product, is the drift this structure exists to prevent.

`TeamPolicy` is the worked example: one policy, two callers, told apart by the
active permission scope.

## What the architecture tests enforce

These fail the build rather than relying on review:

- Every model with a `team_id` uses `BelongsToTeam` (allowlisted exceptions only).
- Nothing outside `app/Filament` calls `acrossAllTeams()`.
- `app/Actions` does not depend on `Illuminate\Http`.
- No `dd`, `dump`, `ray`, `var_dump` reaches a commit.
- Every file declares `strict_types=1`.

Generators that do not use Laravel's stubs — Filament's and Telescope's —
produce files without strict types. Run `./keel lint` after generating; the arch
test will catch it either way.

## Adding a tenant-owned model

```bash
./keel artisan make:tenant-model Invoice
```

Gives you the model, migration, policy, factory, test and Filament resource with
`BelongsToTeam` and a ULID public id already wired. Prefer this over `make:model`
so isolation is automatic rather than remembered.

## Queued work

Extend `App\Jobs\BaseJob`. It implements `ShouldQueueAfterCommit`, which removes
the classic bug where a job dispatched inside a transaction runs before that
transaction commits and cannot find its own row. It also sets sane retries and
backoff.

Do **not** redeclare `$afterCommit` — `Illuminate\Bus\Queueable` declares it with
no default, and PHP rejects a redeclaration whose default differs.

## Types crossing into React

PHP is the source of truth. Mark a DTO or enum `#[TypeScript]` and it is emitted
to `resources/js/types/generated.d.ts` as `App.Data.*` / `App.Enums.*`.
`./keel types` regenerates and typechecks. A renamed property breaks the build
instead of arriving as `undefined`.

## Taking upstream starter-kit changes

This template has diverged from `laravel/react-starter-kit` deliberately, and
these are the places a merge will conflict:

| Area                          | Divergence                                        |
| ----------------------------- | ------------------------------------------------- |
| `app/Policies/TeamPolicy.php` | serves both product and staff (ADR 0003)          |
| `app/Models/User.php`         | trait alias, staff helpers, `canAccessPanel`      |
| `bootstrap/app.php`           | request ids, permission scope, API error envelope |
| `database/seeders/`           | roles are infrastructure, demo data is separate   |
| every `app/` file             | `declare(strict_types=1)`                         |

Take upstream changes as a diff to review, not a merge to accept. Re-run
`./keel test` and the browser suite afterwards.

## Before you believe it works

```bash
docker compose down -v && rm -rf vendor node_modules .env
./keel setup
```

The only check that exercises the bootstrap path. It has caught two real bugs
that were invisible while `vendor/` existed.
