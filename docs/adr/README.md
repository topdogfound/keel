# Architecture Decision Records

Short notes on decisions that are **not obvious from the code**, and would
otherwise be "simplified" back into the bug they were made to avoid.

A decision belongs here when someone reading the code could reasonably ask
"why is this not the straightforward thing?" — the answer is usually that the
straightforward thing was tried and did not work.

| #                                                | Decision                                                                 |
| ------------------------------------------------ | ------------------------------------------------------------------------ |
| [0001](0001-spatie-permissions-with-teams.md)    | _Superseded._ Spatie team scoping, and why staff roles needed a sentinel |
| [0002](0002-two-permission-vocabularies.md)      | Staff permissions belong to Shield, not to an enum                       |
| [0003](0003-tenant-isolation-by-global-scope.md) | _Superseded._ Tenant isolation via a global scope, not route guards      |
| [0004](0004-docker-only-bootstrap.md)            | Docker-only bootstrap, and why the composer container is special         |
| [0005](0005-pluggable-services.md)               | Pluggable Compose services via one `KEEL_SERVICES` profile list          |
| [0006](0006-single-user-domain.md)               | A single-user domain; teams and tenancy removed                          |

A superseded record is kept, not deleted. 0001 and 0003 describe code that no
longer exists, but the constraints and the argument behind them are what you
need if you add tenancy to a project built from this template.

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
- **`spatie/laravel-pdf`** — Browsershot drives Puppeteer, _not_ the Playwright
  Chromium this project already installs, so it means a second ~150MB browser.
  Add it only when PDFs are an actual requirement.
- **Multi-tenancy of any shape** — out of scope for the template (ADR 0006). The
  `/admin` staff panel is deliberately global; a product's tenant model is the
  product's decision.
- Still open from the original plan: impersonation, Pennant, Socialite, Reverb,
  webhooks, i18n, backups, Codespaces prebuild.
