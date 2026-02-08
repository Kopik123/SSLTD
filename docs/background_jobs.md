# Background Jobs Strategy (Future)

This MVP runs as a single PHP app (XAMPP) without a job runner. The current approach is:

- Keep all user-facing requests synchronous.
- Use WorkManager on Android for offline-safe uploads.
- Store enough data in the DB (audit_log, api_tokens, uploads) to allow future async processing.

When we add background jobs (v0.2+), recommended approach:

1. Add a `jobs` table with payload JSON, status, attempts, next_run_at.
2. Add a CLI worker (PHP) that loops and executes jobs.
3. Run worker with Windows Task Scheduler / a service (or move to a platform that supports long-running processes).
4. Use job types:
- `send_email` (lead created, password reset)
- `image_reencode` (if we move re-encode out of request cycle)
- `report_build` (PDF/status exports)

For MySQL-only deployments, the same pattern works; the worker only needs DB access and the uploads folder.

