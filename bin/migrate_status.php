<?php
declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Database\Db;
use App\Support\Config;
use App\Support\Env;

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

$driver = strtolower($config->getString('DB_CONNECTION'));
$db = Db::connect($config);

// Determine migrations directory (same logic as bin/migrate.php).
$dirPath = __DIR__ . '/../database/migrations';
if ($driver === 'mysql' && is_dir($dirPath . '/mysql')) {
  $dirPath .= '/mysql';
} elseif ($driver === 'sqlite' && is_dir($dirPath . '/sqlite')) {
  $dirPath .= '/sqlite';
}
$dir = realpath($dirPath);
if ($dir === false) {
  fwrite(STDERR, "Missing migrations dir.\n");
  exit(1);
}

$files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
sort($files);
$names = array_map(static fn($p) => basename((string)$p), $files);

// Ensure migrations table exists (for status checks).
$pdo = $db->pdo();
if ($driver === 'mysql') {
  $pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(255) NOT NULL,
      applied_at VARCHAR(32) NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uq_migrations_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
  );
} else {
  $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, applied_at TEXT NOT NULL);');
}

$appliedRows = $db->fetchAll('SELECT name, applied_at FROM migrations ORDER BY id ASC');
$applied = [];
foreach ($appliedRows as $r) {
  $n = (string)($r['name'] ?? '');
  if ($n !== '') $applied[$n] = (string)($r['applied_at'] ?? '');
}

$pending = [];
foreach ($names as $n) {
  if (!array_key_exists($n, $applied)) {
    $pending[] = $n;
  }
}

fwrite(STDOUT, "Driver: " . $driver . "\n");
fwrite(STDOUT, "Applied: " . count($applied) . "\n");
fwrite(STDOUT, "Pending: " . count($pending) . "\n");
if ($pending !== []) {
  fwrite(STDOUT, "Pending list:\n");
  foreach ($pending as $p) {
    fwrite(STDOUT, " - " . $p . "\n");
  }
}
