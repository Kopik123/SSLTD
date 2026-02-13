#!/bin/bash
# backup.sh
# Creates backup of database and uploaded files

set -e

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/root/backups/ss_ltd}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "========================================="
echo "S&S LTD - Backup Script"
echo "========================================="
echo "Timestamp: $TIMESTAMP"
echo "Backup directory: $BACKUP_DIR"
echo ""

# Backup database
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Backing up MySQL database..."
    
    BACKUP_FILE="$BACKUP_DIR/database_$TIMESTAMP.sql"
    
    # Build mysqldump command
    MYSQL_HOST="${DB_HOST:-localhost}"
    MYSQL_PORT="${DB_PORT:-3306}"
    MYSQL_USER="${DB_USER:-root}"
    MYSQL_DB="${DB_NAME:-ss_ltd}"
    
    if [ -n "$DB_PASS" ]; then
        mysqldump -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$DB_PASS" "$MYSQL_DB" > "$BACKUP_FILE"
    else
        mysqldump -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" "$MYSQL_DB" > "$BACKUP_FILE"
    fi
    
    gzip "$BACKUP_FILE"
    echo "✓ Database backed up to: ${BACKUP_FILE}.gz"
    
elif [ "$DB_CONNECTION" = "sqlite" ]; then
    echo "Backing up SQLite database..."
    
    if [ -n "$DB_DATABASE" ] && [ -f "$DB_DATABASE" ]; then
        BACKUP_FILE="$BACKUP_DIR/database_$TIMESTAMP.db"
        cp "$DB_DATABASE" "$BACKUP_FILE"
        gzip "$BACKUP_FILE"
        echo "✓ Database backed up to: ${BACKUP_FILE}.gz"
    else
        echo "✗ SQLite database file not found: $DB_DATABASE"
    fi
else
    echo "✗ Unknown database connection type: $DB_CONNECTION"
fi

# Backup uploads directory
if [ -d "storage/uploads" ] && [ "$(ls -A storage/uploads)" ]; then
    echo ""
    echo "Backing up uploaded files..."
    
    UPLOADS_BACKUP="$BACKUP_DIR/uploads_$TIMESTAMP.tar.gz"
    tar -czf "$UPLOADS_BACKUP" storage/uploads/
    echo "✓ Uploads backed up to: $UPLOADS_BACKUP"
else
    echo ""
    echo "⊘ No uploads to backup"
fi

# Backup .env file
if [ -f ".env" ]; then
    echo ""
    echo "Backing up .env configuration..."
    
    ENV_BACKUP="$BACKUP_DIR/env_$TIMESTAMP.backup"
    cp .env "$ENV_BACKUP"
    chmod 600 "$ENV_BACKUP"
    echo "✓ .env backed up to: $ENV_BACKUP"
fi

# Clean old backups
echo ""
echo "Cleaning old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "database_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "uploads_*.tar.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "env_*.backup" -mtime +$RETENTION_DAYS -delete
echo "✓ Old backups cleaned"

# Show backup directory size
echo ""
echo "========================================="
echo "Backup Summary"
echo "========================================="
du -sh "$BACKUP_DIR"
echo ""
echo "Recent backups:"
ls -lh "$BACKUP_DIR" | tail -10
echo ""
echo "Backup completed successfully!"
