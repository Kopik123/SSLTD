# Project Notes For Codex Agents

This repo is a greenfield MVP for the S&S LTD web portal + Android field app described in `plans/`.

## Environment
- Windows + XAMPP. PHP CLI is available at `c:\xampp\php\php.exe`.
- `cmd.exe` does not handle `&` safely in many contexts (even inside quotes). Use the junction `c:\xampp\htdocs\ss_ltd` for shell work to avoid the `S&S LTD` path issues.

## Run (Local)
1. Create `.env` from `.env.example`.
2. Initialize the MySQL DB (XAMPP):
   - Option A (recommended): `c:\xampp\php\php.exe bin\migrate.php` then `c:\xampp\php\php.exe bin\seed.php`
   - Option B (manual import): `c:\xampp\mysql\bin\mysql.exe -u root < mysql.sql` then `c:\xampp\php\php.exe bin\seed.php`
3. Serve via Apache (XAMPP) or PHP's built-in server:
   - `c:\xampp\php\php.exe -S 127.0.0.1:8000 index.php`
   - Apache (XAMPP): open `http://localhost/ss_ltd/` (subdirectory hosting is supported via base-path aware routing)

## Architecture
- `index.php` is the single entrypoint.
- Server code lives in `src/` under the `App\` namespace.
- Data lives in MySQL by default (`ss_ltd` on XAMPP). SQLite is supported for dev-only usage via `.env`.
- Uploads are stored under `storage/uploads/` and are *not* directly web-accessible (download is routed through PHP).

## Security Baselines (Do Not Regress)
- Passwords: `password_hash()` / `password_verify()`.
- Sessions: `HttpOnly` cookies, regenerated on login.
- Forms: CSRF token required for all non-API state-changing requests.
- SQL: prepared statements only (PDO).
- Uploads: validate size + allowed mime-types + store outside web paths; never trust user filenames.
- AuthZ: role checks at the route layer; project/lead access must be scoped (ACL) before exposing records.
