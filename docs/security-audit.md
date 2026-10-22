# M Talent security audit

**Audit date:** 2026-10-21  
**Scope:** Repository-wide static review of Laravel routes, authentication, authorization, policies, Livewire actions, uploads, private files, public views, billing/webhooks, configuration, and relevant feature tests.

This review is an application code audit. It is not a penetration test or an infrastructure assessment. Production identity provider, proxy, object-storage, database, TLS, backup, and secret-management settings were not available for inspection.

## Finding resolved

### Missing browser security response headers — Low

**Risk:** Without anti-framing headers, an external site could embed M Talent pages in a frame and attempt clickjacking. Without `nosniff`, browsers may interpret served content using a type other than the declared MIME type. Referrer and browser capability policies were also not set centrally.

**Affected files:** `bootstrap/app.php`; new `app/Http/Middleware/SecurityHeaders.php`.

**Fix:** Added global response headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, CSP `frame-ancestors 'none'`, `Referrer-Policy: strict-origin-when-cross-origin`, and a restrictive `Permissions-Policy` disabling camera, microphone, and geolocation.

**Regression test:** `tests/Feature/SecurityHeadersTest.php` asserts the headers on the homepage response.

## Reviewed controls

| Area | Result |
| --- | --- |
| Authentication and passwords | Passwords use Laravel hashing; registration/reset enforce strong passwords; login, reset, and registration are throttled; login/registration rotate the session; logout invalidates it. |
| Authorization, policies, and IDOR | Sensitive workflows use policies or explicit permission checks. Opaque IDs are used for public-facing resources; private resume, project attachment, and conversation attachment downloads authorize the actor. Sequential IDs in some authenticated route bindings remain protected by these checks. |
| Uploads and private storage | Resumes, profile photos, project attachments, and message attachments use the private local disk and application download paths. Resume downloads require ownership or a relevant hiring-team relationship and a clean scan for non-owners. Images and document uploads have type/size validation. Company logo/cover assets are intentionally public branding assets. |
| SQL injection and mass assignment | Application queries use Eloquent bindings; review found no interpolated raw SQL. HTTP/Livewire mutations use validated fields or explicit attribute maps, with model fillable/guarding. |
| XSS and CSRF | Blade output is escaped except the JSON-LD script, which uses JSON_HEX flags. Laravel request forgery middleware covers web mutations; only the payment webhook bypasses it, and provider signature verification fails closed. |
| Rate limiting | Authentication, registration, reset, verification, and webhook endpoints have throttles. Search and upload abuse limits remain dependent on Livewire and deployment-level controls. |
| Session and sensitive data | Session cookies default to HttpOnly, SameSite=Lax, and secure outside local/testing. Session-version checks invalidate stale sessions. Sensitive model attributes are hidden and selected profile fields are intentionally excluded from public views. |
| Payments and webhooks | No provider adapter is registered and the webhook endpoint returns unavailable until one exists. Accepted events are signature-verified by the adapter, deduplicated, encrypted at rest, and audited; charge/refund processing is not enabled. |
| Admin, API, logging, audit | Admin sections and mutations require granular permissions; privileged business changes write audit records. No standalone API routes were found. Application logs do not currently log request payloads or uploaded document contents in reviewed code. |
| Signed URLs | Private local-disk serving requires Laravel signed URLs. The application’s private documents use separately authorized controller endpoints rather than public storage URLs. |

## Release and verification notes

- Existing user columns for two-factor secrets/recovery codes are encrypted and hidden, but this repository contains no MFA enrollment or login challenge flow. `docs/security.md` requires MFA before production admin access. Treat MFA as a production release gate; this audit did not invent an enrollment protocol or add an incomplete one-time-code implementation.
- `composer audit --locked` could not reach Packagist because the environment could not resolve `repo.packagist.org`. Dependency advisories therefore remain unverified.
- The full test run had 3 failures in `tests/Feature/GeographicLocationsTest.php`, which call `SaveLocation::create()` without its required actor argument. These are outside the security changes. The homepage route regression test was updated to use `RefreshDatabase` because the homepage now queries marketplace data.

## Test results

- Security/authentication/profile/messaging/project/billing and homepage regression tests: **37 passed (203 assertions)**.
- Full suite: **86 passed, 3 failed**; all remaining failures are the geography test argument mismatches described above.
- Pint check passed for the changed PHP files.
- PHPStan could not start because its worker could not bind a local TCP socket in this sandbox (`EPERM`).
- `composer audit --locked` could not reach Packagist because the environment could not resolve `repo.packagist.org`.
