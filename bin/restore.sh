#!/bin/bash
# restore.sh
# Restores database and uploads from backup

set -e

if [ $# -lt 1 ]; then
    echo "Usage: $0 <backup_timestamp>"
    echo ""
    echo "Example: $0 20260213_140530"
    echo ""
    echo "Available backups:"
    ls -1 /root/backups/ss_ltd/database_*.sql.gz 2>/dev/null | sed 's/.*database_\(.*\)\.sql\.gz/  \1/' || echo "  No backups found"
    exit 1
fi

TIMESTAMP=$1
BACKUP_DIR="${BACKUP_DIR:-/root/backups/ss_ltd}"

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

echo "========================================="
echo "S&S LTD - Restore Script"
echo "========================================="
echo "Timestamp: $TIMESTAMP"
echo "Backup directory: $BACKUP_DIR"
echo ""

# Confirm restoration
echo "⚠️  WARNING: This will replace your current data!"
echo ""
read -p "Are you sure you want to restore from backup $TIMESTAMP? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# Restore database
DATABASE_BACKUP="$BACKUP_DIR/database_${TIMESTAMP}.sql.gz"

if [ -f "$DATABASE_BACKUP" ]; then
    echo ""
    echo "Restoring database from $DATABASE_BACKUP..."
    
    if [ "$DB_CONNECTION" = "mysql" ]; then
        MYSQL_HOST="${DB_HOST:-localhost}"
        MYSQL_PORT="${DB_PORT:-3306}"
        MYSQL_USER="${DB_USER:-root}"
        MYSQL_DB="${DB_NAME:-ss_ltd}"
        
        if [ -n "$DB_PASS" ]; then
            gunzip < "$DATABASE_BACKUP" | mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$DB_PASS" "$MYSQL_DB"
        else
            gunzip < "$DATABASE_BACKUP" | mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" "$MYSQL_DB"
        fi
        
        echo "✓ Database restored successfully"
    else
        echo "✗ Only MySQL restoration is supported by this script"
    fi
else
    echo "✗ Database backup not found: $DATABASE_BACKUP"
fi

# Restore uploads
UPLOADS_BACKUP="$BACKUP_DIR/uploads_${TIMESTAMP}.tar.gz"

if [ -f "$UPLOADS_BACKUP" ]; then
    echo ""
    echo "Restoring uploads from $UPLOADS_BACKUP..."
    
    # Backup current uploads first
    if [ -d "storage/uploads" ] && [ "$(ls -A storage/uploads)" ]; then
        CURRENT_BACKUP="storage/uploads.before_restore_$(date +%Y%m%d_%H%M%S)"
        mv storage/uploads "$CURRENT_BACKUP"
        echo "  Current uploads moved to: $CURRENT_BACKUP"
    fi
    
    tar -xzf "$UPLOADS_BACKUP"
    echo "✓ Uploads restored successfully"
else
    echo ""
    echo "⊘ No uploads backup found: $UPLOADS_BACKUP"
fi

echo ""
echo "========================================="
echo "Restore completed!"
echo "========================================="
