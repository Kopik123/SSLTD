# Rozwiązywanie Problemów XAMPP / XAMPP Troubleshooting

## 🇵🇱 Polski

### Najczęstsze Problemy i Rozwiązania

---

#### ❌ Problem: "Apache nie chce się uruchomić"

**Objawy:**
- Przycisk "Start" dla Apache w XAMPP Control Panel nie działa
- Apache startuje i natychmiast się wyłącza
- Komunikat: "Port 80 in use by ..."

**Rozwiązania:**

1. **Sprawdź czy port 80 jest zajęty:**
   ```cmd
   netstat -ano | findstr :80
   ```

2. **Najczęstsza przyczyna - Skype lub inny program:**
   - Zamknij Skype
   - Wyłącz IIS (jeśli zainstalowany)
   - Sprawdź VMware, inne serwery web

3. **Zmień port Apache:**
   - Otwórz `C:\xampp\apache\conf\httpd.conf`
   - Znajdź `Listen 80` i zmień na `Listen 8080`
   - Otwórz `C:\xampp\apache\conf\extra\httpd-ssl.conf`
   - Znajdź `Listen 443` i zmień na `Listen 4433`
   - Zrestartuj Apache
   - Teraz używaj: `http://localhost:8080/ss_ltd/`

4. **Uruchom XAMPP jako Administrator:**
   - Kliknij prawym na XAMPP Control Panel
   - Wybierz "Uruchom jako administrator"

---

#### ❌ Problem: "MySQL nie chce się uruchomić"

**Objawy:**
- MySQL nie startuje
- Błąd: "Port 3306 in use"

**Rozwiązania:**

1. **Sprawdź czy port 3306 jest zajęty:**
   ```cmd
   netstat -ano | findstr :3306
   ```

2. **Sprawdź czy inna instancja MySQL działa:**
   - Otwórz Task Manager (Ctrl+Shift+Esc)
   - Zakładka "Usługi" / "Services"
   - Znajdź "MySQL" lub "MySQL80"
   - Zatrzymaj usługę

3. **Zmień port MySQL:**
   - Otwórz `C:\xampp\mysql\bin\my.ini`
   - Znajdź `port=3306`
   - Zmień na `port=3307`
   - Zaktualizuj `.env`: `DB_PORT=3307`

4. **Sprawdź logi:**
   ```
   C:\xampp\mysql\data\mysql_error.log
   ```

---

#### ❌ Problem: "Could not connect to database"

**Objawy:**
- Aplikacja wyświetla błąd połączenia z bazą
- Strona nie ładuje się

**Rozwiązania:**

1. **Sprawdź czy MySQL działa:**
   - Otwórz XAMPP Control Panel
   - Sprawdź czy MySQL ma zielony status

2. **Sprawdź plik `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=ss_ltd
   DB_USER=root
   DB_PASS=
   ```

3. **Sprawdź czy baza została utworzona:**
   - Otwórz http://localhost/phpmyadmin
   - Sprawdź czy istnieje baza `ss_ltd`
   - Jeśli nie, utwórz ją

4. **Test połączenia:**
   ```cmd
   C:\xampp\php\php.exe bin\health_db.php
   ```

---

#### ❌ Problem: "404 Not Found"

**Objawy:**
- Strona `http://localhost/ss_ltd/` nie działa
- Błąd 404

**Rozwiązania:**

1. **Sprawdź czy plik index.php istnieje:**
   ```
   C:\xampp\htdocs\ss_ltd\index.php
   ```

2. **Sprawdź czy Apache działa:**
   - Otwórz http://localhost/
   - Powinna pojawić się strona XAMPP

3. **Sprawdź ścieżkę:**
   - Upewnij się że folder nazywa się dokładnie `ss_ltd`
   - Wielkość liter nie ma znaczenia na Windows

4. **Sprawdź .htaccess:**
   ```cmd
   dir C:\xampp\htdocs\ss_ltd\.htaccess
   ```
   - Jeśli brak, to Apache może nie przekierowywać poprawnie

---

#### ❌ Problem: "PHP extensions not loaded"

**Objawy:**
- Błąd: "Call to undefined function..."
- Brakujące rozszerzenia PHP

**Rozwiązania:**

1. **Sprawdź wersję PHP:**
   ```cmd
   C:\xampp\php\php.exe -v
   ```
   - Wymagane: PHP 7.4+

2. **Włącz rozszerzenia w php.ini:**
   - Otwórz `C:\xampp\php\php.ini`
   - Znajdź i odkomentuj (usuń `;`):
     ```ini
     extension=pdo_mysql
     extension=mbstring
     extension=openssl
     extension=fileinfo
     ```

3. **Zrestartuj Apache**

---

#### ❌ Problem: "Strona ładuje się bardzo wolno"

**Objawy:**
- Pierwsza wizyta na stronie trwa 30+ sekund
- Każde przeładowanie jest wolne

**Rozwiązania:**

1. **Wyłącz Xdebug (jeśli nie jest potrzebny):**
   - Otwórz `C:\xampp\php\php.ini`
   - Znajdź linię z `zend_extension` dla Xdebug
   - Dodaj `;` na początku, aby zakomentować

2. **Wyłącz antywirus dla folderu XAMPP:**
   - Dodaj `C:\xampp` do wyjątków antyvirusa
   - Windows Defender często skanuje pliki PHP

3. **Sprawdź czy to problem DNS:**
   - W `.env` użyj `127.0.0.1` zamiast `localhost`

---

#### ❌ Problem: "Brak uprawnień do zapisu w storage/"

**Objawy:**
- Błąd podczas uploadu plików
- Nie można zapisać logów

**Rozwiązania:**

1. **Nadaj uprawnienia folderowi:**
   - Kliknij prawym na `C:\xampp\htdocs\ss_ltd\storage`
   - Właściwości → Bezpieczeństwo
   - Upewnij się że "Users" mają "Modyfikuj"

2. **Utwórz potrzebne foldery:**
   ```cmd
   mkdir C:\xampp\htdocs\ss_ltd\storage\uploads
   mkdir C:\xampp\htdocs\ss_ltd\storage\logs
   ```

---

#### ❌ Problem: "CSRF token mismatch"

**Objawy:**
- Błąd przy wysyłaniu formularzy
- "CSRF token mismatch" lub 403

**Rozwiązania:**

1. **Wyczyść sesję:**
   - Wyloguj się
   - Wyczyść cookies przeglądarki
   - Zaloguj się ponownie

2. **Sprawdź APP_KEY w .env:**
   - Upewnij się że istnieje
   - Zrestartuj serwer po zmianie

3. **Sprawdź czy sesje działają:**
   ```cmd
   C:\xampp\php\php.exe -r "session_start(); echo 'OK';"
   ```

---

### 🔧 Narzędzia Diagnostyczne

**Sprawdź konfigurację PHP:**
```cmd
C:\xampp\php\php.exe -i | findstr "Configuration File"
```

**Sprawdź załadowane rozszerzenia:**
```cmd
C:\xampp\php\php.exe -m
```

**Sprawdź logi Apache:**
```
C:\xampp\apache\logs\error.log
```

**Sprawdź logi MySQL:**
```
C:\xampp\mysql\data\mysql_error.log
```

**Test aplikacji:**
```cmd
C:\xampp\php\php.exe bin\health_db.php
```

---

### 📞 Dalsze Wsparcie

Jeśli powyższe rozwiązania nie pomogły:

1. **Sprawdź logi błędów:**
   - Apache: `C:\xampp\apache\logs\error.log`
   - MySQL: `C:\xampp\mysql\data\mysql_error.log`
   - PHP: włącz `display_errors` w `php.ini`

2. **Tryb debug:**
   - Ustaw w `.env`: `APP_DEBUG=1`
   - Odśwież stronę
   - Szczegółowe informacje o błędzie będą widoczne

3. **Dokumentacja:**
   - XAMPP FAQ: https://www.apachefriends.org/faq_windows.html
   - PHP Manual: https://www.php.net/manual/en/

---

## 🇬🇧 English

### Common Problems and Solutions

---

#### ❌ Problem: "Apache won't start"

**Symptoms:**
- "Start" button for Apache in XAMPP Control Panel doesn't work
- Apache starts and immediately stops
- Message: "Port 80 in use by ..."

**Solutions:**

1. **Check if port 80 is in use:**
   ```cmd
   netstat -ano | findstr :80
   ```

2. **Most common cause - Skype or other program:**
   - Close Skype
   - Disable IIS (if installed)
   - Check VMware, other web servers

3. **Change Apache port:**
   - Open `C:\xampp\apache\conf\httpd.conf`
   - Find `Listen 80` and change to `Listen 8080`
   - Open `C:\xampp\apache\conf\extra\httpd-ssl.conf`
   - Find `Listen 443` and change to `Listen 4433`
   - Restart Apache
   - Now use: `http://localhost:8080/ss_ltd/`

4. **Run XAMPP as Administrator:**
   - Right-click XAMPP Control Panel
   - Select "Run as administrator"

---

#### ❌ Problem: "MySQL won't start"

**Symptoms:**
- MySQL doesn't start
- Error: "Port 3306 in use"

**Solutions:**

1. **Check if port 3306 is in use:**
   ```cmd
   netstat -ano | findstr :3306
   ```

2. **Check if another MySQL instance is running:**
   - Open Task Manager (Ctrl+Shift+Esc)
   - "Services" tab
   - Find "MySQL" or "MySQL80"
   - Stop the service

3. **Change MySQL port:**
   - Open `C:\xampp\mysql\bin\my.ini`
   - Find `port=3306`
   - Change to `port=3307`
   - Update `.env`: `DB_PORT=3307`

4. **Check logs:**
   ```
   C:\xampp\mysql\data\mysql_error.log
   ```

---

#### ❌ Problem: "Could not connect to database"

**Symptoms:**
- Application displays database connection error
- Page doesn't load

**Solutions:**

1. **Check if MySQL is running:**
   - Open XAMPP Control Panel
   - Check if MySQL has green status

2. **Check `.env` file:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=ss_ltd
   DB_USER=root
   DB_PASS=
   ```

3. **Check if database was created:**
   - Open http://localhost/phpmyadmin
   - Check if `ss_ltd` database exists
   - If not, create it

4. **Test connection:**
   ```cmd
   C:\xampp\php\php.exe bin\health_db.php
   ```

---

#### ❌ Problem: "404 Not Found"

**Symptoms:**
- Page `http://localhost/ss_ltd/` doesn't work
- 404 error

**Solutions:**

1. **Check if index.php exists:**
   ```
   C:\xampp\htdocs\ss_ltd\index.php
   ```

2. **Check if Apache is running:**
   - Open http://localhost/
   - Should see XAMPP page

3. **Check path:**
   - Make sure folder is named exactly `ss_ltd`
   - Case doesn't matter on Windows

4. **Check .htaccess:**
   ```cmd
   dir C:\xampp\htdocs\ss_ltd\.htaccess
   ```
   - If missing, Apache may not redirect properly

---

#### ❌ Problem: "PHP extensions not loaded"

**Symptoms:**
- Error: "Call to undefined function..."
- Missing PHP extensions

**Solutions:**

1. **Check PHP version:**
   ```cmd
   C:\xampp\php\php.exe -v
   ```
   - Required: PHP 7.4+

2. **Enable extensions in php.ini:**
   - Open `C:\xampp\php\php.ini`
   - Find and uncomment (remove `;`):
     ```ini
     extension=pdo_mysql
     extension=mbstring
     extension=openssl
     extension=fileinfo
     ```

3. **Restart Apache**

---

#### ❌ Problem: "Page loads very slowly"

**Symptoms:**
- First visit takes 30+ seconds
- Every reload is slow

**Solutions:**

1. **Disable Xdebug (if not needed):**
   - Open `C:\xampp\php\php.ini`
   - Find line with `zend_extension` for Xdebug
   - Add `;` at the beginning to comment out

2. **Disable antivirus for XAMPP folder:**
   - Add `C:\xampp` to antivirus exceptions
   - Windows Defender often scans PHP files

3. **Check if it's a DNS issue:**
   - In `.env` use `127.0.0.1` instead of `localhost`

---

### 🔧 Diagnostic Tools

**Check PHP configuration:**
```cmd
C:\xampp\php\php.exe -i | findstr "Configuration File"
```

**Check loaded extensions:**
```cmd
C:\xampp\php\php.exe -m
```

**Check Apache logs:**
```
C:\xampp\apache\logs\error.log
```

**Check MySQL logs:**
```
C:\xampp\mysql\data\mysql_error.log
```

**Test application:**
```cmd
C:\xampp\php\php.exe bin\health_db.php
```

---

### 📞 Further Support

If the above solutions didn't help:

1. **Check error logs:**
   - Apache: `C:\xampp\apache\logs\error.log`
   - MySQL: `C:\xampp\mysql\data\mysql_error.log`
   - PHP: enable `display_errors` in `php.ini`

2. **Debug mode:**
   - Set in `.env`: `APP_DEBUG=1`
   - Refresh page
   - Detailed error information will be visible

3. **Documentation:**
   - XAMPP FAQ: https://www.apachefriends.org/faq_windows.html
   - PHP Manual: https://www.php.net/manual/en/
