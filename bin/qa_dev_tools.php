<?php
declare(strict_types=1);

/**
 * QA: Dev tools overlay endpoints (dev-only).
 *
 * - Starts a temporary PHP built-in server on a free port
 * - Calls /app/dev/tools/whoami and /app/dev/tools/users
 * - Uses CSRF from /login to call POST endpoints (ratelimit clear, login-as, logout)
 *
 * Expected: OK in APP_DEBUG=1.
 */

require __DIR__ . '/../src/autoload.php';

final class QaDev
{
  public static function assert(bool $ok, string $msg): void
  {
    if (!$ok) throw new RuntimeException($msg);
  }

  /** @return array{status:int, body:string, headers:array<string,string>} */
  public static function curl(string $url, string $cookieJar, array $opts = []): array
  {
    $ch = curl_init($url);
    if ($ch === false) {
      throw new RuntimeException('curl_init failed');
    }
    $headers = [];
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_COOKIEJAR => $cookieJar,
      CURLOPT_COOKIEFILE => $cookieJar,
      CURLOPT_USERAGENT => 'ss-ltd-qa-dev-tools/1.0',
      CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
        $len = strlen($line);
        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) return $len;
        [$k, $v] = explode(':', $line, 2);
        $k = strtolower(trim($k));
        $v = trim($v);
        if ($k !== '') $headers[$k] = $v;
        return $len;
      },
    ] + $opts);
    $body = curl_exec($ch);
    if (!is_string($body)) {
      $err = curl_error($ch);
      curl_close($ch);
      throw new RuntimeException('curl_exec failed: ' . $err);
    }
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $body, 'headers' => $headers];
  }
}

function pick_port(): int
{
  for ($p = 8121; $p <= 8140; $p++) {
    $errno = 0;
    $errstr = '';
    $s = @stream_socket_server('tcp://127.0.0.1:' . $p, $errno, $errstr);
    if ($s !== false) {
      fclose($s);
      return $p;
    }
  }
  return 8121;
}

$root = dirname(__DIR__);
$php = 'c:\\xampp\\php\\php.exe';
$cookieJar = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'qa_dev_tools_cookies.txt';

try {
  $port = pick_port();
  $base = 'http://127.0.0.1:' . $port;

  $desc = [
    0 => ['pipe', 'r'],
    1 => ['file', $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'qa_dev_tools_out.log', 'ab'],
    2 => ['file', $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'qa_dev_tools_err.log', 'ab'],
  ];
  $server = proc_open([$php, '-S', '127.0.0.1:' . $port, 'index.php'], $desc, $pipes, $root, null, ['bypass_shell' => true]);
  QaDev::assert(is_resource($server), 'failed to start php -S server');
  if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);

  // Wait for server.
  $t0 = microtime(true);
  $ready = false;
  while ((microtime(true) - $t0) < 8.0) {
    try {
      $r = QaDev::curl($base . '/health', $cookieJar);
      if ($r['status'] === 200) { $ready = true; break; }
    } catch (Throwable $_) {}
    usleep(200_000);
  }
  QaDev::assert($ready, 'server not ready on ' . $base);

  $w = QaDev::curl($base . '/app/dev/tools/whoami', $cookieJar);
  QaDev::assert($w['status'] === 200, 'whoami status=' . $w['status']);
  $wj = json_decode($w['body'], true);
  QaDev::assert(is_array($wj) && array_key_exists('user', $wj), 'whoami invalid json');

  $u = QaDev::curl($base . '/app/dev/tools/users', $cookieJar);
  QaDev::assert($u['status'] === 200, 'users status=' . $u['status']);
  $uj = json_decode($u['body'], true);
  QaDev::assert(is_array($uj) && isset($uj['items']) && is_array($uj['items']) && $uj['items'] !== [], 'users invalid/empty json');

  // CSRF from /login.
  $login = QaDev::curl($base . '/login', $cookieJar);
  QaDev::assert($login['status'] === 200, 'login form status=' . $login['status']);
  QaDev::assert(preg_match('/name=\"_csrf\"\\s+value=\"([^\"]+)\"/', $login['body'], $m) === 1, 'csrf not found');
  $csrf = $m[1];

  $rl = QaDev::curl($base . '/app/dev/tools/ratelimit/clear', $cookieJar, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query(['_csrf' => $csrf]),
  ]);
  QaDev::assert($rl['status'] === 200, 'ratelimit clear status=' . $rl['status']);

  // Pick an active admin if possible.
  $pick = null;
  foreach ($uj['items'] as $it) {
    if (!is_array($it)) continue;
    if (($it['status'] ?? '') === 'active' && ($it['role'] ?? '') === 'admin') { $pick = $it; break; }
  }
  if ($pick === null) {
    foreach ($uj['items'] as $it) {
      if (!is_array($it)) continue;
      if (($it['status'] ?? '') === 'active') { $pick = $it; break; }
    }
  }
  QaDev::assert($pick !== null, 'no active user to login-as');
  $pickId = (int)($pick['id'] ?? 0);
  QaDev::assert($pickId > 0, 'bad picked user id');

  $la = QaDev::curl($base . '/app/dev/tools/login-as', $cookieJar, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query(['_csrf' => $csrf, 'user_id' => (string)$pickId]),
  ]);
  QaDev::assert($la['status'] === 200, 'login-as status=' . $la['status']);

  $w2 = QaDev::curl($base . '/app/dev/tools/whoami', $cookieJar);
  QaDev::assert($w2['status'] === 200, 'whoami2 status=' . $w2['status']);
  $w2j = json_decode($w2['body'], true);
  QaDev::assert(is_array($w2j) && is_array($w2j['user'] ?? null) && (int)($w2j['user']['id'] ?? 0) === $pickId, 'whoami did not switch');

  $lo = QaDev::curl($base . '/app/dev/tools/logout', $cookieJar, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query(['_csrf' => $csrf]),
  ]);
  QaDev::assert($lo['status'] === 200, 'logout status=' . $lo['status']);

  $w3 = QaDev::curl($base . '/app/dev/tools/whoami', $cookieJar);
  QaDev::assert($w3['status'] === 200, 'whoami3 status=' . $w3['status']);
  $w3j = json_decode($w3['body'], true);
  QaDev::assert(is_array($w3j) && ($w3j['user'] ?? null) === null, 'whoami not null after logout');

  proc_terminate($server);

  fwrite(STDOUT, "QA dev tools: OK\n");
  exit(0);
} catch (Throwable $e) {
  $msg = "QA dev tools: FAIL\n" . $e->getMessage() . "\n";
  fwrite(STDOUT, $msg);
  fwrite(STDERR, $msg);
  exit(1);
}

