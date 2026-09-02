# Contributing

## Setup

```bash
git clone https://github.com/topdogfound/keel.git && cd keel
./keel setup
```

Docker is the only requirement. If something looks wrong, `./keel doctor` before
anything else.

## The loop

```bash
./keel dev      # Vite with HMR, while you work
./keel test     # Pint, PHPStan and Pest
./keel check    # everything CI runs — do this before you push
```

`./keel check` runs `composer ci:check`, which is exactly what
`.github/workflows/tests.yml` runs. If it passes locally it passes in CI; if it
fails in CI and passed locally, that is a bug worth reporting.

`./keel setup` points git at `.githooks`, so:

- **pre-commit** runs `pint --dirty` on staged PHP.
- **pre-push** runs `./keel check`.

Both skip silently when Docker or the stack is down, so they never block you
offline — which also means a green push is not proof the hooks ran. Run
`./keel check` yourself when it matters.

A second CI job, `docker-parity`, runs `./keel setup --ci` from scratch weekly.
It exists because the bootstrap path is the one thing the normal job cannot
exercise — see [ADR 0004](docs/adr/0004-docker-only-bootstrap.md).

## Rules that will fail your build

- **Never run `npm` on the host.** `package.json` pins platform-specific native
  binaries; a `node_modules` built on macOS or Windows and mounted into the Linux
  container fails at build time. Use `./keel npm`. If you slip,
  `./keel node-reset`.
- **`declare(strict_types=1)` in every PHP file.** An architecture test enforces
  it. Filament's and Telescope's generators do not use Laravel's stubs, so run
  `./keel lint` after generating.
- **No `dd`, `dump`, `ray`, `var_dump`, `die` or `exit`** anywhere in `app/`.
- The rest of the architecture rules live in
  [`tests/Arch/LayeringTest.php`](tests/Arch/LayeringTest.php); they are short,
  read them once.

## Tests

Pest, in `tests/`. `Feature` gets `RefreshDatabase` automatically via
`tests/Pest.php`. Browser tests are Playwright specs in `tests/Browser/`, run
inside the container with `./keel e2e` — including an accessibility pass driven
by `@axe-core/playwright`.

A bug worth fixing is a bug worth a test.

## Documentation

Four places, and it matters which one:

| Change                                   | Update                   |
| ---------------------------------------- | ------------------------ |
| A new surface a person can click or curl | `docs/manual-testing.md` |
| How the code is organised, a new rule    | `docs/conventions.md`    |
| What ships and how a request flows       | `docs/architecture.md`   |
| A non-obvious choice someone would undo  | a new ADR in `docs/adr/` |

Write an ADR when the straightforward version was tried and did not work. Number
it next in sequence, and never rewrite an existing one — mark it superseded and
add a new record, the way [0006](docs/adr/0006-single-user-domain.md) supersedes
0001 and 0003.

Markdown is formatted by `vp check --fix`:

```bash
./keel npm run check:fix
```

## Commits and pull requests

Branch off `main`. Keep the diff to one concern. If the change touches something
the docs describe, the doc change belongs in the same commit — the docs in this
repo drifted badly once already, which is what prompted a full rewrite.
