#!/bin/bash
# deploy-prepare.sh
# Prepares the application for deployment by checking requirements and creating necessary files

set -e

echo "========================================="
echo "S&S LTD - Deployment Preparation Script"
echo "========================================="
echo ""

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

echo "Project root: $PROJECT_ROOT"
echo ""

# Check if running as root (not recommended for local prep)
if [[ $EUID -eq 0 ]]; then
   echo -e "${YELLOW}Warning: Running as root. This script is meant for local preparation.${NC}"
   echo "For server deployment, see docs/deployment.md"
   echo ""
fi

# Function to check command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

echo "Checking system requirements..."
echo "--------------------------------"

# Check PHP
if command_exists php; then
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    echo -e "${GREEN}✓${NC} PHP found: $PHP_VERSION"
    
    # Check PHP version
    if php -r 'exit(version_compare(PHP_VERSION, "8.1.0", ">=") ? 0 : 1);'; then
        echo -e "${GREEN}  PHP version is 8.1.0 or higher${NC}"
    else
        echo -e "${RED}  ✗ PHP 8.1.0 or higher required${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ PHP not found${NC}"
    exit 1
fi

# Check required PHP extensions
echo ""
echo "Checking PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "json" "curl" "fileinfo" "openssl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^${ext}$"; then
        echo -e "${GREEN}✓${NC} $ext"
    else
        echo -e "${RED}✗${NC} $ext (missing)"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo ""
    echo -e "${RED}Missing PHP extensions: ${MISSING_EXTENSIONS[*]}${NC}"
    echo "Install them before deploying."
    exit 1
fi

echo ""
echo "Checking project structure..."
echo "--------------------------------"

# Check important directories exist
REQUIRED_DIRS=("src" "bin" "database" "storage" "assets")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "${GREEN}✓${NC} $dir/"
    else
        echo -e "${RED}✗${NC} $dir/ (missing)"
        exit 1
    fi
done

# Check important files exist
REQUIRED_FILES=("index.php" ".htaccess" "mysql.sql")
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file (missing)"
        exit 1
    fi
done

echo ""
echo "Checking storage directories..."
echo "--------------------------------"

# Create storage directories if they don't exist
mkdir -p storage/logs
mkdir -p storage/tmp
mkdir -p storage/uploads

echo -e "${GREEN}✓${NC} storage/logs/"
echo -e "${GREEN}✓${NC} storage/tmp/"
echo -e "${GREEN}✓${NC} storage/uploads/"

# Check .env file
echo ""
echo "Checking environment configuration..."
echo "--------------------------------"

if [ -f ".env" ]; then
    echo -e "${YELLOW}!${NC} .env file exists (will not be overwritten)"
    
    # Check for common misconfigurations
    if grep -q "APP_DEBUG=1" .env 2>/dev/null; then
        echo -e "${YELLOW}  Warning: APP_DEBUG is set to 1${NC}"
        echo -e "${YELLOW}  Set to 0 for production!${NC}"
    fi
    
    if grep -q "APP_KEY=change-me" .env 2>/dev/null; then
        echo -e "${RED}  ✗ APP_KEY is still set to default value${NC}"
        echo -e "${RED}  Generate a secure key before deployment!${NC}"
    fi
else
    if [ -f ".env.example" ]; then
        echo -e "${YELLOW}!${NC} .env file not found"
        echo "  Copy .env.example to .env and configure it"
    else
        echo -e "${RED}✗${NC} .env.example not found"
        exit 1
    fi
fi

echo ""
echo "Creating deployment package..."
echo "--------------------------------"

# Create deployment directory
DEPLOY_DIR="ss_ltd_deploy_$(date +%Y%m%d_%H%M%S)"
mkdir -p "../$DEPLOY_DIR"

echo "Copying files to ../$DEPLOY_DIR/"

# Copy files excluding development/local files
rsync -av \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/tmp/*' \
    --exclude='storage/app.db' \
    --exclude='node_modules' \
    --exclude='*.tmp' \
    --exclude='full_todos.md' \
    --exclude='full_todos.tmp' \
    --exclude='checklist_tests_todos.md' \
    --exclude='ChatGPT Image*' \
    --exclude="$DEPLOY_DIR" \
    ./ "../$DEPLOY_DIR/"

# Copy .env.production.example as a reference
if [ -f ".env.production.example" ]; then
    cp .env.production.example "../$DEPLOY_DIR/.env.production.example"
fi

echo -e "${GREEN}✓${NC} Files copied to ../$DEPLOY_DIR/"

echo ""
echo "Creating deployment archive..."
cd ..
tar -czf "${DEPLOY_DIR}.tar.gz" "$DEPLOY_DIR"
echo -e "${GREEN}✓${NC} Created ${DEPLOY_DIR}.tar.gz"

# Calculate archive size
ARCHIVE_SIZE=$(du -h "${DEPLOY_DIR}.tar.gz" | cut -f1)
echo "  Archive size: $ARCHIVE_SIZE"

echo ""
echo "========================================="
echo -e "${GREEN}Deployment package ready!${NC}"
echo "========================================="
echo ""
echo "Archive: ../${DEPLOY_DIR}.tar.gz"
echo ""
echo "Next steps:"
echo "1. Transfer ${DEPLOY_DIR}.tar.gz to your server via Termius/SFTP"
echo "2. Extract on server: tar -xzf ${DEPLOY_DIR}.tar.gz"
echo "3. Follow deployment guide: docs/deployment.md"
echo ""
echo "IMPORTANT REMINDERS:"
echo "- Create .env file on server (use .env.production.example as template)"
echo "- Set APP_DEBUG=0 for production"
echo "- Generate secure APP_KEY"
echo "- Configure database credentials"
echo "- Run migrations: php bin/migrate.php"
echo "- Set proper permissions (see deployment.md)"
echo "- Install SSL certificate"
echo "- Change default admin password after first login"
echo ""
