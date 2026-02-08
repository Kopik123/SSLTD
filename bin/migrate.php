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

function mysql_bootstrap_db(Config $config): void
{
  $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config->getString('DB_HOST'), $config->getString('DB_PORT'));
  $pdo = new PDO($dsn, $config->getString('DB_USER'), $config->getString('DB_PASS'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  $dbName = $config->getString('DB_NAME');
  if ($dbName === '') {
    throw new RuntimeException('DB_NAME is required for mysql.');
  }

  $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
}

$driver = strtolower($config->getString('DB_CONNECTION'));
if ($driver === 'mysql') {
  mysql_bootstrap_db($config);
}

$db = Db::connect($config);
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

/**
 * Extremely small SQL splitter for migration files.
 * Assumes no semicolons inside strings (true for our schema).
 *
 * @return array<int, string>
 */
function split_sql(string $sql): array
{
  $lines = preg_split("/\\r\\n|\\n|\\r/", $sql) ?: [];
  $clean = [];
  foreach ($lines as $line) {
    $trim = ltrim($line);
    if (str_starts_with($trim, '--')) {
      continue;
    }
    $clean[] = $line;
  }
  $sql = implode("\n", $clean);
  $parts = explode(';', $sql);
  $out = [];
  foreach ($parts as $p) {
    $p = trim($p);
    if ($p !== '') {
      $out[] = $p . ';';
    }
  }
  return $out;
}

/**
 * Executes a single MySQL statement safely, draining any result sets.
 * Some migrations use PREPARE/EXECUTE with a fallback "SELECT 1" which produces
 * a result set; if we don't consume it, MySQL/PDO can error on subsequent stmts.
 */
function exec_mysql_stmt(PDO $pdo, string $stmt): void
{
  $stmt = trim($stmt);
  if ($stmt === '') return;

  try {
    $s = $pdo->prepare($stmt);
    $s->execute();

    // Drain all result sets (covers SELECT, EXECUTE ... returning a result, etc.)
    do {
      try {
        $s->fetchAll();
      } catch (Throwable $_) {
        // ignore fetch errors for non-select statements
      }
    } while ($s->nextRowset());

    $s->closeCursor();
  } catch (Throwable $e) {
    // Last resort; should be rare. Note: can re-trigger unbuffered-query issues
    // if the statement produces a result set.
    $pdo->exec($stmt);
  }
}

foreach ($files as $file) {
  $name = basename($file);
  $exists = $db->fetchOne('SELECT 1 AS ok FROM migrations WHERE name = :n LIMIT 1', ['n' => $name]);
  if ($exists !== null) {
    continue;
  }

  $sql = file_get_contents($file);
  if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Empty migration: $name\n");
    exit(1);
  }

  try {
    if ($driver === 'mysql') {
      foreach (split_sql($sql) as $stmt) {
        exec_mysql_stmt($pdo, $stmt);
      }
    } else {
      $pdo->beginTransaction();
      $pdo->exec($sql);
      $pdo->commit();
    }
    $db->insert('INSERT INTO migrations (name, applied_at) VALUES (:n, :t)', ['n' => $name, 't' => gmdate('c')]);
    fwrite(STDOUT, "Applied: $name\n");
  } catch (Throwable $e) {
    if ($driver !== 'mysql' && $pdo->inTransaction()) {
      $pdo->rollBack();
    }
    fwrite(STDERR, "Failed: $name\n" . $e->getMessage() . "\n");
    exit(1);
  }
}

fwrite(STDOUT, "Migrations complete.\n");
