# Termius Transfer Guide

Quick guide for transferring S&S LTD application to DigitalOcean using Termius.

## What is Termius?

Termius is a modern SSH client with built-in SFTP support, available for Windows, Mac, Linux, iOS, and Android. It makes transferring files to your server easy.

## Prerequisites

- [ ] Termius installed on your computer
- [ ] DigitalOcean droplet created and running
- [ ] Droplet IP address
- [ ] SSH access credentials (root password or SSH key)

## Step 1: Prepare Deployment Package Locally

On your local machine (Windows with XAMPP or development environment):

### Using Git Bash / WSL / Linux:
```bash
cd /path/to/ss_ltd
bash bin/deploy-prepare.sh
```

### Using Windows Command Prompt:
If bash is not available, manually create a deployment package:

1. Create a new folder for deployment (e.g., `C:\ss_ltd_deploy`)
2. Copy all project files EXCEPT:
   - `.git` folder
   - `.env` file
   - `storage/logs/*.log`
   - `storage/tmp/*`
   - `node_modules`
   - Large image files

The deployment package will be created in the parent directory as `ss_ltd_deploy_YYYYMMDD_HHMMSS.tar.gz`

## Step 2: Set Up Termius Connection

### Add Your Server to Termius

1. **Open Termius**
2. **Click "New Host"** or "+" button
3. **Enter server details**:
   - **Label**: DigitalOcean - S&S LTD
   - **Address**: Your droplet IP (e.g., 164.90.123.45)
   - **Port**: 22
   - **Username**: root
   - **Password**: Your droplet password (or use SSH key)
4. **Click "Save"**

### Test Connection

1. Click on your saved host
2. Click "Connect"
3. If prompted about host key, click "Yes" or "Accept"
4. You should see a terminal prompt: `root@your-droplet:~#`

✅ If you see the prompt, SSH connection is working!

## Step 3: Transfer Files Using Termius SFTP

### Open SFTP in Termius

1. **Connect to your server** (if not already connected)
2. **Click the SFTP button** (usually looks like a folder icon)
3. This opens a split-pane view:
   - **Left side**: Your local computer
   - **Right side**: Your server

### Navigate and Upload

#### On Local Side (Left):
1. Navigate to the folder containing `ss_ltd_deploy_YYYYMMDD_HHMMSS.tar.gz`
2. Select the deployment archive file

#### On Server Side (Right):
1. Navigate to `/root/` or `/tmp/`
2. This is where you'll upload the file

#### Upload the File:
1. **Drag and drop** the `.tar.gz` file from left to right, OR
2. **Right-click** on the file → **Upload**, OR
3. **Select file** and click the **Upload** button

#### Wait for Transfer:
- Watch the progress bar
- Large files may take several minutes
- Don't close Termius until transfer completes

✅ Transfer complete when progress shows 100%

## Step 4: Extract Files on Server

### Switch to Terminal in Termius

1. Close SFTP view or switch to Terminal tab
2. You should see: `root@your-droplet:~#`

### Extract the Archive

```bash
# Navigate to web directory
cd /var/www/html

# Extract the uploaded archive
tar -xzf ~/ss_ltd_deploy_*.tar.gz

# Rename the extracted folder to ss_ltd
mv ss_ltd_deploy_* ss_ltd

# Verify extraction
ls -la ss_ltd/
```

You should see all your project files listed.

## Step 5: Continue with Deployment

Now follow the deployment guide from Step 5 onwards:

```bash
cd /var/www/html/ss_ltd
```

Then refer to **[docs/deployment.md](deployment.md)** starting from:
- **Step 5: Configure Application**

Or use the **[deployment checklist](deployment-checklist.md)** to track your progress.

## Alternative: Upload Individual Files

If you prefer not to use archives, you can transfer files directly:

### Method A: Upload Entire Folder

1. Open SFTP in Termius
2. On local side: Navigate to your project folder
3. On server side: Navigate to `/var/www/html/`
4. **Drag the entire project folder** to the server
5. Wait for all files to upload (this may take longer)

### Method B: Selective Upload

Upload only necessary files/folders:
- `index.php`
- `src/` folder
- `bin/` folder
- `database/` folder
- `storage/` folder (create on server, upload .htaccess only)
- `assets/` folder
- `.htaccess`
- `.env.production.example`
- `mysql.sql`

## Troubleshooting Termius

### Cannot Connect to Server

**Problem**: "Connection refused" or "Connection timeout"

**Solutions**:
1. Verify droplet IP address is correct
2. Check droplet is running in DigitalOcean dashboard
3. Verify SSH port is 22
4. Check firewall allows SSH (should be open by default)

### Authentication Failed

**Problem**: "Authentication failed" or "Access denied"

**Solutions**:
1. Double-check username (usually `root` for new droplets)
2. Verify password is correct
3. If using SSH key, ensure key is added to Termius
4. Reset root password in DigitalOcean dashboard if needed

### SFTP Not Working

**Problem**: Cannot open SFTP or permission denied

**Solutions**:
1. Ensure SSH connection is working first
2. Try reconnecting to the server
3. Verify you have write permissions in target directory
4. Use `/root/` or `/tmp/` directories which are always writable

### Upload Fails or Stalls

**Problem**: Upload stops or fails partway through

**Solutions**:
1. Check your internet connection
2. Try uploading to `/tmp/` instead
3. Break large folders into smaller chunks
4. Use archive (.tar.gz) instead of individual files
5. Restart Termius and try again

### File Already Exists

**Problem**: Cannot upload because file exists

**Solutions**:
1. Delete existing file on server first
2. Rename the file on your local machine
3. In SFTP, enable "Overwrite" option if available

## Tips for Faster Transfers

1. **Use compression**: Transfer `.tar.gz` archives instead of individual files
2. **Close unnecessary applications**: Free up bandwidth
3. **Use wired connection**: More stable than WiFi
4. **Upload during off-peak hours**: Less network congestion
5. **Exclude unnecessary files**: Don't upload `.git`, logs, tmp files

## Termius Features for Deployment

### Port Forwarding
- Useful for secure database access
- Forward MySQL port for remote management

### Snippets
- Save common commands as snippets
- Quick access to deployment commands

### Multiple Sessions
- Run multiple terminal sessions simultaneously
- Monitor logs while deploying

### Terminal Tabs
- Keep multiple connections organized
- Switch between different servers easily

## After Transfer

Once files are transferred and extracted:

1. ✅ Verify all files are present: `ls -la /var/www/html/ss_ltd/`
2. ✅ Set permissions: `chown -R www-data:www-data /var/www/html/ss_ltd`
3. ✅ Continue with deployment guide: [docs/deployment.md](deployment.md)

## Security Notes

- 🔒 Never transfer your local `.env` file to production
- 🔒 Create a new `.env` on the server with production settings
- 🔒 Use SSH keys instead of passwords when possible
- 🔒 Change default admin password immediately after first login

## Quick Reference

| Task | Command/Action |
|------|----------------|
| Connect to server | Click host in Termius |
| Open SFTP | Click SFTP button in terminal |
| Upload file | Drag & drop in SFTP view |
| Extract archive | `tar -xzf filename.tar.gz` |
| List files | `ls -la` |
| Change directory | `cd /path/to/directory` |
| Check disk space | `df -h` |
| Disconnect | Type `exit` or close window |

## Next Steps

After successful file transfer:

1. 📖 Follow [Deployment Guide](deployment.md)
2. ✅ Use [Deployment Checklist](deployment-checklist.md)
3. 🔧 Configure Apache and MySQL
4. 🔒 Install SSL certificate
5. 🚀 Launch your application!

---

**Need Help?**

- Check [Deployment Guide](deployment.md) for full instructions
- Review [Deployment Checklist](deployment-checklist.md) for step tracking
- Consult Termius documentation: https://termius.com/help
