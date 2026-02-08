# Backups (MySQL + Uploads)

This project is designed to run on XAMPP MySQL (Windows). Backups should cover:
- Database (`ss_ltd`)
- Uploaded files (`storage/uploads/`)

## Database Backup (mysqldump)

Typical XAMPP path:
- `C:\xampp\mysql\bin\mysqldump.exe`

Backup to a timestamped file:

```bat
cd /d C:\xampp\mysql\bin
mysqldump -u root ss_ltd > C:\backups\ss_ltd_YYYYMMDD_HHMM.sql
```

If your MySQL root has a password:

```bat
cd /d C:\xampp\mysql\bin
mysqldump -u root -p ss_ltd > C:\backups\ss_ltd_YYYYMMDD_HHMM.sql
```

## Database Restore

```bat
cd /d C:\xampp\mysql\bin
mysql -u root ss_ltd < C:\backups\ss_ltd_YYYYMMDD_HHMM.sql
```

## Uploads Backup

Uploads are stored in:
- `storage/uploads/`

Backup by copying the folder (or zipping it):

```bat
robocopy C:\xampp\htdocs\ss_ltd\storage\uploads C:\backups\ss_ltd_uploads /MIR
```

## Recommended Routine (MVP)

- Daily: DB dump + uploads mirror.
- Keep at least 7 daily backups.
- Test restore monthly on a separate machine or separate MySQL schema.

