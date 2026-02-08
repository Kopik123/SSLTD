# Setup (XAMPP + MySQL)

This project is intended to run in XAMPP on Windows with MySQL.

## 1. Create Database

Create an empty database named `ss_ltd` in phpMyAdmin, then import:
- `mysql.sql`

## 2. Configure Environment

Copy `.env.example` to `.env` and adjust:
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_NAME=ss_ltd`
- `DB_USER=root`
- `DB_PASS=` (blank unless you set one)

## 3. Run Migrations

```bat
cd /d C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe bin\migrate.php
C:\xampp\php\php.exe bin\migrate_status.php
```

Optional: run against a different env file (example: local staging):

```bat
cd /d C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe bin\migrate.php --env .env.staging
C:\xampp\php\php.exe bin\migrate_status.php --env .env.staging
```

## 4. Seed Demo Data (Includes Admin Login)

```bat
cd /d C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe bin\seed.php
```

Default accounts created by `bin/seed.php`:
- `admin@ss.local` / `Admin123!`
- `pm@ss.local` / `Pm123456!`
- `client@ss.local` / `Client123!`
- `employee@ss.local` / `Employee123!`
- `sub@ss.local` / `Sub123456!`
- `subworker@ss.local` / `Worker123!`

## 5. Quick Health Checks

```bat
C:\xampp\php\php.exe bin\health_db.php
```

Optional (staging env file):

```bat
C:\xampp\php\php.exe bin\health_db.php --env .env.staging
```

If the web server is running, also check:
- `GET /health`
- `GET /health/db`

## Dev Test Tools (APP_DEBUG=1 only)

When `APP_DEBUG=1`, the website shows a floating overlay with:
- server logs (tail)
- a `Tools` tab to speed up manual QA (quick "login as", quick links, clear rate limits)

Dev endpoints used by the overlay:
- `GET /app/dev/logs`
- `GET /app/dev/tools/whoami`
- `GET /app/dev/tools/users`
- `POST /app/dev/tools/login-as` (CSRF)
- `POST /app/dev/tools/logout` (CSRF)
- `POST /app/dev/tools/ratelimit/clear` (CSRF)

Safety:
- disabled when `APP_DEBUG=0`
- gated to private/LAN IPs; dangerous actions are loopback-only unless `SS_DEV_TOOLS_KEY` is set

## QA and Release Scripts

The `bin/` directory contains several scripts to help with QA and release:

**QA Scripts:**
- `php bin/qa_ops_checklist.php` - Automated ops health checks (DB, migrations, health endpoints)
- `php bin/qa_prerelease.php` - Guided walkthrough for manual QA checklist
- `php bin/qa_large_files.php` - Test upload/download boundaries near 10MB limit
- `php bin/qa_dev_tools.php` - Test dev tools endpoints (debug mode only)

**Release Scripts:**
- `php bin/release_helper.php checklist` - Show release checklist (items 23-26)
- `php bin/release_helper.php prepare` - Run pre-flight checks before release
- `php bin/release_helper.php export` - Export mysql.sql for release

**Other Utilities:**
- `php bin/php_lint.php` - PHP syntax check across all source files
- `php bin/rc1_local_staging.php` - RC1 validation on staging-like environment
- `php bin/smoke_http.php` - Basic HTTP smoke tests
- `php bin/find_unchecked_todos.php` - Find unchecked TODOs in full_todos.md
- `php bin/check_full_todos_done.php` - Check if all TODOs are complete

See `docs/manual_test_checklist.md` and `full_todos.md` for the complete QA and release plan.
