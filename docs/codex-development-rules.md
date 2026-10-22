# Codex Development Rules

This document is the detailed companion to root `AGENTS.md`; `AGENTS.md` is mandatory for every Codex session.

## Before changing code

1. Inspect relevant routes, models, migrations, policies, actions, tests, and existing documentation.
2. State assumptions when a product decision is not settled. Do not invent payment, legal, retention, visibility, or role policy.
3. Scope the smallest coherent change. Preserve unrelated user changes and existing functionality.
4. For new dependencies, explain why Laravel/PHP cannot already solve the need and obtain approval where material.

## Implementation contract

- Use `declare(strict_types=1);`, typed properties/returns, PHP enums, DTOs/value objects where they clarify boundaries, and Laravel conventions.
- Controllers call `$this->authorize(...)`, accept a Form Request, invoke an Action, and return a response. Avoid direct query/workflow orchestration in controllers.
- Livewire components authorize on mount/action, validate intentionally, use actions for writes, and never make security decisions from UI state.
- Actions define the transaction boundary and dispatch after-commit events. Jobs are serializable, idempotent, retry-safe, timeout-aware, and authorized at dispatch time plus defensively at execution when necessary.
- Policies define record access. Query scopes restrict lists but do not replace policy checks on individual records.
- Prefer private storage disks. File access always routes through an authorized controller/action or short-lived signed URL.
- Use `config()` and localization mechanisms; never read `.env` outside config files or hard-code environment/region values.

## Data and migrations

- Include indexes, foreign keys, unique constraints, enum/status strategy, and a factory with each meaningful model.
- Prevent race conditions with transactions, unique constraints, locks where appropriate, and idempotency keys for external/financial operations.
- Do not drop/rename data or run destructive migrations without explicit approval, a backfill plan, and rollback/restore consideration.

## Verification and handoff

Run only relevant commands when possible, then report exactly what passed or was not run: Pint, PHPStan, Pest, and build where affected. Add/update docs for changed design decisions. Handoff includes changed files, behavior, tests, and any remaining decision/risk—never claim verification that was not run.
