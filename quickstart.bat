@echo off
REM Quick Start Script for Windows
setlocal enabledelayedexpansion

echo ========================================
echo SSLTD Quick Start Setup (Windows)
echo ========================================
echo.

REM Check PHP
echo Checking PHP installation...
php --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found in PATH
    echo Please install XAMPP and add PHP to PATH
    echo Example: C:\xampp\php\php.exe
    pause
    exit /b 1
)
echo [OK] PHP detected
echo.

REM Check .env
if not exist .env (
    echo Creating .env from .env.example...
    copy .env.example .env
    echo [OK] .env created
    echo.
    echo [WARNING] Please edit .env with your database credentials
    echo   Example: notepad .env
    echo.
    pause
)

REM Check database
echo Checking database connection...
php bin\health_db.php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Database connection failed
    echo.
    echo Please ensure:
    echo   1. XAMPP MySQL is running
    echo   2. Database exists in phpMyAdmin
    echo   3. .env has correct credentials
    echo.
    pause
)
echo [OK] Database connection successful
echo.

REM Run migrations
echo Running database migrations...
php bin\migrate.php
if errorlevel 1 (
    echo [ERROR] Migration failed
    pause
    exit /b 1
)
echo [OK] Migrations completed
echo.

REM Seed data
set /p seed="Seed demo data with test accounts? (y/n): "
if /i "%seed%"=="y" (
    echo Seeding database...
    php bin\seed.php
    echo [OK] Demo data seeded
    echo.
    echo Test accounts created:
    echo   - admin@ss.local / Admin123!
    echo   - pm@ss.local / Pm123456!
    echo   - client@ss.local / Client123!
    echo.
)

echo ========================================
echo Setup complete!
echo ========================================
echo.
echo Start the development server with:
echo   php -S 127.0.0.1:8000 index.php
echo.
echo Or using XAMPP:
echo   Place in C:\xampp\htdocs\ss_ltd
echo   Open http://localhost/ss_ltd
echo.

set /p start="Start built-in server now? (y/n): "
if /i "%start%"=="y" (
    echo.
    echo Starting server at http://127.0.0.1:8000
    echo Press Ctrl+C to stop
    echo.
    php -S 127.0.0.1:8000 index.php
)
