# Manual testing walkthrough

A guided pass over everything the template does. Each check is written as
**action → what you should see**, so a wrong result is unambiguous.

Budget about half an hour for the whole thing, or jump to the section you care
about.

---

## Before you start

```bash
./keel up                                  # stack running
./keel artisan migrate:fresh --seed        # predictable baseline
./keel db-gui                              # pgweb (opt-in, behind a profile)
```

`./keel up` prints a URL banner for whatever services are enabled (see §10).

To watch queries, jobs and mail behind each click, set `TELESCOPE_ENABLED=true`
in `.env` (leave `.env.example` alone — it stays off by default) and run
`./keel artisan config:clear`.

### Where things live

**One application, on port 8765.** These are all routes inside the same Laravel
app. Port 80 is the default for HTTP, so the browser hides it —
`http://localhost:8765` is the app; the paths below hang off it.

| Surface         | URL                                                |
| --------------- | -------------------------------------------------- |
| Product UI      | `http://localhost:8765`                            |
| Staff panel     | `http://localhost:8765/admin`                      |
| Role management | `http://localhost:8765/admin/shield/roles`         |
| Health          | `http://localhost:8765/health` _(staff only)_      |
| API docs        | `http://localhost:8765/docs/api`                   |
| Horizon         | `http://localhost:8765/horizon`                    |
| Telescope       | `http://localhost:8765/telescope` _(once enabled)_ |

**Separate containers, so their own ports.**

| Service         | URL                                                      |
| --------------- | -------------------------------------------------------- |
| Mailpit         | `http://localhost:8767`                                  |
| pgweb           | `http://localhost:8768`                                  |
| Vite dev server | `http://localhost:8766` _(only while `./keel dev` runs)_ |

> ⚠️ **Every port above comes from the "Host ports" block near the top of
> `.env`** — one contiguous run of 8765-8771, chosen to avoid colliding with
> whatever else you have running. Change `APP_PORT` there and every URL in the
> first table follows, as do compose, Vite and the `./keel` banner. Don't touch
> `DB_PORT`, `REDIS_PORT` or `MAIL_PORT`: those are container-internal.

### Accounts

Password is `password` for all of them.

| Account                 | Who they are                                          |
| ----------------------- | ----------------------------------------------------- |
| `super_admin@keel.test` | Ada Lovelace — staff, every permission                |
| `support@keel.test`     | Grace Hopper — staff, read-only                       |
| `member@keel.test`      | Alan Turing — an ordinary user, no staff panel access |

Use **two browsers** (or one plus a private window) so you can hold two sessions
at once. Several checks compare what different people see.

---

## 1 · Product UI

| Do this                                 | Expect                                 |
| --------------------------------------- | -------------------------------------- |
| Open `http://localhost:8765`            | Welcome page, Log in / Register links  |
| Register a new account                  | Lands on `/dashboard`                  |
| Log out, log in as `member@keel.test`   | The dashboard                          |
| `/settings/profile`                     | Name and email, editable               |
| `/settings/security`                    | Password, two-factor, passkeys         |
| Enable two-factor                       | QR code and recovery codes appear      |
| Log out and back in                     | Prompted for the 2FA code              |
| `/settings/appearance`                  | Light / dark / system, applied at once |
| Change your password, then log in again | New password works, old one does not   |

`/settings/security` sits behind Laravel's `RequirePassword` middleware, so
reaching it after a while asks you to confirm your password first. That is the
guard working, not a bug.

## 2 · Staff panel

The `/admin` boundary is the authorization story in this template: staff
authority is a property of the user, not something the product UI can grant.

| As                      | Go to `/admin` | Expect                       |
| ----------------------- | -------------- | ---------------------------- |
| Logged out              |                | Redirected to `/admin/login` |
| `member@keel.test`      |                | **403 Forbidden**            |
| `support@keel.test`     |                | The dashboard                |
| `super_admin@keel.test` |                | The dashboard                |

Then compare the two staff roles on the **same page**, `/admin/users`. This is
Filament Shield's permission set made visible.

| As                      | Should be able to                      | Should **not** see             |
| ----------------------- | -------------------------------------- | ------------------------------ |
| `super_admin@keel.test` | List, view, create, edit, delete users | —                              |
| `support@keel.test`     | List and view users                    | Create, Edit or Delete actions |

`RolesAndPermissionsSeeder` grants Support only the `View*` permissions on
purpose — a read-only default you widen in Shield's UI rather than in code.

| Do this                          | Expect                                              |
| -------------------------------- | --------------------------------------------------- |
| `/admin/shield/roles`            | `super_admin` and `support`, with their permissions |
| As Support, open a role for edit | Refused — Support cannot manage roles               |

Check the boundary in the other direction too, since it is easy to get
backwards: `super_admin@keel.test` logged in to the **product** UI at
`/dashboard` gets no elevated powers there. Staff authority applies to `/admin`.

## 3 · Inside PostgreSQL

Use pgweb at `:8768`, or `./keel psql`.

```sql
-- Emails are case-insensitive at the database level
SELECT column_name, udt_name FROM information_schema.columns
WHERE table_name = 'users' AND column_name = 'email';
--> citext

-- Who holds which staff role
SELECT r.name AS role, count(*) AS assignments
FROM model_has_roles mhr JOIN roles r ON r.id = mhr.role_id
GROUP BY r.name ORDER BY r.name;
--> super_admin | 1
--> support     | 1

-- Shield generated one permission per resource action
SELECT count(*) FROM permissions;
```

## 4 · Case-insensitive email

| Do this                             | Expect                        |
| ----------------------------------- | ----------------------------- |
| Register `Foo@Example.com`          | Succeeds                      |
| Log out, register `foo@example.com` | **Rejected as already taken** |

Two accounts differing only by case would otherwise split one person in two.
Enforced in Postgres via `citext`, so every path inherits it — including a raw
query that never goes through validation.

## 5 · API

Mint a token (there is no UI for this):

```bash
./keel artisan tinker --execute='echo App\Models\User::where("email","super_admin@keel.test")->first()->createToken("manual")->plainTextToken;'
```

```bash
TOKEN='paste-it-here'
AUTH=(-H "Authorization: Bearer $TOKEN" -H "Accept: application/json")

curl -s "${AUTH[@]}" http://localhost:8765/api/v1/user     # the authenticated user
curl -s -H "Accept: application/json" \
     http://localhost:8765/api/v1/user                     # 401, no token
```

Every error uses one envelope, whatever the status:

```json
{
    "error": {
        "status": 401,
        "message": "...",
        "errors": null,
        "request_id": "..."
    }
}
```

Confirm the correlation id ties together:

```bash
curl -si "${AUTH[@]}" http://localhost:8765/api/v1/user | grep -i x-request-id
```

That `request_id` also appears in the error envelope and in the logs — a bug
report ties straight back to what happened.

Browsable docs, generated from the code with no annotations to maintain:
`http://localhost:8765/docs/api`.

## 6 · Queues and the scheduler

```bash
./keel artisan tinker --execute='App\Jobs\PingJob::dispatch("hello");'
```

Open **Horizon** at `/horizon` → the job appears under recent jobs, processed.

```bash
./keel artisan schedule:list     # three health heartbeats
docker compose logs scheduler | tail
```

## 7 · Health — worth actually breaking

```bash
./keel artisan health:check      # 5 checks, all Ok
```

Five, not eight: the debug-mode, environment and optimized-app checks are
skipped outside a deployed environment.

Visit `/health` as `super_admin@keel.test` (staff-gated — the output describes
your infrastructure). `/up` stays public for load balancers.

Now prove the checks are real rather than decorative:

```bash
docker compose stop horizon
# wait ~1 minute for the heartbeat to go stale
./keel artisan health:check      # Queue -> Failed

docker compose start horizon
./keel artisan health:check      # Queue -> Ok again
```

## 8 · Mail

Everything goes to Mailpit at `:8767`; nothing leaves your machine.

| Do this                     | Expect                        |
| --------------------------- | ----------------------------- |
| Request a password reset    | Email in Mailpit, link works  |
| Register, then verify email | Verification email in Mailpit |

## 9 · The developer loop

| Command                     | Expect                                          |
| --------------------------- | ----------------------------------------------- |
| `./keel dev`, edit a `.tsx` | Browser updates without a reload                |
| `./keel doctor`             | No problems found                               |
| `./keel test`               | Pint, PHPStan and the Pest suite green          |
| `./keel e2e`                | The Playwright suite green                      |
| `./keel check`              | Everything CI runs, in one command              |
| `./keel ts`                 | Regenerates `resources/js/types/generated.d.ts` |

Prove the PHP → TypeScript contract holds: rename the `Support` case in
`app/Enums/StaffRole.php`, run `./keel types`, and the TypeScript build
**fails** rather than shipping a value React does not expect. Change it back.

## 10 · Pluggable services

```bash
./keel services                  # all six optional services: enabled? running?
```

`laravel.test` is always on and not listed. Now drop one and re-apply:

```bash
./keel services disable mailpit  # rewrites KEEL_SERVICES in .env
./keel up                        # "Stopping mailpit — no longer in KEEL_SERVICES"
```

| Check                      | Expect                                 |
| -------------------------- | -------------------------------------- |
| `./keel up` banner         | no Mailpit line                        |
| `docker compose ps`        | no `mailpit` container                 |
| app still loads at `:8765` | yes — `laravel.test` starts without it |
| `./keel doctor`            | no warning about port 8767/8771        |

```bash
./keel services enable mailpit && ./keel up   # back, banner lists Mailpit again
```

For a service you run on the host instead, see **Which services run** in the
README — drop it from `KEEL_SERVICES` and point `DB_HOST` / `REDIS_HOST` /
`MAIL_*` at your own.

---

## When you are done

```bash
docker compose stop pgweb
./keel artisan migrate:fresh --seed
```

Set `TELESCOPE_ENABLED=false` in `.env` if you enabled it.

Found something wrong? It is a bug worth a test — see `docs/conventions.md`.
