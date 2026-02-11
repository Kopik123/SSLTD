# S&S LTD - Web Portal & Field App

[![CI](https://github.com/Kopik123/SSLTD/actions/workflows/ci.yml/badge.svg)](https://github.com/Kopik123/SSLTD/actions/workflows/ci.yml)

This repository contains the S&S LTD web portal (PHP) and Android field application.

## Project Structure

```
.
├── android/          # Android field app (Kotlin, Jetpack Compose)
├── bin/              # CLI utilities and scripts
├── database/         # Database migrations
├── docs/             # Documentation
├── src/              # PHP application source code
├── storage/          # Storage for uploads and logs
├── index.php         # Application entry point
├── mysql.sql         # MySQL schema (XAMPP quick-start)
└── .github/          # GitHub Actions workflows
```

## Quick Start

### Prerequisites

- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher (or MariaDB)
- **Android Studio**: Latest stable version (for Android app)
- **Java**: 17 (for Android builds)

### Setup (Local Development)

1. **Clone the repository**
   ```bash
   git clone https://github.com/Kopik123/SSLTD.git
   cd SSLTD
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Set up database**
   
   Option A - Using migrations (recommended):
   ```bash
   php bin/migrate.php
   php bin/seed.php
   ```
   
   Option B - Import mysql.sql directly:
   ```bash
   mysql -u root -p < mysql.sql
   php bin/seed.php
   ```

4. **Start the development server**
   ```bash
   php -S 127.0.0.1:8000 index.php
   ```

5. **Access the application**
   - Open http://127.0.0.1:8000 in your browser
   - Login with seeded credentials (see `docs/setup.md`)

### Android App Setup

See [android/README.md](android/README.md) for detailed Android setup instructions.

## Development

### Running Tests

**PHP Lint:**
```bash
php bin/php_lint.php
```

**Smoke Tests:**
```bash
# Start server first
php -S 127.0.0.1:8000 index.php &
# Run smoke tests
php bin/smoke_http.php http://127.0.0.1:8000
```

**Validate Migrations:**
```bash
php bin/validate_migrations.php
```

**Android Build:**
```bash
cd android
./gradlew assembleDebug
```

## Continuous Integration (CI/CD)

This repository uses GitHub Actions for automated testing and validation. The CI pipeline runs on every push and pull request to the `main` and `develop` branches.

### CI Pipeline Jobs

1. **PHP Lint** - Validates PHP syntax across all PHP files
2. **PHP Smoke Tests** - Runs HTTP smoke tests against key endpoints
3. **Android Build** - Builds the debug APK
4. **Migration Validation** - Ensures mysql.sql is up-to-date with migrations

### Workflow Configuration

The CI workflow is defined in [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

### Branch Protection

To enforce CI checks before merging:

1. Go to **Settings** → **Branches** in GitHub
2. Add a branch protection rule for `main` and `develop`
3. Enable **Require status checks to pass before merging**
4. Select the following required checks:
   - `PHP Lint`
   - `PHP Smoke Tests`
   - `Android Build`
   - `Validate Migration State`
5. Enable **Require branches to be up to date before merging**

### CI Notifications

GitHub automatically sends notifications for:
- Failed CI runs (to commit authors)
- Failed PR checks (to PR authors and reviewers)

You can configure additional notifications in:
- **Settings** → **Notifications** (personal)
- **Settings** → **Webhooks** (repository-level, for Slack/Discord/etc.)

### Viewing CI Results

- **Pull Requests**: Check status at the bottom of the PR page
- **Actions Tab**: View detailed logs at https://github.com/Kopik123/SSLTD/actions
- **Commit Status**: Look for ✓ or ✗ next to commits

### Troubleshooting CI Failures

**PHP Lint Failures:**
- Check syntax errors in the failing files
- Run `php bin/php_lint.php` locally to reproduce

**Smoke Test Failures:**
- Verify all required endpoints return 200 status
- Check database migrations are applied correctly
- Review server logs in the CI output

**Android Build Failures:**
- Check Gradle configuration
- Verify dependencies are up-to-date
- Review build logs in the Actions tab

**Migration Validation Failures:**
- Ensure `mysql.sql` includes all tables from migrations
- Run `php bin/validate_migrations.php` locally
- Update `mysql.sql` if migrations have changed:
  ```bash
  php bin/migrate.php
  mysqldump -u root -p --no-data ss_ltd > mysql.sql
  ```

### Manual CI Trigger

You can manually trigger the CI workflow:
1. Go to **Actions** → **CI**
2. Click **Run workflow**
3. Select the branch and click **Run workflow**

## Database Migrations

Migrations are stored in `database/migrations/` and are applied in order by filename.

**Run migrations:**
```bash
php bin/migrate.php
```

**Check migration status:**
```bash
php bin/migrate_status.php
```

**Important:** When adding new migrations, remember to update `mysql.sql`:
```bash
php bin/migrate.php
mysqldump -u root -p --no-data ss_ltd > mysql.sql
```

## Documentation

- [Setup Guide](docs/setup.md) - XAMPP installation and configuration
- [Background Jobs](docs/background_jobs.md) - Scheduled tasks and workers
- [Backups](docs/backups.md) - Database backup procedures
- [Manual Test Checklist](docs/manual_test_checklist.md) - QA procedures

## Security

- Passwords are hashed using `password_hash()` with bcrypt
- Sessions use HttpOnly cookies
- CSRF protection on all state-changing requests
- SQL injection prevention via prepared statements
- File upload validation and sandboxing

**Security Scans:**
The CI pipeline includes automated security checks. Review findings in the Actions logs.

## Contributing

1. Create a feature branch from `develop`
2. Make your changes
3. Ensure all tests pass locally
4. Push and create a Pull Request
5. Wait for CI checks to pass
6. Request review from team members
7. Merge after approval and passing CI

## License

Proprietary - All rights reserved.

## Support

For issues or questions, contact the development team or create an issue in the repository.
