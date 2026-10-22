# Testing Strategy

## Test pyramid

Pest is the test framework. Most confidence comes from fast feature and unit tests; browser tests cover a small set of critical interactive journeys.

| Layer | Purpose | Examples |
| --- | --- | --- |
| Unit | Pure rules/value objects/actions with isolated collaborators | transition guards, visibility decisions, match inputs |
| Feature | HTTP, policies, database, events, queues | submit application, recruiter access, private resume download |
| Livewire | component validation/state/authorization | profile editor, pipeline updates, filters |
| Browser | highest-value user paths | sign in, apply, job publish, accessible keyboard workflow |
| Integration | adapters/providers in sandbox | storage upload/scanning callback, email provider |

## Required coverage

- Every Action governing a workflow has success, invalid-state, authorization, and transaction-failure coverage.
- Every Policy has allow and deny cases, including cross-company and cross-user isolation.
- Company workspace tests cover owner/member role boundaries, public-profile privacy, verification submission/review access, and audit-safe decisions.
- Candidate profile tests cover private-by-default behavior, public field allowlists, derived completion recommendations, owner-only editing, private image/resume storage, authorized streaming, and resume scan-state restrictions.
- Freelancer tests cover additive role enablement, private/listed discovery boundaries, filters, verified project and review eligibility, moderator approval, and cross-account/company authorization.
- Job marketplace tests cover company-scoped authoring and applicant access, lifecycle transitions, discoverability/SEO visibility, candidate application/withdrawal, status history, duplicate behavior, audit records, and notification recipients.
- Privacy tests prove anonymous, candidate, employer, recruiter, moderator, and admin visibility rules; include direct URL access and queued/export/search paths.
- Validation tests cover required, invalid, boundary, normalized, and malicious input. Upload tests cover unauthorized, invalid type/size, and unscanned files.
- Queue/event tests assert dispatch after commit, idempotency/retry behavior where relevant, and no premature notification on rollback.
- Migration tests cover constraints/indexes critical to correctness. Regression bugs receive a focused test first or alongside the fix.

## Test practices

Use factories, named states, fakes (`Queue`, `Event`, `Notification`, `Storage`, `Http`), database refresh/transactions, frozen time, and deterministic IDs. Do not use production data, external network calls, sleeps, or order-dependent tests. Seed only intentional reference data. Tests state the business rule in their description.

## Gates

Before merge: `vendor/bin/pint --test`, `composer analyse`, and affected Pest suites. CI runs the full suite, static analysis, formatting check, dependency/security scan, and build. Treat PHPStan errors as failures; baseline only with explicit documented approval. Add performance/query-count assertions for high-traffic discovery pages once measurements exist.
