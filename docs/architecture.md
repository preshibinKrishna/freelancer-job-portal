# Architecture

## Principles

Laravel is the application platform. We use a modular monolith: independently understandable bounded contexts in one deployable application and one primary database. This minimizes operational complexity while preserving boundaries needed for future scale.

```text
Browser (Blade + Livewire + Alpine)
        │ HTTPS
Laravel HTTP / Console / Queue workers
        │
Actions + domain services + policies + events
        │
MySQL ── Redis (cache, queues, rate limits)
        │
Object storage / email / search provider (approved integrations)
```

## Application layout

Keep Laravel’s standard directories for framework concerns. Group domain use cases under `app/Domain`:

```text
app/Domain/
  Identity/       Profile/       Companies/       Jobs/
  Applications/   Projects/      Proposals/       Verification/
  Recruitment/    Notifications/ Moderation/      Shared/
    Actions/ Data/ Events/ Listeners/ Policies/ Services/ ValueObjects/
app/Http/Controllers/     app/Http/Requests/
app/Livewire/             app/View/Components/
app/Models/               app/Policies/
```

Models remain Eloquent models and relationships are the persistence boundary. Actions represent a named use case, e.g. `SubmitJobApplication`, `PublishJob`, `ReviewVerification`. Services encapsulate domain algorithms or external gateways. Controllers, commands, and Livewire components invoke Actions and return responses; they do not contain workflow logic.

## Context boundaries

| Context | Owns |
| --- | --- |
| Identity | accounts, credentials, sessions, consent, roles, MFA later |
| Profile | talent profiles, skills, career data, resume privacy, portfolios |
| Companies | organizations, workspaces, memberships, employer verification |
| Jobs & Applications | jobs, discovery, saves, applications, screening state |
| Projects & Proposals | freelance briefs, proposals, awards, milestones foundation |
| Recruitment | pipelines, interviews, scorecards, internal notes, offers later |
| Verification & Moderation | cases, evidence, reviews, decisions, reports, auditability |
| Notifications | preferences, templates, delivery records |
| Geography | countries and nested state/province, city, area, and IT park locations; reusable location assignments |

Contexts communicate through explicit Actions and domain events—not direct cross-context table updates. Read models may join appropriately scoped data when performance requires it.

## Delivery patterns

- Routes are named, use route model binding via public UUID/ULID, middleware, and policies.
- Form Requests own HTTP validation and normalization. Livewire components use dedicated rule methods/data objects and call the same Actions.
- Use PHP backed enums for finite state machines. Only Actions may perform transitions, with guards and auditable events.
- Use `DB::transaction()` for atomic writes. Publish events with `ShouldDispatchAfterCommit`; jobs use `afterCommit()`.
- Queue email, indexing, virus scans, document processing, notification fan-out, exports, and third-party calls. Define retry/backoff/timeout/unique/idempotency behavior.
- Cache read-heavy, non-sensitive queries with explicit keys/tags and invalidate on domain events. Redis is not source of truth.
- Use Laravel scheduler for recurring expiration, retention, reminders, and cleanup; each task must be idempotent and observable.

## Interfaces

Server-rendered Blade is default. Reusable components in `resources/views/components` provide forms, data display, feedback, and layouts. Livewire supports dynamic, authenticated workflow screens; Alpine is limited to presentation-local interactions. Vite manages Tailwind and small JavaScript entry points.

## Integration and evolution

Wrap email, storage, identity verification, payment, and search providers behind narrow interfaces/configuration. Do not couple domain state to a vendor payload. Public API and AI matching are future bounded interfaces, not MVP dependencies. Record material choices as ADR entries in this document’s `Decisions` section or a future `docs/adr/`.

## Identity decisions

- Identity uses first-party Laravel session authentication and a role/permission pivot. Accounts can hold several capabilities; public registration grants only `candidate`. Administrative roles are never selectable during signup.
- Roles and permissions are seeded from stable names. Admin role changes, suspension, activation, and account deletion are policy checked and written to append-only audit records. Admins can assign marketplace roles; only a `super_admin` can grant or remove the `admin` and `super_admin` capabilities or manage those accounts. The last active `super_admin` cannot be suspended, deleted, or unassigned.
- Initial super-admin access is provisioned from the CLI to an existing active, verified account; there is no seeded password or public route for creating administrators.
- Account deletion soft deletes and anonymizes login/profile details, unlinks external identities, clears role grants, invalidates sessions, and keeps related marketplace records referentially intact.
- `user_identities` stores provider subject references for later Google, LinkedIn, or GitHub linking. No OAuth provider is installed or enabled. Nullable encrypted TOTP/recovery fields reserve storage for a future 2FA implementation; no 2FA flow is active.
- A per-user session version is captured after authentication and checked on authenticated requests. Password reset, suspension, and deletion increment it to revoke active sessions across session backends.

## Geography decisions

- Geography is a typed adjacency-list tree in `locations`, not country-specific tables or Kerala-specific enums. A country may contain state/province nodes and/or cities directly; the allowed parent type is explicit for each node type.
- `Location::path()` produces hierarchical slugs under `/locations/…`; the public resolver walks parent-child slug pairs so repeated city names remain unambiguous. Admin records route by public ULID.
- `user_profiles.location_id` references the talent profile’s primary location. Future Company, Job, Project, and other records can opt into the `HasLocations` polymorphic relationship with named contexts (for example `office`, `workplace`, or `project_site`) without requiring location columns or marketplace workflows to be introduced prematurely.
- Administrative writes are permission/policy checked, validated, transactional, and reject invalid parentage, cycles, or deletion of locations still referenced by children/records. Country codes use ISO 3166-1 alpha-2; initial geographic values are seed data only, not business rules.

## Company workspace decisions

- A company has one founding owner and multiple active team memberships (`owner`, `admin`, `recruiter`, `member`). Company membership is the organization-scoped authorization boundary; a global `employer` role is an additive capability granted to owners and added members, not a substitute for membership checks.
- Company profile changes and membership/verification operations are transaction-backed Actions. Company profile editing and team management are Livewire interfaces; job posting and invitations to accounts that do not yet exist remain out of scope.
- Public profile publication is explicit (`published_at`). Contact name/email/phone and reviewer notes are private. Logo and cover files use the public media disk. The initial company verification workflow accepts a statement and staff review only; evidence uploads remain disabled until private object storage and malware scanning are available.
- Verification submissions are append-only records. The current company status is a summary of the latest decision; approval/rejection records reviewer and time, and public verified badges appear only after approval. Company records are not automatically seeded because a company must have a real account owner.

## Jobs marketplace decisions

- Job publishing is scoped to an active company owner/admin/recruiter membership; a global employer/recruiter role alone does not grant access. The company profile must be published before its jobs can be published. Job posting and application review are separate policies and Actions.
- Job statuses are `draft`, `published`, `paused`, and `closed`. Publishing requires valid content and a future (or absent) deadline. Pausing/closing stops new applications but preserves existing applications and their histories. Job duplication creates an unpublished draft without copying applicants or status history.
- Visibility is `public` or `unlisted`: both are directly viewable while published, but only public jobs appear in discovery; unlisted pages are marked noindex. Draft, paused, and closed job pages are not publicly accessible. Job slugs are globally unique and are not identifiers for private workflows (those use ULIDs).
- A candidate with a verified, active account may apply once per job. The application captures an optional cover note, not a resume or contact-data copy; employers can access the applicant's account identity only through the scoped application. Candidates may withdraw until hired/rejected. Employers move applications through explicit allowed transitions and all changes append to application history.
- Job and application writes use transactional Actions, append-only audit/history records, and after-commit domain events. In-app database notifications are queued; no external email/SMS provider is assumed. Search starts with indexed MySQL queries and normalized shared skills, not an external search dependency.

## Candidate profile decisions

- `user_profiles` is the candidate/freelancer talent profile and remains one-to-one with the account. Career history, education, certifications, languages, portfolio links, normalized skills, preferred locations, and resumes are separate owned records; a profile completion service provides actionable recommendations rather than persisting a stale percentage.
- Profiles are private by default and become public only by explicit opt-in. Public pages expose professional presentation fields only; expected salary, resume, private job preferences and account contact information are never rendered publicly. Location visibility follows the profile opt-in.
- Profile and resume files use private storage keys and opaque IDs. Profile photos are streamed through a visibility-aware controller. Resumes are never linked directly: download checks owner or an eligible, active hiring-team relationship and application context. Resume scanning is not available yet; employer access remains unavailable until scan status is clean, while the owner can manage/download their own file.
- Candidate profile edits are owner-only (or explicitly permissioned staff access); public profile reads are separately authorized. Resume access is a distinct policy and is never inherited from public profile visibility.

## Freelancer marketplace decisions

- Freelancer capabilities are additive: enabling the freelancer marketplace grants the `freelancer` role without removing `candidate`. A separate `freelancer_profiles` extension owns rates, currency, weekly capacity, listing state, and platform verification; the shared talent profile continues to own skills, languages, location, and professional history.
- Freelancer discovery requires both the shared talent profile's explicit public visibility and the freelancer extension's explicit listed state. Rate, currency, capacity, availability, experience, skills, location, project category, rating, and verified state are structured filters; discovery never returns a private profile.
- Freelancer-authored projects are portfolio claims and are labelled as such. A project may request confirmation from a selected company; only an active member of that company may mark it verified. The company name and verified-completed status are shown publicly only after confirmation.
- A review is tied to one verified completed freelancer project and the confirming company, is unique per project, requires an active verified company member, and is not publicly visible until moderator approval. Ratings are aggregates of approved reviews only; this is an MVP anti-abuse boundary, not a guarantee against collusion.
- Freelancer verification badges are separate from project confirmations and can only be granted by a user with the explicit freelancer-verification permission. No payment, contract, or escrow workflow is implied by a portfolio project or review.

## Project marketplace decisions

- Client project briefs and freelancer portfolio claims are separate records and workflows. Client briefs belong to a company and use an explicit lifecycle; proposals and their milestone plans remain immutable snapshots except for status changes and appended messages.
- Project briefs may be published by an active, verified company owner/admin/recruiter. Proposals are available to active, verified candidate/freelancer accounts on published or reviewing briefs. Awarding a proposal locks the selected proposal, rejects competing open proposals, copies its milestone plan to the project, and advances the brief to awarded in one transaction.
- Messages are scoped to a proposal. Only the proposal author and eligible hiring-team members for that project company may read or send them. Project and proposal attachments use private storage and policy-checked streams; no attachment receives a public URL.
- Budget values are informational marketplace terms. The system does not collect, hold, or transfer money. Project milestones are delivery records only and do not trigger payment.
- An awarded project may have one draft contract, created by its client from the awarded proposal. The freelancer activates that agreement by accepting its terms. Contract milestones are snapshots of the awarded delivery plan, with append-only state history and party-scoped transitions. `funded` and `released` are workflow states only until a separately approved payment integration exists; no state transition calls a provider, moves funds, or represents proof of payment. Future providers belong behind a dedicated payment-provider interface and idempotent payment operations, separate from contract state and ledger records.
- Recruitment assistance requests belong to companies and are private. Platform recruiters access unassigned requests and requests assigned to them; company owners/admins/recruiters access only requests for their own company. Candidate profiles enter a requisition pipeline only when public, with recruiter-only notes and append-only stage history. Client Review decisions are company scoped. Interviews and activities inherit the same requisition authorization.

## Internal messaging decisions

## Guided employer talent discovery

- Employers may submit a structured solution brief independently of a job requisition or recruitment-service request. The brief captures an approved solution category/type, type-specific selections, shared delivery context, and suggested professional capabilities; it does not require technical prose.
- The solution catalogue is configuration-backed so labels and question sets can evolve without storing free-form category names. Submitted answers and capability suggestions are snapshotted on a ULID-addressed request, with company-scoped policy checks and an audit entry.
- Recommendations are a read-only view of active, verified-account profiles that candidates have explicitly made public. Profiles are ranked by overlap with the selected capabilities; private profiles, resumes, contact information, and salary preferences are never queried or exposed.
- This discovery flow recommends public professional profiles only. It does not create a job posting, initiate recruitment pipeline placement, or represent that a profile owner is available for a specific project.

- General inbox conversations are separate from proposal-scoped project notes and are anchored to an application, project proposal, recruitment pipeline entry, or recruitment request. The participant pair is derived server-side from that context; arbitrary account-to-account conversations are not allowed. Current business relationships are rechecked when reading or sending.
- Messages and attachments are private to the conversation participants. Attachments use the private local disk with opaque paths and are downloaded through a participant-authorized controller. Message notifications contain a generic notice and an internal route, never the message body, email, or phone number.
- Email addresses and phone-like numbers are rejected in message text unless a future explicit business rule enables exchange. Per-user archive/read timestamps, user blocks, and participant-submitted reports are stored separately from message content.

## Billing architecture decisions

- Billing plans, subscriptions, invoices and line items, transactions, refunds, payment attempts, audit entries, and provider webhook events are separate records. Money amounts are decimal database values paired with an ISO-style currency code; provider references and payloads are not domain identifiers.
- `PaymentProvider` is an adapter contract for intent creation, refunds, and signature-verified webhook normalization. A registry supports explicit adapters; no adapter is registered or invoked, so the current application cannot process real payments. Contract milestone `funded` and `released` states remain informational.
- Transaction intents, refund requests, payment attempts, and accepted webhook events use unique idempotency keys and append-only audit entries. Refund requests reserve amounts against successful charges and reject totals over the original charge. Provider I/O belongs outside database transactions. Webhook bodies are encrypted at rest, de-duplicated by provider event ID, and dispatched only after commit for a future idempotent processor.
- Payment activation requires an approved provider adapter, account authorization and consent flows, reconciliation, operational monitoring, and jurisdictional/legal review. Billing architecture alone does not activate pricing or money movement.

## Operations

## SEO architecture decisions

- Public marketplace pages share layout-driven metadata for canonical URLs, descriptions, Open Graph, Twitter/X cards, and JSON-LD. Page controllers provide schema.org data for published jobs, organizations, public people, projects, skills, and geographic landing pages; breadcrumb navigation emits a matching `BreadcrumbList`.
- Sitemap URLs are generated from currently discoverable public records and location/skill landing pages that pass a configurable minimum-content threshold. Thin landing pages can render for navigation but are `noindex` and excluded from sitemaps. Robots rules guide crawlers, while private authenticated responses also carry `X-Robots-Tag: noindex` as the privacy safeguard.
- Landing pages aggregate live public jobs, projects, and listed freelancers for their location or skill. No standalone location/skill SEO text pages are generated without sufficient matching inventory. Filtered discovery views canonicalize to the base directory; unfiltered pagination uses page-specific canonical URLs and previous/next links.
- SEO metadata is presentation data only. Public visibility and authorization checks remain the source of truth; private candidate fields, documents, and dashboards are never placed in structured data or sitemap output.

Separate web, queue, scheduler, and deployment processes. Production requires encrypted environment secrets, HTTPS, central structured logs, error tracking, backups, queue monitoring, health checks, and restore drills. Horizont is preferred when Redis queues are adopted in production.
