# Database Architecture

## Standards

MySQL 8+ with InnoDB and `utf8mb4` is authoritative. All tables have `id` (internal bigint), `public_id` (ULID/UUID with a unique index), timestamps, and foreign keys where lifecycle ownership is clear. Use decimal strings/integers—not floats—for money. Store time in UTC; format in user-selected IANA timezone. Use ISO 3166 country and ISO 4217 currency codes.

Use soft deletes only where recovery/retention needs justify them. PII and documents may require purpose-specific deletion/anonymization rather than indefinite soft deletion. Index foreign keys, common filter columns, status, published timestamps, and compound search/sort patterns established by actual queries.

## Core entities

| Area | Principal tables | Notes |
| --- | --- | --- |
| Identity | `users`, `user_identities`, `sessions`, `consents`, `roles`, `permissions` | User is the account root; do not encode a single persona role on it. |
| Profiles | `talent_profiles`, `skills`, `talent_profile_skills`, `employments`, `educations`, `portfolio_projects`, `profile_media`, `resumes` | Visibility is field/document-aware; resumes use private storage references only. |
| Companies | `companies`, `company_memberships`, `company_verifications`, `location_assignments` | Membership is company-scoped; verification submissions preserve decisions without storing uploaded evidence until secure scanning is available. |
| Jobs | `job_listings` (Eloquent `Job`), `skills`, `job_skill`, `saved_jobs` | `jobs` is already the Laravel queue table; marketplace job rows therefore use `job_listings`. Skills are normalized for reuse. |
| Applications | `job_applications`, `application_status_changes` | Unique `(job_id, user_id)`; status transitions are append-only and candidate-controlled withdrawal is retained. |
| Projects | `projects`, `project_skills`, `proposals`, `proposal_milestones`, `project_awards` | Keep payment ledger separate if/when money is enabled. |
| Recruitment | `pipelines`, `pipeline_stages`, `candidate_stage_entries`, `interviews`, `scorecards`, `recruitment_notes` | Notes are private to authorized company members. |
| Trust | `verification_cases`, `verification_evidence`, `verification_decisions`, `reports`, `moderation_actions` | Evidence paths private; decision is append-only. |
| Platform | `notifications`, `notification_preferences`, `audit_logs`, `outbox_events`, `failed_jobs` | Audit log is append-only and redacted. |
| Geography | `locations`, `location_assignments` | Typed hierarchy supports country, state/province, city, area, and IT park; polymorphic assignments are context-labeled. |
| Identity | `roles`, `permissions`, `role_user`, `permission_role`, `user_profiles`, `user_identities` | Additive capabilities; provider table is storage preparation only until OAuth is approved. |
| Profiles | `user_profiles`, `profile_experiences`, `profile_educations`, `profile_certifications`, `profile_languages`, `profile_portfolio_links`, `profile_resumes`, `talent_profile_skills`, `talent_profile_preferred_locations` | Owned by a talent profile; resume records keep private disk/path metadata and scan state, never a public URL. |
| Freelancers | `freelancer_profiles`, `project_categories`, `freelancer_projects`, `freelancer_reviews` | Separate rate/listing extension, project-category taxonomy, company-confirmed completed work, and moderated project-linked reviews. |
| Contracts | `freelance_contracts`, `contract_milestones`, `contract_status_changes`, `contract_milestone_status_changes` | Awarded project terms and delivery-state history; milestone funding/release statuses do not represent money movement. |
| Billing | `billing_plans`, `billing_subscriptions`, `billing_invoices`, `billing_invoice_items`, `billing_transactions`, `billing_refunds`, `billing_payment_attempts`, `billing_audit_entries`, `billing_webhook_events` | Provider-neutral billing records with unique idempotency keys, private encrypted webhook payloads, and append-only audit history. No payment provider is configured. |
| Recruitment | `recruitment_requests`, `recruitment_request_skills`, `recruitment_pipeline_entries`, `recruitment_pipeline_stage_changes`, `recruitment_interviews`, `recruitment_activities` | Private employer assistance requests and company-scoped candidate pipelines with activity history. |

## Relationship rules

- `User hasOne TalentProfile`; user has many resumes, applications, proposals, consents, and verification cases.
- TalentProfile owns normalized career/credential/language/portfolio records, skills, preferred work/location settings, and private resumes. Profile completion is derived from profile data and is not a stored counter.
- A freelancer profile extends one talent profile; projects belong to that extension and a category. Reviews belong to a verified completed freelancer project, one review per project, and only approved reviews contribute to public rating aggregates.
- `Company hasMany CompanyMemberships`, jobs, projects, and locations; `User belongsToMany Company` through memberships.
- `Company belongsTo User` as founding owner and belongsToMany Users through company memberships. Location references include headquarters, IT park, and contextual office locations; contact fields are never included in public profile output.
- Company verification submissions belong to company and submitting/reviewing users. Evidence uploads are deferred until private storage and malware scanning are operational.
- `Job belongsTo Company` and `hasMany JobApplication`; job-to-skill is many-to-many with optional proficiency/importance.
- Job search applies public/published/open/deadline constraints before filters, and visibility/status are rechecked on direct detail access. Public and unlisted jobs are directly viewable; only public jobs are indexed/discoverable.
- Applications belong to a job and candidate user. Their applicant identity is available only through the application policy; do not duplicate resumes or contact details into application records.
- `Project belongsTo Company`; a proposal belongs to project and freelancer profile/user; awards point to accepted proposal.
- Use Eloquent relations, constrained eager loading, dedicated scopes, and database uniqueness constraints. Authorization never relies only on relationship existence.
- Geographic records are stored in one adjacency tree (`locations.parent_id`) with a backed `type`; `country_code` is ISO alpha-2 and populated only for country records. `user_profiles.location_id` is the primary talent location. Other current/future models use `HasLocations` and `location_assignments` for one or more contextual references. Location deletes are restricted while child nodes, primary profiles, or assignments exist.

## State and integrity

Store status in enums with indexed columns: draft/pending_review/published/paused/closed/expired; submitted/withdrawn/in_review/shortlisted/rejected/hired; and pending/approved/rejected/expired verification states. State changes are performed transactionally and recorded in dedicated history tables with actor, timestamp, reason, and metadata.

Billing intents and requests are stored with currency, unique idempotency keys, provider references, transactions, and append-only audit entries. Before enabling real money movement, add balanced immutable ledger postings and reconciliation workflows; current transaction rows do not represent settled funds.

## Search and privacy

Start with indexed MySQL queries/full-text indexes only after relevance testing. Candidate discovery queries must apply visibility and policy constraints before ranking. A future external search index contains only approved, minimized searchable fields and is deleted/updated through queued outbox events.

## Migrations and retention

Migrations are additive and reversible where safely possible; never modify deployed migrations. Large-table changes use online-safe, staged techniques. Retention is configured by data class: audit evidence, verification evidence, resumes, inactive accounts, and deleted accounts each require a documented policy and scheduled anonymization/purge process.
