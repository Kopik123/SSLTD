<?php
declare(strict_types=1);

// This file is both the app entrypoint (Apache) and the router script for
// PHP's built-in server (`php -S 127.0.0.1:8000 index.php`).

require __DIR__ . '/src/autoload.php';
require __DIR__ . '/src/helpers.php';

use App\Context;
use App\Database\Db;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Http\Session;
use App\Support\Config;
use App\Support\Env;
use App\Support\Log;
use App\Support\RequestId;

// Allow selecting an alternate env file (e.g. `.env.staging`) for local "staging" runs.
// Hard-gate the filename to avoid path traversal.
$envFile = getenv('SS_ENV_FILE');
if (!is_string($envFile) || trim($envFile) === '') {
  $envFile = '.env';
} else {
  $envFile = trim($envFile);
  $envFile = str_replace(['\\', '/'], '', $envFile);
  if (!preg_match('/^\\.env(\\.[A-Za-z0-9._-]+)?$/', $envFile)) {
    $envFile = '.env';
  }
}
Env::load(__DIR__ . '/' . $envFile);
$config = Config::fromEnv([
  'APP_ENV' => 'dev',
  'APP_DEBUG' => '1',
  'APP_URL' => 'http://127.0.0.1:8000',
  'APP_KEY' => 'change-me',
  'DB_CONNECTION' => 'mysql',
  'DB_DATABASE' => __DIR__ . '/storage/app.db',
  'DB_HOST' => '127.0.0.1',
  'DB_PORT' => '3306',
  'DB_NAME' => 'ss_ltd',
  'DB_USER' => 'root',
  'DB_PASS' => '',
  'SERVICE_AREA_RADIUS_MILES' => '60',
]);

$t0 = microtime(true);

$appEnv = strtolower($config->getString('APP_ENV'));
$isProd = ($appEnv === 'prod' || $appEnv === 'production');
if ($isProd) {
  if ($config->isDebug()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Misconfiguration: APP_DEBUG must be 0 in production.\n";
    exit;
  }
  $appKey = trim($config->getString('APP_KEY'));
  if ($appKey === '' || $appKey === 'change-me' || strlen($appKey) < 32) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Misconfiguration: APP_KEY must be set to a strong random value in production.\n";
    exit;
  }
}

$req = Request::fromGlobals();
$path = $req->path();

// Request ID for troubleshooting (also injected into API JSON).
$rid = $req->header('X-Request-Id');
if (!is_string($rid) || trim($rid) === '') {
  try {
    $rid = bin2hex(random_bytes(8));
  } catch (Throwable $_) {
    $rid = (string)mt_rand(10000000, 99999999);
  }
}
RequestId::set($rid);
header('X-Request-Id: ' . $rid);

// Never serve internal folders directly, even on the built-in server.
if (preg_match('#^/(storage|database|src|bin|plans)(/|$)#', $path) === 1) {
  Response::text('Not Found', 404)->send();
  exit;
}
if (preg_match('#^/(\\.env(\\..*)?|AGENTS\\.md|changelogs\\.lua|mysql\\.sql)$#', $path) === 1) {
  Response::text('Not Found', 404)->send();
  exit;
}

// Basic security headers (CSP enabled; keep views free of inline scripts/styles).
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
  'Content-Security-Policy: '
  . "default-src 'self'; "
  . "base-uri 'self'; "
  . "object-src 'none'; "
  . "frame-ancestors 'none'; "
  . "form-action 'self'; "
  . "img-src 'self' data:; "
  . "style-src 'self' https://fonts.googleapis.com; "
  . "font-src 'self' https://fonts.gstatic.com data:; "
  . "script-src 'self'; "
  . "connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com"
);

if (str_starts_with($config->getString('APP_URL'), 'https://')) {
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Allow static asset serving (CSS/JS/images) via built-in server.
if (php_sapi_name() === 'cli-server') {
  $candidate = __DIR__ . $path;
  if (preg_match('#^/assets/#', $path) === 1 && is_file($candidate)) {
    return false;
  }
}

date_default_timezone_set('UTC');

set_exception_handler(static function (Throwable $e) use ($config): void {
  Log::error('uncaught_exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
  if ($config->isDebug()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "500 Internal Server Error\n\n";
    echo $e;
    return;
  }

  http_response_code(500);
  header('Content-Type: text/html; charset=utf-8');
  echo "<h1>500</h1><p>Something went wrong.</p>";
});

$db = Db::connect($config);
$session = Session::start($config);
$ctx = new Context($config, $db, $session, $req->basePath());

$router = new Router();
require __DIR__ . '/src/routes.php';

$res = $router->dispatch($req, $ctx);
$res->send();

// Dev-only request activity logging for the floating overlay (avoid logging sensitive data).
if ($config->isDebug()) {
  try {
    // Avoid self-poll noise from the overlay endpoint.
    if ($path !== '/app/dev/logs') {
      $ms = (int)round((microtime(true) - $t0) * 1000);
      $status = (int)http_response_code();

      $u = $req->isApi() ? null : $ctx->auth()->user();
      $userId = is_array($u) && isset($u['id']) ? (int)$u['id'] : null;
      $userRole = is_array($u) && isset($u['role']) ? (string)$u['role'] : null;

      Log::info('http', [
        'rid' => $rid,
        'method' => $req->method(),
        'path' => $path,
        'status' => $status,
        'ms' => $ms,
        'ip' => $req->ip(),
        'user_id' => $userId,
        'role' => $userRole,
      ]);
    }
  } catch (Throwable $_) {
    // Never fail the request due to logging.
  }
}
