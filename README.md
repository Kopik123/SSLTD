# S&S LTD Web Portal + Android Field App

Greenfield MVP for S&S LTD - a construction management system with a web portal and Android field application.

## Overview

This repository contains:
- **Web Portal**: PHP-based web application for project management, quotes, and administration
- **Android Field App**: Kotlin/Compose mobile app for field workers

## Environment

- **Server**: Windows + XAMPP (Apache, MySQL, PHP)
- **PHP CLI**: `c:\xampp\php\php.exe`
- **Database**: MySQL (XAMPP) or SQLite (dev-only)
- **Android**: Gradle-based build with JDK 17

## Quick Start

### Web Portal Setup

1. **Create environment file**:
   ```bash
   # Copy and customize .env (see bin/migrate.php for defaults)
   ```

2. **Initialize database** (Option A - Recommended):
   ```bash
   c:\xampp\php\php.exe bin\migrate.php
   c:\xampp\php\php.exe bin\seed.php
   ```

   Or (Option B - Manual):
   ```bash
   c:\xampp\mysql\bin\mysql.exe -u root < mysql.sql
   c:\xampp\php\php.exe bin\seed.php
   ```

3. **Run the application**:
   - **Apache (XAMPP)**: Open `http://localhost/ss_ltd/`
   - **PHP Built-in Server**:
     ```bash
     c:\xampp\php\php.exe -S 127.0.0.1:8000 index.php
     ```

### Android App Setup

1. Open the `android/` directory in Android Studio
2. Sync Gradle
3. Build or run on emulator/device

**Environment Variables** (for custom API endpoints):
- `SS_API_BASE_URL_DEBUG`: Debug API URL (default: `http://10.0.2.2:8000/`)
- `SS_API_BASE_URL`: Release API URL (required for signed releases)
- `SS_SENTRY_DSN`: Optional Sentry DSN for error reporting

## Development

### Directory Structure

```
.
├── android/           # Android field app (Kotlin/Compose)
├── assets/            # Web static assets (CSS, JS, images)
├── bin/               # CLI tools and utilities
├── database/          # Database migrations
│   └── migrations/    # SQL migration files
│       └── mysql/     # MySQL-specific migrations
├── docs/              # Documentation
├── plans/             # Project planning documents
├── src/               # PHP source code (App\ namespace)
├── storage/           # File uploads and logs (not web-accessible)
│   ├── uploads/       # User-uploaded files
│   └── logs/          # Application logs
├── index.php          # Single entry point for web app
└── mysql.sql          # MySQL schema snapshot
```

### Testing

#### PHP Linting
```bash
php bin/php_lint.php
```
Recursively checks all PHP files for syntax errors.

#### PHP Smoke Tests
```bash
# Start server first
php -S 127.0.0.1:8000 index.php

# In another terminal
php bin/smoke_http.php http://127.0.0.1:8000
```
Tests basic HTTP endpoints (health checks, login, quote-request).

#### Android Build
```bash
cd android
./gradlew assembleDebug
./gradlew assembleRelease  # Optional, requires signing config
```

#### Migration Status
```bash
php bin/migrate_status.php
```
Check which migrations have been applied and which are pending.

## Continuous Integration

This repository uses GitHub Actions for automated testing and builds.

### CI Workflow

The CI pipeline runs automatically on:
- **Push** to `main` or `develop` branches
- **Pull Requests** targeting `main` or `develop`

### CI Jobs

1. **PHP Lint** 
   - Runs `bin/php_lint.php` to check PHP syntax
   - Fails if any PHP files have syntax errors

2. **PHP Smoke Tests**
   - Creates a test database (SQLite)
   - Runs migrations and seeds
   - Starts PHP development server
   - Runs `bin/smoke_http.php` to test HTTP endpoints

3. **Android Build**
   - Sets up JDK 17 and Gradle
   - Builds debug APK (`assembleDebug`)
   - Uploads APK as workflow artifact (7-day retention)

4. **MySQL Schema Verification**
   - Imports `mysql.sql` into a MySQL database
   - Runs `bin/migrate_status.php` to check for pending migrations
   - **Fails if `mysql.sql` is out of date** (pending migrations exist)
   - This ensures `mysql.sql` stays synchronized with the migration files

5. **CI Status**
   - Summary job that requires all tests to pass
   - Used as a branch protection requirement

### Status Checks

The CI workflow provides:
- ✅ **Pass/Fail status** on Pull Requests
- 🔒 **Merge blocking** if any test fails
- 📦 **Build artifacts** (debug APK) for successful Android builds
- 📧 **Notifications** via GitHub (email/web) for failed builds

### Viewing CI Results

1. **In Pull Requests**: Check the "Checks" tab for detailed job results
2. **In Actions Tab**: View all workflow runs at `https://github.com/Kopik123/SSLTD/actions`
3. **Download Artifacts**: Debug APKs are available in successful Android build runs

### Branch Protection Rules

To enforce CI checks before merging:

1. Go to repository **Settings** → **Branches**
2. Add a branch protection rule for `main` and `develop`:
   - ☑️ Require status checks to pass before merging
   - ☑️ Select required status checks: `CI Status`
   - ☑️ Require branches to be up to date before merging

This ensures that:
- All PRs must pass CI before merging
- No direct pushes bypass CI checks
- Code quality is maintained on protected branches

### Local CI Testing

To test CI jobs locally before pushing:

```bash
# PHP Lint
php bin/php_lint.php

# PHP Smoke Tests (requires running server)
php -S 127.0.0.1:8000 index.php &
sleep 5
php bin/smoke_http.php http://127.0.0.1:8000

# Android Build
cd android
./gradlew assembleDebug

# MySQL Schema Check
mysql -u root < mysql.sql
php bin/migrate_status.php
```

### Troubleshooting CI Failures

**PHP Lint Failures**:
- Check the error output for specific file and line numbers
- Run `php bin/php_lint.php` locally to reproduce

**Smoke Test Failures**:
- Check if migrations ran successfully
- Verify seeder creates necessary test data
- Check server logs for HTTP errors

**Android Build Failures**:
- Verify Gradle files are valid
- Check for dependency version conflicts
- Review build logs in the Actions tab

**MySQL Schema Out of Date**:
- Run `php bin/migrate.php` on a fresh MySQL database
- Export the schema: `mysqldump -u root ss_ltd --no-data > mysql.sql`
- Commit the updated `mysql.sql`

## Security

### Security Baselines (Do Not Regress)

- **Passwords**: `password_hash()` / `password_verify()`
- **Sessions**: `HttpOnly` cookies, regenerated on login
- **Forms**: CSRF token required for all non-API state-changing requests
- **SQL**: Prepared statements only (PDO)
- **Uploads**: 
  - Validate size + allowed MIME types
  - Store outside web paths (`storage/uploads/`)
  - Never trust user filenames
- **Authorization**: 
  - Role checks at route layer
  - Project/lead access scoped (ACL) before exposing records

## Architecture

- **Entry Point**: `index.php` - single entry point for all requests
- **Server Code**: `src/` directory - uses `App\` namespace
- **Database**: MySQL (default) or SQLite (dev-only via `.env`)
- **File Uploads**: `storage/uploads/` - served via PHP (not direct web access)
- **Routing**: Base-path aware (supports subdirectory hosting)

## Contributing

1. Create a feature branch from `develop`
2. Make your changes
3. Ensure all tests pass locally
4. Open a Pull Request to `develop`
5. Wait for CI to pass
6. Request code review

## License

Proprietary - S&S LTD
