# Architecture Decision Records

Short notes on decisions that are **not obvious from the code**, and would
otherwise be "simplified" back into the bug they were made to avoid.

A decision belongs here when someone reading the code could reasonably ask
"why is this not the straightforward thing?" — the answer is usually that the
straightforward thing was tried and did not work.

| # | Decision |
|---|---|
| [0001](0001-spatie-permissions-with-teams.md) | Spatie permissions with teams, and the reserved global scope |
| [0002](0002-two-permission-vocabularies.md) | Two permission vocabularies, split by who owns them |
| [0003](0003-tenant-isolation-by-global-scope.md) | Tenant isolation via a global scope, not route guards |
| [0004](0004-docker-only-bootstrap.md) | Docker-only bootstrap, and why the composer container is special |

## Backlog

Considered and deliberately deferred, with the reasoning, so they are not
silently rediscovered:

- **CSP** (`spatie/laravel-csp`) — Vite's HMR socket, Livewire and Filament each
  want different things from the policy, and a wrong one fails silently as a
  blank admin panel. High effort, and the benefit is theoretical until there is
  something deployed. Build it behind a dev-relaxed profile if it is picked up.
- **Cashier / billing** — considered during design and excluded as too
  opinionated for a general template.
- **Octane** — the Sail image ships Swoole only, no FrankenPHP, and it
  introduces statefulness bugs for little local benefit.
- **`spatie/laravel-pdf`** — Browsershot drives Puppeteer, *not* the Playwright
  Chromium this project already installs, so it means a second ~150MB browser.
  Add it only when PDFs are an actual requirement.
- **Filament multi-tenancy** — rejected in favour of a global staff back-office;
  the product UI owns the tenant experience.
- Still open from the original plan: impersonation, Pennant, Socialite, Reverb,
  webhooks, i18n, backups/health checks, Codespaces prebuild.
