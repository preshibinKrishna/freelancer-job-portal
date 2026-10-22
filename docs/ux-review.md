# M Talent UX review

Review date: 21 October 2026

## Review scope

Reviewed the Blade page inventory and shared components for the public site, authentication, job and freelancer discovery, company and candidate profiles, employer and recruiter workspaces, project and contract flows, verification and moderation, messaging, and administration. The review focused on reusable layout and interaction patterns that affect the full product.

## Findings and changes

| Area | Finding | Change |
| --- | --- | --- |
| Authenticated navigation | At phone widths, several important links disappeared with no reliable way to reach them. | Added visible quick links and an accessible account menu with role-aware workspace links, account settings, and sign out. Added active-page indication to primary desktop links. |
| Admin navigation | The long list of admin sections depended on horizontal scrolling on mobile. | Kept the dense sidebar on desktop and added an easy-to-scan two-column disclosure on small screens with a bounded scroll area and current-section state. |
| Typography | The shared font named Instrument Sans but did not load it; the public home page also contained an unused font directive. | Removed the unused directive and switched to a consistent system font stack, avoiding a missing font request and keeping text readable across locales. |
| Form feedback | Shared select fields did not expose invalid state or link error and hint copy to the control. Error feedback also replaced hints entirely. | Added `aria-invalid` and `aria-describedby` handling for select fields and retained both error and help text where relevant. |
| Submit feedback | Shared loading buttons showed a spinner without exposing busy state to assistive technology. | Added `aria-busy` to the shared button component and preserved the visible label while work is in progress. |
| Empty states | The shared empty state used a decorative sparkle that implied an action or feature where there was none. | Replaced it with a quiet informational marker, clearer hierarchy, and stronger readable body copy. |
| Page loading | Live updates had inconsistent or static loading announcements. | Added a shared polite loading announcement to authenticated Livewire pages. |
| Interaction consistency | Focus, reduced motion, and browser text selection behavior were not fully consistent across screens. | Centralized reduced-motion, visible focus, selection, tap-highlight, and disabled/progress cursor rules in the existing stylesheet. |

## Quality principles retained

- Continue using server-rendered Blade, Livewire, Tailwind, and Alpine.
- Preserve page-specific workflows, authorization, and information architecture.
- Keep public pages and private workspaces visually related while keeping admin navigation compact and operational.
- Use system typography and existing design tokens rather than adding a font or component dependency.

## Follow-up priorities

1. Run a keyboard and screen reader pass on the major workflows: job search/application, profile editing, proposal submission, verification review, and admin record actions.
2. Check real content at narrow phone widths and at 200% zoom, especially the recruitment pipeline and wide administrative tables.
3. Add route-specific empty and failure recovery copy where backend errors currently rely on generic framework validation or exception pages.
4. When browser automation is available, capture representative public, candidate, employer, recruiter, and administrator pages at phone, tablet, and desktop widths before launch.

This review is based on the available templates and shared UI code. It does not claim a full assistive-technology audit or a visual check against production data and live browser rendering.
