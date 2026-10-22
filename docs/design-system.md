# Design System

## Design direction

M Talent should feel precise, confident, and humane: the focus and clarity of Linear, the trust cues of Stripe, the composition discipline of Notion, and the people-first utility of LinkedIn—without copying any product. Avoid crowded portal layouts, loud gradients, stocky visual noise, and arbitrary badge/status colors.

## Foundations

- Mobile-first responsive Tailwind tokens for color, spacing, radius, shadows, typography, z-index, and motion. Semantic names (`surface`, `text-muted`, `success`) rather than one-off utility values for repeatable patterns.
- Use a clean sans-serif system stack initially; define a deliberate type scale, readable measure, 1.5+ body line-height, and tabular numerals for metrics.
- Favor calm neutral surfaces, an accessible M Talent primary accent, and restrained semantic colors. Every color combination meets WCAG 2.2 AA contrast; never use color as the sole status signal.
- Use 4px-based spacing rhythm, generous whitespace, crisp borders, subtle elevation, and purposeful iconography. Motion respects `prefers-reduced-motion`.

## Components

Create reusable Blade components with slots/variants and semantic defaults: application shell, navigation, heading, button, link, input, select, textarea, combobox, checkbox, radio, file upload, card, avatar, badge, table/list, empty state, pagination, modal/drawer, alert/toast, timeline, and status indicator. Components must have documented states: default, hover, focus-visible, active, disabled, loading, error, empty, and permission-restricted.

Forms use persistent labels, hint/error text linked with `aria-describedby`, clear required indicators, summary errors where useful, preserved values, and non-color validation cues. Destructive and consequential actions require clear language and confirmation proportionate to risk.

## Interaction and accessibility

Use semantic landmarks, headings in order, native controls first, visible keyboard focus, skip links, dialog focus trapping/return, correct announcement of async updates, adequate tap targets, and no hover-only disclosure. Livewire updates must retain focus and communicate state. Test with keyboard, screen reader spot checks, zoom/reflow, and reduced motion.

## Content principles

Write concise, respectful, globally understandable copy. Explain why sensitive data is requested, who can see it, and how visibility can be changed. Do not imply verification or hire outcomes beyond evidence. Use localized dates/numbers/currency and avoid Kerala-specific slang or assumptions in reusable UI.
