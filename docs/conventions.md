# Conventions

How this codebase is organised, and the rules that keep it that way.

## Two presentations, one domain

Inertia and Filament are **two presentations over one domain**, never two
applications:

```
app/Models, app/Actions, app/Policies   the shared domain
app/Http/Controllers + resources/js     the product UI
app/Filament                            the staff back-office at /admin
```

Both layers call the same Actions and the same Policies. A Filament resource
that reimplements an Action, or answers an authorisation question differently
from the product, is the drift this structure exists to prevent.

`UserPolicy` is the worked example: Filament's `UserResource` authorises through
it, and so does any product code that touches another user. There is one answer
to "may this person edit that user", in one file.

## What the architecture tests enforce

These fail the build rather than relying on review — see
[`tests/Arch/LayeringTest.php`](../tests/Arch/LayeringTest.php):

- No `dd`, `dump`, `ray`, `var_dump`, `print_r`, `die` or `exit` reaches a commit.
- `app/Actions` does not depend on `Illuminate\Http` (`App\Actions\Fortify` is
  exempt — Fortify hands its actions the request).
- `app/Models` does not depend on `app/Http/Controllers`.
- Enums are string-backed, so they persist cleanly.
- Controllers are suffixed `Controller`.
- Nothing in `App` calls `env`, `compact` or `extract`.
- Every file declares `strict_types=1`.

Generators that do not use Laravel's stubs — Filament's and Telescope's —
produce files without strict types. Run `./keel lint` after generating; the arch
test will catch it either way.

## Adding a model

```bash
./keel artisan make:model Invoice -mfs --policy
```

The stubs in [`stubs/`](../stubs/) are published, so anything Laravel generates
already carries `declare(strict_types=1)` and passes the arch test. That is the
only reason those 57 files are in the repo — don't delete them thinking they are
unused.

Register the policy's Filament permissions by re-running
`./keel artisan db:seed --class=RolesAndPermissionsSeeder`; it invokes
`shield:generate` and is idempotent.

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

The same applies to routes: Wayfinder generates `resources/js/{actions,routes}`
from the route table at build time. Both directories are gitignored. Import the
generated helper rather than hand-writing a URL string.

## Taking upstream starter-kit changes

This template has diverged from `laravel/react-starter-kit` deliberately, and
these are the places a merge will conflict:

| Area                  | Divergence                                              |
| --------------------- | ------------------------------------------------------- |
| `app/Models/User.php` | staff helpers, `canAccessPanel`, passkeys, activity log |
| `bootstrap/app.php`   | request ids, the API error envelope                     |
| `app/Providers/`      | health checks, TS transformer, the staff gates          |
| `app/Http/Responses/` | Fortify's redirect responses are overridden             |
| `database/seeders/`   | roles are infrastructure, demo data is separate         |
| every `app/` file     | `declare(strict_types=1)`                               |

Take upstream changes as a diff to review, not a merge to accept. Re-run
`./keel test` and the browser suite afterwards.

## Before you believe it works

```bash
docker compose down -v && rm -rf vendor node_modules .env
./keel setup
```

The only check that exercises the bootstrap path. It has caught two real bugs
that were invisible while `vendor/` existed.
