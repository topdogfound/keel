# 0004 — Docker-only bootstrap, and why the composer container is special

**Status:** accepted

## Context

The template's promise is that a machine needs **only Docker and git**. That
runs into a genuine cycle: `compose.yaml` builds the app image from
`./vendor/laravel/sail/runtimes/8.4`, which does not exist until `composer
install` has run — which needs PHP.

## Decision

`./keel setup` breaks the cycle with a throwaway `laravelsail/php84-composer`
container, then hands off to Sail for everything else.

## The non-obvious parts

**`--ignore-platform-reqs` is required, not sloppy.** The bootstrap image ships
PHP 8.4.1 *without* `ext-intl`, which `filament/support` requires, so a fresh
clone cannot install without it. It is safe because that container only resolves
and downloads packages: the application only ever runs in the Sail image
(PHP 8.4.24, full extension set), which is what `composer.lock` was resolved
against.

**The vendor guard tests for a usable install, not a directory.** A run that
fails part-way leaves `vendor/` present but without `vendor/bin`. A naive
`[ -d vendor ]` check then skips the install and fails much later with a far more
confusing error.

**The bundled installer is stale.** The image ships `laravel/installer` 5.10.0,
which predates starter kits entirely (no `--react`). Scaffolding requires
installing a current one into the container first.

**Build order is load-bearing.** Wayfinder, Inertia and the TypeScript
transformer all generate sources at build time, and all are gitignored. A
typecheck before the first build fails on unresolved imports. `./keel setup` and
CI both generate, then build, then check — do not reorder.

**Playwright's browsers are not in the image.** Sail installs Playwright's *OS
libraries* but not the browsers. `npx playwright install chromium` must run
after `npm install`, and again after `./keel node-reset`, since
`PLAYWRIGHT_BROWSERS_PATH=0` puts them inside `node_modules`.

**A misleading PHPStan error.** `Undefined constant LARAVEL_VERSION` from
Larastan never points at its own cause: Larastan boots Laravel inside PHPStan's
bootstrap and swallows any exception. It means either a stale result cache (the
common case — `post-autoload-dump` clears it, but it can still go stale between
dependency changes) or that the app genuinely cannot boot. Clear the cache
first; if it survives that, run `./keel artisan about` for the real error.

**`npm` must never run on the host.** `package.json` pins platform-specific
native binaries (rollup, tailwind oxide, lightningcss). A `node_modules` built
on macOS or Windows and bind-mounted into Linux fails at build time. `./keel
doctor` detects this; `./keel node-reset` fixes it.

## Verify it still holds

The only check that matters:

```bash
docker compose down -v && rm -rf vendor node_modules .env
./keel setup
```

This has caught two real bugs that were invisible while `vendor/` existed. Run
it before believing the template still works.
