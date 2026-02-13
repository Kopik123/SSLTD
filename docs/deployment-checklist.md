# DigitalOcean Deployment Checklist

Use this checklist when deploying S&S LTD to DigitalOcean.

## Pre-Deployment (Local)

- [ ] Review and test all code changes
- [ ] Update documentation if needed
- [ ] Run local tests to verify functionality
- [ ] Generate deployment package: `bash bin/deploy-prepare.sh`
- [ ] Verify deployment archive was created

## Server Preparation

- [ ] Create DigitalOcean droplet (Ubuntu 22.04 LTS, 2GB RAM minimum)
- [ ] Note droplet IP address
- [ ] Point domain DNS to droplet IP
- [ ] Connect via SSH (Termius): `ssh root@your_ip`
- [ ] Update system: `apt update && apt upgrade -y`

## LAMP Stack Installation

- [ ] Install Apache: `apt install apache2 -y`
- [ ] Enable Apache modules: `a2enmod rewrite headers expires`
- [ ] Install MySQL: `apt install mysql-server -y`
- [ ] Run MySQL secure installation: `mysql_secure_installation`
- [ ] Install PHP 8.1+: `apt install php8.1 php8.1-cli php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd libapache2-mod-php8.1 -y`
- [ ] Configure PHP for production (see deployment.md)
- [ ] Restart Apache: `systemctl restart apache2`

## Database Setup

- [ ] Login to MySQL: `mysql -u root -p`
- [ ] Create database: `CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- [ ] Create database user with strong password
- [ ] Grant privileges to user
- [ ] Exit MySQL

## File Transfer

Choose one method:

### Option A: Termius SFTP
- [ ] Open Termius SFTP
- [ ] Upload deployment archive to `/root/`
- [ ] Extract: `cd /var/www/html && tar -xzf ~/ss_ltd_deploy_*.tar.gz`
- [ ] Rename extracted folder: `mv ss_ltd_deploy_* ss_ltd`

### Option B: Git Clone
- [ ] Navigate to web directory: `cd /var/www/html`
- [ ] Clone repository: `git clone https://github.com/Kopik123/SSLTD.git ss_ltd`

### Option C: rsync
- [ ] From local machine: `rsync -avz /path/to/project/ root@your_ip:/var/www/html/ss_ltd/`

## Application Configuration

- [ ] Navigate to app: `cd /var/www/html/ss_ltd`
- [ ] Create .env from template: `cp .env.production.example .env`
- [ ] Edit .env: `nano .env`
  - [ ] Set APP_ENV=production
  - [ ] Set APP_DEBUG=0
  - [ ] Set APP_URL to your domain (https://yourdomain.com)
  - [ ] Generate and set APP_KEY (use `openssl rand -base64 32`)
  - [ ] Configure database credentials
- [ ] Save .env file

## Permissions

- [ ] Set ownership: `chown -R www-data:www-data /var/www/html/ss_ltd`
- [ ] Set directory permissions: `chmod -R 755 /var/www/html/ss_ltd`
- [ ] Set storage permissions: `chmod -R 775 /var/www/html/ss_ltd/storage`
- [ ] Secure .env: `chmod 600 /var/www/html/ss_ltd/.env`
- [ ] Create storage directories: `mkdir -p storage/{logs,tmp,uploads}`

## Database Initialization

- [ ] Run migrations: `php bin/migrate.php`
- [ ] Verify migrations: `php bin/migrate_status.php`
- [ ] Seed initial data: `php bin/seed.php`
- [ ] Note default admin credentials (admin@ss.local / Admin123!)

## Apache Configuration

- [ ] Create virtual host: `nano /etc/apache2/sites-available/ss_ltd.conf`
- [ ] Add configuration (see deployment.md)
- [ ] Enable site: `a2ensite ss_ltd.conf`
- [ ] Disable default: `a2dissite 000-default.conf`
- [ ] Test configuration: `apache2ctl configtest`
- [ ] Restart Apache: `systemctl restart apache2`

## SSL Certificate

- [ ] Install Certbot: `apt install certbot python3-certbot-apache -y`
- [ ] Obtain certificate: `certbot --apache -d yourdomain.com -d www.yourdomain.com`
- [ ] Follow Certbot prompts
- [ ] Choose redirect HTTP to HTTPS: Yes
- [ ] Test auto-renewal: `certbot renew --dry-run`

## Security Hardening

- [ ] Configure firewall:
  - [ ] `ufw allow OpenSSH`
  - [ ] `ufw allow 'Apache Full'`
  - [ ] `ufw enable`
- [ ] Secure PHP: Edit php.ini and set expose_php=Off, allow_url_fopen=Off
- [ ] Restart Apache: `systemctl restart apache2`
- [ ] Verify sensitive files are blocked:
  - [ ] Test `.env`: `curl -I https://yourdomain.com/.env` (should be 403)
  - [ ] Test `storage/`: `curl -I https://yourdomain.com/storage/` (should be 403)

## Testing

- [ ] Test health endpoint: `curl https://yourdomain.com/health`
- [ ] Test database health: `curl https://yourdomain.com/health/db`
- [ ] Visit site in browser: `https://yourdomain.com`
- [ ] Login with default admin credentials
- [ ] Verify dashboard loads correctly
- [ ] Test uploading a file
- [ ] Test creating a test record

## Post-Deployment

- [ ] Change default admin password immediately
- [ ] Update admin email address
- [ ] Review and update/delete test user accounts
- [ ] Set up automated backups:
  - [ ] Create backup script location: `/root/backup-ss-ltd.sh`
  - [ ] Make executable: `chmod +x /root/backup-ss-ltd.sh`
  - [ ] Add to crontab: `crontab -e`
  - [ ] Add line: `0 2 * * * /root/backup-ss-ltd.sh`
- [ ] Configure log rotation (see deployment.md)
- [ ] Document server details (IP, credentials) securely
- [ ] Set up monitoring/alerting (optional)
- [ ] Share credentials with team securely

## Maintenance

- [ ] Test backup script: `bash /home/runner/work/SSLTD/SSLTD/bin/backup.sh`
- [ ] Verify backup files created
- [ ] Document update procedure
- [ ] Schedule regular security updates

## Troubleshooting Quick Reference

| Issue | Check | Solution |
|-------|-------|----------|
| 500 Error | Apache logs | `tail -f /var/log/apache2/ss_ltd_error.log` |
| DB Connection | MySQL status | `systemctl status mysql` |
| Permission Error | File ownership | `chown -R www-data:www-data /var/www/html/ss_ltd` |
| Upload fails | Storage perms | `chmod -R 775 storage` |
| SSL issues | Certificate | `certbot certificates` |

## Important Notes

⚠️ **Security Reminders:**
- NEVER commit .env to git
- ALWAYS use strong passwords
- ALWAYS change default credentials
- ALWAYS use HTTPS in production
- ALWAYS keep system updated

📝 **Backup Reminders:**
- Backups run daily at 2 AM (if configured)
- Keep backups for 14 days minimum
- Test restore procedure regularly
- Store backups off-server for disaster recovery

🔄 **Update Procedure:**
1. Backup current installation
2. Git pull or upload new files
3. Run migrations if any
4. Clear caches if applicable
5. Test functionality

## Support

For detailed instructions, see:
- `docs/deployment.md` - Full deployment guide
- `docs/setup.md` - Local setup instructions
- `docs/backups.md` - Backup/restore procedures

For issues, check:
- `/var/log/apache2/ss_ltd_error.log` - Apache errors
- `/var/log/php_errors.log` - PHP errors
- `storage/logs/app.log` - Application logs
