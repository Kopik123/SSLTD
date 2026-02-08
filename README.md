# SSLTD - Construction Management System

A comprehensive construction project management system with web portal and Android field app.

[![PHP Lint](https://img.shields.io/badge/PHP-Lint%20OK-success)]()
[![Version](https://img.shields.io/badge/version-0.1.0-blue)]()
[![License](https://img.shields.io/badge/license-Proprietary-red)]()

## 📋 Overview

SSLTD is a greenfield MVP for S&S LTD, providing end-to-end project management for construction services. The system consists of:

- **Web Portal**: Office-first interface for leads, quotes, projects, and administration
- **REST API**: Mobile-first endpoints for field operations
- **Android App**: Offline-capable field app for timesheets, photo capture, and messaging
- **Database**: MySQL (production) / SQLite (development) with migrations

## 🚀 Quick Start

### Prerequisites

- PHP 7.4+ with extensions: PDO, mbstring, openssl
- MySQL 5.7+ (or SQLite for development)
- Composer (optional, for dependencies)
- Apache or PHP built-in server

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/Kopik123/SSLTD.git
cd SSLTD
```

2. **Configure environment**
```bash
cp .env.example .env
# Edit .env and set database credentials
```

3. **Initialize database**
```bash
# Option A (recommended): Run migrations
php bin/migrate.php
php bin/seed.php

# Option B: Import SQL dump
mysql -u root ss_ltd < mysql.sql
php bin/seed.php
```

4. **Start development server**
```bash
php -S 127.0.0.1:8000 index.php
```

5. **Access the application**
- Web: http://127.0.0.1:8000
- Default admin: `admin@ss.local` / `Admin123!`

### XAMPP (Windows)

```batch
# Use junction to avoid path issues with '&'
cd C:\xampp\htdocs
mklink /J ss_ltd "C:\path\to\SSLTD"
cd ss_ltd

# Initialize
C:\xampp\php\php.exe bin\migrate.php
C:\xampp\php\php.exe bin\seed.php

# Access via Apache
http://localhost/ss_ltd/
```

## 📚 Documentation

- [Setup Guide](docs/setup.md) - Detailed installation and configuration
- [Architecture](docs/architecture.md) - System design and components (coming soon)
- [Manual Test Checklist](docs/manual_test_checklist.md) - QA procedures
- [v0.1 Scope](docs/v0.1_scope_freeze.md) - Release scope and acceptance criteria
- [Project Analysis](docs/project_analysis.md) - Code quality and improvement roadmap

## 🏗️ Architecture

### System Components

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Browser   │────▶│  Web Portal  │────▶│   MySQL     │
└─────────────┘     │  (PHP + JS)  │     │  Database   │
                    └──────────────┘     └─────────────┘
                           │
                           │ REST API
                           ▼
                    ┌──────────────┐
                    │  Android App │
                    │   (Kotlin)   │
                    └──────────────┘
```

### Key Features

**Web Portal:**
- Public website with quote request forms
- Lead management and PM assignment
- Project tracking with status workflows
- Threaded messaging system
- File uploads with ACL and client visibility
- User management and audit logging
- Admin dashboard

**REST API:**
- JWT authentication with refresh tokens
- Projects, threads, messages endpoints
- File upload with multipart support
- Timesheet start/stop tracking
- Rate limiting and request validation

**Android App:**
- Offline-first architecture with Room DB
- Photo/file capture with metadata
- WorkManager background sync
- Today view with running timer
- Messages with attachments
- Encrypted credential storage

## 🔐 Security

- **Authentication**: Password hashing with `password_hash()`, HttpOnly session cookies
- **CSRF Protection**: Token validation on all state-changing requests
- **SQL Injection**: Prepared statements only (PDO)
- **File Upload**: Type/size validation, safe storage outside web root
- **Authorization**: RBAC + per-project ACL enforcement
- **Rate Limiting**: IP-based limits on auth, uploads, and messages
- **CSP**: Content Security Policy enabled

## 🛠️ Development

### Running Tests

```bash
# PHP Linter
php bin/php_lint.php

# Database Health Check
php bin/health_db.php

# Migration Status
php bin/migrate_status.php
```

### QA Scripts

```bash
# Automated ops health checks
php bin/qa_ops_checklist.php

# Guided manual QA walkthrough
php bin/qa_prerelease.php

# Release preparation
php bin/release_helper.php prepare
```

### Code Standards

- PSR-12 coding style
- No inline JavaScript/CSS (CSP compliant)
- Prepared statements for all SQL
- CSRF tokens for all forms
- HttpOnly cookies for sessions

## 📱 Android App

Located in `android/` directory. Built with Kotlin and Jetpack components.

```bash
cd android
./gradlew assembleDebug
```

See [android/README.md](android/README.md) for details.

## 🗂️ Project Structure

```
SSLTD/
├── android/           # Android field app
├── assets/            # Static files (CSS, JS, images)
├── bin/               # CLI scripts and tools
├── database/          # Migrations
├── docs/              # Documentation
├── plans/             # Project planning documents (Lua)
├── src/               # PHP source code
│   ├── Controllers/   # HTTP controllers
│   ├── Http/          # HTTP utilities (Session, Request)
│   ├── Middleware/    # Middleware (Auth, CSRF, RateLimit)
│   ├── Models/        # Data models
│   └── Views/         # HTML templates
├── storage/           # Uploads, logs, cache
│   ├── uploads/       # User uploads (not web-accessible)
│   └── ratelimit/     # Rate limit state
├── index.php          # Single entry point
└── mysql.sql          # Database schema dump
```

## 🔄 Release Process

See `full_todos.md` for the complete release plan. Current status:

- ✅ v0.1 MVP Implementation (items 2-21): **COMPLETE**
- ✅ Scope freeze: **DONE**
- ⏳ Manual QA: **PENDING** (use `php bin/qa_prerelease.php`)
- ⏳ Production deployment: **PENDING**

## 🤝 Contributing

This is a private project. For team members:

1. Create feature branch from `main`
2. Make changes following code standards
3. Test thoroughly (linter + manual)
4. Update `changelogs.lua`
5. Submit PR for review

## 📝 License

Proprietary - All rights reserved by S&S LTD

## 👥 Authors

- Development Team - v0.1 MVP implementation
- GitHub Copilot - Automation and tooling

## 🆘 Support

For issues or questions:
- Check documentation in `docs/`
- Review `full_todos.md` for known limitations
- Contact project maintainers

## 🎯 Roadmap

**v0.1 (Current)** - Operational MVP
- ✅ Core features complete
- ⏳ QA in progress

**v0.2 (Planned)** - Enhanced Features
- Quote checklist approvals
- Project checklist (API + Android)
- Email notifications
- Push notifications

**v0.3+** - Advanced Features
- Schedule proposals
- Inventory management
- Reports and issues module
- Change request workflow

---

**Built with ❤️ for construction excellence**
