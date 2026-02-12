# S&S LTD - Web Portal & Field App

System zarządzania projektami dla S&S LTD z portalem webowym i aplikacją mobilną Android dla pracowników terenowych.

[🇬🇧 English](#english) | [🇵🇱 Polski](#polish)

---

## <a name="polish"></a>🇵🇱 Polski

### 📋 Opis Projektu

S&S LTD to kompleksowy system zarządzania projektami budowlanymi, który składa się z:
- **Portal Web** - zarządzanie projektami, leadami, użytkownikami i dokumentacją
- **Aplikacja Android** - aplikacja terenowa dla pracowników (offline-first)

System obsługuje różne role użytkowników:
- **Admin** - pełny dostęp do systemu
- **PM (Project Manager)** - zarządzanie projektami i zespołami
- **Client** - dostęp do własnych projektów
- **Employee** - pracownicy terenowi
- **Subcontractor** - podwykonawcy i ich pracownicy

### 🎯 Środowisko - XAMPP czy inne?

**Zalecane środowisko: XAMPP (Windows)**

Projekt został zaprojektowany z myślą o XAMPP na Windows i jest to **najlepszy wybór** ponieważ:
- ✅ Prosta instalacja i konfiguracja
- ✅ Zawiera wszystkie potrzebne komponenty (Apache, MySQL, PHP)
- ✅ Łatwe zarządzanie przez panel kontrolny
- ✅ Idealny do rozwoju i testowania
- ✅ Projekt zawiera gotowe skrypty dla XAMPP

**Alternatywy:**
- **WAMP** - alternatywa dla XAMPP (Windows)
- **Docker** - dla bardziej zaawansowanych użytkowników
- **Linux + LAMP** - dla serwerów produkcyjnych

### 📦 Wymagania

- **XAMPP** (lub WAMP) z:
  - PHP 7.4 lub nowszy
  - MySQL 5.7 lub nowszy / MariaDB
  - Apache
- **Git** (opcjonalnie, do pobrania kodu)
- **Android Studio** (tylko jeśli chcesz budować aplikację Android)

### 🚀 Szybki Start (XAMPP)

#### 1. Zainstaluj XAMPP

Pobierz i zainstaluj XAMPP z [https://www.apachefriends.org/](https://www.apachefriends.org/)

#### 2. Skopiuj Projekt

Skopiuj folder projektu do katalogu XAMPP:
```
C:\xampp\htdocs\ss_ltd\
```

**Ważne:** Możesz utworzyć junction (dowiązanie symboliczne) jeśli nazwa folderu zawiera znaki specjalne:
```cmd
mklink /J "C:\xampp\htdocs\ss_ltd" "C:\xampp\htdocs\S&S LTD"
```

#### 3. Konfiguracja Bazy Danych

**Opcja A - Użyj phpMyAdmin:**
1. Uruchom XAMPP Control Panel
2. Wystartuj Apache i MySQL
3. Otwórz [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
4. Utwórz nową bazę danych o nazwie: `ss_ltd`
5. Możesz zaimportować `mysql.sql` (opcjonalnie - migracje zrobią to automatycznie)

**Opcja B - Wiersz poleceń:**
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

#### 4. Konfiguracja Środowiska

Skopiuj plik `.env.example` jako `.env`:
```cmd
cd C:\xampp\htdocs\ss_ltd
copy .env.example .env
```

Edytuj `.env` i dostosuj ustawienia (domyślne wartości powinny działać dla standardowej instalacji XAMPP):
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ss_ltd
DB_USER=root
DB_PASS=
```

#### 5. Uruchom Migracje i Seed

```cmd
cd C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe bin\migrate.php
C:\xampp\php\php.exe bin\seed.php
```

#### 6. Uruchom Aplikację

**Opcja A - XAMPP Apache (zalecane):**
1. Upewnij się, że Apache jest uruchomiony w XAMPP Control Panel
2. Otwórz przeglądarkę: [http://localhost/ss_ltd/](http://localhost/ss_ltd/)

**Opcja B - Wbudowany serwer PHP:**
```cmd
cd C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php
```
Następnie otwórz: [http://127.0.0.1:8000](http://127.0.0.1:8000)

#### 7. Zaloguj się

Domyślne konta testowe (utworzone przez `bin\seed.php`):

| Rola | Email | Hasło |
|------|-------|-------|
| Admin | admin@ss.local | Admin123! |
| PM | pm@ss.local | Pm123456! |
| Client | client@ss.local | Client123! |
| Employee | employee@ss.local | Employee123! |
| Subcontractor | sub@ss.local | Sub123456! |
| Sub Worker | subworker@ss.local | Worker123! |

### 🔧 Narzędzia Pomocnicze

```cmd
# Sprawdź status migracji
C:\xampp\php\php.exe bin\migrate_status.php

# Test połączenia z bazą danych
C:\xampp\php\php.exe bin\health_db.php

# Utwórz nowego administratora
C:\xampp\php\php.exe bin\create_admin_user.php

# Sprawdź składnię PHP
C:\xampp\php\php.exe bin\php_lint.php
```

### 📱 Aplikacja Android

Instrukcje budowania aplikacji Android znajdują się w `android/README.md`.

### 📚 Dodatkowa Dokumentacja

- **[docs/QUICKSTART_PL.md](docs/QUICKSTART_PL.md)** - 🇵🇱 Szybki start po polsku
- **[docs/ENVIRONMENT_COMPARISON.md](docs/ENVIRONMENT_COMPARISON.md)** - Porównanie środowisk (XAMPP vs inne)
- **[docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)** - Rozwiązywanie problemów XAMPP
- **[docs/setup.md](docs/setup.md)** - Szczegółowe instrukcje instalacji
- `docs/manual_test_checklist.md` - Lista testów manualnych
- `docs/background_jobs.md` - Zadania w tle
- `docs/backups.md` - Tworzenie kopii zapasowych
- `AGENTS.md` - Notatki architektoniczne dla developerów

### 🔒 Bezpieczeństwo

- Hasła są hashowane używając `password_hash()` (bcrypt)
- Sesje używają HttpOnly cookies
- Wszystkie formularze wymagają tokenów CSRF
- Przesyłane pliki są walidowane i przechowywane poza katalogiem web
- SQL: tylko prepared statements (PDO)

### 🐛 Rozwiązywanie Problemów

**Błąd połączenia z bazą danych:**
- Upewnij się, że MySQL jest uruchomiony w XAMPP Control Panel
- Sprawdź dane w pliku `.env`
- Sprawdź czy baza `ss_ltd` została utworzona

**Błąd 404 / strona nie ładuje się:**
- Sprawdź czy Apache jest uruchomiony
- Sprawdź czy ścieżka jest poprawna: `http://localhost/ss_ltd/`
- Sprawdź plik `.htaccess` w katalogu głównym

**Problemy z uprawnieniami do plików:**
- Upewnij się, że folder `storage/` ma prawa zapisu
- Na Windowsie zwykle nie jest to problem

**Więcej rozwiązań:** Zobacz [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) dla pełnego przewodnika rozwiązywania problemów

---

## <a name="english"></a>🇬🇧 English

### 📋 Project Description

S&S LTD is a comprehensive construction project management system consisting of:
- **Web Portal** - project, lead, user, and documentation management
- **Android App** - field application for employees (offline-first)

The system supports various user roles:
- **Admin** - full system access
- **PM (Project Manager)** - project and team management
- **Client** - access to own projects
- **Employee** - field workers
- **Subcontractor** - subcontractors and their workers

### 🎯 Environment - XAMPP or Other?

**Recommended Environment: XAMPP (Windows)**

The project was designed for XAMPP on Windows and this is the **best choice** because:
- ✅ Simple installation and configuration
- ✅ Includes all necessary components (Apache, MySQL, PHP)
- ✅ Easy management through control panel
- ✅ Perfect for development and testing
- ✅ Project includes ready-made scripts for XAMPP

**Alternatives:**
- **WAMP** - alternative to XAMPP (Windows)
- **Docker** - for more advanced users
- **Linux + LAMP** - for production servers

### 📦 Requirements

- **XAMPP** (or WAMP) with:
  - PHP 7.4 or newer
  - MySQL 5.7 or newer / MariaDB
  - Apache
- **Git** (optional, for code download)
- **Android Studio** (only if you want to build the Android app)

### 🚀 Quick Start (XAMPP)

#### 1. Install XAMPP

Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)

#### 2. Copy Project

Copy the project folder to XAMPP directory:
```
C:\xampp\htdocs\ss_ltd\
```

**Important:** You can create a junction (symbolic link) if the folder name contains special characters:
```cmd
mklink /J "C:\xampp\htdocs\ss_ltd" "C:\xampp\htdocs\S&S LTD"
```

#### 3. Database Configuration

**Option A - Use phpMyAdmin:**
1. Launch XAMPP Control Panel
2. Start Apache and MySQL
3. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
4. Create a new database named: `ss_ltd`
5. You can import `mysql.sql` (optional - migrations will do this automatically)

**Option B - Command Line:**
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

#### 4. Environment Configuration

Copy the `.env.example` file as `.env`:
```cmd
cd C:\xampp\htdocs\ss_ltd
copy .env.example .env
```

Edit `.env` and adjust settings (default values should work for standard XAMPP installation):
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ss_ltd
DB_USER=root
DB_PASS=
```

#### 5. Run Migrations and Seed

```cmd
cd C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe bin\migrate.php
C:\xampp\php\php.exe bin\seed.php
```

#### 6. Run the Application

**Option A - XAMPP Apache (recommended):**
1. Make sure Apache is running in XAMPP Control Panel
2. Open browser: [http://localhost/ss_ltd/](http://localhost/ss_ltd/)

**Option B - PHP Built-in Server:**
```cmd
cd C:\xampp\htdocs\ss_ltd
C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php
```
Then open: [http://127.0.0.1:8000](http://127.0.0.1:8000)

#### 7. Login

Default test accounts (created by `bin\seed.php`):

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ss.local | Admin123! |
| PM | pm@ss.local | Pm123456! |
| Client | client@ss.local | Client123! |
| Employee | employee@ss.local | Employee123! |
| Subcontractor | sub@ss.local | Sub123456! |
| Sub Worker | subworker@ss.local | Worker123! |

### 🔧 Utility Tools

```cmd
# Check migration status
C:\xampp\php\php.exe bin\migrate_status.php

# Test database connection
C:\xampp\php\php.exe bin\health_db.php

# Create new admin user
C:\xampp\php\php.exe bin\create_admin_user.php

# Check PHP syntax
C:\xampp\php\php.exe bin\php_lint.php
```

### 📱 Android Application

Instructions for building the Android app are in `android/README.md`.

### 📚 Additional Documentation

- **[docs/QUICKSTART_PL.md](docs/QUICKSTART_PL.md)** - 🇵🇱 Quick start in Polish
- **[docs/ENVIRONMENT_COMPARISON.md](docs/ENVIRONMENT_COMPARISON.md)** - Environment comparison (XAMPP vs others)
- **[docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)** - XAMPP troubleshooting guide
- **[docs/setup.md](docs/setup.md)** - Detailed installation instructions
- `docs/manual_test_checklist.md` - Manual testing checklist
- `docs/background_jobs.md` - Background jobs
- `docs/backups.md` - Creating backups
- `AGENTS.md` - Architectural notes for developers

### 🔒 Security

- Passwords are hashed using `password_hash()` (bcrypt)
- Sessions use HttpOnly cookies
- All forms require CSRF tokens
- Uploaded files are validated and stored outside web directory
- SQL: prepared statements only (PDO)

### 🐛 Troubleshooting

**Database connection error:**
- Make sure MySQL is running in XAMPP Control Panel
- Check credentials in `.env` file
- Check if `ss_ltd` database was created

**404 error / page not loading:**
- Check if Apache is running
- Check if path is correct: `http://localhost/ss_ltd/`
- Check `.htaccess` file in root directory

**File permission issues:**
- Make sure `storage/` folder has write permissions
- On Windows, this is usually not an issue

**More solutions:** See [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) for complete troubleshooting guide

---

### 📄 License

Copyright © 2026 S&S LTD. All rights reserved.
