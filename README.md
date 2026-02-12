# S&S LTD - Web Portal & Field Management System

A comprehensive web portal and Android field application for construction project management, designed for luxury residential contracting in the Manchester (MCR) area.

## 🚀 Features

### Web Portal (Office Management)
- **Public Website**: Marketing pages, service catalog, quote request system
- **Lead Management**: Track inquiries, convert to projects, quote generation
- **Project Management**: Status tracking, team assignments, timeline management
- **Messaging System**: Threaded conversations for leads and projects
- **File Management**: Secure document storage and sharing
- **Time Tracking**: Timesheet management and reporting
- **User Management**: Role-based access control (Admin, PM, Client, Employee, Subcontractor)

### Android Field App
- **Today View**: Quick time tracking (start/stop)
- **Project Access**: View assigned projects and details
- **Photo Capture**: Site photos with offline upload queue
- **Messaging**: Real-time communication with office
- **Offline Support**: Work without connectivity, sync when online

## 📋 Requirements

### Local Development
- **PHP**: 8.0 or higher (8.3+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or PHP built-in server
- **Extensions**: PDO, PDO_MySQL (or PDO_SQLite for dev)

### Optional
- **Docker**: For containerized development (recommended)
- **XAMPP**: For Windows development (tested configuration)

## 🛠️ Quick Start

### Option 1: Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/Kopik123/SSLTD.git
cd SSLTD

# Copy environment file
cp .env.example .env

# Start with Docker Compose
docker-compose up -d

# Run migrations
docker-compose exec app php bin/migrate.php

# Seed demo data
docker-compose exec app php bin/seed.php

# Open in browser
open http://localhost:8000
```

### Option 2: Local PHP

```bash
# Clone the repository
git clone https://github.com/Kopik123/SSLTD.git
cd SSLTD

# Copy environment file
cp .env.example .env

# Edit .env with your database credentials
nano .env

# Create database
mysql -u root -p -e "CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p ss_ltd < mysql.sql

# Run migrations
php bin/migrate.php

# Seed demo data
php bin/seed.php

# Start PHP built-in server
php -S 127.0.0.1:8000 index.php

# Open in browser
open http://127.0.0.1:8000
```

### Option 3: XAMPP (Windows)

See detailed instructions in [docs/setup.md](docs/setup.md)

## 🔐 Default Accounts

After running `bin/seed.php`, these test accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ss.local | Admin123! |
| Project Manager | pm@ss.local | Pm123456! |
| Client | client@ss.local | Client123! |
| Employee | employee@ss.local | Employee123! |
| Subcontractor | sub@ss.local | Sub123456! |
| Subcontractor Worker | subworker@ss.local | Worker123! |

⚠️ **Change these passwords in production!**

## 📚 Documentation

- [Setup Guide](docs/setup.md) - Detailed setup instructions
- [Manual Testing Checklist](docs/manual_test_checklist.md) - QA procedures
- [Background Jobs](docs/background_jobs.md) - Async task handling
- [Backups Strategy](docs/backups.md) - Data protection
- [Conflict Resolution](docs/conflict_strategy.md) - Team workflows

## 🚢 Deployment

### Traditional Hosting (VPS, Shared Hosting)

1. **Upload Files**: Upload all files except `.git/`, `.env*`, `android/`
2. **Configure Environment**: Create `.env` from `.env.production.example`
3. **Set Up Database**: Import `mysql.sql`, run migrations
4. **Configure Web Server**: Point document root to project root, ensure `.htaccess` works
5. **Set Permissions**: `storage/` and subdirectories should be writable
6. **Security**: Ensure `.env`, `storage/`, `src/`, `bin/`, `database/` are not web-accessible

### Vercel / Serverless

⚠️ **Note**: This project is designed for traditional PHP hosting. Vercel deployment requires significant modifications:

- Use Vercel PHP Runtime (experimental)
- External database (PlanetScale, AWS RDS, etc.)
- Cloud storage for uploads (S3, Cloudinary)
- Serverless-compatible session handling

See [DEPLOYMENT_VERCEL.md](DEPLOYMENT_VERCEL.md) for detailed Vercel setup (coming soon).

### Recommended Hosting Providers

For easiest deployment:
- **DigitalOcean App Platform** (PHP support)
- **Heroku** (with PHP buildpack)
- **AWS Lightsail** (LAMP stack)
- **Traditional VPS** (Ubuntu + Apache/Nginx + MySQL)

## 🔧 Configuration

### Environment Variables

Key environment variables (see `.env.example` for full list):

```bash
APP_ENV=production          # Environment: dev, staging, production
APP_DEBUG=0                 # Debug mode: 0 = off, 1 = on
APP_URL=https://your-domain.com
APP_KEY=random-secret-key   # Generate random string (32+ chars)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ss_ltd
DB_USER=your_db_user
DB_PASS=your_db_password

SERVICE_AREA_RADIUS_MILES=60
```

### Security Checklist

Before going live:

- [ ] Change `APP_KEY` to random string
- [ ] Set `APP_DEBUG=0`
- [ ] Set `APP_ENV=production`
- [ ] Change all default passwords
- [ ] Configure SSL/TLS (HTTPS)
- [ ] Restrict database access
- [ ] Enable firewall
- [ ] Set up backups
- [ ] Configure file upload limits
- [ ] Review `.htaccess` security rules

## 🧪 Development

### Dev Tools (Debug Mode)

When `APP_DEBUG=1`, a floating overlay provides:
- **Logs Tab**: Real-time server logs
- **Tools Tab**: Quick login switcher, rate limit reset, test data autofill

Access dev endpoints at `/app/dev/*` (localhost only by default)

### Running Tests

```bash
# PHP syntax check
php bin/php_lint.php

# Database health check
php bin/health_db.php

# Migration status
php bin/migrate_status.php

# HTTP smoke test
php bin/smoke_http.php
```

### Project Structure

```
SSLTD/
├── android/              # Android field app source
├── assets/               # Public assets (CSS, JS, images)
├── bin/                  # CLI tools (migrations, seeds, health checks)
├── database/
│   └── migrations/       # Database migration files
├── docs/                 # Documentation
├── plans/                # Project planning documents
├── src/
│   ├── Controllers/      # HTTP request handlers
│   ├── Database/         # Database layer
│   ├── Http/             # HTTP abstractions (Request, Response, Router)
│   ├── Middleware/       # HTTP middleware
│   ├── Support/          # Utilities (Config, Env, Log)
│   ├── Views/            # HTML templates
│   ├── autoload.php      # PSR-4 autoloader
│   ├── helpers.php       # Helper functions
│   └── routes.php        # Route definitions
├── storage/
│   ├── logs/             # Application logs
│   ├── uploads/          # User uploaded files (not web-accessible)
│   └── tmp/              # Temporary files
├── .htaccess             # Apache configuration
├── index.php             # Application entry point
└── mysql.sql             # Full database schema
```

## 🤝 Contributing

This is a private project. For internal team members:

1. Create feature branch from `main`
2. Make changes with descriptive commits
3. Test thoroughly (see `docs/manual_test_checklist.md`)
4. Submit pull request
5. Wait for code review

## 📄 License

Proprietary - S&S LTD. All rights reserved.

## 🆘 Support

For issues or questions:
- Check documentation in `docs/`
- Review `full_todos.md` for known issues
- Contact project maintainer

---

**Project Status**: Active Development (MVP Phase)  
**Last Updated**: February 2026
