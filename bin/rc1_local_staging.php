<?php
declare(strict_types=1);

/**
 * RC1 (Local Staging) runner.
 *
 * Creates/updates `.env.staging` (gitignored) and runs:
 * - migrations on staging DB
 * - minimal admin user creation
 * - starts a prod-like server with APP_DEBUG=0
 * - runs HTTP smoke checks
 *
 * Usage:
 *   C:\xampp\php\php.exe bin\rc1_local_staging.php
 *
 * Notes:
 * - Uses MySQL (XAMPP) by default.
 * - The staging app is served via PHP built-in server on 127.0.0.1:8001
 */

require __DIR__ . '/../src/autoload.php';

use App\Support\Crypto;

final class Rc1
{
  public static function assert(bool $ok, string $msg): void
  {
    if (!$ok) throw new RuntimeException($msg);
  }

  /** @return array{code:int,out:string} */
  public static function run(array $cmd, string $cwd, ?array $env = null): array
  {
    $desc = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $desc, $pipes, $cwd, $env, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
      return ['code' => 1, 'out' => 'proc_open failed'];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return ['code' => is_int($code) ? $code : 1, 'out' => (string)$out . (string)$err];
  }

  /** @return array{status:int, body:string} */
  public static function httpGet(string $url): array
  {
    $ctx = stream_context_create([
      'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true,
        'header' => "User-Agent: ss_ltd_rc1\r\n",
      ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $headers = $http_response_header ?? [];
    $status = 0;
    foreach ($headers as $h) {
      if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', (string)$h, $m) === 1) {
        $status = (int)$m[1];
        break;
      }
    }
    return ['status' => $status, 'body' => is_string($body) ? $body : ''];
  }
}

/** @return array<string, string> */
function current_env_vars(): array
{
  $env = [];
  foreach ($_SERVER as $k => $v) {
    if (is_string($k) && is_string($v)) $env[$k] = $v;
  }
  foreach ($_ENV as $k => $v) {
    if (is_string($k) && is_string($v)) $env[$k] = $v;
  }
  return $env;
}

function ensure_env_staging(string $root): void
{
  $src = $root . DIRECTORY_SEPARATOR . '.env.staging.example';
  $dst = $root . DIRECTORY_SEPARATOR . '.env.staging';

  // Don't overwrite an existing staging env file (it may contain real secrets).
  if (is_file($dst)) {
    return;
  }

  if (!is_file($src)) {
    throw new RuntimeException('Missing .env.staging.example');
  }

  $raw = file_get_contents($src);
  if (!is_string($raw)) {
    throw new RuntimeException('Failed to read .env.staging.example');
  }

  $lines = preg_split("/\\r\\n|\\n|\\r/", $raw) ?: [];
  $out = [];
  $seen = [];

  // Generate strong key (do not print it).
  $key = 'staging-' . bin2hex(random_bytes(24));

  foreach ($lines as $line) {
    $t = trim($line);
    if ($t === '' || str_starts_with($t, '#') || strpos($t, '=') === false) {
      $out[] = $line;
      continue;
    }

    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $seen[$k] = true;

    if ($k === 'APP_URL') {
      // Default; the RC1 runner will also override APP_URL in the server process env.
      $out[] = 'APP_URL=http://127.0.0.1:8001';
      continue;
    }
    if ($k === 'APP_KEY') {
      $out[] = 'APP_KEY=' . $key;
      continue;
    }
    if ($k === 'DB_NAME') {
      $out[] = 'DB_NAME=ss_ltd_staging';
      continue;
    }
    $out[] = $line;
  }

  // Ensure required keys exist (in case example changes).
  if (!isset($seen['APP_URL'])) $out[] = 'APP_URL=http://127.0.0.1:8001';
  if (!isset($seen['APP_KEY'])) $out[] = 'APP_KEY=' . $key;
  if (!isset($seen['DB_NAME'])) $out[] = 'DB_NAME=ss_ltd_staging';

  $final = implode("\n", $out);
  // Always overwrite to keep it in sync for RC1.
  file_put_contents($dst, $final . "\n");
}

$root = dirname(__DIR__);
$php = 'c:\\xampp\\php\\php.exe';
$envFile = '.env.staging';

try {
  ensure_env_staging($root);

  // Migrate staging DB.
  $mig = Rc1::run([$php, 'bin/migrate.php', '--env', $envFile], $root);
  Rc1::assert($mig['code'] === 0, 'migrate failed: ' . trim($mig['out']));

  // Create minimal admin for staging.
  // User requested owner/admin: karolsztylc@gmail.com / Ekwurna5!!
  // (Do not store passwords in repo files; only pass via CLI here.)
  $adm = Rc1::run([$php, 'bin/create_admin_user.php', '--env', $envFile, 'karolsztylc@gmail.com', 'Ekwurna5!!', 'Owner Admin'], $root);
  Rc1::assert($adm['code'] === 0, 'create_admin_user failed: ' . trim($adm['out']));

  // Start prod-like server. Do not "probe-bind" ports, it can cause a short
  // TIME_WAIT-style collision on Windows. Instead, try a small range until one runs.
  $ports = [];
  for ($p = 8099; $p <= 8125; $p++) $ports[] = $p;

  $server = null;
  $base = '';
  foreach ($ports as $port) {
    $desc = [
      0 => ['pipe', 'r'],
      1 => ['file', $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'rc1_staging_out_' . $port . '.log', 'ab'],
      2 => ['file', $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'rc1_staging_err_' . $port . '.log', 'ab'],
    ];
    // IMPORTANT: passing a sparse env can break networking on Windows.
    // Always start from the current process env.
    $env = current_env_vars();
    $env['SS_ENV_FILE'] = $envFile;
    $env['APP_URL'] = 'http://127.0.0.1:' . $port;
    // Ensure dev tools stay disabled for RC1.
    $env['APP_DEBUG'] = '0';
    $env['APP_ENV'] = 'prod';

    $proc = proc_open([$php, '-S', '127.0.0.1:' . $port, 'index.php'], $desc, $pipes, $root, $env, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
      continue;
    }
    if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);

    // Give PHP a moment; if it exited immediately, try the next port.
    usleep(250_000);
    $st = proc_get_status($proc);
    if (!is_array($st) || !($st['running'] ?? false)) {
      proc_close($proc);
      continue;
    }

    $server = $proc;
    $base = 'http://127.0.0.1:' . $port;
    break;
  }
  Rc1::assert(is_resource($server) && $base !== '', 'failed to start server on any port (8099-8125)');

  // Wait for server.
  $t0 = microtime(true);
  $ready = false;
  while ((microtime(true) - $t0) < 8.0) {
    $r = Rc1::httpGet($base . '/health');
    if ($r['status'] === 200) { $ready = true; break; }
    usleep(200_000);
  }
  Rc1::assert($ready, 'server not ready on ' . $base);

  // Smoke checks (HTTP).
  $checks = [
    '/health' => 200,
    '/health/db' => 200,
    '/quote-request' => 200,
    '/login' => 200,
  ];
  foreach ($checks as $path => $want) {
    $r = Rc1::httpGet($base . $path);
    Rc1::assert($r['status'] === $want, $path . ' status=' . $r['status'] . ' want=' . $want);
  }

  proc_terminate($server);

  fwrite(STDOUT, "RC1 local staging: OK (" . $base . ")\n");
  exit(0);
} catch (Throwable $e) {
  $msg = "RC1 local staging: FAIL\n" . $e->getMessage() . "\n";
  fwrite(STDOUT, $msg);
  fwrite(STDERR, $msg);
  exit(1);
}
