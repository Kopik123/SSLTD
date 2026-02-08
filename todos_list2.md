# SSLTD - Lista Zadań do Wykonania (todos_list2)

**Data utworzenia**: 2026-02-08  
**Źródło**: docs/project_analysis.md  
**Status**: W trakcie realizacji

---

## 1. BŁĘDY I PROBLEMY - KRYTYCZNE (Priorytet 1)

### 1.1 Brak Testów Jednostkowych
- [x] Zainstalować PHPUnit (`composer require --dev phpunit/phpunit`) - częściowo (wymaga access do GitHub)
- [x] Utworzyć katalog `tests/Unit/`
- [x] Utworzyć katalog `tests/Integration/`
- [x] Napisać test dla ErrorHandler
- [ ] Napisać test dla User model
- [ ] Napisać test dla AuthController
- [ ] Napisać test dla CSRF middleware
- [ ] Napisać test dla Database class
- [x] Skonfigurować phpunit.xml
- [x] Utworzyć tests/bootstrap.php
- [ ] Dodać coverage reporting - wymaga pełnej instalacji PHPUnit

### 1.2 Brak CI/CD Pipeline
- [x] Utworzyć `.github/workflows/ci.yml`
- [x] Skonfigurować GitHub Actions dla PHP linting
- [x] Skonfigurować GitHub Actions dla testów PHPUnit
- [x] Dodać badge statusu CI do README
- [ ] Skonfigurować automatyczne deployment (opcjonalnie)

### 1.3 Brak Centralnego Logowania Błędów
- [x] Utworzyć klasę `src/ErrorHandler.php`
- [x] Zaimplementować `set_error_handler()`
- [x] Zaimplementować `set_exception_handler()`
- [x] Zaimplementować `register_shutdown_function()`
- [x] Dodać strukturyzowane logowanie JSON
- [x] Utworzyć katalog `storage/logs/` (już istnieje)
- [x] Dodać rotację logów
- [ ] Zintegrować z Sentry (opcjonalnie)

---

## 2. BŁĘDY I PROBLEMY - WYSOKIE (Priorytet 2)

### 2.1 Brak Walidacji Środowiska Produkcyjnego
- [ ] Utworzyć `bin/validate_production.php`
- [ ] Sprawdzać `APP_DEBUG=0`
- [ ] Sprawdzać `APP_ENV=prod`
- [ ] Sprawdzać czy `APP_KEY` jest ustawiony
- [ ] Weryfikować uprawnienia katalogów (storage/, uploads/)
- [ ] Testować połączenie z bazą danych
- [ ] Sprawdzać wymagane rozszerzenia PHP

### 2.2 Brak Dokumentacji API
- [ ] Utworzyć `docs/api-spec.yaml` (OpenAPI 3.0)
- [ ] Udokumentować wszystkie endpointy `/api/auth/*`
- [ ] Udokumentować endpointy `/api/projects/*`
- [ ] Udokumentować endpointy `/api/threads/*`
- [ ] Udokumentować endpointy `/api/uploads`
- [ ] Udokumentować endpointy `/api/timesheets/*`
- [ ] Dodać przykłady request/response
- [ ] Udokumentować kody błędów

### 2.3 Rate Limiting na Krytycznych Endpointach
- [ ] Sprawdzić pokrycie rate limiting dla `/api/auth/login`
- [ ] Sprawdzić pokrycie rate limiting dla `/api/auth/register`
- [ ] Dodać rate limiting do formularzy kontaktowych
- [ ] Rozważyć CAPTCHA dla publicznych formularzy
- [ ] Udokumentować limity w API docs

---

## 3. BŁĘDY I PROBLEMY - ŚREDNIE (Priorytet 3)

### 3.1 Brak README.md w Głównym Katalogu
- [x] Utworzyć `README.md`
- [ ] Dodać opis projektu
- [ ] Dodać sekcję "Quick Start"
- [ ] Dodać linki do dokumentacji
- [ ] Dodać badges (build status, coverage)
- [ ] Dodać informacje o licencji
- [ ] Dodać sekcję "Contributing"

### 3.2 Brak CONTRIBUTING.md
- [ ] Utworzyć `CONTRIBUTING.md`
- [ ] Opisać proces zgłaszania issues
- [ ] Opisać proces tworzenia PR
- [ ] Określić coding standards
- [ ] Dodać template PR
- [ ] Dodać template Issue

### 3.3 Brak Standardowego CHANGELOG
- [ ] Utworzyć `CHANGELOG.md`
- [ ] Przenieść wpisy z `changelogs.lua`
- [ ] Zastosować format "Keep a Changelog"
- [ ] Dodać wersję 0.1.0
- [ ] Dodać kategorie (Added, Changed, Fixed, etc.)

---

## 4. CO MOŻNA POPRAWIĆ - ARCHITEKTURA

### 4.1 Dependency Injection
- [ ] Utworzyć `src/Container.php`
- [ ] Zarejestrować serwis 'db'
- [ ] Zarejestrować serwis 'auth'
- [ ] Refaktoryzować kontrolery do używania DI
- [ ] Dodać testy dla Container
- [ ] Udokumentować użycie DI

### 4.2 Centralna Walidacja
- [ ] Utworzyć `src/Validation/Validator.php`
- [ ] Zaimplementować podstawowe reguły (required, email, min, max)
- [ ] Dodać custom rules
- [ ] Refaktoryzować istniejącą walidację w kontrolerach
- [ ] Dodać testy dla Validator
- [ ] Udokumentować dostępne reguły

### 4.3 Response Helpers
- [ ] Utworzyć `src/Http/Response.php`
- [ ] Dodać metodę `json()`
- [ ] Dodać metodę `error()`
- [ ] Dodać metodę `success()`
- [ ] Refaktoryzować kontrolery do używania Response
- [ ] Standaryzować format błędów

---

## 5. CO MOŻNA POPRAWIĆ - BEZPIECZEŃSTWO

### 5.1 Enhanced CSP Headers
- [ ] Dodać `X-Frame-Options: DENY`
- [ ] Dodać `X-Content-Type-Options: nosniff`
- [ ] Dodać `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] Dodać `Permissions-Policy`
- [ ] Przetestować headers w produkcji
- [ ] Udokumentować security headers

### 5.2 Query Builder
- [ ] Utworzyć `src/Database/QueryBuilder.php`
- [ ] Zaimplementować metodę `select()`
- [ ] Zaimplementować metodę `insert()`
- [ ] Zaimplementować metodę `update()`
- [ ] Zaimplementować metodę `delete()`
- [ ] Dodać testy dla QueryBuilder
- [ ] Refaktoryzować kod do używania QueryBuilder

---

## 6. CO MOŻNA POPRAWIĆ - PERFORMANCE

### 6.1 Caching Strategy
- [ ] Utworzyć `src/Cache/FileCache.php`
- [ ] Zaimplementować metodę `get()`
- [ ] Zaimplementować metodę `set()`
- [ ] Dodać katalog `storage/cache/`
- [ ] Cachować często używane dane (user sessions, config)
- [ ] Dodać cache invalidation
- [ ] Przetestować cache performance

### 6.2 Query Optimization
- [ ] Utworzyć `bin/analyze_slow_queries.php`
- [ ] Logować query > 100ms
- [ ] Analizować EXPLAIN dla wolnych query
- [ ] Dodać brakujące indeksy
- [ ] Udokumentować optymalizacje

---

## 7. CO MOŻNA POPRAWIĆ - DOKUMENTACJA

### 7.1 PHPDoc Annotations
- [ ] Dodać PHPDoc do wszystkich public methods w Controllers
- [ ] Dodać PHPDoc do wszystkich public methods w Models
- [ ] Dodać PHPDoc do wszystkich public methods w Middleware
- [ ] Zdefiniować @param typy
- [ ] Zdefiniować @return typy
- [ ] Dodać @throws dla wyjątków

### 7.2 Architecture Documentation
- [ ] Utworzyć `docs/architecture.md`
- [ ] Opisać komponenty systemu
- [ ] Dodać diagram przepływu requestów
- [ ] Opisać strukturę katalogów
- [ ] Udokumentować wzorce projektowe
- [ ] Dodać diagramy (opcjonalnie)

---

## 8. CO TRZEBA DODAĆ - WYSOKIE (Must Have)

### 8.1 System Backupów
- [ ] Utworzyć `bin/backup.sh`
- [ ] Zaimplementować backup bazy danych (mysqldump)
- [ ] Zaimplementować backup plików (storage/uploads/)
- [ ] Utworzyć `bin/restore.sh`
- [ ] Skonfigurować cron job
- [ ] Przetestować proces restore
- [ ] Udokumentować procedury backup/restore

### 8.2 System Email
- [ ] Utworzyć `src/Email/Mailer.php`
- [ ] Skonfigurować SMTP driver
- [ ] Zaimplementować wysyłanie emaili
- [ ] Utworzyć katalog `src/Views/emails/`
- [ ] Utworzyć template dla password reset
- [ ] Utworzyć template dla powiadomień
- [ ] Przetestować wysyłanie emaili
- [ ] Dodać konfigurację w .env

### 8.3 Monitoring i Alerting
- [ ] Utworzyć endpoint `/health/metrics`
- [ ] Zbierać metryki (uptime, memory, db connections)
- [ ] Zintegrować error tracking (Sentry opcjonalnie)
- [ ] Utworzyć dashboard dla metryk
- [ ] Skonfigurować alerty dla błędów krytycznych

---

## 9. CO TRZEBA DODAĆ - ŚREDNIE (Should Have)

### 9.1 Push Notifications (Android)
- [ ] Zainstalować Firebase Cloud Messaging
- [ ] Utworzyć `src/Notifications/FCMService.php`
- [ ] Zaimplementować wysyłanie notyfikacji
- [ ] Dodać konfigurację FCM w Android app
- [ ] Przetestować notyfikacje
- [ ] Udokumentować flow notyfikacji

### 9.2 Search Functionality
- [ ] Utworzyć `src/Controllers/SearchController.php`
- [ ] Zaimplementować wyszukiwanie w Projects
- [ ] Zaimplementować wyszukiwanie w Leads
- [ ] Zaimplementować wyszukiwanie w Messages
- [ ] Dodać full-text search indexes
- [ ] Utworzyć UI dla wyszukiwania
- [ ] Przetestować search performance

---

## 10. CO TRZEBA DODAĆ - NISKIE (Nice to Have)

### 10.1 Analytics Dashboard
- [ ] Zaprojektować metryki (leads conversion, revenue)
- [ ] Utworzyć `src/Controllers/AnalyticsController.php`
- [ ] Zbierać dane analityczne
- [ ] Utworzyć dashboard UI
- [ ] Zintegrować bibliotekę wykresów
- [ ] Dodać filtry i zakresy dat

### 10.2 Internationalization (i18n)
- [ ] Utworzyć `src/i18n/Translator.php`
- [ ] Utworzyć pliki tłumaczeń (pl, en)
- [ ] Refaktoryzować hard-coded stringi
- [ ] Dodać wybór języka w UI
- [ ] Przetestować różne języki

---

## PRIORYTETY WYKONANIA

### Tydzień 1-2 (Natychmiastowe)
1. ✅ README.md (2h) - DONE (2026-02-08)
2. ✅ Error Handler (4h) - DONE (2026-02-08)
3. ✅ PHPUnit Setup (4h) - DONE (2026-02-08) - kompletna konfiguracja, wymaga `composer install`
4. ⏳ Pierwsze testy (8h) - IN PROGRESS (1/7 testów)
5. ⏳ CI/CD Pipeline (8h) - NEXT

### Tydzień 3-4 (Krótkoterminowe)
6. Dokumentacja API (8h)
7. Email System (16h)
8. Validation Framework (8h)
9. Backup Automation (4h)

### Miesiąc 2 (Średnioterminowe)
10. Push Notifications (16h)
11. Search Functionality (12h)
12. Analytics Dashboard (24h)
13. Dependency Injection (16h)

---

## TRACKING

**Data rozpoczęcia**: 2026-02-08  
**Ostatnia aktualizacja**: 2026-02-08 13:30  
**Postęp ogólny**: 15/100+ zadań (15%)  
**Postęp krytycznych**: 13/24 zadań (54%)  
**Postęp wysokich**: 1/9 zadań (README.md)

**Uwagi**:
- PHPUnit wymaga uruchomienia `composer install` w środowisku z dostępem do GitHub
- Po instalacji PHPUnit: `vendor/bin/phpunit` uruchomi testy
- Pierwszy test (ErrorHandlerTest) jest gotowy do uruchomienia

---

## NOTES

- Każde zadanie powinno być wykonane w osobnym commicie
- Każdy commit powinien być przetestowany
- Aktualizować changelogs.lua po każdej zmianie
- Dokumentować decyzje architektoniczne
- Reviewować kod przed mergem
