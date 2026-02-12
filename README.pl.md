# S&S LTD - Portal Internetowy & System Zarządzania Pracą Terenową

Kompleksowy portal internetowy i aplikacja Android do zarządzania projektami budowlanymi, zaprojektowany dla luksusowych projektów mieszkaniowych w obszarze Manchester (MCR).

## 🚀 Funkcje

### Portal Internetowy (Zarządzanie Biurowe)
- **Strona Publiczna**: Strony marketingowe, katalog usług, system zapytań ofertowych
- **Zarządzanie Leadami**: Śledzenie zapytań, konwersja do projektów, generowanie ofert
- **Zarządzanie Projektami**: Śledzenie statusu, przypisywanie zespołów, zarządzanie harmonogramem
- **System Wiadomości**: Konwersacje wątkowe dla leadów i projektów
- **Zarządzanie Plikami**: Bezpieczne przechowywanie i udostępnianie dokumentów
- **Śledzenie Czasu**: Zarządzanie kartami czasu pracy i raportowanie
- **Zarządzanie Użytkownikami**: Kontrola dostępu oparta na rolach (Admin, PM, Klient, Pracownik, Podwykonawca)

### Aplikacja Android (Praca Terenowa)
- **Widok Dziś**: Szybkie śledzenie czasu (start/stop)
- **Dostęp do Projektów**: Przeglądaj przypisane projekty i szczegóły
- **Robienie Zdjęć**: Zdjęcia z placu budowy z kolejką offline
- **Wiadomości**: Komunikacja w czasie rzeczywistym z biurem
- **Wsparcie Offline**: Praca bez łączności, synchronizacja po połączeniu

## 📋 Wymagania

### Rozwój Lokalny
- **PHP**: 8.0 lub wyższy (zalecane 8.3+)
- **Baza Danych**: MySQL 5.7+ lub MariaDB 10.3+
- **Serwer WWW**: Apache 2.4+ lub wbudowany serwer PHP
- **Rozszerzenia**: PDO, PDO_MySQL (lub PDO_SQLite dla dev)

### Opcjonalnie
- **Docker**: Do rozwoju w kontenerach (zalecane)
- **XAMPP**: Do rozwoju na Windows (testowana konfiguracja)

## 🛠️ Szybki Start

### Opcja 1: Docker (Zalecane)

```bash
# Sklonuj repozytorium
git clone https://github.com/Kopik123/SSLTD.git
cd SSLTD

# Skopiuj plik środowiskowy
cp .env.example .env

# Uruchom z Docker Compose
docker-compose up -d

# Wykonaj migracje
docker-compose exec app php bin/migrate.php

# Załaduj dane demo
docker-compose exec app php bin/seed.php

# Otwórz w przeglądarce
open http://localhost:8000
```

### Opcja 2: Lokalne PHP

```bash
# Sklonuj repozytorium
git clone https://github.com/Kopik123/SSLTD.git
cd SSLTD

# Skopiuj plik środowiskowy
cp .env.example .env

# Edytuj .env z danymi bazy danych
nano .env

# Utwórz bazę danych
mysql -u root -p -e "CREATE DATABASE ss_ltd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importuj schemat
mysql -u root -p ss_ltd < mysql.sql

# Wykonaj migracje
php bin/migrate.php

# Załaduj dane demo
php bin/seed.php

# Uruchom wbudowany serwer PHP
php -S 127.0.0.1:8000 index.php

# Otwórz w przeglądarce
open http://127.0.0.1:8000
```

### Opcja 3: XAMPP (Windows)

Zobacz szczegółowe instrukcje w [docs/setup.md](docs/setup.md)

## 🔐 Domyślne Konta

Po uruchomieniu `bin/seed.php`, dostępne są testowe konta:

| Rola | Email | Hasło |
|------|-------|-------|
| Administrator | admin@ss.local | Admin123! |
| Project Manager | pm@ss.local | Pm123456! |
| Klient | client@ss.local | Client123! |
| Pracownik | employee@ss.local | Employee123! |
| Podwykonawca | sub@ss.local | Sub123456! |
| Pracownik Podwykonawcy | subworker@ss.local | Worker123! |

⚠️ **Zmień te hasła w produkcji!**

## 📚 Dokumentacja

- [Przewodnik Konfiguracji](docs/setup.md) - Szczegółowe instrukcje
- [Lista Kontrolna Testów Manualnych](docs/manual_test_checklist.md) - Procedury QA
- [Zadania w Tle](docs/background_jobs.md) - Obsługa zadań asynchronicznych
- [Strategia Backupów](docs/backups.md) - Ochrona danych
- [Rozwiązywanie Konfliktów](docs/conflict_strategy.md) - Workflow zespołu

## 🚢 Wdrożenie

### Tradycyjny Hosting (VPS, Hosting Współdzielony)

1. **Prześlij Pliki**: Wszystkie pliki oprócz `.git/`, `.env*`, `android/`
2. **Skonfiguruj Środowisko**: Utwórz `.env` z `.env.production.example`
3. **Skonfiguruj Bazę Danych**: Importuj `mysql.sql`, wykonaj migracje
4. **Skonfiguruj Serwer WWW**: Ustaw document root na katalog projektu, upewnij się że `.htaccess` działa
5. **Ustaw Uprawnienia**: `storage/` i podkatalogi powinny być zapisywalne
6. **Bezpieczeństwo**: Upewnij się że `.env`, `storage/`, `src/`, `bin/`, `database/` nie są dostępne przez WWW

Zobacz [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) dla kompletnej listy kontrolnej.

### Vercel / Serverless

⚠️ **Uwaga**: Ten projekt jest zaprojektowany dla tradycyjnego hostingu PHP. Wdrożenie na Vercel wymaga znaczących modyfikacji.

Zobacz [DEPLOYMENT_VERCEL.md](DEPLOYMENT_VERCEL.md) dla szczegółowej konfiguracji Vercel.

### Zalecani Dostawcy Hostingu

Dla najłatwiejszego wdrożenia:
- **DigitalOcean App Platform** (wsparcie PHP)
- **Heroku** (z buildpack PHP)
- **AWS Lightsail** (stack LAMP)
- **Tradycyjny VPS** (Ubuntu + Apache/Nginx + MySQL)

## 🔧 Konfiguracja

### Zmienne Środowiskowe

Kluczowe zmienne środowiskowe (zobacz `.env.example` dla pełnej listy):

```bash
APP_ENV=production          # Środowisko: dev, staging, production
APP_DEBUG=0                 # Tryb debug: 0 = wyłączony, 1 = włączony
APP_URL=https://twoja-domena.com
APP_KEY=losowy-klucz-tajny  # Wygeneruj losowy ciąg (32+ znaków)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ss_ltd
DB_USER=twoj_uzytkownik_db
DB_PASS=twoje_haslo_db

SERVICE_AREA_RADIUS_MILES=60
```

### Lista Kontrolna Bezpieczeństwa

Przed uruchomieniem:

- [ ] Zmień `APP_KEY` na losowy ciąg
- [ ] Ustaw `APP_DEBUG=0`
- [ ] Ustaw `APP_ENV=production`
- [ ] Zmień wszystkie domyślne hasła
- [ ] Skonfiguruj SSL/TLS (HTTPS)
- [ ] Ogranicz dostęp do bazy danych
- [ ] Włącz firewall
- [ ] Skonfiguruj backupy
- [ ] Skonfiguruj limity przesyłania plików
- [ ] Przejrzyj reguły bezpieczeństwa `.htaccess`

## 🧪 Rozwój

### Narzędzia Deweloperskie (Tryb Debug)

Gdy `APP_DEBUG=1`, dostępne są dodatkowe narzędzia:
- **Zakładka Logów**: Logi serwera w czasie rzeczywistym
- **Zakładka Narzędzi**: Szybkie przełączanie użytkowników, reset limitów, autouzupełnianie testowe

Dostęp do endpointów dev pod `/app/dev/*` (domyślnie tylko localhost)

### Uruchamianie Testów

```bash
# Sprawdzenie składni PHP
php bin/php_lint.php

# Sprawdzenie zdrowia bazy danych
php bin/health_db.php

# Status migracji
php bin/migrate_status.php

# Test HTTP
php bin/smoke_http.php
```

## 🤝 Współpraca

To jest projekt prywatny. Dla członków zespołu wewnętrznego:

1. Utwórz branch funkcjonalności z `main`
2. Wprowadź zmiany z opisowymi commitami
3. Przetestuj dokładnie (zobacz `docs/manual_test_checklist.md`)
4. Wyślij pull request
5. Poczekaj na code review

## 📄 Licencja

Własnościowy - S&S LTD. Wszelkie prawa zastrzeżone.

## 🆘 Wsparcie

W przypadku problemów lub pytań:
- Sprawdź dokumentację w `docs/`
- Przejrzyj `full_todos.md` dla znanych problemów
- Skontaktuj się z opiekunem projektu

---

**Status Projektu**: Aktywny Rozwój (Faza MVP)  
**Ostatnia Aktualizacja**: Luty 2026

---

## English Version

For English documentation, see [README.md](README.md) (main file).
