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
git clone https://github.com/topdogfound/keel.git && cd keel
./keel setup
```

That's the whole thing. It bootstraps dependencies in a throwaway container,
builds the app image, starts the stack, migrates, seeds and builds assets.

|              |                                           |
| ------------ | ----------------------------------------- |
| App          | http://localhost:8765                     |
| Staff panel  | http://localhost:8765/admin               |
| Mailpit      | http://localhost:8767                     |
| Database GUI | `./keel db-gui` → http://localhost:8768   |
| Health       | http://localhost:8765/health (staff only) |

Demo accounts are seeded with the password `password`:

| Account                 | Can reach `/admin` as         |
| ----------------------- | ----------------------------- |
| `super_admin@keel.test` | Super Admin — full access     |
| `support@keel.test`     | Support — read-only           |
| `member@keel.test`      | — an ordinary user, no access |

See `DemoSeeder`; `./keel new` removes it.

## The stack

| Layer         | Choice                                          |
| ------------- | ----------------------------------------------- |
| Framework     | Laravel 13 on PHP 8.4                           |
| Product UI    | Inertia v3 + React 19 + TypeScript + Tailwind 4 |
| Staff panel   | Filament 5 with Shield, at `/admin`             |
| Auth          | Fortify — password, 2FA, passkeys               |
| Database      | PostgreSQL 18                                   |
| Cache / queue | Redis                                           |
| Mail          | Mailpit (local capture)                         |
| Local env     | Laravel Sail                                    |

The domain is deliberately small: one `User` model and a `StaffRole` enum. Teams,
tenancy and billing are things you add for your product, not things the template
decides for you — see [ADR 0006](docs/adr/0006-single-user-domain.md).

### Which services run

`./keel up` starts only the containers listed in `KEEL_SERVICES` in `.env` — by
default `pgsql,redis,mailpit,horizon,scheduler`. The app container
(`laravel.test`) is always on; everything else is optional and toggleable:

```bash
./keel services                    # list every service: enabled? running?
./keel services disable horizon    # edit KEEL_SERVICES in .env, then
./keel up                          # apply — a dropped service is stopped
```

Each name maps to a Docker Compose profile, so `KEEL_SERVICES` is just exported
as `COMPOSE_PROFILES` — raw `docker compose` follows the same list.

**Using a service you already run on the host** — drop it from `KEEL_SERVICES`
and point the app at your own:

| Drop      | Then set in `.env`                                          |
| --------- | ----------------------------------------------------------- |
| `pgsql`   | `DB_HOST=host.docker.internal`, `DB_PORT=<your port>`       |
| `redis`   | `REDIS_HOST=host.docker.internal`, `REDIS_PORT=<your port>` |
| `mailpit` | `MAIL_HOST` / `MAIL_PORT` / … for your own SMTP             |

Dropping `horizon` or `scheduler` means queued or scheduled work stops running —
`/health` reports it. A dropped service simply doesn't publish its host port.

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
fresh git history. `docs/` is kept on purpose — the new project inherits the
non-obvious decisions along with the code that depends on them.

## Types shared with the frontend

PHP is the source of truth for anything crossing into React. Mark a DTO or enum
with `#[TypeScript]` and it is emitted into `resources/js/types/generated.d.ts`
as `App.Data.*` / `App.Enums.*`:

```php
#[TypeScript]
readonly class DashboardStats
{
    public function __construct(public int $userCount, public string $generatedAt) {}
}
```

```ts
const stats: App.Data.DashboardStats = props.stats;
```

`App.Enums.StaffRole` is currently the only generated type — the template ships
the pipeline, not a pile of DTOs to delete.

Regenerate with `./keel ts`. `./keel types` regenerates and then typechecks, and
`./keel setup` and CI both do it before building. The file is gitignored, like
Wayfinder's output -- renaming a property in PHP breaks the TypeScript build
rather than reaching the browser as `undefined`.

## API

Versioned from the first endpoint at `/api/v1`, authenticated with Sanctum
tokens. Versioned from the _first_ endpoint deliberately: a breaking change later
means adding v2 rather than negotiating with every existing client.

Today that is one endpoint:

```
GET /api/v1/user
```

`App\Http\Controllers\Api\V1\ApiController` is the base your controllers extend,
with `ok()` and `noContent()` helpers so responses stay uniform as endpoints are
added.

Errors use a single envelope, whatever the status:

```json
{
    "error": {
        "status": 403,
        "message": "...",
        "errors": null,
        "request_id": "..."
    }
}
```

That `request_id` is the same one in `X-Request-Id` and in the logs, so a bug
report ties straight back to what actually happened.

OpenAPI docs are generated from the code with no annotations to maintain --
browse them at `/docs/api` (local only, or staff elsewhere).

## Health

`/up` stays public for load balancers and only proves PHP is executing.
`/health` and `/health.json` are staff-gated and report what actually breaks:
database, cache, queue worker, scheduler and disk.

The queue and scheduler checks only pass while the `horizon` and `scheduler`
Compose services are running — that is deliberate. Sail ships neither, so
without them scheduled and queued work silently never happens.

## Conventions

- **Never run `npm` on the host.** `package.json` pins platform-specific native
  binaries (rollup, tailwind oxide, lightningcss). A `node_modules` built on
  macOS or Windows and mounted into the Linux container fails at build time.
  Always use `./keel npm`. If you slip, `./keel node-reset` fixes it.
- **`./keel down -v` destroys the database.** Plain `./keel down` does not.

## Documentation

- [`docs/architecture.md`](docs/architecture.md) — what the template ships and
  how a request moves through it: the two presentations, auth, authorization,
  generated code, health.
- [`docs/manual-testing.md`](docs/manual-testing.md) — a guided pass over every
  surface: product UI, staff panel, PostgreSQL, API, queues, mail and health.
  Start here if you want to see what the template actually does.
- [`docs/conventions.md`](docs/conventions.md) — how the code is organised, what
  the architecture tests enforce, and how to take upstream starter-kit changes.
- [`docs/adr/`](docs/adr/) — why the non-obvious decisions are the way they are.
  Read the relevant one before "simplifying" the bootstrap, the service profiles
  or the staff permission model; the straightforward version was tried and did
  not work.
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — the loop to run before you push.

## Troubleshooting

Run `./keel doctor` first — it checks for most of the below automatically.

**`Cannot find module @rollup/rollup-linux-x64-gnu`** (or a lightningcss/oxide
load error) — npm was run on the host. Fix with `./keel node-reset`.

**A port is already in use** — every host port is defined once, in the
"Host ports" block near the top of `.env`, as one contiguous run of 8765-8771
picked to dodge the usual defaults:

| Port | Service      | Variable                         |
| ---- | ------------ | -------------------------------- |
| 8765 | App          | `APP_PORT`                       |
| 8766 | Vite / HMR   | `VITE_PORT`                      |
| 8767 | Mailpit UI   | `FORWARD_MAILPIT_DASHBOARD_PORT` |
| 8768 | pgweb        | `PGWEB_PORT`                     |
| 8769 | PostgreSQL   | `FORWARD_DB_PORT`                |
| 8770 | Redis        | `FORWARD_REDIS_PORT`             |
| 8771 | Mailpit SMTP | `FORWARD_MAILPIT_PORT`           |

Change the value there and compose, Vite and `./keel` all follow. `DB_PORT`,
`REDIS_PORT` and `MAIL_PORT` are _container-internal_ and should stay as they
are. `./keel doctor` reports any of these that a non-Keel process has taken —
except for a service you've dropped from `KEEL_SERVICES`, whose port is then
yours to use.

**Files created by artisan are owned by root** — `WWWUSER`/`WWWGROUP` in `.env`
don't match your host user. Re-run `./keel setup`.

**PHPStan fails with `Undefined constant "Larastan\Larastan\LARAVEL_VERSION"`**
— Larastan boots Laravel inside PHPStan's bootstrap and swallows any exception,
so this message never points at the real cause. Two things produce it:

1. **A stale result cache** — the common case. Fix:
   `./keel composer exec -- phpstan clear-result-cache`, then re-run.
2. **The application genuinely cannot boot** — a fatal in a service provider or
   model. If the error survives clearing the cache, run `./keel artisan about`;
   that shows the real error.

Always try the cache first, then check the boot.

**HMR is slow, intermittent, or only updates on refresh** — Docker Desktop mounts
the project through a VM, and host inotify events don't cross that boundary
reliably. Partial propagation is the usual symptom: some saves land instantly,
others not until you refresh. Set `VITE_USE_POLLING=1` in `.env`, then run
`./keel up`.

`./keel up` is the part that matters, and restarting `./keel dev` is not enough:
`./keel dev` execs into the running container, which keeps the environment it was
created with. `./keel doctor` flags this case, and you can confirm the setting
arrived with:

```bash
docker compose exec -T laravel.test env | grep VITE
```

Polling costs some idle CPU. Tune it with `VITE_POLL_INTERVAL` (ms, default 300).

## License

MIT — see [LICENSE](LICENSE).
