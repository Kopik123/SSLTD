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

try {
  $db = Db::connect($config);
  $row = $db->fetchOne('SELECT 1 AS ok');
  $ok = $row !== null && (int)($row['ok'] ?? 0) === 1;
  if (!$ok) {
    fwrite(STDERR, "DB check failed.\n");
    exit(2);
  }
  fwrite(STDOUT, "DB OK\n");
  exit(0);
} catch (Throwable $e) {
  fwrite(STDERR, "DB ERROR: " . $e->getMessage() . "\n");
  exit(2);
}
