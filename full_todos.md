# Full TODOs + Release Plan (WEB + Android)

This file is a release-oriented checklist derived from `plans/`:
- `plans/base plan.lua`
- `plans/screen list.lua`
- `plans/design plan.lua`

Current state (as of 2026-02-07):
- WEB: public pages + Request a Quote (Simple/Advanced) + portal login + Leads list/detail + assign PM + convert to project.
- API: auth (login/register/me/refresh) + projects + threads/messages + uploads + timesheets (Android MVP endpoints).
- Android: MVP implemented (login, projects list/detail, today timesheets, capture + offline upload queue, messages + attachments, profile).
- DB: MySQL (XAMPP) default; schema/migrations aligned (`mysql.sql` + migrations).

Rule for this workspace:
- Every change must be logged in `changelogs.lua`.

## Release Scope Proposal (v0.1 "Operational MVP")

WEB (Office-first):
- Public website + lead intake (Simple/Advanced quote request)
- Portal: Leads -> Projects (minimal) + Messages (per lead/project) + basic Files (download) + Timesheets (read)
- Admin: user management (basic) + audit log viewer (basic)

Android (Field-first):
- Login
- Today: Start/Stop time + quick capture entry points
- Projects: list + details (read-only first)
- Capture: photo/file upload with offline queue
- Messages: threads + send
- Profile: account + sign out

## TODO List (WEB)

- [x] Portal navigation per `plans/screen list.lua` (Dashboard, Leads, Projects, Messages, Timesheets, Admin)
- [x] Projects module (minimum):
- [x] Project list (`/app/projects`) with filters (status, assigned PM)
- [x] Project detail (`/app/projects/:id`) Overview tab (status, address, client, team)
- [x] Convert lead -> project should redirect to created project detail (and link in lead view)
- [x] Messages module (WEB):
- [x] Thread model: one thread per lead + one per project (auto-create if missing)
- [x] Inbox view + thread view + compose
- [x] Mark read/unread (optional MVP)
- [x] Files/Uploads (WEB):
- [x] Secure download route (stream file via PHP) with ACL checks (owner + role)
- [x] Client visibility toggle for uploads (respects `client_visible`)
- [x] Leads UX:
- [x] Add lead status transitions (quote_review, meeting_scheduled, etc. from plan)
- [x] Attachments table: show download links (after download route exists)
- [x] Client portal (minimum):
- [x] Client can see own leads/projects and message thread
- [x] Basic approvals panel placeholder (quote/schedule/change) for future
- [x] Admin basics:
- [x] Users list + create + set/reset password
- [x] Users: role changes + status toggle
- [x] Audit log viewer (basic list)
- [x] Audit log filters (entity/action)

## TODO List (API / Backend)

- [x] Standardize API auth:
- [x] Bearer token only for mobile (`Authorization: Bearer ...`)
- [x] Token expiration + refresh strategy (or short-lived tokens + re-login for MVP)
- [x] Error format + request IDs for troubleshooting
- [x] API endpoints needed for Android MVP:
- [x] GET `/api/projects` (assigned-to-me for employee/subcontractor)
- [x] GET `/api/projects/:id`
- [x] GET `/api/threads?scope=project|lead&scope_id=...`
- [x] GET `/api/threads/:id/messages` (pagination)
- [x] POST `/api/threads/:id/messages`
- [x] POST `/api/uploads` (multipart) or presign-like flow (phase 2)
- [x] POST `/api/timesheets/start`
- [x] POST `/api/timesheets/stop`
- [x] GET `/api/timesheets?from=...&to=...` (me)
- [x] Rate limiting per endpoint bucket (login, uploads, messages)
- [x] Input validation hardening:
- [x] Central validator helpers (avoid copy/paste)
- [x] Consistent trimming, max lengths, allowed enum values
- [x] File upload hardening:
- [x] Enforce safe storage paths + deny traversal
- [x] Enforce mime + size + count limits consistently (web + api)
- [x] Add server-side image re-encode (optional, but reduces risk)
- [x] Background jobs strategy for later (queue, email sending)

## TODO List (Database / Migrations)

- [x] Expand roles and entity model (from `plans/base plan.lua`):
- [x] Users: add roles `employee`, `subcontractor`, `subcontractor_worker`
- [x] Subcontractors + subcontractor_workers tables
- [x] Project membership table (project_id <-> user_id + role_in_project)
- [x] Threads/Messages: add indexes (thread_id, created_at)
- [x] Threads/Messages: unread tracking
- [x] Timesheets: add indexes (user_id, started_at)
- [x] Add `projects.status` enum consistency with plan (materials_planning, in_progress, completed, closed, etc.)
- [x] Keep MySQL and SQLite migrations in sync (or officially drop SQLite to reduce surface area)
- [x] Update `mysql.sql` after each migration set (release artifact)

## TODO List (Android App)

- [x] Networking:
- [x] Retrofit/OkHttp client + JSON models
- [x] Auth flow: login + token storage via Jetpack Security (EncryptedSharedPreferences)
- [x] Global error handling (401 re-auth, offline mode)
- [x] Offline storage:
- [x] Room DB for upload queue
- [x] Sync engine:
- [x] WorkManager for background uploads + retries + constraints (WiFi optional)
- [x] Conflict strategy (server-wins for MVP) documented in `docs/conflict_strategy.md`
- [x] Screens:
- [x] Today: start/stop timesheet
- [x] Today: current running timer UI (elapsed)
- [x] Projects: list read-only
- [x] Projects: detail read-only
- [x] Capture: camera intent / in-app camera, attach to project, stage (before/during/after), notes
- [x] Messages: threads list + thread view + send text
- [x] Messages: attach photo/file
- [x] Profile: me + sign out
- [x] Permissions + privacy:
- [x] Runtime permissions (camera) + file picker (no storage permission)
- [x] Strip EXIF GPS by default (or make it opt-in)
- [x] Release build:
- [x] Versioning (versionCode/versionName)
- [x] Signing configs + Proguard/R8

## Security / Compliance (Release Gate)

- [x] Production config rules:
- [x] Require non-default `APP_KEY` in prod (enforced in `index.php`)
- [x] `APP_DEBUG=0`, `APP_ENV=prod` (enforced for prod env in `index.php`)
- [x] Secure cookies when HTTPS (Secure + SameSite) (configured in `src/Http/Session.php`)
- [x] CSRF coverage review (all state-changing web routes) complete (see `src/routes.php`)
- [x] RBAC + per-project ACL enforcement (no data leakage) implemented across controllers/routes
- [x] Brute force protections:
- [x] Login lockout or increasing backoff (rate-limit by IP and IP+email via `RateLimitMiddleware`)
- [x] Password reset token rotation + single-use enforcement
- [x] Content Security Policy:
- [x] CSP enabled in `index.php` (no inline scripts/styles expected)
- [x] Privacy policy + terms text (even MVP) (`/privacy`, `/terms`)
- [x] Backups:
- [x] Document DB backup/restore for MySQL (mysqldump) (`docs/backups.md`)
- [x] Document uploads backup (storage/uploads) (`docs/backups.md`)

## Performance / Reliability (Release Gate)

- [x] Pagination everywhere (Leads, Projects, Messages)
- [x] DB indexes on all filtering/sorting columns used in list screens
- [x] File streaming download (no full read into memory)
- [x] Static asset caching headers (Apache) + compression
- [x] Log rotation for `storage/logs/`
- [x] Health checks / smoke tests:
- [x] DB connection test
- [x] Migrations status
- [x] HTTP health endpoints (`/health`, `/health/db`)
- [x] HTTP smoke script (`bin/smoke_http.php`)

## QA (Release Gate)

- [x] Manual test checklist documented (`docs/manual_test_checklist.md`)
- [x] Automated checks:
- [x] PHP syntax lint (`bin/php_lint.php`)
- [x] Basic integration tests (HTTP happy paths) via smoke runner (`bin/smoke_http.php`)

## Release Plan (Checklist)

Status as of 2026-02-07 (local/dev):

- [ ] 1. Freeze v0.1 scope and acceptance criteria (what MUST ship vs backlog).
- [x] 2. DB design pass for missing MVP tables (project_members, subcontractors, etc.) and write migrations for MySQL (and SQLite only if still supported).
- [x] 3. Implement backend API endpoints required by Android MVP (projects, threads/messages, uploads, timesheets).
- [x] 4. Implement WEB portal modules needed for office usage.
- [x] 5. Projects list/detail.
- [x] 6. Messages (lead/project).
- [x] 7. Files download with ACL.
- [x] 8. Admin users + audit log viewer.
- [x] 9. Implement Android MVP end-to-end.
- [x] 10. Auth + token storage.
- [x] 11. Offline queue + background sync.
- [x] 12. Projects + Capture + Messages + Timesheets screens wired to API.
- [x] 13. Security hardening sprint.
- [x] 14. CSP plan (remove inline styles or add nonces).
- [x] 15. Lockout/backoff + audit key actions.
- [x] 16. Review ACL rules and add missing checks.
- [x] 17. Performance pass.
- [x] 18. Add indexes + pagination + cache headers.
- [x] 19. Large file upload/download testing (automated in dev via `php bin/qa_large_files.php`, validates 9MB OK + 11MB reject + streaming download).
- [x] 20. Release candidate (RC1) on staging (local prod-like run via `php bin/rc1_local_staging.php`).
- [x] 21. Run migrations on staging DB + seed minimal admin (done by `bin/rc1_local_staging.php`).
- [ ] 22. Run manual QA checklist + fix blockers (`docs/manual_test_checklist.md`).
- [ ] 23. Release.
- [ ] 24. Tag version + export `mysql.sql` (final) + ship Android signed build (APK/AAB).
- [ ] 25. Post-release.
- [ ] 26. Monitor logs, handle hotfixes, create v0.1.1 plan.

## Backlog After v0.1 (Phase 2 / v0.2+)

- [x] v0.2 Android offline cache:
- [x] Room DB cache for projects + threads/messages
- [x] Persisted offline queue UX: per-item status + retry/delete (Capture screen) + queued attachments
- [x] Timesheets status (running/stopped) derived from `stopped_at` and shown in web and Android
- [x] Crash reporting (Sentry optional) for Android (set `SS_SENTRY_DSN` at build time)
- [x] v0.3 Quote checklist approvals (WEB):
- [x] DB tables + migrations for checklists/items (MySQL + SQLite) + update `mysql.sql`
- [x] Staff: lead checklist/estimate builder + submit-to-client flow
- [x] Client: approvals list + detail + approve/reject actions (CSRF protected)
- [x] Workflow: lead status aligns with checklist submit/decision; checklist copied when converting lead -> project
- [x] v0.4 Project checklist (API + Android):
- [x] API: get current project checklist + update checklist item status
- [x] Android: project checklist screen + status toggle
- [x] v0.5 Schedule proposals + client approval (WEB):
- [x] DB: schedule_proposals + schedule_events tables (MySQL + SQLite) + update `mysql.sql`
- [x] Staff: propose schedule window from Project detail (CSRF protected) + view pending proposals (`/app/schedule`)
- [x] Client: approvals list includes pending schedule proposals + detail view + approve/reject with optional note
- [x] Workflow: approving schedule creates an approved schedule event and updates project status to `client_approved`
- [x] v0.6 Inventory (WEB):
- [x] DB: materials/tools + project allocations + deliveries tables (MySQL + SQLite) + update `mysql.sql`
- [x] WEB: Materials catalog (list/create/edit) + Tools catalog (list/create/edit)
- [x] Nav: enable Inventory link in sidebar (admin/pm only)
- [x] v0.7 Project inventory usage (WEB):
- [x] Project detail: add Materials & Tools section (add/update/remove allocations)
- [x] Project detail: add Deliveries section (create/update/delete)
- [x] Seed: add demo allocation + demo delivery for the sample project
- [x] v0.8 Schedule feed (API + Android):
- [x] API: `GET /api/schedule?from=...&to=...` returns approved schedule events, filtered by role/ACL
- [x] Android Today: show upcoming schedule events (uses Room cache as fallback when offline/API fails)
- [x] v0.9 Reports + Issues (WEB + API + Android):
- [x] DB: `project_reports` + `issues` tables (MySQL + SQLite) + update `mysql.sql`
- [x] WEB: Project detail shows Reports (add + list) and Issues (create + edit status/severity/body)
- [x] API: list/create reports; list/create/update issues with project ACL
- [x] Android: Project detail adds quick actions (Report/Issues) + basic screens (online-only for now)
- [x] v0.10 Change requests + client approvals (WEB):
- [x] DB: `change_requests` table (MySQL + SQLite) + update `mysql.sql`
- [x] Staff: create draft/submitted change requests from Project detail + submit-to-client flow
- [x] Client: approvals list includes pending change requests + detail view + approve/reject with optional note
- [x] Seed: add a pending change request for demo project
- [x] v0.11 People (WEB):
- [x] WEB: enable People nav link (admin/pm)
- [x] WEB: Subcontractors list + detail (workers list) for admin/pm
- [x] WEB: Admin can approve/reject pending subcontractor workers
- [x] Seed: create a pending worker request for demo subcontractor
- [x] v0.12 Schedule calendar (WEB):
- [x] WEB: schedule supports week/month calendar view with navigation
- [x] WEB: staff can create/update/cancel schedule events
- [x] WEB: conflict indicator for overlapping events per assigned PM (best-effort)
- [x] Seed: create a demo schedule event so calendar has content
- [x] v0.13 Project checklist (WEB):
- [x] WEB: project checklist page (`/app/projects/:id/checklist`) with total + lock status
- [x] WEB: draft mode supports add/edit/delete; locked mode supports status-only updates
- [x] Seed: create demo project checklist + items
- Backlog notes:
- Notifications (email + push) + reminder automations
- Integrations (Drive/Dropbox, SMTP, backups)
