# M Talent Engineering Rules

These rules apply to every change in this repository.

## Non-negotiables

- Preserve existing behavior and do not alter unrelated files.
- Follow Laravel conventions and PHP 8.3+ strict typing. Keep dependencies minimal; do not add a package without a documented need and approval when a framework facility suffices.
- Keep HTTP controllers and Livewire components orchestration-only. Validate with Form Requests (or Livewire rules), authorize with Policies/Gates, and place use cases in focused Actions/Services.
- Use Eloquent relationships, scopes, casts, enums, factories, migrations, seeders, and resource classes deliberately. Avoid raw SQL unless measured and justified.
- Wrap state-changing, multi-record, financial, verification, and other business-critical workflows in database transactions. Make queued work idempotent.
- Dispatch domain events after successful commits; listeners that may be slow must be queued. Never do network calls in a database transaction.
- Enforce authorization server-side for every private record and mutation. Never make resumes, identity documents, contact details, or private portfolios public by default.
- Build Blade, Livewire, Tailwind, and Alpine interfaces only. Do not introduce React or Vue without explicit approval.
- Design mobile-first, keyboard-accessible, responsive interfaces. Meet WCAG 2.2 AA as the baseline.
- Do not hard-code Kerala, India, currency, timezone, or locale assumptions in core domain rules. Use configured/localized data.
- Write or update Pest tests for changed business rules, authorization, and regressions. Run Pint, PHPStan, and affected tests before handoff.

## Structure and review

- Organize code by bounded context under `app/Domain/<Context>` with Actions, Data, Events, Listeners, Policies, Services, and ValueObjects as needed. Framework adapters remain in conventional Laravel locations when appropriate.
- Prefer explicit names and small, single-purpose classes. Avoid god services, repository abstractions around Eloquent without a real boundary, static mutable state, and hidden side effects.
- Use UUID/ULID public identifiers; do not expose sequential internal IDs in public routes or APIs.
- New schema changes require reversible migrations, appropriate indexes, foreign keys, and tests. Never edit an already-applied migration.
- Never log secrets, CV contents, identity-document data, tokens, passwords, or payment details. Use environment configuration and secret stores.
- Document material architecture decisions in `docs/architecture.md` or an ADR before implementation.
