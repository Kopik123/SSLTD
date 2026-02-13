# S&S LTD - Web Portal & Field Application

Professional web portal and Android field application for managing construction and renovation projects.

## Overview

This is a comprehensive project management system designed for construction and renovation companies, featuring:

- **Web Portal**: Full-featured office management interface for project planning, quotes, scheduling, client management, and reporting
- **Android App**: Field application for on-site reporting, time tracking, photo uploads, and offline functionality
- **Unified Backend**: Single PHP-based backend serving both web and mobile interfaces

## Key Features

### For Office Staff (Web Portal)
- Lead and quote management (CRM)
- Project planning and scheduling
- Budget and cost tracking
- Material and tool management
- Document and file management
- Client communication
- Reporting and analytics
- Team management

### For Field Workers (Android App)
- Quick progress reports
- Photo capture and upload
- Time tracking (clock in/out)
- On-site checklists
- Issue reporting
- Offline mode with auto-sync
- Digital signatures for sign-offs

## Technology Stack

- **Backend**: PHP 8.1+ (custom MVC framework)
- **Database**: MySQL 8.0+ / MariaDB 10.6+
- **Frontend**: HTML, CSS, JavaScript (vanilla)
- **Mobile**: Android (Java/Kotlin)
- **Web Server**: Apache 2.4 with mod_rewrite

## Quick Start (Local Development)

### Requirements
- Windows with XAMPP (or Linux with LAMP)
- PHP 8.1 or higher
- MySQL 8.0 or MariaDB 10.6+
- Apache with mod_rewrite

### Setup Steps

1. **Clone or extract** the project to your web directory:
   ```bash
   # XAMPP on Windows
   C:\xampp\htdocs\ss_ltd\
   
   # Or use the junction to avoid path issues with "&"
   C:\xampp\htdocs\ss_ltd\
   ```

2. **Create environment file**:
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` and configure your database settings.

3. **Create database**:
   ```bash
   # Using XAMPP MySQL
   C:\xampp\mysql\bin\mysql.exe -u root
   CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

4. **Run migrations**:
   ```bash
   C:\xampp\php\php.exe bin\migrate.php
   ```

5. **Seed demo data** (optional, includes default admin account):
   ```bash
   C:\xampp\php\php.exe bin\seed.php
   ```

6. **Start server**:
   
   **Option A - Apache (XAMPP):**
   - Start Apache in XAMPP Control Panel
   - Visit: `http://localhost/ss_ltd/`
   
   **Option B - PHP Built-in Server:**
   ```bash
   C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php
   ```
   Visit: `http://127.0.0.1:8000/`

7. **Login** with default credentials:
   - Email: `admin@ss.local`
   - Password: `Admin123!`

For detailed setup instructions, see [`docs/setup.md`](docs/setup.md).

## Production Deployment

### DigitalOcean Deployment

This application is ready for deployment to DigitalOcean or any Linux server with LAMP stack.

#### Quick Deployment Steps

1. **Prepare deployment package** (on local machine):
   ```bash
   bash bin/deploy-prepare.sh
   ```
   This creates a deployment archive ready for transfer.

2. **Transfer to server** using Termius or SFTP:
   - Upload the generated `.tar.gz` file to your server
   - Extract in `/var/www/html/`

3. **Follow deployment guide**:
   - See [`docs/deployment.md`](docs/deployment.md) for complete step-by-step instructions
   - Use [`docs/deployment-checklist.md`](docs/deployment-checklist.md) to track progress

#### Deployment Resources

- **[Full Deployment Guide](docs/deployment.md)** - Complete instructions for DigitalOcean deployment
- **[Deployment Checklist](docs/deployment-checklist.md)** - Step-by-step checklist
- **[Setup Guide](docs/setup.md)** - Local development setup
- **[Backup Guide](docs/backups.md)** - Backup and restore procedures

### Server Requirements

- Ubuntu 22.04 LTS (or similar)
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.6+
- Apache 2.4 with mod_rewrite
- SSL certificate (Let's Encrypt recommended)
- Minimum 2GB RAM

### Security Features

- ✅ Password hashing with `password_hash()`
- ✅ CSRF protection on all forms
- ✅ Prepared SQL statements (PDO)
- ✅ Session security (HttpOnly cookies)
- ✅ Upload validation and secure storage
- ✅ Role-based access control (RBAC)
- ✅ Project-level ACL
- ✅ Rate limiting (planned)

## Project Structure

```
ss_ltd/
├── index.php              # Application entry point
├── .htaccess             # Apache configuration
├── .env.example          # Environment config template
├── src/                  # Application source code
│   ├── Http/            # HTTP layer (Router, Request, Response)
│   ├── Database/        # Database layer
│   ├── Support/         # Utilities and helpers
│   └── ...
├── bin/                  # CLI scripts
│   ├── migrate.php      # Run database migrations
│   ├── seed.php         # Seed demo data
│   ├── backup.sh        # Backup script
│   └── deploy-prepare.sh # Deployment preparation
├── database/            # Database migrations
├── storage/             # Application storage
│   ├── logs/           # Log files
│   ├── uploads/        # User uploads (not web-accessible)
│   └── tmp/            # Temporary files
├── assets/             # Public assets (CSS, JS, images)
├── docs/               # Documentation
└── android/            # Android application source
```

## User Roles

- **Admin** - Full system access, configuration, user management
- **Project Manager** - Project oversight, scheduling, approvals, reporting
- **Employee** - Task execution, time tracking, reporting
- **Subcontractor** - Assigned projects, worker management, reporting
- **Subcontractor Worker** - Like Employee, within subcontractor scope
- **Client** - View projects, approvals, communications, change requests

## Development

### Running Tests

```bash
# Database health check
C:\xampp\php\php.exe bin\health_db.php

# PHP syntax check
C:\xampp\php\php.exe bin\php_lint.php
```

### Debug Mode

Set `APP_DEBUG=1` in `.env` to enable:
- Detailed error messages
- Debug toolbar with logs
- Dev tools overlay (login-as, quick links)
- Request logging

**⚠️ Never enable in production!**

## Documentation

- **[Setup Guide](docs/setup.md)** - Local development setup
- **[Deployment Guide](docs/deployment.md)** - Production deployment
- **[Deployment Checklist](docs/deployment-checklist.md)** - Deployment steps
- **[Backup Guide](docs/backups.md)** - Backup and restore
- **[Manual Test Checklist](docs/manual_test_checklist.md)** - QA testing
- **[Background Jobs](docs/background_jobs.md)** - Background processing
- **[Conflict Strategy](docs/conflict_strategy.md)** - Data sync approach

## Support & Maintenance

### Backups

Use the included backup scripts:

```bash
# Create backup
bash bin/backup.sh

# Restore from backup
bash bin/restore.sh 20260213_140530
```

For automated daily backups, add to crontab:
```bash
0 2 * * * /var/www/html/ss_ltd/bin/backup.sh
```

### Logs

Application logs are stored in `storage/logs/`:
- `app.log` - Application events
- `error.log` - PHP errors (when logging enabled)

Apache logs (production):
- `/var/log/apache2/ss_ltd_error.log` - Apache errors
- `/var/log/apache2/ss_ltd_access.log` - Access logs

### Health Checks

- `GET /health` - Basic health check
- `GET /health/db` - Database connectivity check
- `php bin/health_db.php` - CLI database check

## License

Proprietary - All rights reserved.

## Contact

For support or questions about deployment, refer to the documentation in the `docs/` directory.

---

**Last Updated**: February 2026
**Version**: 0.1.0 (MVP)
