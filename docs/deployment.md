# DigitalOcean Deployment Guide

This guide explains how to deploy the S&S LTD web application to a DigitalOcean droplet.

## Prerequisites

- DigitalOcean droplet (Ubuntu 22.04 LTS recommended, minimum 2GB RAM)
- Domain name pointed to your droplet's IP
- SSH access via Termius or similar SSH client
- Basic knowledge of Linux command line

## Server Requirements

- PHP 8.1 or higher
- MySQL 8.0 or MariaDB 10.6+
- Apache 2.4 with mod_rewrite enabled
- Composer (for dependency management if needed)
- Git (for version control)

### Required PHP Extensions
- pdo
- pdo_mysql
- mbstring
- json
- curl
- fileinfo
- openssl

## Step 1: Prepare DigitalOcean Droplet

### 1.1 Create Droplet
1. Log in to DigitalOcean
2. Create new droplet:
   - Distribution: Ubuntu 22.04 LTS
   - Plan: Basic (2GB RAM minimum recommended)
   - Region: Choose closest to your users
   - Authentication: SSH key (recommended) or password
3. Note your droplet's IP address

### 1.2 Initial Server Setup

Connect via SSH (using Termius):
```bash
ssh root@your_droplet_ip
```

Update system packages:
```bash
apt update && apt upgrade -y
```

## Step 2: Install LAMP Stack

### 2.1 Install Apache
```bash
apt install apache2 -y
systemctl enable apache2
systemctl start apache2
```

Enable required modules:
```bash
a2enmod rewrite
a2enmod headers
a2enmod expires
systemctl restart apache2
```

### 2.2 Install MySQL
```bash
apt install mysql-server -y
systemctl enable mysql
systemctl start mysql
```

Secure MySQL installation:
```bash
mysql_secure_installation
```

Follow prompts:
- Set root password
- Remove anonymous users: Yes
- Disallow root login remotely: Yes
- Remove test database: Yes
- Reload privilege tables: Yes

### 2.3 Install PHP
```bash
apt install php8.1 php8.1-cli php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd libapache2-mod-php8.1 -y
```

Verify PHP installation:
```bash
php -v
```

### 2.4 Configure PHP for Production

Edit PHP configuration:
```bash
nano /etc/php/8.1/apache2/php.ini
```

Recommended settings:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

Restart Apache:
```bash
systemctl restart apache2
```

## Step 3: Create Database

Login to MySQL:
```bash
mysql -u root -p
```

Create database and user:
```sql
CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ss_ltd_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON ss_ltd.* TO 'ss_ltd_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 4: Transfer Application Files

### Option A: Using Termius SFTP

1. Open Termius and connect to your server
2. Use the SFTP feature to transfer files:
   - Navigate to `/var/www/html/` on server
   - Upload entire project directory excluding:
     - `.git/` directory (optional, keep if you want version control)
     - `.env` file (create new on server)
     - `storage/logs/*.log`
     - `storage/tmp/`
     - `node_modules/` (if any)
     - Any local database files

### Option B: Using Git (Recommended)

On the server:
```bash
cd /var/www/html
git clone https://github.com/Kopik123/SSLTD.git ss_ltd
cd ss_ltd
```

Or if you're using a private repository:
```bash
# Generate SSH key on server
ssh-keygen -t ed25519 -C "your_email@example.com"
cat ~/.ssh/id_ed25519.pub
# Add this key to your GitHub account, then clone
```

### Option C: Using rsync via SSH

From your local machine (Windows with WSL or Linux):
```bash
rsync -avz --exclude='.git' --exclude='.env' --exclude='storage/logs/*.log' \
  /path/to/local/project/ root@your_droplet_ip:/var/www/html/ss_ltd/
```

## Step 5: Configure Application

### 5.1 Set Up Environment File

Create production environment file:
```bash
cd /var/www/html/ss_ltd
cp .env.production.example .env
nano .env
```

Update the following values:
```ini
APP_ENV=production
APP_DEBUG=0
APP_URL=https://yourdomain.com
APP_KEY=generate-random-32-character-string-here

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ss_ltd
DB_USER=ss_ltd_user
DB_PASS=your_secure_password_here
```

### 5.2 Generate App Key

Generate a secure random key:
```bash
openssl rand -base64 32
```
Copy the output and use it for APP_KEY in .env

### 5.3 Set Permissions

```bash
cd /var/www/html/ss_ltd
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage
chmod 600 .env
```

### 5.4 Create Required Directories

```bash
mkdir -p storage/logs storage/tmp storage/uploads
chown -R www-data:www-data storage
chmod -R 775 storage
```

## Step 6: Initialize Database

Run migrations:
```bash
cd /var/www/html/ss_ltd
php bin/migrate.php
```

Verify migrations:
```bash
php bin/migrate_status.php
```

Seed initial data (creates default admin account):
```bash
php bin/seed.php
```

**Important**: Default admin credentials from seed:
- Email: `admin@ss.local`
- Password: `Admin123!`

**Change these immediately after first login!**

## Step 7: Configure Apache Virtual Host

Create virtual host configuration:
```bash
nano /etc/apache2/sites-available/ss_ltd.conf
```

Add the following configuration:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/ss_ltd
    
    <Directory /var/www/html/ss_ltd>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Block access to sensitive directories
    <DirectoryMatch "^/.*/\.(git|env|htaccess)">
        Require all denied
    </DirectoryMatch>
    
    <DirectoryMatch "^/var/www/html/ss_ltd/(storage|database|src|bin|plans)/">
        Require all denied
    </DirectoryMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/ss_ltd_error.log
    CustomLog ${APACHE_LOG_DIR}/ss_ltd_access.log combined
</VirtualHost>
```

Enable the site and disable default:
```bash
a2ensite ss_ltd.conf
a2dissite 000-default.conf
systemctl restart apache2
```

## Step 8: Install SSL Certificate (Let's Encrypt)

Install Certbot:
```bash
apt install certbot python3-certbot-apache -y
```

Obtain and install SSL certificate:
```bash
certbot --apache -d yourdomain.com -d www.yourdomain.com
```

Follow the prompts:
- Enter email address
- Agree to Terms of Service
- Choose whether to redirect HTTP to HTTPS (recommended: Yes)

Certbot will automatically:
- Obtain SSL certificate
- Configure Apache for HTTPS
- Set up auto-renewal

Verify auto-renewal:
```bash
certbot renew --dry-run
```

## Step 9: Security Hardening

### 9.1 Configure Firewall (UFW)

```bash
ufw allow OpenSSH
ufw allow 'Apache Full'
ufw enable
ufw status
```

### 9.2 Secure PHP

Edit `/etc/php/8.1/apache2/php.ini`:
```ini
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
```

Restart Apache:
```bash
systemctl restart apache2
```

### 9.3 Disable Directory Listing

This is already handled in .htaccess, but verify:
```bash
cat /var/www/html/ss_ltd/.htaccess | grep "Options -Indexes"
```

### 9.4 Protect Sensitive Files

Verify .htaccess blocks sensitive files:
```bash
curl -I https://yourdomain.com/.env
# Should return 403 Forbidden
```

## Step 10: Verify Deployment

### 10.1 Health Checks

Test database connection:
```bash
cd /var/www/html/ss_ltd
php bin/health_db.php
```

### 10.2 Web Access

Visit your domain:
- `https://yourdomain.com/health` - Should show "OK"
- `https://yourdomain.com/health/db` - Should show database status
- `https://yourdomain.com/` - Should show login page

### 10.3 Test Login

1. Navigate to `https://yourdomain.com/`
2. Login with default credentials:
   - Email: `admin@ss.local`
   - Password: `Admin123!`
3. **Immediately change password** after first login

## Step 11: Post-Deployment Tasks

### 11.1 Change Default Credentials

1. Login as admin
2. Navigate to user settings
3. Change password to a strong, unique password
4. Update email if needed

### 11.2 Remove or Update Test Accounts

Review and update test accounts created by seed:
```sql
mysql -u ss_ltd_user -p ss_ltd
SELECT id, email, role FROM users;
```

### 11.3 Set Up Backups

Create backup script (see `docs/backups.md` for details):
```bash
nano /root/backup-ss-ltd.sh
```

Add to crontab for daily backups:
```bash
crontab -e
# Add: 0 2 * * * /root/backup-ss-ltd.sh
```

### 11.4 Monitor Logs

Set up log rotation:
```bash
nano /etc/logrotate.d/ss_ltd
```

Add:
```
/var/www/html/ss_ltd/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
}
```

## Troubleshooting

### 500 Internal Server Error
- Check Apache error log: `tail -f /var/log/apache2/ss_ltd_error.log`
- Check PHP error log: `tail -f /var/log/php_errors.log`
- Verify .env file exists and has correct permissions
- Check database credentials in .env

### Database Connection Failed
- Verify MySQL is running: `systemctl status mysql`
- Test database credentials: `mysql -u ss_ltd_user -p ss_ltd`
- Check DB_HOST in .env (should be 'localhost')
- Verify user has correct privileges

### Permission Denied Errors
- Fix ownership: `chown -R www-data:www-data /var/www/html/ss_ltd`
- Fix permissions: `chmod -R 755 /var/www/html/ss_ltd && chmod -R 775 /var/www/html/ss_ltd/storage`

### Uploads Not Working
- Verify storage directory exists: `ls -la /var/www/html/ss_ltd/storage/uploads`
- Check permissions: `ls -ld /var/www/html/ss_ltd/storage/uploads`
- Ensure PHP upload limits are correct in php.ini

### SSL Certificate Issues
- Verify domain DNS points to droplet IP
- Check port 80 and 443 are open: `ufw status`
- Renew certificate manually: `certbot renew --force-renewal`

## Updating the Application

### Using Git Pull

```bash
cd /var/www/html/ss_ltd
git pull origin main
```

### After Updates

1. Run any new migrations:
   ```bash
   php bin/migrate.php
   php bin/migrate_status.php
   ```

2. Clear any caches if implemented

3. Restart Apache if needed:
   ```bash
   systemctl restart apache2
   ```

## Maintenance Commands

### Database Backup
```bash
mysqldump -u ss_ltd_user -p ss_ltd > /root/backups/ss_ltd_$(date +%Y%m%d).sql
```

### Database Restore
```bash
mysql -u ss_ltd_user -p ss_ltd < /root/backups/ss_ltd_20260213.sql
```

### View Recent Logs
```bash
tail -f /var/www/html/ss_ltd/storage/logs/app.log
tail -f /var/log/apache2/ss_ltd_error.log
```

### Check Disk Space
```bash
df -h
du -sh /var/www/html/ss_ltd/storage/*
```

## Support and Resources

- [DigitalOcean Documentation](https://docs.digitalocean.com/)
- [PHP Documentation](https://www.php.net/docs.php)
- [Apache Documentation](https://httpd.apache.org/docs/)
- [Let's Encrypt](https://letsencrypt.org/)

## Security Checklist

- [ ] APP_DEBUG set to 0 in production .env
- [ ] Strong APP_KEY generated
- [ ] Strong database password set
- [ ] Default admin password changed
- [ ] SSL certificate installed and working
- [ ] Firewall (UFW) enabled and configured
- [ ] Regular backups configured
- [ ] Log rotation configured
- [ ] Sensitive directories blocked via Apache config
- [ ] PHP error display disabled
- [ ] File upload limits set appropriately
