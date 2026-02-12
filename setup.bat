@echo off
setlocal enabledelayedexpansion

:: S&S LTD - XAMPP Setup Script
:: This script automates the setup process for XAMPP on Windows

echo ========================================
echo S^&S LTD - XAMPP Setup Script
echo ========================================
echo.

:: Check if we're in the correct directory
if not exist "index.php" (
    echo ERROR: This script must be run from the project root directory!
    echo Please cd to the ss_ltd directory and run setup.bat again.
    pause
    exit /b 1
)

:: Set XAMPP paths (modify if your XAMPP is installed elsewhere)
set "PHP_EXE=C:\xampp\php\php.exe"
set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"

:: Check if PHP exists
if not exist "%PHP_EXE%" (
    echo ERROR: PHP not found at %PHP_EXE%
    echo Please install XAMPP or update the PHP_EXE path in this script.
    pause
    exit /b 1
)

:: Check if MySQL exists
if not exist "%MYSQL_EXE%" (
    echo WARNING: MySQL not found at %MYSQL_EXE%
    echo Make sure MySQL is running in XAMPP Control Panel.
    echo.
)

echo [1/6] Checking environment file...
if not exist ".env" (
    if exist ".env.example" (
        echo Creating .env from .env.example...
        copy ".env.example" ".env" > nul
        echo .env created successfully!
        echo IMPORTANT: Please review and update .env with your settings.
        echo.
    ) else (
        echo ERROR: .env.example not found!
        pause
        exit /b 1
    )
) else (
    echo .env already exists, skipping...
    echo.
)

echo [2/6] Creating database...
echo Please make sure MySQL is running in XAMPP Control Panel!
echo.
choice /C YN /M "Do you want to create the database now"
if errorlevel 2 goto skip_db
if errorlevel 1 (
    echo Creating database ss_ltd...
    "%MYSQL_EXE%" -u root -e "CREATE DATABASE IF NOT EXISTS ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    if errorlevel 1 (
        echo WARNING: Failed to create database. You may need to do this manually.
        echo.
    ) else (
        echo Database created successfully!
        echo.
    )
)
:skip_db

echo [3/6] Running database migrations...
"%PHP_EXE%" bin\migrate.php
if errorlevel 1 (
    echo WARNING: Migration failed. Check your database connection.
    echo.
    pause
    exit /b 1
)
echo.

echo [4/6] Checking migration status...
"%PHP_EXE%" bin\migrate_status.php
echo.

echo [5/6] Seeding database with demo data...
choice /C YN /M "Do you want to seed the database with demo accounts"
if errorlevel 2 goto skip_seed
if errorlevel 1 (
    "%PHP_EXE%" bin\seed.php
    if errorlevel 1 (
        echo WARNING: Seeding failed.
        echo.
    ) else (
        echo.
        echo ========================================
        echo Demo Accounts Created:
        echo ========================================
        echo Admin:        admin@ss.local / Admin123!
        echo PM:           pm@ss.local / Pm123456!
        echo Client:       client@ss.local / Client123!
        echo Employee:     employee@ss.local / Employee123!
        echo Subcontractor: sub@ss.local / Sub123456!
        echo Sub Worker:   subworker@ss.local / Worker123!
        echo ========================================
        echo.
    )
)
:skip_seed

echo [6/6] Testing database connection...
"%PHP_EXE%" bin\health_db.php
echo.

echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Make sure Apache is running in XAMPP Control Panel
echo 2. Open your browser to: http://localhost/ss_ltd/
echo 3. Login with one of the demo accounts above
echo.
echo Alternative: Run with PHP built-in server:
echo    %PHP_EXE% -S 127.0.0.1:8000 index.php
echo    Then open: http://127.0.0.1:8000
echo.
echo For more information, see README.md
echo.

choice /C YN /M "Do you want to start the PHP development server now"
if errorlevel 2 goto end
if errorlevel 1 (
    echo.
    echo Starting PHP development server...
    echo Press Ctrl+C to stop the server.
    echo.
    "%PHP_EXE%" -S 127.0.0.1:8000 index.php
)

:end
pause
