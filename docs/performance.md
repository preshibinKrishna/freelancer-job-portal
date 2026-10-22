# Performance audit and optimizations

**Audit date:** 2026-10-21

## Changes made

### Search result count

`SearchJobs::render()` used `paginate(12)` and then cloned the same filtered query for a second `count()`. Laravel’s length-aware paginator already runs the total count, so the extra count was removed and the existing paginator total is reused.

### Conversation inbox and history

`ConversationInbox::render()` previously loaded every conversation and counted unread messages with one additional query per conversation. It now:

- filters active or archived membership in SQL and paginates the inbox at 20 conversations;
- loads only each conversation’s latest message for the inbox preview, with ordering reset so the latest row is selected;
- computes unread messages with one grouped join query;
- paginates the open thread at 50 messages and offers pagination for older history;
- polls only while visible, every 30 seconds rather than every 15 seconds.

The inbox query count regression test seeds 25 conversations and verifies only two inbox queries touch `conversation_messages`: latest previews and the unread aggregate.

### Job detail and edit views

`JobDetailController` now calculates application availability once and passes the value to the view. `Job::acceptsApplications()` reuses its already-loaded company relationship when available, avoiding repeated existence queries. `ManageJob` now loads the application count with the job and the view uses that count instead of running a relationship count during rendering.

### Query indexes

Added an additive, reversible migration for query paths observed in the code:

- `(is_listed, created_at)` supports the default newest-first freelancer directory;
- `(status, published_at)` supports sorting discoverable freelance projects by publication date;
- `(status, starts_at)` supports scheduled upcoming-interview filtering and ordering;
- the conversation participant inbox index now covers user, archive status, conversation ID, and read timestamp used by the inbox and unread aggregate.

Existing indexes already cover job discovery, company membership, application lists, proposals by project, skill pivots, pipeline stages, message chronology, and project deadlines, so no duplicate indexes were added for those paths.

## Reviewed without changing

- Search currently uses contains-style `LIKE '%term%'` predicates. Ordinary B-tree indexes would not make those predicates seek efficiently. Full-text search would change matching behavior and has engine-specific behavior, so it needs measured data volume and product requirements before implementation.
- Public listing pages eager load the relationships rendered by their result rows. Admin and recruiter lists are paginated or explicitly limited; aggregate dashboard counts are scalar queries.
- Company logos and cover images are constrained to raster formats and size limits but are served as uploaded. Generating responsive variants needs an image-processing pipeline and storage lifecycle; no image package or cache was added without an operational requirement.
- Notifications use database channels and do not make network calls. Existing domain events are cheap persistence workflows; queueing them without a slow external listener would add operational complexity without reducing user-facing work.
- No response/query caching was introduced. Public listing cache invalidation depends on publication, moderation, and privacy changes and should be added only with measured evidence and explicit invalidation rules.

## Validation

The inbox regression test checks bounded output and confirms that message table reads remain fixed at two with 25 conversations.

`php artisan test --compact` completed with **91 passed and 3 failed** (548 assertions). All three failures are in the pre-existing `GeographicLocationsTest`: two calls omit the required actor argument to `SaveLocation::create()`, and the third then fails because of that same missing argument. These tests do not exercise the performance changes. The focused jobs and messaging tests pass (13 tests, 88 assertions). Pint passes for all changed PHP files.
