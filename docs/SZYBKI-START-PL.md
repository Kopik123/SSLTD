# Szybki Start - Wdrożenie na DigitalOcean

Krótki przewodnik po wdrożeniu aplikacji S&S LTD na serwer DigitalOcean przy użyciu Termius.

## Przygotowanie Lokalne

1. **Przygotuj paczkę wdrożeniową**:
   ```bash
   bash bin/deploy-prepare.sh
   ```
   
   Skrypt utworzy plik `.tar.gz` w katalogu nadrzędnym.

2. **Znajdź utworzony plik**:
   - Nazwa: `ss_ltd_deploy_YYYYMMDD_HHMMSS.tar.gz`
   - Rozmiar: około 6-8 MB

## Transfer przez Termius

### 1. Połącz się z serwerem

1. Otwórz Termius
2. Dodaj nowy host:
   - Adres: IP twojego serwera DigitalOcean
   - Port: 22
   - Użytkownik: root
   - Hasło: hasło z DigitalOcean
3. Połącz się

### 2. Prześlij plik

1. Kliknij przycisk **SFTP** w Termius
2. Po lewej stronie: znajdź plik `ss_ltd_deploy_*.tar.gz`
3. Po prawej stronie: przejdź do `/root/`
4. **Przeciągnij i upuść** plik z lewej na prawą stronę
5. Poczekaj na zakończenie transferu (pasek postępu 100%)

### 3. Wypakuj na serwerze

Wróć do terminala i wpisz:

```bash
cd /var/www/html
tar -xzf ~/ss_ltd_deploy_*.tar.gz
mv ss_ltd_deploy_* ss_ltd
cd ss_ltd
```

## Konfiguracja

### 1. Utwórz plik .env

```bash
cp .env.production.example .env
nano .env
```

Zmień następujące wartości:
- `APP_URL=https://twoja-domena.com`
- `APP_KEY=` (wygeneruj: `openssl rand -base64 32`)
- `DB_PASS=` (ustaw silne hasło)

Zapisz: `Ctrl+O`, `Enter`, `Ctrl+X`

### 2. Ustaw uprawnienia

```bash
chown -R www-data:www-data /var/www/html/ss_ltd
chmod -R 755 /var/www/html/ss_ltd
chmod -R 775 /var/www/html/ss_ltd/storage
chmod 600 /var/www/html/ss_ltd/.env
```

### 3. Uruchom migracje

```bash
php bin/migrate.php
php bin/seed.php
```

## Konfiguracja Apache i SSL

### 1. Utwórz wirtualny host

```bash
nano /etc/apache2/sites-available/ss_ltd.conf
```

Dodaj konfigurację (patrz `docs/deployment.md` dla szczegółów)

```bash
a2ensite ss_ltd.conf
a2dissite 000-default.conf
systemctl restart apache2
```

### 2. Zainstaluj SSL (Let's Encrypt)

```bash
apt install certbot python3-certbot-apache -y
certbot --apache -d twoja-domena.com -d www.twoja-domena.com
```

Wybierz: przekieruj HTTP na HTTPS (Yes)

## Sprawdzenie

Odwiedź stronę:
- `https://twoja-domena.com/health` - powinno pokazać "OK"
- `https://twoja-domena.com/` - strona logowania

Zaloguj się:
- Email: `admin@ss.local`
- Hasło: `Admin123!`

**WAŻNE**: Zmień hasło administratora natychmiast po pierwszym logowaniu!

## Pełna Dokumentacja

Dla szczegółowych instrukcji:
- **[docs/deployment.md](deployment.md)** - Pełny przewodnik wdrożenia (angielski)
- **[docs/deployment-checklist.md](deployment-checklist.md)** - Lista kontrolna
- **[docs/termius-guide.md](termius-guide.md)** - Przewodnik Termius

## Bezpieczeństwo

Po wdrożeniu koniecznie:
- ✅ Zmień domyślne hasło administratora
- ✅ Sprawdź `APP_DEBUG=0` w .env
- ✅ Sprawdź czy działa SSL (https://)
- ✅ Usuń lub zaktualizuj testowe konta użytkowników
- ✅ Skonfiguruj kopie zapasowe

## Kopie Zapasowe

Aby utworzyć kopię zapasową:
```bash
bash bin/backup.sh
```

Aby przywrócić:
```bash
bash bin/restore.sh 20260213_140530
```

## Pomoc

W razie problemów:
- Sprawdź logi: `tail -f /var/log/apache2/ss_ltd_error.log`
- Zobacz pełną dokumentację: `docs/deployment.md`
- Sprawdź listę kontrolną: `docs/deployment-checklist.md`

## Typowe Problemy

| Problem | Rozwiązanie |
|---------|-------------|
| Błąd 500 | Sprawdź logi Apache i uprawnienia plików |
| Brak połączenia z bazą | Sprawdź dane w .env i czy MySQL działa |
| SSL nie działa | Sprawdź DNS i czy porty 80/443 są otwarte |
| Nie można uploadować plików | Sprawdź uprawnienia folderu storage/ |

---

**Data aktualizacji**: Luty 2026
