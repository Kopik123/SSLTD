# Conflict Strategy (MVP)

For v0.1, the Android app is designed to minimize write conflicts by avoiding local edits of existing server records.

## Server Wins

- The server is the source of truth for projects, threads, and messages.
- The client does not support editing server messages or timesheet rows; it only creates new events (start/stop, send message, upload doc).
- If a server response differs from a previously displayed value, the UI should prefer the latest server response.

## Offline Queue Behavior

- When offline, uploads and attachments are stored in a local queue and retried by WorkManager when constraints are met.
- If an upload fails permanently (e.g. 401), the item remains queued until the user re-authenticates.

## Known Limitations

- No merge UI exists for conflicting edits because the MVP does not support editing server-owned objects.
- Future phase: local caching of projects/messages and a sync engine may require explicit conflict resolution rules.

