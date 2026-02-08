# SSLTD - Architecture Documentation

**Last Updated**: 2026-02-08  
**Version**: 0.1.0

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Component Architecture](#component-architecture)
3. [Directory Structure](#directory-structure)
4. [Request Flow](#request-flow)
5. [Database Schema](#database-schema)
6. [Security Architecture](#security-architecture)
7. [Design Patterns](#design-patterns)
8. [API Architecture](#api-architecture)

---

## System Overview

SSLTD is a dual-platform project management system consisting of:

1. **Web Portal** (PHP 8.0+) - Main application for office/admin users
2. **Android App** (Flutter/Dart) - Mobile field application for workers

### Technology Stack

**Backend (Web)**:
- PHP 8.0+
- MySQL 8.0+
- Custom MVC framework
- SQLite (for testing)

**Frontend (Web)**:
- Server-side rendering (PHP)
- Vanilla JavaScript
- CSS3

**Mobile**:
- Flutter/Dart
- Android SDK

**Infrastructure**:
- Apache/PHP built-in server
- XAMPP (development)
- GitHub Actions (CI/CD)

---

## Component Architecture

### Core Components

```
┌─────────────────────────────────────────────────────┐
│                   HTTP Request                       │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│               index.php (Entry Point)                │
│  - Load autoloader                                   │
│  - Initialize environment (.env)                     │
│  - Setup error handling                              │
│  - Create Context                                    │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│                   Router                             │
│  - Match route                                       │
│  - Apply middleware stack                            │
│  - Dispatch to controller                            │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│              Middleware Stack                        │
│  1. SecurityHeadersMiddleware                        │
│  2. CsrfMiddleware                                   │
│  3. AuthMiddleware                                   │
│  4. RoleMiddleware                                   │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│                Controller                            │
│  - Validate input (Validator)                        │
│  - Business logic                                    │
│  - Interact with models                              │
│  - Return response (Response helper)                 │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│                  Model Layer                         │
│  - Database queries (Db class)                       │
│  - Data transformation                               │
│  - Business rules                                    │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│             Database (MySQL/SQLite)                  │
└─────────────────────────────────────────────────────┘
```

---

## Directory Structure

```
SSLTD/
├── .github/
│   └── workflows/          # CI/CD pipelines
│       └── ci.yml          # GitHub Actions workflow
├── android/                # Flutter mobile application
│   ├── app/
│   └── lib/
├── bin/                    # CLI tools and scripts
│   ├── migrate.php         # Database migrations
│   ├── seed.php            # Database seeding
│   ├── php_lint.php        # PHP linter
│   ├── rotate_logs.php     # Log rotation
│   ├── qa_*.php            # QA helper scripts
│   ├── release_helper.php  # Release automation
│   └── validate_production.php
├── database/
│   └── migrations/         # SQL migration files
├── docs/                   # Documentation
│   ├── setup.md
│   ├── v0.1_scope_freeze.md
│   ├── project_analysis.md
│   ├── architecture.md     # This file
│   └── manual_test_checklist.md
├── src/                    # Application source code
│   ├── Controllers/        # HTTP controllers
│   │   ├── App/           # Application controllers
│   │   ├── Api/           # API controllers
│   │   └── Auth/          # Authentication controllers
│   ├── Models/            # Data models
│   ├── Middleware/        # HTTP middleware
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   └── SecurityHeadersMiddleware.php
│   ├── Database/          # Database layer
│   │   └── Db.php         # PDO wrapper
│   ├── Http/              # HTTP utilities
│   │   ├── Router.php
│   │   └── Request.php
│   ├── Support/           # Helper classes
│   │   ├── Log.php
│   │   ├── Response.php   # Response helpers
│   │   ├── Validator.php  # Input validation
│   │   └── Session.php
│   ├── ErrorHandler.php   # Error handling (CLI)
│   ├── Context.php        # Application context
│   └── autoload.php       # PSR-4 autoloader
├── storage/
│   ├── logs/              # Application logs
│   └── uploads/           # User uploads
├── tests/                 # PHPUnit tests
│   ├── Unit/
│   │   ├── Database/
│   │   └── Middleware/
│   ├── Integration/
│   └── bootstrap.php
├── index.php              # Entry point
├── composer.json          # PHP dependencies
├── phpunit.xml            # PHPUnit configuration
├── README.md
├── CONTRIBUTING.md
├── CHANGELOG.md
└── .env                   # Environment configuration
```

---

## Request Flow

### Web Application Request Flow

```
1. Browser Request
   ↓
2. Apache/.htaccess → index.php
   ↓
3. Load .env configuration
   ↓
4. Autoload classes (PSR-4)
   ↓
5. Create Context (db, user, config)
   ↓
6. Router::dispatch(Request)
   ↓
7. Apply Middleware Stack:
   - SecurityHeadersMiddleware → Add security headers
   - CsrfMiddleware → Validate CSRF token (POST/PUT/DELETE)
   - AuthMiddleware → Check authentication
   - RoleMiddleware → Check authorization
   ↓
8. Controller Action
   - Validate input (Validator)
   - Execute business logic
   - Query database (Db/Models)
   - Generate response (Response)
   ↓
9. Send HTTP Response
```

### API Request Flow

```
1. API Request (JSON)
   ↓
2. index.php → Router
   ↓
3. API Middleware:
   - SecurityHeadersMiddleware
   - (No CSRF for API endpoints)
   - AuthMiddleware (JWT/Session)
   ↓
4. API Controller
   - Validate JSON (Validator)
   - Process request
   - Return JSON (Response::json())
   ↓
5. JSON Response
```

---

## Database Schema

### Core Tables

**users**
- User authentication and profiles
- Roles: admin, manager, project_lead, worker

**projects**
- Project management
- Status tracking
- Budget management

**threads**
- Communication/discussion threads
- Attachments support

**timesheets**
- Time tracking
- Worker hours
- Approval workflow

**reports**
- Custom reports
- Export functionality

### Relationships

```
users (1) ──────→ (N) projects (as project_lead)
users (1) ──────→ (N) threads (as creator)
users (1) ──────→ (N) timesheets
projects (1) ────→ (N) threads
projects (1) ────→ (N) timesheets
```

---

## Security Architecture

### Authentication

- **Session-based** authentication for web
- **Password hashing**: `password_hash()` with bcrypt
- **Session regeneration**: On login to prevent fixation
- **Remember me**: Secure token-based

### Authorization

- **Role-based** access control (RBAC)
- **Middleware**: RoleMiddleware checks permissions
- **ACL**: Project-level access control

### Input Validation

- **CSRF Protection**: Token validation on state-changing requests
- **Input Sanitization**: Validator framework
- **SQL Injection Prevention**: Prepared statements only
- **XSS Prevention**: Output escaping

### Security Headers

- **X-Frame-Options**: Clickjacking protection
- **X-Content-Type-Options**: MIME sniffing protection
- **CSP**: Content Security Policy
- **HSTS**: Strict Transport Security (production)
- **Permissions-Policy**: Feature restrictions

### File Uploads

- **Validation**: Type, size, extension checks
- **Storage**: Outside web root (`storage/uploads/`)
- **Access Control**: Routed through PHP (no direct access)
- **Virus Scanning**: Recommended for production

---

## Design Patterns

### MVC (Model-View-Controller)

- **Models**: Database interaction, business logic
- **Views**: HTML rendering (PHP templates)
- **Controllers**: Request handling, orchestration

### Front Controller

- Single entry point (`index.php`)
- Centralized routing
- Consistent middleware application

### Repository Pattern (Partial)

- Models act as repositories
- Encapsulate database queries
- Data transformation

### Dependency Injection (Planned)

- Context object provides dependencies
- Future: DI container for better testability

### Factory Pattern

- Database connection factory
- Router factory

### Middleware Pattern

- Chainable request processors
- Cross-cutting concerns (auth, security, logging)

---

## API Architecture

### REST API Structure

```
/api/auth/*          - Authentication endpoints
/api/projects/*      - Project management
/api/threads/*       - Communication
/api/timesheets/*    - Time tracking
/api/uploads         - File uploads
```

### API Response Format

**Success Response**:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["validation error"]
  }
}
```

### API Versioning

- Current: v1 (implicit)
- Future: `/api/v2/` for breaking changes

### Authentication

- **Web**: Session-based
- **Mobile**: Token-based (JWT recommended)

---

## Performance Considerations

### Current Optimizations

- **Prepared Statements**: Query caching
- **Session Management**: Efficient storage
- **Autoloading**: PSR-4 on-demand loading

### Future Optimizations

- **Query Caching**: Redis/Memcached
- **Database Indexes**: Optimize slow queries
- **CDN**: Static asset delivery
- **Database Connection Pooling**
- **OpCache**: PHP bytecode caching

---

## Testing Strategy

### Unit Tests

- **PHPUnit 9.5**
- **Coverage Target**: 80%+
- **CI Integration**: GitHub Actions

### Test Suites

- `tests/Unit/` - Unit tests for individual classes
- `tests/Integration/` - Integration tests

### Testing Approach

- **TDD**: For new features
- **Mocking**: Database, external services
- **Fixtures**: Test data seeding

---

## Deployment Architecture

### Development

- **XAMPP**: Local development environment
- **SQLite**: Testing database
- **PHP Built-in Server**: Quick testing

### Production

- **Apache**: Web server
- **MySQL 8.0**: Production database
- **HTTPS**: Required
- **Backup Strategy**: Automated daily backups
- **Monitoring**: Error logging, performance tracking

---

## Maintenance and Operations

### Logging

- **Application Logs**: `storage/logs/app.log`
- **Daily Logs**: `storage/logs/app-YYYY-MM-DD.log`
- **Format**: JSON structured logging
- **Rotation**: Automated via `bin/rotate_logs.php`

### Database Migrations

- **Migration Files**: `database/migrations/`
- **Execution**: `php bin/migrate.php`
- **Versioning**: Timestamp-based ordering

### Backup and Recovery

- **Database**: `bin/backup.sh` (planned)
- **Files**: `storage/uploads/` backup
- **Frequency**: Daily automated
- **Retention**: 30 days

---

## Future Enhancements

### Planned Features

1. **Email System**: SMTP integration for notifications
2. **Push Notifications**: Mobile app notifications
3. **Search**: Full-text search with indexing
4. **Analytics**: Usage dashboard
5. **i18n**: Internationalization support
6. **Mobile Web**: Responsive mobile version

### Architectural Improvements

1. **Dependency Injection Container**: Better testability
2. **Query Builder**: Fluent interface for SQL
3. **Event System**: Decoupled event handling
4. **Cache Layer**: Redis/Memcached integration
5. **API Gateway**: Rate limiting, throttling

---

## References

- [README.md](../README.md) - Project overview
- [CONTRIBUTING.md](../CONTRIBUTING.md) - Development guidelines
- [docs/setup.md](setup.md) - Setup instructions
- [docs/project_analysis.md](project_analysis.md) - Technical analysis
- [manual_todos.md](../manual_todos.md) - Manual tasks

---

**Maintained by**: Development Team  
**Last Review**: 2026-02-08
