# Setup (XAMPP + MySQL)

This project is intended to run in XAMPP on Windows with MySQL.

> **Quick Start:** For an easier setup experience, see [QUICKSTART_PL.md](QUICKSTART_PL.md) (Polish) or run `setup.bat` from the project root.
>
> **Main Documentation:** See [README.md](../README.md) for full project overview in both Polish and English.

## Automated Setup (Recommended)

The easiest way to set up the project:

```bat
cd C:\xampp\htdocs\ss_ltd
setup.bat
```

This script will:
1. Create `.env` from `.env.example`
2. Create the database
3. Run migrations
4. Seed demo data
5. Verify the setup

Continue reading for manual setup steps.

---

## Manual Setup

## 1. Create Database

Create an empty database named `ss_ltd` in phpMyAdmin, or via command line:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Note:** You don't need to import `mysql.sql` manually - the migrations will handle this. The `mysql.sql` file is kept for reference and backup purposes.

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
