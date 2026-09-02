# 0005 — Pluggable Compose services via one profile list

**Status:** accepted

## Context

`./keel up` used to start every container in `compose.yaml`. That is wrong for a
developer who already runs PostgreSQL or Redis on the host, and wasteful for one
who doesn't want the queue worker, the scheduler or a database GUI running all
day. The stack needs to be configurable per developer, without a second config
file — `.env` is already the single source of truth for ports and UID/GID.

## Decision

Every optional service in `compose.yaml` carries a Docker Compose **profile
named after itself** (`pgsql`, `redis`, `mailpit`, `horizon`, `scheduler`,
`pgweb`). `laravel.test` has no profile — it is the one service that always
runs. `.env` gains `KEEL_SERVICES`, a comma-separated allowlist, which the
`keel` script exports verbatim as `COMPOSE_PROFILES` before dispatching. Raw
`docker compose` therefore honours the same set with no `--profile` flags, and
`./keel services {list,enable,disable}` just reads and rewrites that one line.

## The non-obvious parts

**`depends_on` uses the long form with `required: false`.** `laravel.test`
depends on `pgsql`, `redis` and `mailpit`. With a plain `depends_on: [pgsql]`,
disabling the `pgsql` profile makes Compose refuse to start `laravel.test` at
all ("depends on undefined service"). `required: false` keeps the health-gated
ordering when the service *is* enabled and simply skips it when it isn't — which
is exactly the "I run Postgres on the host" case.

**`./keel up` prunes, because `docker compose up` doesn't.** Starting with a
narrower profile set never stops a profile you just dropped — the old container
lingers until the next `down`. `up` explicitly `stop`s any optional service that
is running but no longer in `KEEL_SERVICES`. `pgweb` is exempt: it has its own
on-demand command (`./keel db-gui`) and is expected to outlive an `up`.

**`down`, `doctor --fresh` use `--profile '*'`.** Teardown and the fresh-rebuild
must act on every container regardless of the current `KEEL_SERVICES`, or a
service you disabled yesterday leaks past `./keel down`.

**`doctor`'s port scan is scoped to enabled services.** Once you've dropped
`pgsql`, a host Postgres on `FORWARD_DB_PORT` is your own doing, not a conflict.

**Scope: Compose services only.** Telescope and Pulse are Composer packages
already gated by `TELESCOPE_ENABLED` / `PULSE_ENABLED`; making the packages
themselves removable is a separate, larger decision.

## Verify it still holds

```bash
./keel services disable horizon && ./keel up   # horizon container gone, /health flags the queue
./keel services enable horizon  && ./keel up   # back
docker compose down -v && rm -rf vendor node_modules .env && ./keel setup
```
