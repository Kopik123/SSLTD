#!/bin/bash
set -e

echo "========================================"
echo "SSLTD Quick Start Setup"
echo "========================================"
echo ""

# Check PHP version
echo "Checking PHP version..."
php_version=$(php -r 'echo PHP_VERSION;')
echo "✓ PHP $php_version detected"

# Check if .env exists
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env from .env.example..."
    cp .env.example .env
    echo "✓ .env created"
    echo ""
    echo "⚠️  Please edit .env with your database credentials:"
    echo "   nano .env"
    echo ""
    read -p "Press Enter when .env is configured..."
fi

# Check database connection
echo ""
echo "Checking database connection..."
if php bin/health_db.php > /dev/null 2>&1; then
    echo "✓ Database connection successful"
else
    echo "✗ Database connection failed"
    echo ""
    echo "Please ensure:"
    echo "  1. MySQL/MariaDB is running"
    echo "  2. Database exists: CREATE DATABASE ss_ltd;"
    echo "  3. .env has correct credentials"
    echo ""
    read -p "Press Enter to continue anyway or Ctrl+C to exit..."
fi

# Run migrations
echo ""
echo "Running database migrations..."
if php bin/migrate.php; then
    echo "✓ Migrations completed"
else
    echo "✗ Migration failed"
    exit 1
fi

# Seed data
echo ""
read -p "Seed demo data with test accounts? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Seeding database..."
    php bin/seed.php
    echo "✓ Demo data seeded"
    echo ""
    echo "Test accounts created:"
    echo "  • admin@ss.local / Admin123!"
    echo "  • pm@ss.local / Pm123456!"
    echo "  • client@ss.local / Client123!"
fi

# Start server
echo ""
echo "========================================"
echo "Setup complete!"
echo "========================================"
echo ""
echo "Start the development server with:"
echo "  php -S 127.0.0.1:8000 index.php"
echo ""
echo "Or using Docker:"
echo "  docker-compose up -d"
echo ""
read -p "Start built-in server now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Starting server at http://127.0.0.1:8000"
    echo "Press Ctrl+C to stop"
    echo ""
    php -S 127.0.0.1:8000 index.php
fi
