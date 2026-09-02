# Manual testing walkthrough

A guided pass over everything the template does. Each check is written as
**action → what you should see**, so a wrong result is unambiguous.

Budget about an hour for the whole thing, or jump to the section you care about.

---

## Before you start

```bash
./keel up                                  # stack running
./keel artisan migrate:fresh --seed        # predictable baseline
./keel db-gui                              # pgweb (opt-in, behind a profile)
```

`./keel up` prints a URL banner for whatever services are enabled (see §15).

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

| Account            | Who they are                                               |
| ------------------ | ---------------------------------------------------------- |
| `owner@keel.test`  | Owner of **Acme Corp**                                     |
| `admin@keel.test`  | Admin of Acme Corp                                         |
| `member@keel.test` | Member of Acme Corp                                        |
| `rival@keel.test`  | Owner of **Rival Industries** — the isolation counterparty |
| `staff@keel.test`  | Staff panel access, belongs to no shared team              |

> **Everyone also owns a personal team.** So `owner@keel.test` belongs to _two_
> teams, not one. That is why counts look higher than you might expect.
>
> Personal teams in the demo data have mismatched slugs — "Ada Lovelace's Team"
> lives at `/smith-deckow`. That is a seeding artifact, not a bug: the factory
> sets a random slug, so the model hook that derives it from the name is
> skipped. Teams made through the app get proper slugs, which is why Acme Corp
> is at `/acme-corp`.

Use **two browsers** (or one plus a private window) so you can hold two sessions
at once. Several checks compare what different people see.

---

## 1 · Product UI

| Do this                                 | Expect                                                                                 |
| --------------------------------------- | -------------------------------------------------------------------------------------- |
| Open `http://localhost:8765`            | Welcome page, Log in / Register links                                                  |
| Register a new account                  | Lands on a dashboard at `/{your-team}/dashboard` — a personal team was created for you |
| Log out, log in as `owner@keel.test`    | Dashboard for Acme Corp                                                                |
| `/settings/profile`                     | Name and email, editable                                                               |
| `/settings/security`                    | Password, two-factor, passkeys                                                         |
| Enable two-factor                       | QR code and recovery codes appear                                                      |
| Change your password, then log in again | New password works, old one does not                                                   |

## 2 · Teams

As `owner@keel.test`, at `/settings/teams`.

| Do this                                     | Expect                                           |
| ------------------------------------------- | ------------------------------------------------ |
| Create a team                               | Appears in the list; you are its owner           |
| Rename it                                   | Name updates; slug stays put                     |
| Switch between teams                        | The dashboard URL changes to `/{slug}/dashboard` |
| Invite `newperson@example.com` to Acme Corp | Invitation listed as pending                     |
| Open **Mailpit** at `:8767`                 | The invitation email is there                    |
| Open the invite link, register, accept      | You join Acme Corp                               |
| Back as owner: change that member's role    | Role updates                                     |
| Remove them                                 | They disappear from the member list              |
| As a member: leave a team                   | You lose access to it                            |
| Delete a team you own                       | It goes, along with its memberships              |

## 3 · Authorization, seen through the UI

Open the **same page** — `/settings/teams/acme-corp` — as three different people
and compare. This is `TeamPermission` made visible.

| As                 | Should be able to                                        | Should **not** see            |
| ------------------ | -------------------------------------------------------- | ----------------------------- |
| `owner@keel.test`  | Everything: rename, invite, change roles, remove, delete | —                             |
| `admin@keel.test`  | Rename, invite, cancel invitations                       | Delete team, remove members   |
| `member@keel.test` | View the team                                            | Rename, invite, roles, delete |

## 4 · Tenant isolation

The one that matters most. Stay logged in as `owner@keel.test` — who has no
standing in Rival Industries — and try to reach it directly.

| Do this                                                 | Expect                                 |
| ------------------------------------------------------- | -------------------------------------- |
| `http://localhost:8765/rival-industries/dashboard`      | Refused — **never** renders their data |
| `http://localhost:8765/settings/teams/rival-industries` | Refused                                |

Then confirm the mirror image: as `rival@keel.test`, `/acme-corp/dashboard` is
equally refused.

## 5 · Staff panel

| As                 | Go to `/admin` | Expect                                          |
| ------------------ | -------------- | ----------------------------------------------- |
| Logged out         |                | Redirected to `/admin/login`                    |
| `member@keel.test` |                | **403 Forbidden**                               |
| `owner@keel.test`  |                | **403** — owning a team does not make you staff |
| `staff@keel.test`  |                | The dashboard                                   |

Then, as `staff@keel.test`:

| Do this               | Expect                                                                      |
| --------------------- | --------------------------------------------------------------------------- |
| `/admin/teams`        | **Every** team, across all tenants — the panel is deliberately cross-tenant |
| `/admin/users`        | Every user                                                                  |
| Edit a team           | Saves                                                                       |
| `/admin/shield/roles` | `super_admin`, `support`, `owner`, `admin`, `member` with their permissions |

## 6 · The boundary in both directions

Easy to get backwards, so check both ways.

| Do this                                                             | Expect                                          |
| ------------------------------------------------------------------- | ----------------------------------------------- |
| `owner@keel.test` — full powers in Acme — visits `/admin`           | 403. Tenant authority is not staff authority    |
| `staff@keel.test` logs in to the **product** UI and opens Acme Corp | No elevated powers there. They are not a member |

## 7 · Inside PostgreSQL

Use pgweb at `:8768`, or `./keel psql`.

```sql
-- Emails are case-insensitive at the database level
SELECT column_name, udt_name FROM information_schema.columns
WHERE table_name = 'users' AND column_name = 'email';
--> citext

-- Staff roles sit at team_id 0; tenant roles carry a real team id
SELECT r.name AS role, mhr.team_id, count(*) AS assignments
FROM model_has_roles mhr JOIN roles r ON r.id = mhr.role_id
GROUP BY r.name, mhr.team_id ORDER BY mhr.team_id, r.name;
--> super_admin | 0 | 1      <- the reserved global scope
--> owner       | 4 | 1      <- scoped to Acme Corp
```

That second result _is_ the permission model. `0` is the reserved global scope
for staff; everything else is a real tenant. See `docs/adr/0001`.

## 8 · Case-insensitive email

| Do this                             | Expect                        |
| ----------------------------------- | ----------------------------- |
| Register `Foo@Example.com`          | Succeeds                      |
| Log out, register `foo@example.com` | **Rejected as already taken** |

Two accounts differing only by case would otherwise split one person in two.
Enforced in Postgres, so every path inherits it.

## 9 · Account deletion guard

| Do this                                                    | Expect                                                    |
| ---------------------------------------------------------- | --------------------------------------------------------- |
| As `owner@keel.test`, `/settings/profile` → delete account | **Refused**, naming Acme Corp                             |
| Register a throwaway user, delete it                       | **Succeeds** — only a personal team, nobody else affected |

Without the guard the account deletes, the cascade removes the membership, and
Acme Corp is left with no owner — unmanageable forever.

## 10 · API

Mint a token (there is no UI for this):

```bash
./keel artisan tinker --execute='echo App\Models\User::where("email","owner@keel.test")->first()->createToken("manual")->plainTextToken;'
```

```bash
TOKEN='paste-it-here'
AUTH=(-H "Authorization: Bearer $TOKEN" -H "Accept: application/json")

curl -s "${AUTH[@]}" http://localhost:8765/api/v1/user
curl -s "${AUTH[@]}" http://localhost:8765/api/v1/teams
```

> **Use `curl -g` for anything with `filter[...]`.** curl treats `[` and `]` as
> glob characters and will silently return nothing otherwise.

```bash
curl -sg "${AUTH[@]}" 'http://localhost:8765/api/v1/teams?filter[name]=Acme'
curl -sg "${AUTH[@]}" 'http://localhost:8765/api/v1/teams?sort=-name'
curl -sg "${AUTH[@]}" 'http://localhost:8765/api/v1/teams?filter[is_personal]=0'
curl -sg "${AUTH[@]}" 'http://localhost:8765/api/v1/teams?per_page=5000'   # meta.per_page caps at 100

curl -s -H "Accept: application/json" http://localhost:8765/api/v1/teams   # 401
curl -s "${AUTH[@]}" http://localhost:8765/api/v1/teams/rival-industries   # 403
```

Every error uses one envelope, whatever the status:

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

That `request_id` also comes back in the `X-Request-Id` header and appears in the
logs — a bug report ties straight back to what happened.

Browsable docs, generated from the code: `http://localhost:8765/docs/api`.

## 11 · Queues and the scheduler

```bash
./keel artisan tinker --execute='App\Jobs\PingJob::dispatch("hello");'
```

Open **Horizon** at `/horizon` → the job appears under recent jobs, processed.

```bash
./keel artisan schedule:list     # invitation cleanup + health heartbeats
docker compose logs scheduler | tail
```

## 12 · Health — worth actually breaking

```bash
./keel artisan health:check      # 5 checks, all Ok
```

Visit `/health` as `staff@keel.test` (staff-gated — the output describes your
infrastructure). `/up` stays public for load balancers.

Now prove the checks are real rather than decorative:

```bash
docker compose stop horizon
# wait ~1 minute for the heartbeat to go stale
./keel artisan health:check      # Queue -> Failed

docker compose start horizon
./keel artisan health:check      # Queue -> Ok again
```

## 13 · Mail

Everything goes to Mailpit at `:8767`; nothing leaves your machine.

| Do this                  | Expect                       |
| ------------------------ | ---------------------------- |
| Request a password reset | Email in Mailpit, link works |
| Invite someone to a team | Invitation email in Mailpit  |

## 14 · The developer loop

| Command                     | Expect                                          |
| --------------------------- | ----------------------------------------------- |
| `./keel dev`, edit a `.tsx` | Browser updates without a reload                |
| `./keel doctor`             | No problems found                               |
| `./keel test`               | Pint, PHPStan, 154 Pest tests green             |
| `./keel e2e`                | 9 browser tests green                           |
| `./keel ts`                 | Regenerates `resources/js/types/generated.d.ts` |

Prove the PHP → TypeScript contract holds: rename a property in
`app/Data/UserTeam.php`, run `./keel types`, and it **fails**. Change it back.

Then prove the tenancy generator: `./keel artisan make:tenant-model Invoice`
produces a model already carrying `BelongsToTeam` and a ULID public id. Delete
the generated files afterwards.

## 15 · Pluggable services

```bash
./keel services                  # all six optional services: enabled? running?
```

`laravel.test` is always on and not listed. Now drop one and re-apply:

```bash
./keel services disable mailpit  # rewrites KEEL_SERVICES in .env
./keel up                        # "Stopping mailpit — no longer in KEEL_SERVICES"
```

| Check                                    | Expect                                  |
| ---------------------------------------- | --------------------------------------- |
| `./keel up` banner                       | no Mailpit line                         |
| `docker compose ps`                      | no `mailpit` container                  |
| app still loads at `:8765`               | yes — `laravel.test` starts without it  |
| `./keel doctor`                          | no warning about port 8767/8771         |

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
