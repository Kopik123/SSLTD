# Deploying SSLTD to Vercel

⚠️ **Important Notice**: This project was designed for traditional PHP hosting (Apache + MySQL). Deploying to Vercel requires external services and architectural changes.

## Prerequisites

1. **Vercel Account**: Sign up at [vercel.com](https://vercel.com)
2. **External Database**: This app needs a persistent MySQL database. Options:
   - [PlanetScale](https://planetscale.com) (MySQL-compatible, free tier available)
   - [AWS RDS](https://aws.amazon.com/rds/)
   - [DigitalOcean Managed Databases](https://www.digitalocean.com/products/managed-databases)
   - [Railway](https://railway.app) (MySQL hosting)
3. **File Storage**: For production uploads (optional for MVP):
   - [AWS S3](https://aws.amazon.com/s3/)
   - [Cloudinary](https://cloudinary.com)
   - [Vercel Blob](https://vercel.com/docs/storage/vercel-blob)

## Limitations on Vercel

Vercel serverless functions have limitations:

- **Execution Time**: 10s (Hobby), 60s (Pro), 900s (Enterprise)
- **File System**: Read-only except `/tmp` (ephemeral)
- **Uploads**: Must use external storage (S3, Cloudinary, Vercel Blob)
- **Sessions**: File-based sessions won't work (use database or Redis)
- **Background Jobs**: Need external queue (not covered in this guide)

## Step 1: Set Up External Database

### Using PlanetScale (Recommended)

1. Create account at [planetscale.com](https://planetscale.com)
2. Create new database: `ssltd-production`
3. Get connection string from dashboard
4. Import schema:

```bash
# Install PlanetScale CLI
brew install planetscale/tap/pscale

# Authenticate
pscale auth login

# Connect to database
pscale connect ssltd-production main --port 3309

# Import schema (in another terminal)
mysql -h 127.0.0.1 -P 3309 -u root < mysql.sql

# Run migrations
DB_HOST=127.0.0.1 DB_PORT=3309 DB_NAME=ssltd-production \
  php bin/migrate.php

# Seed data
php bin/seed.php
```

## Step 2: Configure Environment Variables

In your Vercel project dashboard, add these environment variables:

```bash
# Application
APP_ENV=production
APP_DEBUG=0
APP_URL=https://your-project.vercel.app
APP_KEY=CHANGE_TO_RANDOM_32_CHAR_STRING

# Database (from PlanetScale or your provider)
DB_CONNECTION=mysql
DB_HOST=your-db-host.psdb.cloud
DB_PORT=3306
DB_NAME=ssltd-production
DB_USER=your-db-username
DB_PASS=your-db-password

# Service Configuration
SERVICE_AREA_RADIUS_MILES=60

# Dev Tools (MUST be disabled)
SS_DEV_TOOLS_KEY=
```

### Generate Secure APP_KEY

```bash
# On Linux/Mac:
openssl rand -base64 32

# Or use PHP:
php -r "echo bin2hex(random_bytes(32));"
```

## Step 3: Deploy to Vercel

### Using Vercel CLI

```bash
# Install Vercel CLI
npm i -g vercel

# Login
vercel login

# Deploy
cd SSLTD
vercel --prod
```

### Using GitHub Integration

1. Connect your GitHub repository to Vercel
2. Configure project:
   - Framework Preset: **Other**
   - Build Command: (leave empty)
   - Output Directory: (leave empty)
   - Install Command: (leave empty)
3. Add environment variables (from Step 2)
4. Deploy

## Step 4: Configure File Uploads (Optional)

For production file uploads, integrate cloud storage:

### Option A: Vercel Blob

```bash
# Install Vercel Blob SDK
npm install @vercel/blob

# Add to vercel.json
{
  "storage": {
    "blob": true
  }
}
```

Then modify upload handling in `src/Controllers/` to use Vercel Blob API.

### Option B: AWS S3

1. Create S3 bucket
2. Get access credentials (Access Key ID + Secret)
3. Add to environment variables:

```bash
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_S3_BUCKET=ssltd-uploads
AWS_S3_REGION=us-east-1
```

4. Modify upload controllers to use AWS SDK

## Step 5: Session Handling

Vercel's serverless environment requires database-backed sessions:

1. Sessions already use PHP native (file-based)
2. For Vercel, modify `src/Http/Session.php` to use MySQL session handler:

```php
// In Session.php start() method
session_set_save_handler(
    new DatabaseSessionHandler($db),
    true
);
```

*Note: Implementation not included in this guide*

## Step 6: Test Deployment

After deployment:

1. Visit your Vercel URL: `https://your-project.vercel.app`
2. Test public pages (home, quote request)
3. Test login: Use seeded credentials
4. Check health endpoint: `https://your-project.vercel.app/health`
5. Test API endpoints: `https://your-project.vercel.app/api/*`

## Troubleshooting

### "Connection refused" errors
- Check database credentials
- Verify database allows connections from Vercel IPs
- For PlanetScale, ensure connection is from `main` branch

### "File not found" errors
- Check `vercel.json` routing configuration
- Ensure `api/index.php` exists and is correct

### "Session errors"
- Implement database session handler
- Or disable sessions for API-only usage

### Upload failures
- Vercel filesystem is read-only except `/tmp`
- Must use external storage (S3, Cloudinary, Vercel Blob)

## Alternative: DigitalOcean App Platform

For easier PHP deployment with MySQL, consider:

1. **DigitalOcean App Platform**: Native PHP + MySQL support
2. **Heroku**: PHP buildpack + ClearDB MySQL
3. **AWS Lightsail**: Full LAMP stack control

These platforms better suit traditional PHP apps.

## Production Checklist

Before going live:

- [ ] External database configured and migrated
- [ ] All environment variables set in Vercel
- [ ] `APP_DEBUG=0` in production
- [ ] `APP_KEY` is random and secure
- [ ] Default passwords changed
- [ ] SSL/HTTPS enabled (automatic on Vercel)
- [ ] File uploads configured (if needed)
- [ ] Database backups scheduled
- [ ] Session handling tested
- [ ] Health check endpoint working
- [ ] Error monitoring configured (Sentry, etc.)

## Cost Estimate (Monthly)

Using free tiers:

- **Vercel**: Free (Hobby plan, up to 100GB bandwidth)
- **PlanetScale**: Free (5GB storage, 1 billion rows read/month)
- **Vercel Blob**: $0.15/GB stored + transfer fees

Estimated: **$0-5/month** for low-traffic MVP

---

**Need Help?** For production deployment assistance, consider:
- Hiring a DevOps consultant
- Using managed hosting (Laravel Forge, Ploi, RunCloud)
- Switching to PaaS with better PHP support (DigitalOcean, Heroku)
