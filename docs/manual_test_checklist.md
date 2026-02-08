# Manual Test Checklist (Release Gate)

Run these steps on a staging-like environment (fresh DB + fresh uploads folder when possible).

**QUICK START**: Use `php bin/qa_prerelease.php` for a guided walkthrough of this checklist.

**AUTOMATED OPS CHECKS**: Run `php bin/qa_ops_checklist.php` to automate the Ops section below.

## Web: Auth

- [ ] Register (client) creates account and redirects to portal.
- [ ] Login works for existing user.
- [ ] Logout invalidates session.
- [ ] Reset password flow:
- [ ] Request reset link for an existing email.
- [ ] Use reset link, set new password, login with new password.

## Web: Quote Request

- [ ] Quote request mode chooser works (Simple vs Advanced).
- [ ] Simple: requires name/email/consent/description (min length); creates lead.
- [ ] Advanced: requires name/email/consent/address/scope; creates lead.
- [ ] Invalid inputs show errors and do not create records.
- [ ] Optional attachments:
- [ ] Upload JPG/PNG/PDF succeeds (size limits enforced).
- [ ] Upload a disallowed type fails safely.

## Web: Leads

- [ ] Leads list shows pagination.
- [ ] Lead detail shows metadata and attachments.
- [ ] Assign PM works (admin only).
- [ ] Status transitions:
- [ ] Changing status creates an audit log entry.
- [ ] Convert lead to project:
- [ ] Redirects to created project detail.
- [ ] Lead view shows link to project.

## Web: Projects

- [ ] Projects list shows pagination.
- [ ] Project detail shows overview.
- [ ] Status transitions write audit.
- [ ] Project members view (if enabled in UI) respects role restrictions.

## Web: Messages

- [ ] Inbox lists threads and supports pagination.
- [ ] Unread badge appears for unread threads.
- [ ] Opening thread marks as read.
- [ ] Sending a message adds it to the thread and marks as read for sender.

## Web: Uploads ACL

- [ ] Staff uploads file to lead and can download.
- [ ] Staff uploads file to project and can download.
- [ ] Client cannot download staff file until `client_visible` is enabled.
- [ ] After enabling `client_visible`, client can download the file for their own lead/project.
- [ ] Client cannot download files for other clients.

## Android (Field App)

- [ ] Login works and token is persisted.
- [ ] Offline mode:
- [ ] Toggle airplane mode and verify offline banner.
- [ ] Ensure API calls fail gracefully (no crash).
- [ ] Capture:
- [ ] Take a photo, attach to a project.
- [ ] With offline: item is queued.
- [ ] Back online: WorkManager uploads queued item.
- [ ] Messages:
- [ ] Open thread, send text.
- [ ] Attach photo/file and verify it queues/uploads.
- [ ] Timesheets:
- [ ] Start timesheet.
- [ ] Timer increments.
- [ ] Stop timesheet; entry appears in list.

## Ops

- [ ] `/health` returns 200.
- [ ] `/health/db` returns 200.
- [ ] `php bin/health_db.php` prints `DB OK`.
- [ ] `php bin/migrate_status.php` shows pending=0.
- [ ] Optional automation: `php bin/qa_large_files.php` passes (tests upload/download boundaries around 10MB).
- [ ] Optional automation: `php bin/qa_dev_tools.php` passes (verifies dev tools endpoints + CSRF flows in debug).
- [ ] Optional automation: `php bin/rc1_local_staging.php` passes (prod-like RC1 on separate staging DB + debug off).
