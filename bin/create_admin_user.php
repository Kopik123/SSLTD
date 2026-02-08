<?php
declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Database\Db;
use App\Support\Config;
use App\Support\Env;

/**
 * Creates or updates an admin user (MySQL or SQLite depending on env/config).
 *
 * Usage:
 *   C:\xampp\php\php.exe bin\create_admin_user.php email password [name]
 */

// Optional: select env file (e.g. `.env.staging`) via `--env .env.staging` or `SS_ENV_FILE`.
$envFile = getenv('SS_ENV_FILE');
if (!is_string($envFile) || trim($envFile) === '') {
  $envFile = '.env';
}
for ($i = 1; $i < count($argv); $i++) {
  $a = (string)$argv[$i];
  if ($a === '--env' && isset($argv[$i + 1])) {
    $envFile = (string)$argv[$i + 1];
    break;
  }
  if (str_starts_with($a, '--env=')) {
    $envFile = substr($a, 6);
    break;
  }
}
$envFile = trim($envFile);
$envFile = str_replace(['\\', '/'], '', $envFile);
if (!preg_match('/^\\.env(\\.[A-Za-z0-9._-]+)?$/', $envFile)) {
  $envFile = '.env';
}
Env::load(__DIR__ . '/../' . $envFile);
$config = Config::fromEnv([
  'APP_ENV' => 'dev',
  'APP_DEBUG' => '1',
  'APP_URL' => 'http://127.0.0.1:8000',
  'APP_KEY' => 'change-me',
  'DB_CONNECTION' => 'mysql',
  'DB_DATABASE' => __DIR__ . '/../storage/app.db',
  'DB_HOST' => '127.0.0.1',
  'DB_PORT' => '3306',
  'DB_NAME' => 'ss_ltd',
  'DB_USER' => 'root',
  'DB_PASS' => '',
  'SERVICE_AREA_RADIUS_MILES' => '60',
]);

// Positional args: email password [name]
// Strip `--env`/`--env=` options from argv so positions remain stable.
$rest = [];
for ($i = 1; $i < count($argv); $i++) {
  $a = (string)$argv[$i];
  if ($a === '--env') {
    $i++; // skip value
    continue;
  }
  if (str_starts_with($a, '--env=')) {
    continue;
  }
  $rest[] = $a;
}

$email = (string)($rest[0] ?? '');
$password = (string)($rest[1] ?? '');
$name = (string)($rest[2] ?? 'Owner Admin');

if ($email === '' || $password === '') {
  fwrite(STDERR, "Usage: php bin/create_admin_user.php email password [name]\n");
  exit(2);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fwrite(STDERR, "Invalid email.\n");
  exit(2);
}

$db = Db::connect($config);
$now = gmdate('c');
$hash = password_hash($password, PASSWORD_DEFAULT);

$existing = $db->fetchOne('SELECT id FROM users WHERE email = :e LIMIT 1', ['e' => $email]);
if ($existing === null) {
  $id = (int)$db->insert(
    'INSERT INTO users (role, name, email, phone, password_hash, status, created_at, updated_at)
     VALUES (:r, :n, :e, NULL, :h, :s, :c, :u)',
    [
      'r' => 'admin',
      'n' => $name,
      'e' => $email,
      'h' => $hash,
      's' => 'active',
      'c' => $now,
      'u' => $now,
    ]
  );
  fwrite(STDOUT, "OK: created admin user id={$id} email={$email}\n");
  exit(0);
}

$id = (int)$existing['id'];
$db->execute(
  'UPDATE users
   SET role = :r, name = :n, password_hash = :h, status = :s, updated_at = :u
   WHERE id = :id',
  [
    'r' => 'admin',
    'n' => $name,
    'h' => $hash,
    's' => 'active',
    'u' => $now,
    'id' => $id,
  ]
);

fwrite(STDOUT, "OK: updated admin user id={$id} email={$email}\n");
exit(0);
