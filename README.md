# Keel

A Docker-first Laravel monolith template. Clone it, run one command, start building.

> A keel is the structural backbone a ship is built on — the member that makes a
> vessel stable and seaworthy.

## Requirements

**Docker. That's it.**

No PHP, no Composer, no Node, no PostgreSQL on your machine. Everything runs in
containers, including the Composer install that bootstraps the project. If you
already have PHP or Node installed, Keel ignores them — so every developer on the
team gets a byte-identical environment regardless of what their laptop looks like.

## Getting started

```bash
git clone <your-repo> && cd <your-repo>
./keel setup
```

That's the whole thing. It bootstraps dependencies in a throwaway container,
builds the app image, starts the stack, migrates, seeds and builds assets.

| | |
|---|---|
| App | http://localhost |
| Admin panel | http://localhost/admin |
| Mailpit | http://localhost:8025 |
| Database GUI | `./keel db-gui` → http://localhost:8081 |

Demo accounts are seeded with the password `password` — see `DemoSeeder`.

## The stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 on PHP 8.4 |
| Product UI | Inertia v3 + React 19 + TypeScript + Tailwind 4 |
| Auth | Fortify — password, 2FA, passkeys — with teams and invitations |
| Database | PostgreSQL 18 |
| Cache / queue | Redis |
| Mail | Mailpit (local capture) |
| Local env | Laravel Sail |

## Commands

Everything goes through `./keel`. Run `./keel help` for the full list.

```bash
./keel up                 # start the stack
./keel down               # stop it (keeps data; `down -v` would drop it)
./keel dev                # Vite dev server with HMR
./keel test               # Pint, PHPStan and the test suite
./keel shell              # a shell inside the app container
./keel artisan migrate    # any artisan command
./keel e2e                # Playwright browser tests (+ accessibility)
./keel doctor             # diagnose a broken environment
```

### Starting a new project from this template

```bash
./keel new acme/widgets
```

Renames the package, resets the app identity, strips demo content and starts a
fresh git history.

## Types shared with the frontend

PHP is the source of truth for anything crossing into React. Mark a DTO or enum
with `#[TypeScript]` and it is emitted into `resources/js/types/generated.d.ts`
as `App.Data.*` / `App.Enums.*`:

```php
#[TypeScript]
readonly class UserTeam
{
    public function __construct(public int $id, public string $name) {}
}
```

```ts
const team: App.Data.UserTeam = props.team;
```

Regenerate with `./keel ts`. `./keel types` regenerates and then typechecks, and
`./keel setup` and CI both do it before building. The file is gitignored, like
Wayfinder's output -- renaming a property in PHP breaks the TypeScript build
rather than reaching the browser as `undefined`.

## API

Versioned from the first endpoint at `/api/v1`, authenticated with Sanctum
tokens. Every collection endpoint shares one query-string vocabulary via
`spatie/laravel-query-builder`:

```
GET /api/v1/teams?filter[name]=acme&sort=-created_at&per_page=25
```

Errors use a single envelope, whatever the status:

```json
{ "error": { "status": 403, "message": "...", "errors": null, "request_id": "..." } }
```

That `request_id` is the same one in `X-Request-Id` and in the logs, so a bug
report ties straight back to what actually happened.

OpenAPI docs are generated from the code with no annotations to maintain --
browse them at `/docs/api` (local only, or staff elsewhere).

## Conventions

- **Never run `npm` on the host.** `package.json` pins platform-specific native
  binaries (rollup, tailwind oxide, lightningcss). A `node_modules` built on
  macOS or Windows and mounted into the Linux container fails at build time.
  Always use `./keel npm`. If you slip, `./keel node-reset` fixes it.
- **`./keel down -v` destroys the database.** Plain `./keel down` does not.

## Troubleshooting

Run `./keel doctor` first — it checks for most of the below automatically.

**`Cannot find module @rollup/rollup-linux-x64-gnu`** (or a lightningcss/oxide
load error) — npm was run on the host. Fix with `./keel node-reset`.

**A port is already in use** — override it in `.env`: `APP_PORT`,
`VITE_PORT`, `FORWARD_DB_PORT`, `PGWEB_PORT`.

**Files created by artisan are owned by root** — `WWWUSER`/`WWWGROUP` in `.env`
don't match your host user. Re-run `./keel setup`.

**PHPStan fails with `Undefined constant "Larastan\Larastan\LARAVEL_VERSION"`**
— this almost always means **the application cannot boot**, not that anything is
wrong with PHPStan. Larastan boots Laravel inside PHPStan's bootstrap and
swallows any exception, so a fatal in a service provider or model surfaces later
as this unrelated-looking message. Run `./keel artisan about` to see the real
error, fix that, and PHPStan goes green again.

**HMR doesn't reload** — on some Docker Desktop setups bind-mount file events
don't propagate. Set `VITE_USE_POLLING=1` in `.env` and restart `./keel dev`.
