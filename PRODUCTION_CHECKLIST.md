# Production Deployment Checklist

This checklist ensures your SSLTD deployment is secure and ready for production use.

## Pre-Deployment

### Environment Configuration

- [ ] **Copy `.env.production.example` to `.env`** on production server
- [ ] **Set `APP_ENV=production`** in `.env`
- [ ] **Set `APP_DEBUG=0`** in `.env` (CRITICAL - disables dev tools)
- [ ] **Generate secure `APP_KEY`**
  ```bash
  # Generate random 32-character key
  openssl rand -base64 32
  # Or use PHP
  php -r "echo bin2hex(random_bytes(32));"
  ```
- [ ] **Set production `APP_URL`** (your actual domain with https://)
- [ ] **Configure production database** credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- [ ] **Remove or empty `SS_DEV_TOOLS_KEY`** (disable dev tools access)
- [ ] **Set `SERVICE_AREA_RADIUS_MILES`** to actual service area

### Database Setup

- [ ] **Create production database**
  ```sql
  CREATE DATABASE ss_ltd_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```
- [ ] **Create dedicated database user** (don't use root)
  ```sql
  CREATE USER 'ssltd_prod'@'%' IDENTIFIED BY 'secure-random-password';
  GRANT ALL PRIVILEGES ON ss_ltd_prod.* TO 'ssltd_prod'@'%';
  FLUSH PRIVILEGES;
  ```
- [ ] **Import schema**: `mysql -u ssltd_prod -p ss_ltd_prod < mysql.sql`
- [ ] **Run migrations**: `php bin/migrate.php`
- [ ] **Verify migrations**: `php bin/migrate_status.php`
- [ ] **DO NOT run** `bin/seed.php` in production (contains test passwords!)

### Security Hardening

#### Web Server Configuration

- [ ] **Ensure `.htaccess` is working** (for Apache)
- [ ] **Block access to sensitive files**:
  - `.env` and `.env.*`
  - `storage/`, `database/`, `src/`, `bin/`, `plans/`
  - `mysql.sql`, `*.md` files
  - Already configured in `.htaccess`, verify it's active
- [ ] **Configure SSL/TLS certificate** (HTTPS only)
  - Use Let's Encrypt, Cloudflare, or your hosting provider
- [ ] **Force HTTPS** (redirect HTTP to HTTPS)
  ```apache
  # In .htaccess, add before other rules:
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
  ```
- [ ] **Set secure headers** (already in `.htaccess`, verify):
  - X-Content-Type-Options
  - X-Frame-Options
  - X-XSS-Protection

#### File Permissions

- [ ] **Set proper ownership**:
  ```bash
  # Owned by web server user (e.g., www-data, apache, nginx)
  chown -R www-data:www-data /path/to/ssltd
  ```
- [ ] **Set secure permissions**:
  ```bash
  # Directories: 755
  find /path/to/ssltd -type d -exec chmod 755 {} \;
  
  # Files: 644
  find /path/to/ssltd -type f -exec chmod 644 {} \;
  
  # Storage writable: 777 (or better: owned by web server)
  chmod -R 777 storage/logs storage/uploads storage/tmp
  ```
- [ ] **Protect .env**:
  ```bash
  chmod 600 .env
  chown www-data:www-data .env
  ```

#### Database Security

- [ ] **Use strong database password** (20+ characters, mixed case, numbers, symbols)
- [ ] **Restrict database user privileges** (only to production database)
- [ ] **Enable database SSL/TLS** if available
- [ ] **Limit database network access** (firewall rules, bind to localhost if possible)
- [ ] **Disable remote root login** to MySQL

#### Application Security

- [ ] **Change ALL default passwords**:
  - Don't use accounts from `bin/seed.php`
  - Create production admin: `php bin/create_admin_user.php`
- [ ] **Review CSRF protection** is active (already implemented, verify it works)
- [ ] **Verify file upload restrictions**:
  - Size limits configured
  - Allowed MIME types enforced
  - Files stored outside web root (in `storage/uploads/`)
- [ ] **Enable rate limiting** (already implemented for login, uploads)
- [ ] **Review SQL injection prevention** (all queries use prepared statements)
- [ ] **Verify password hashing** (uses `password_hash()` with bcrypt)
- [ ] **Check session security**:
  - HttpOnly cookies enabled
  - Secure flag (HTTPS only)
  - Session regeneration on login

### Performance Optimization

- [ ] **Enable PHP OPcache**:
  ```ini
  ; In php.ini
  opcache.enable=1
  opcache.memory_consumption=128
  opcache.max_accelerated_files=10000
  opcache.revalidate_freq=2
  ```
- [ ] **Enable Apache compression** (mod_deflate, already in `.htaccess`)
- [ ] **Enable browser caching** (already in `.htaccess`)
- [ ] **Optimize database** (indexes already set in migrations)
- [ ] **Configure PHP limits**:
  ```ini
  ; In php.ini
  upload_max_filesize = 10M
  post_max_size = 10M
  max_execution_time = 30
  memory_limit = 256M
  ```

### Monitoring & Logging

- [ ] **Set up error logging**:
  ```ini
  ; In php.ini (production)
  display_errors = Off
  log_errors = On
  error_log = /var/log/php/error.log
  ```
- [ ] **Configure log rotation** for `storage/logs/*.log`
  ```bash
  # Example logrotate config
  /path/to/ssltd/storage/logs/*.log {
      daily
      rotate 14
      compress
      delaycompress
      notifempty
      create 0644 www-data www-data
  }
  ```
- [ ] **Set up monitoring**:
  - Application uptime monitoring (Pingdom, UptimeRobot, etc.)
  - Error tracking (Sentry, Rollbar, etc.)
  - Server resource monitoring (CPU, RAM, disk)
- [ ] **Test health check endpoints**:
  - `GET /health`
  - `GET /health/db`

### Backup Strategy

- [ ] **Set up automated database backups**:
  ```bash
  # Example cron job (daily at 2 AM)
  0 2 * * * /usr/bin/mysqldump -u ssltd_prod -p'password' ss_ltd_prod | gzip > /backups/ssltd_$(date +\%Y\%m\%d).sql.gz
  ```
- [ ] **Set up file backups** (uploads in `storage/uploads/`)
- [ ] **Test backup restoration** (verify backups actually work)
- [ ] **Store backups off-site** (S3, different server, etc.)
- [ ] **Document backup/restore procedure**
- [ ] **Set retention policy** (how long to keep backups)

### Testing

- [ ] **Test public website**:
  - Homepage loads
  - Quote request forms work
  - SSL certificate valid
- [ ] **Test authentication**:
  - Login works
  - Logout works
  - Password reset works (if implemented)
- [ ] **Test user roles**:
  - Admin can access admin panel
  - PM can access projects
  - Client can see their leads/projects only
  - Employees can log time
- [ ] **Test file uploads**:
  - Can upload files
  - Files are not directly accessible (security test)
  - Download works through PHP route
- [ ] **Test API endpoints** (for Android app):
  - `/api/auth/login`
  - `/api/projects`
  - `/api/timesheets/*`
  - `/api/uploads`
- [ ] **Security scan**:
  - Run vulnerability scanner (OWASP ZAP, Burp Suite)
  - Check for SQL injection (try `' OR '1'='1` in inputs)
  - Check for XSS (try `<script>alert('XSS')</script>` in inputs)
  - Verify CSRF protection (try submitting forms without token)
- [ ] **Performance test**:
  - Load test with Apache Bench or similar
  - Check page load times
  - Monitor database query performance

## Deployment

### Upload Files

- [ ] **Upload all files EXCEPT**:
  - `.git/`
  - `.env` (create separately on server)
  - `android/` (separate Android app deployment)
  - `docs/`, `plans/` (optional, for reference)
  - Test/temp files

### Post-Deployment

- [ ] **Verify environment**: `php bin/health_db.php`
- [ ] **Check logs**: `tail -f storage/logs/*.log`
- [ ] **Monitor first hour** for errors
- [ ] **Test all critical paths**:
  - Public homepage
  - Login flow
  - Quote submission
  - File upload/download
- [ ] **Verify dev tools are disabled**:
  - Try accessing `/app/dev/tools/whoami` (should 404)
  - Check no dev overlay appears

## Post-Launch

### First Week

- [ ] **Monitor error logs daily**
- [ ] **Check database performance** (slow query log)
- [ ] **Monitor disk space** (especially `storage/uploads/`)
- [ ] **Test backups** (do a test restore)
- [ ] **Check SSL certificate expiry** (set up auto-renewal if using Let's Encrypt)

### Ongoing Maintenance

- [ ] **Weekly backup verification**
- [ ] **Monthly security updates** (PHP, MySQL, server OS)
- [ ] **Quarterly security audit**
- [ ] **Log review** (check for unusual activity)
- [ ] **Performance monitoring** (response times, server load)

## Incident Response Plan

Document your plan for:

- [ ] **Database failure** (how to restore from backup)
- [ ] **Server downtime** (backup server? hosting provider support?)
- [ ] **Security breach** (who to contact, how to lock down)
- [ ] **Data loss** (backup restore procedure)
- [ ] **DDoS attack** (Cloudflare? hosting provider DDoS protection?)

## Compliance (if applicable)

Depending on your jurisdiction and data stored:

- [ ] **GDPR compliance** (EU data protection)
- [ ] **Data retention policy**
- [ ] **Privacy policy published**
- [ ] **Terms of service published**
- [ ] **Cookie consent** (if tracking users)
- [ ] **Data breach notification plan**

---

## Quick Launch Command Reference

```bash
# On production server

# 1. Create database
mysql -u root -p -e "CREATE DATABASE ss_ltd_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Create DB user
mysql -u root -p -e "CREATE USER 'ssltd_prod'@'%' IDENTIFIED BY 'secure-password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON ss_ltd_prod.* TO 'ssltd_prod'@'%';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# 3. Import schema
mysql -u ssltd_prod -p ss_ltd_prod < mysql.sql

# 4. Configure environment
cp .env.production.example .env
nano .env  # Edit with actual values

# 5. Run migrations
php bin/migrate.php

# 6. Create admin user (interactive)
php bin/create_admin_user.php

# 7. Verify setup
php bin/health_db.php
php bin/migrate_status.php

# 8. Set permissions
chown -R www-data:www-data .
chmod 600 .env
chmod -R 777 storage/logs storage/uploads storage/tmp

# 9. Test
curl https://your-domain.com/health
curl https://your-domain.com/health/db
```

---

## Emergency Rollback

If deployment fails:

```bash
# 1. Restore database from backup
mysql -u ssltd_prod -p ss_ltd_prod < /backups/ssltd_20260212.sql

# 2. Restore previous code version
cd /path/to/ssltd
git checkout <previous-working-commit>

# 3. Clear caches
rm -rf storage/tmp/*
```

---

## Support Contacts

Document your emergency contacts:

- Hosting provider support: _______________
- Database admin: _______________
- Developer: _______________
- Domain registrar: _______________
- SSL certificate provider: _______________

---

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Verified By**: _______________  
**Notes**: _______________
