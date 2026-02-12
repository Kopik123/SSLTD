# Setup Guide

This guide covers local development setup for the SSLTD project. For production deployment, see the main [README.md](../README.md) and [DEPLOYMENT_VERCEL.md](../DEPLOYMENT_VERCEL.md).

## Prerequisites

- **PHP**: 8.0+ (8.3+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ with mod_rewrite, or PHP built-in server

## Quick Start

The fastest way to get started is using the quick-start scripts:

### Linux / macOS

```bash
./quickstart.sh
```

### Windows

```cmd
quickstart.bat
```

### Docker (Cross-platform)

```bash
# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php bin/migrate.php

# Seed demo data
docker-compose exec app php bin/seed.php

# Access application
# Web: http://localhost:8000
# phpMyAdmin: http://localhost:8080
```

---

## Manual Setup (XAMPP / Traditional)

This project is intended to run in XAMPP on Windows with MySQL, but works on any PHP environment.

### 1. Create Database

Create an empty database named `ss_ltd`:

**Option A: phpMyAdmin (XAMPP)**
- Open http://localhost/phpmyadmin
- Create new database: `ss_ltd`
- Collation: `utf8mb4_unicode_ci`
- Import `mysql.sql` via Import tab

**Option B: MySQL Command Line**

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p ss_ltd < mysql.sql
```

**Option C: Docker**

Database is auto-created when using `docker-compose up`

### 2. Configure Environment

```bash
# Copy example file
cp .env.example .env

# Edit with your settings
nano .env  # or use your preferred editor
```

Required settings:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ss_ltd
DB_USER=root
DB_PASS=          # Your MySQL password (blank for XAMPP default)
APP_KEY=change-me-to-random-string  # Generate random string for production
```

### 3. Run Migrations

**Linux / macOS:**

```bash
php bin/migrate.php
php bin/migrate_status.php
```

**Windows (XAMPP):**

```bat
cd /d C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe bin\migrate.php
C:\xampp\php\php.exe bin\migrate_status.php
```

**Docker:**

```bash
docker-compose exec app php bin/migrate.php
docker-compose exec app php bin/migrate_status.php
```

**Optional: Use different env file (staging):**

```bash
php bin/migrate.php --env .env.staging
php bin/migrate_status.php --env .env.staging
```

### 4. Seed Demo Data

This creates test accounts for all user roles.

**Linux / macOS:**

```bash
php bin/seed.php
```

**Windows (XAMPP):**

```bat
C:\xampp\php\php.exe bin\seed.php
```

**Docker:**

```bash
docker-compose exec app php bin/seed.php
```

**Test Accounts Created:**

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ss.local | Admin123! |
| Project Manager | pm@ss.local | Pm123456! |
| Client | client@ss.local | Client123! |
| Employee | employee@ss.local | Employee123! |
| Subcontractor | sub@ss.local | Sub123456! |
| Subcontractor Worker | subworker@ss.local | Worker123! |

⚠️ **Security Warning**: Change these passwords before deploying to production!

### 5. Start the Application

**Option A: PHP Built-in Server (Development)**

```bash
# Linux / macOS
php -S 127.0.0.1:8000 index.php

# Windows
C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php

# Access at: http://127.0.0.1:8000
```

**Option B: Apache (XAMPP)**

1. Place project in `C:\xampp\htdocs\ss_ltd\`
2. Ensure `.htaccess` is enabled (mod_rewrite active)
3. Access at: http://localhost/ss_ltd/

**Option C: Docker**

Already running if you used `docker-compose up -d`
- Web: http://localhost:8000
- phpMyAdmin: http://localhost:8080

---

## Health Checks

Verify your setup is working:

### Database Connection

```bash
php bin/health_db.php
```

Expected output:
```
✓ Database connection successful
✓ Migrations table exists
✓ X migrations applied
```

### HTTP Endpoints

If server is running, test these URLs:

- `GET /health` - Basic health check
- `GET /health/db` - Database connectivity check

```bash
# Using curl
curl http://127.0.0.1:8000/health
curl http://127.0.0.1:8000/health/db
```

### Migration Status

```bash
php bin/migrate_status.php
```

Shows list of applied and pending migrations.

---

## Development Tools (Debug Mode Only)

When `APP_DEBUG=1` in your `.env` file, additional development tools are available:

### Floating Dev Overlay

A floating overlay appears in the browser with two tabs:

**Logs Tab:**
- Real-time server logs (tail -f style)
- Color-coded by log level (INFO, WARN, ERROR)
- Auto-scroll to latest entries

**Tools Tab:**
- **Current User**: Shows logged-in user (whoami)
- **Quick Login**: Switch between test accounts instantly
- **Logout**: Quick logout button
- **Clear Rate Limits**: Reset login rate limiting for testing
- **Quick Links**: Jump to important screens
- **Autofill Support**:
  - `/login?autofill=admin|pm|client|employee|sub|subworker` - Pre-fills credentials
  - `/quote-request?mode=simple&autofill=1` - Pre-fills form with test data
  - `/quote-request?mode=advanced&autofill=1` - Pre-fills advanced form

### Dev API Endpoints

Available at `/app/dev/*` (localhost only by default):

- `GET /app/dev/logs` - Server logs (JSON)
- `GET /app/dev/tools/whoami` - Current session user (JSON)
- `GET /app/dev/tools/users` - List all test users (JSON)
- `POST /app/dev/tools/login-as` - Login as another user (CSRF protected)
- `POST /app/dev/tools/logout` - Logout (CSRF protected)
- `POST /app/dev/tools/ratelimit/clear` - Clear rate limits (CSRF protected)

### Security Gates

Dev tools are protected by:

1. **Hard Gate**: Only works when `APP_DEBUG=1`
2. **IP Gate**: Restricted to private/LAN IPs by default
3. **Rate Limiting**: Prevents abuse of dev endpoints
4. **Optional Key**: Set `SS_DEV_TOOLS_KEY` in `.env` to allow non-localhost access

**To disable dev tools completely:**

```bash
# In .env
APP_DEBUG=0
```

---

## Troubleshooting

### "Database connection failed"

**Check:**
1. MySQL/MariaDB service is running
   ```bash
   # Linux
   sudo systemctl status mysql
   
   # Windows (XAMPP)
   # Open XAMPP Control Panel, start MySQL
   
   # Docker
   docker-compose ps
   ```

2. Database exists:
   ```bash
   mysql -u root -p -e "SHOW DATABASES LIKE 'ss_ltd';"
   ```

3. Credentials in `.env` match MySQL user/password

4. Host/port are correct (usually `127.0.0.1:3306`)

### "Migration failed"

**Check:**
1. Database is empty or contains only `migrations` table
2. No conflicting data exists
3. Run `php bin/migrate_status.php` to see migration state
4. Check `storage/logs/*.log` for error details

### "Permission denied" on storage/

**Fix permissions:**

```bash
# Linux / macOS
chmod -R 755 storage/
chmod -R 777 storage/logs storage/uploads storage/tmp

# Or use your web server user
chown -R www-data:www-data storage/
```

### ".htaccess not working" (404 on routes)

**For Apache:**
1. Ensure `mod_rewrite` is enabled
   ```bash
   # Linux
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. Check `AllowOverride All` in Apache config
   ```apache
   <Directory /var/www/html>
       AllowOverride All
   </Directory>
   ```

3. Restart Apache

**For Nginx:**
Use this location block instead of `.htaccess`:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### "Headers already sent" errors

**Common causes:**
1. Whitespace before `<?php` in files
2. Echo/print before header() calls
3. UTF-8 BOM in PHP files

**Fix:**
```bash
# Remove BOM from all PHP files
find . -type f -name "*.php" -exec sed -i '1s/^\xEF\xBB\xBF//' {} \;
```

### "Session errors"

**For file-based sessions (default):**
```bash
# Ensure session directory exists and is writable
mkdir -p storage/sessions
chmod 777 storage/sessions
```

**For Docker:**
Sessions are already configured to use `/tmp/sessions`

---

## Alternative Setups

### Using SQLite (Development Only)

For quick testing without MySQL:

```bash
# In .env
DB_CONNECTION=sqlite
DB_DATABASE=./storage/app.db

# Run migrations (will create SQLite file)
php bin/migrate.php
php bin/seed.php
```

⚠️ **Warning**: SQLite is for development only. Use MySQL for production.

### Using Different Env Files

Run commands with different environment:

```bash
# Staging
php bin/migrate.php --env .env.staging
php bin/seed.php --env .env.staging
SS_ENV_FILE=.env.staging php -S 127.0.0.1:8000 index.php

# Production (local test)
php bin/migrate.php --env .env.production
SS_ENV_FILE=.env.production php -S 127.0.0.1:8000 index.php
```

---

## Next Steps

After successful setup:

1. **Test Login**: Visit http://127.0.0.1:8000 and login with admin account
2. **Explore Features**: Check leads, projects, messages, timesheets
3. **Read Documentation**: See [manual_test_checklist.md](manual_test_checklist.md) for QA procedures
4. **Review Security**: Before production, see security checklist in [README.md](../README.md)
5. **Plan Deployment**: See [DEPLOYMENT_VERCEL.md](../DEPLOYMENT_VERCEL.md) or traditional hosting guides

---

## Getting Help

- **Check logs**: `storage/logs/*.log`
- **Run health checks**: `php bin/health_db.php`
- **Review documentation**: All docs in `docs/` folder
- **Check GitHub Issues**: Report problems or check existing issues

**Common Commands Reference:**

```bash
# Health checks
php bin/health_db.php
php bin/migrate_status.php
php bin/smoke_http.php  # If server is running

# Migrations
php bin/migrate.php
php bin/migrate_status.php

# Data seeding
php bin/seed.php
php bin/create_admin_user.php  # Create single admin

# Linting
php bin/php_lint.php  # Check PHP syntax
```

