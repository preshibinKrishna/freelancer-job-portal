# M Talent — Product Requirements

## Product vision

MTalent is a premium technology-talent marketplace. It connects employers with verified technology professionals for permanent roles, contract work, and project engagements. The experience should feel focused, trusted, and calm—not like a high-volume classifieds portal.

Initial go-to-market serves Kerala technology ecosystems (Technopark, Infopark, Cyberpark and surrounding companies); the domain model, language, currency, location, and compliance design must support expansion from Kerala to India and globally.

## Personas and outcomes

| Persona | Primary outcome |
| --- | --- |
| Candidate | Find relevant technology employment while controlling who sees their profile and resume. |
| Freelancer | Win suitable engagements, present credible work, and build trusted reputation. |
| Employer recruiter | Source, assess, communicate with, and hire qualified talent efficiently. |
| Hiring manager | Collaborate on openings and make auditable decisions. |
| Platform operations | Verify users and companies, resolve abuse, and maintain marketplace quality. |
| Administrator | Manage policies, moderation, entitlements, reports, and platform configuration. |

## MVP scope

1. Authenticated accounts with one or more roles; account recovery, email/phone verification, sessions and consent.
2. Candidate and freelancer profiles: skills, work history, education, preferences, location, availability, work authorization, portfolio links/projects, and privacy controls.
3. Private resume upload, versioning and controlled sharing.
4. Employer/company pages, recruiter membership and role-based workspace access.
5. Job posting, review, publishing, search/browse, saved searches, applications, screening stages, notes, and candidate communication.
6. Freelance/project postings, proposals, shortlist/award workflow, milestones as a domain-ready foundation (payments require a separate approved scope).
7. Verification requests and administrative review for identity, professional, and company verification; badges only after approval.
8. Notifications (in-app and email) for meaningful workflow changes.
9. Operations back office, audit history, moderation, and privacy/consent administration.
10. Freelancer directory with listed professional profiles, rate/capacity preferences, categorized portfolio work, company-confirmed completions, and moderated project-linked reviews.

## Explicitly deferred

- AI ranking, semantic matching, automated rejection, and generative job content.
- Escrow, wallet, payouts, invoicing, tax calculation, and dispute resolution.
- Native mobile apps, public APIs, multilingual UI beyond localization readiness, and enterprise SSO.
- Ratings/reviews until anti-abuse, moderation, and eligibility policy are designed.

## Core product rules

- A person may be a candidate, freelancer, employer member, or multiple simultaneously; permissions derive from membership and context, not a single global role.
- A public profile is opt-in and exposes only expressly public fields. Resume, contact details, verification evidence, recruiter notes, applications, and proposal contents are private.
- Job applications and proposals are immutable submissions with explicitly modeled status changes; candidate withdrawal and employer closure preserve an audit trail.
- Employers with an active owner/admin/recruiter membership may author jobs; a published company profile is required before publishing. Job visibility supports public discovery or unlisted direct links. Draft/paused/closed listings are not discoverable and cannot accept applications.
- Applications are one-per-candidate/job, initially `applied`, employer-managed through `reviewing`, `shortlisted`, `interview`, `offer`, `hired`, or `rejected`, and may be withdrawn by the candidate before a terminal hired/rejected decision. Every transition is audited and notified in-app.
- Employer members can act only within their organization and assigned permissions. Company verification is independent of individual identity verification.
- Search visibility honors profile privacy, location/work preferences, opt-outs, blocks, and entitlement rules.
- Any future matching must be explainable, configurable, privacy-preserving, and human-reviewable.

## Quality requirements

- Mobile-first, quick perceived performance, clear empty states, recoverable errors, and accessible workflows.
- WCAG 2.2 AA baseline, semantic HTML, full keyboard operation, visible focus, accessible names, and contrast-safe design.
- Every sensitive action is authorized, audited, and attributable. Critical user notices are reliable and traceable.
- India-ready at launch, international-ready by configuration: IANA time zones, ISO country/currency codes, localized formatting, and configurable legal/retention policies.

## Success measures

Track activation (completed profiles/verified companies), qualified application and proposal rates, employer time-to-shortlist, interview conversion, successful hires/awards, response-time reliability, abuse rates, and candidate privacy incidents (target: zero).
