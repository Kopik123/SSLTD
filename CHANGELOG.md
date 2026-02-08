# Changelog

All notable changes to the S&S LTD project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- GitHub Actions CI/CD pipeline with lint, test, and security jobs
- PHPUnit testing framework with 26 unit tests
- Centralized ErrorHandler class for CLI tools
- Production validation script (`bin/validate_production.php`)
- Comprehensive README.md with quick start guide
- CONTRIBUTING.md with development guidelines
- Project analysis document (`docs/project_analysis.md`)
- Actionable roadmap (`todos_list2.md`) with 100+ prioritized tasks
- QA helper scripts:
  - `bin/qa_ops_checklist.php` - operational health checks
  - `bin/qa_prerelease.php` - guided manual QA walkthrough
- Release helper script (`bin/release_helper.php`)
- Log rotation script (`bin/rotate_logs.php`)
- Unit tests for Database, CSRF Middleware, and ErrorHandler
- CI status badge in README

### Changed
- Updated all QA and release documentation with script references
- Marked scope freeze (v0.1) as complete in full_todos.md

### Fixed
- 404 routing issue caused by ErrorHandler conflicting with existing exception handler
- PDO parameter mismatch in SubcontractorsController causing 500 error
- Code quality issues: removed trailing whitespaces, added curly braces

### Security
- SQL injection prevention validated via prepared statement tests
- CSRF protection validated via middleware tests
- Production environment validation script

## [0.1.0] - 2024-XX-XX (Planned)

### Added
- User authentication system (login, registration, password reset)
- Role-based access control (Admin, ProjectLead, Subcontractor)
- Project management (CRUD operations)
- Lead management with status tracking
- Message threading system
- File upload functionality with security validations
- Timesheet management
- Android mobile application
- RESTful API endpoints
- CSRF protection middleware
- Session management
- Rate limiting on authentication endpoints
- Health check endpoints (`/health`, `/health/db`)

### Database
- 12 database migrations
- User, Projects, Leads, Messages, Uploads, Timesheets tables
- Proper foreign key relationships
- Status and role enumerations

### Documentation
- Setup guide (`docs/setup.md`)
- Manual test checklist (`docs/manual_test_checklist.md`)
- v0.1 scope freeze document (`docs/v0.1_scope_freeze.md`)
- Release plan (`full_todos.md`)
- Project structure documentation
- Android app ProGuard configuration

## [0.0.1] - Initial Development

### Added
- Basic project structure
- PHP autoloader
- Database abstraction layer
- Routing system
- MVC architecture
- Configuration management via .env
- Storage directories (logs, uploads)
- CLI tools for migrations and seeding
- Support for both MySQL and SQLite

---

## Release Notes

### Version 0.1.0 (Upcoming)

**Status**: MVP Release Candidate

**Highlights**:
- First production-ready release
- Complete authentication and authorization system
- Full CRUD for projects, leads, and timesheets
- Mobile app for subcontractors
- Comprehensive test coverage (26+ unit tests)
- CI/CD pipeline for quality assurance
- Production validation tooling

**Known Limitations**:
- Email functionality not yet implemented
- Push notifications pending
- Analytics dashboard pending
- Search functionality basic (SQL LIKE only)
- No internationalization (English only)

**Deployment Checklist**:
1. Run `php bin/validate_production.php --strict`
2. Run `php bin/qa_prerelease.php` for manual QA
3. Run `php bin/release_helper.php prepare`
4. Export database: `php bin/release_helper.php export`
5. Deploy to production server
6. Run migrations on production
7. Monitor logs for first 24 hours

**Upgrade Path**: N/A (first release)

---

## Versioning Strategy

- **Major version (X.0.0)**: Breaking changes, major feature additions
- **Minor version (0.X.0)**: New features, backwards-compatible
- **Patch version (0.0.X)**: Bug fixes, security patches

## Support

- **v0.1.x**: Supported until v0.3.0 release
- Security fixes backported for 6 months after next major release

## Links

- [Project Repository](https://github.com/Kopik123/SSLTD)
- [Issue Tracker](https://github.com/Kopik123/SSLTD/issues)
- [Documentation](docs/)
- [Contributing Guide](CONTRIBUTING.md)
