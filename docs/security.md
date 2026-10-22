# Security and Privacy

## Security posture

Treat the platform as holding sensitive employment, identity, and business data. Default deny; minimize collection, access, exposure, and retention. Align launch operations with applicable Indian privacy law and obtain legal review before collecting identity-verification evidence or operating payments. International expansion requires jurisdiction-specific review.

## Identity and access

- Use Laravel’s current secure authentication facilities, password hashing, email verification, password reset throttling, secure/session rotation, and rate limiting.
- Support stronger authentication for administrators and privileged employer roles; require MFA before production admin access.
- Authorize every model action using Policies/Gates, scoped by company membership and record ownership. Never trust client-supplied organization IDs, role claims, status, or storage paths.
- Company workspace policies check active company membership and scoped member role; the global employer capability alone never grants access to a company. Company contact details and reviewer notes are private. Verification evidence uploads are disabled until private storage and malware scanning are operational.
- Job listings and applicant inboxes are company-membership scoped; employer/recruiter roles alone do not grant access. Candidate identities, cover letters, and internal application notes are visible only to the candidate and the hiring team for that job. Public search excludes unlisted listings and draft/paused/closed listings; saved jobs and applications remain available only to their owner.
- Recruitment requests and their candidate pipelines are private to the owning company's active hiring team and the assigned platform recruiter. The global recruiter role grants access only to unassigned requests or requests explicitly assigned to that recruiter; candidate search uses public profiles only. Pipeline notes, interview details, and activity history never appear on public pages.
- Apply least privilege, separate operations/admin roles, periodic access review, and immediate membership revocation.
- Roles are additive capabilities. Signup assigns only the candidate role; administrative role assignment requires explicit permission, and super-admin grants require an existing super administrator. The last active super administrator is protected from suspension, deletion, and role removal.
- Regenerate the session after login and registration, invalidate it at logout, and compare a per-account session version on protected requests. Password reset, suspension, and deletion revoke sessions even when the configured session backend cannot enumerate all active records.
- Account deletion confirms the current password, anonymizes basic login/profile data, clears roles and linked identities, revokes sessions, and soft deletes the account. Retention of related records remains governed by data-class policy.
- Record role changes, account lifecycle transitions, and registration in an append-only audit table. Audit metadata must not contain passwords, tokens, recovery codes, or sensitive profile content.

## Data protection

- Classify data: public profile fields; authenticated marketplace fields; confidential resumes/contact details; highly sensitive identity evidence and credentials.
- Resumes and verification evidence live in private object storage, are malware-scanned before availability, and are accessed with short-lived authorized URLs. Never use public disks, guessable URLs, or public cache headers.
- Candidate profile photos and resumes use private storage and opaque database identifiers; no storage path or public disk URL is exposed. Resume download is authorized independently of profile visibility. Until malware scanning is operational, employer resume access is denied unless the record is explicitly marked clean; the candidate owner retains access to manage their file.
- Freelancer discovery requires both public talent-profile visibility and explicit freelancer listing. Project claims are marked self-reported until the named company confirms them. Review creation is limited to active company members on confirmed completed work; reviews remain private until moderator approval, and only approved ratings affect discovery.
- Encrypt in transit (TLS) and at rest using managed infrastructure. Encrypt particularly sensitive application fields where needed, with managed key rotation.
- Billing webhook payloads are application-encrypted at rest and deduplicated before processing. Do not log provider payloads, payment credentials, or raw customer payment details. No payment provider is configured and no charge/refund operation is enabled.
- Public profile is opt-in. Mask contact data and prevent scraping/enumeration. Visibility controls must be enforced on all web, export, notification, search, and future API paths.
- Use data minimization, consent records, access/export/deletion workflows, retention schedules, and verified deletion/anonymization processes.

## Application defenses

- Use Form Requests, server-side allowlists, output escaping, CSRF protection, ORM binding, safe redirects, MIME/content validation, upload size limits, and Content Security Policy.
- Rate-limit sign-in, reset, OTP, application/proposal submission, search, exports, uploads, and abuse-prone endpoints. Add bot controls based on risk.
- Prevent IDOR with policies and opaque public IDs. Use generic authentication errors where enumeration is possible.
- Keep secrets only in environment/secret management; never commit, log, render, or send them. Rotate compromised secrets promptly.
- Use dependency updates/scanning, Pint/PHPStan/tests, security headers, secure cookies, production debug disabled, and hardened error responses.

## Audit, monitoring, incident response

Audit privileged actions, authorization-sensitive document access, status/verification decisions, exports, and admin configuration changes with actor, target, time, request context, and safe metadata. Never audit plaintext secrets or document contents. Centralize logs, alert on anomalous access, queue failures, and permission changes. Maintain an incident runbook: contain, preserve evidence, assess scope, rotate credentials, notify responsible stakeholders/legal counsel, remediate, and conduct a post-incident review.
