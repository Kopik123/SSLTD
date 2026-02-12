# Szybki Start - XAMPP

Ten przewodnik pomoże Ci szybko uruchomić projekt S&S LTD na XAMPP.

## Krok 1: Instalacja XAMPP

1. Pobierz XAMPP z [https://www.apachefriends.org/pl/index.html](https://www.apachefriends.org/pl/index.html)
2. Zainstaluj XAMPP (zalecana lokalizacja: `C:\xampp`)
3. Uruchom XAMPP Control Panel

## Krok 2: Przygotowanie Projektu

1. Skopiuj folder projektu do:
   ```
   C:\xampp\htdocs\ss_ltd\
   ```

2. Jeśli nazwa folderu zawiera znak `&`, utwórz junction:
   ```cmd
   mklink /J "C:\xampp\htdocs\ss_ltd" "C:\xampp\htdocs\S&S LTD"
   ```

## Krok 3: Automatyczna Instalacja (Zalecane)

1. W XAMPP Control Panel uruchom **MySQL** i **Apache**

2. Otwórz wiersz poleceń (cmd) i przejdź do folderu projektu:
   ```cmd
   cd C:\xampp\htdocs\ss_ltd
   ```

3. Uruchom skrypt instalacyjny:
   ```cmd
   setup.bat
   ```

4. Postępuj zgodnie z instrukcjami na ekranie

**To wszystko!** Skrypt automatycznie:
- Utworzy plik `.env` z konfiguracją
- Utworzy bazę danych `ss_ltd`
- Wykona migracje bazy danych
- Utworzy konta testowe
- Zaoferuje uruchomienie serwera

## Krok 4: Otwórz Aplikację

Po zakończeniu instalacji, otwórz przeglądarkę:

- **Apache (XAMPP):** [http://localhost/ss_ltd/](http://localhost/ss_ltd/)
- **PHP Server:** [http://127.0.0.1:8000](http://127.0.0.1:8000) (jeśli wybrałeś tę opcję)

## Konta Testowe

| Rola | Email | Hasło |
|------|-------|-------|
| Administrator | admin@ss.local | Admin123! |
| Project Manager | pm@ss.local | Pm123456! |
| Klient | client@ss.local | Client123! |
| Pracownik | employee@ss.local | Employee123! |
| Podwykonawca | sub@ss.local | Sub123456! |
| Pracownik Podwykonawcy | subworker@ss.local | Worker123! |

## Instalacja Manualna (Alternatywa)

Jeśli nie chcesz używać `setup.bat`, możesz zainstalować ręcznie:

### 1. Utwórz plik .env
```cmd
copy .env.example .env
```

### 2. Utwórz bazę danych
- Otwórz [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Utwórz nową bazę o nazwie: `ss_ltd`
- Ustaw kodowanie: `utf8mb4_unicode_ci`

### 3. Uruchom migracje
```cmd
C:\xampp\php\php.exe bin\migrate.php
```

### 4. Dodaj dane testowe
```cmd
C:\xampp\php\php.exe bin\seed.php
```

### 5. Uruchom aplikację
- Upewnij się że Apache działa w XAMPP Control Panel
- Otwórz: [http://localhost/ss_ltd/](http://localhost/ss_ltd/)

## Rozwiązywanie Problemów

### Błąd: "Could not connect to database"

**Rozwiązanie:**
1. Sprawdź czy MySQL jest uruchomiony w XAMPP Control Panel
2. Otwórz `.env` i sprawdź ustawienia:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=ss_ltd
   DB_USER=root
   DB_PASS=
   ```
3. Sprawdź czy baza `ss_ltd` została utworzona w phpMyAdmin

### Błąd: "404 Not Found"

**Rozwiązanie:**
1. Sprawdź czy Apache jest uruchomiony
2. Sprawdź czy ścieżka jest poprawna: `http://localhost/ss_ltd/`
3. Sprawdź czy w folderze projektu istnieje plik `.htaccess`

### Błąd: "Call to undefined function..."

**Rozwiązanie:**
1. Sprawdź wersję PHP: `C:\xampp\php\php.exe -v`
2. Upewnij się że PHP jest w wersji 7.4 lub nowszej
3. Sprawdź czy wymagane rozszerzenia są włączone w `C:\xampp\php\php.ini`:
   - `extension=pdo_mysql`
   - `extension=mbstring`
   - `extension=openssl`

### Strona się nie ładuje / długo się ładuje

**Rozwiązanie:**
1. Sprawdź logi Apache: `C:\xampp\apache\logs\error.log`
2. Włącz tryb debug w `.env`:
   ```env
   APP_DEBUG=1
   ```
3. Odśwież stronę i sprawdź szczegóły błędu

## Przydatne Komendy

```cmd
:: Sprawdź status migracji
C:\xampp\php\php.exe bin\migrate_status.php

:: Test połączenia z bazą
C:\xampp\php\php.exe bin\health_db.php

:: Utwórz nowego admina
C:\xampp\php\php.exe bin\create_admin_user.php

:: Uruchom serwer PHP (alternatywa dla Apache)
C:\xampp\php\php.exe -S 127.0.0.1:8000 index.php
```

## Dalsze Kroki

1. **Zmień hasło administratora** po pierwszym logowaniu
2. **Wygeneruj nowy APP_KEY** w `.env` dla bezpieczeństwa
3. **Przeczytaj dokumentację** w folderze `docs/`
4. **Dostosuj konfigurację** w pliku `.env` do swoich potrzeb

## Potrzebujesz Pomocy?

- Sprawdź pełną dokumentację: `README.md`
- Dokumentacja XAMPP: [https://www.apachefriends.org/](https://www.apachefriends.org/)
- Dokumentacja projektu: `docs/setup.md`

## Następne Kroki

Po uruchomieniu projektu możesz:
- Tworzyć nowe projekty i leady
- Zarządzać użytkownikami
- Przeglądać raporty
- Konfigurować aplikację Android (patrz `android/README.md`)

---

**Powodzenia!** 🚀
