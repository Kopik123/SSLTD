<?php
declare(strict_types=1);

/**
 * QA: Large file upload/download boundary tests (dev-only).
 *
 * This script:
 * - runs migrations + seed (idempotent)
 * - starts a temporary PHP built-in server on a free port
 * - logs in via API and uploads a near-limit file (9MB) and an over-limit file (11MB)
 * - logs in via WEB and downloads the uploaded file (streaming) to verify download path
 *
 * It uses the default seeded admin account:
 *   admin@ss.local / Admin123!
 */

require __DIR__ . '/../src/autoload.php';

use App\Support\RequestId;

final class Qa
{
  /** @return array{code:int, out:string} */
  public static function run(array $cmd, string $cwd): array
  {
    $desc = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $desc, $pipes, $cwd, null, ['bypass_shell' => true]);
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

  public static function curlInit(string $url, string $cookieJar): CurlHandle
  {
    $ch = curl_init($url);
    if ($ch === false) {
      throw new RuntimeException('curl_init failed');
    }
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_COOKIEJAR => $cookieJar,
      CURLOPT_COOKIEFILE => $cookieJar,
      CURLOPT_USERAGENT => 'ss-ltd-qa/1.0',
      CURLOPT_HTTPHEADER => [
        'X-Request-Id: qa-' . (RequestId::get() ?? 'local'),
      ],
    ]);
    return $ch;
  }

  /** @return array{status:int, body:string, headers:array<string,string>} */
  public static function curlExec(CurlHandle $ch): array
  {
    $headers = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($ch, string $line) use (&$headers): int {
      $len = strlen($line);
      $line = trim($line);
      if ($line === '' || !str_contains($line, ':')) return $len;
      [$k, $v] = explode(':', $line, 2);
      $k = strtolower(trim($k));
      $v = trim($v);
      if ($k !== '') $headers[$k] = $v;
      return $len;
    });

    $body = curl_exec($ch);
    if (!is_string($body)) {
      $err = curl_error($ch);
      throw new RuntimeException('curl_exec failed: ' . $err);
    }
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    return ['status' => $status, 'body' => $body, 'headers' => $headers];
  }

  public static function assert(bool $ok, string $msg): void
  {
    if (!$ok) {
      throw new RuntimeException($msg);
    }
  }
}

function make_fake_jpeg(string $path, int $bytes): void
{
  if ($bytes < 4096) {
    throw new RuntimeException('bytes too small');
  }
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }

  // Minimal JFIF header. The file may not be a valid image, but should be detected as image/jpeg by finfo.
  $hdr = hex2bin('FFD8FFE000104A46494600010101004800480000');
  if (!is_string($hdr)) {
    throw new RuntimeException('header build failed');
  }
  $eoi = hex2bin('FFD9');
  if (!is_string($eoi)) {
    throw new RuntimeException('eoi build failed');
  }

  $pad = $bytes - strlen($hdr) - strlen($eoi);
  if ($pad < 0) {
    throw new RuntimeException('bytes too small for header');
  }

  $fh = fopen($path, 'wb');
  if ($fh === false) {
    throw new RuntimeException('failed to open file for write: ' . $path);
  }
  fwrite($fh, $hdr);
  $chunk = str_repeat("\0", 1024 * 1024);
  while ($pad > 0) {
    $n = min($pad, strlen($chunk));
    fwrite($fh, substr($chunk, 0, $n));
    $pad -= $n;
  }
  fwrite($fh, $eoi);
  fclose($fh);
}

function pick_port(): int
{
  // Try a small range to avoid collisions.
  for ($p = 8099; $p <= 8120; $p++) {
    $errno = 0;
    $errstr = '';
    $s = @stream_socket_server('tcp://127.0.0.1:' . $p, $errno, $errstr);
    if ($s !== false) {
      fclose($s);
      return $p;
    }
  }
  return 8099;
}

$root = dirname(__DIR__);
$php = 'c:\\xampp\\php\\php.exe';

try {
  // Ensure DB/schema/content exists. Seed is idempotent.
  $r1 = Qa::run([$php, 'bin/migrate.php'], $root);
  Qa::assert($r1['code'] === 0, 'migrate failed: ' . trim($r1['out']));
  $r2 = Qa::run([$php, 'bin/seed.php'], $root);
  Qa::assert($r2['code'] === 0, 'seed failed: ' . trim($r2['out']));

  $port = pick_port();
  $base = 'http://127.0.0.1:' . $port;

  $desc = [
    0 => ['pipe', 'r'],
    1 => ['file', $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'qa_server_out.log', 'ab'],
    2 => ['file', $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'qa_server_err.log', 'ab'],
  ];
  $server = proc_open([$php, '-S', '127.0.0.1:' . $port, 'index.php'], $desc, $pipes, $root, null, ['bypass_shell' => true]);
  Qa::assert(is_resource($server), 'failed to start php -S server');
  if (isset($pipes[0]) && is_resource($pipes[0])) {
    fclose($pipes[0]);
  }

  // Wait for server.
  $cookieJar = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'qa_large_files_cookies.txt';
  $t0 = microtime(true);
  $ready = false;
  while ((microtime(true) - $t0) < 8.0) {
    try {
      $ch = Qa::curlInit($base . '/health', $cookieJar);
      $res = Qa::curlExec($ch);
      curl_close($ch);
      if ($res['status'] === 200) {
        $ready = true;
        break;
      }
    } catch (Throwable $_) {
      // ignore
    }
    usleep(200_000);
  }
  Qa::assert($ready, 'server not ready on ' . $base);

  // API login (seeded admin).
  $ch = Qa::curlInit($base . '/api/auth/login', $cookieJar);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'X-Request-Id: qa-' . (RequestId::get() ?? 'local'),
    ],
    CURLOPT_POSTFIELDS => json_encode(['email' => 'admin@ss.local', 'password' => 'Admin123!'], JSON_UNESCAPED_SLASHES),
  ]);
  $login = Qa::curlExec($ch);
  curl_close($ch);
  Qa::assert($login['status'] === 200, 'api login failed, status=' . $login['status']);
  $loginJson = json_decode($login['body'], true);
  Qa::assert(is_array($loginJson) && isset($loginJson['token']) && is_string($loginJson['token']) && $loginJson['token'] !== '', 'api login response invalid');
  $token = $loginJson['token'];

  // Fetch a project id.
  $ch = Qa::curlInit($base . '/api/projects', $cookieJar);
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $token,
      'X-Request-Id: qa-' . (RequestId::get() ?? 'local'),
    ],
  ]);
  $projects = Qa::curlExec($ch);
  curl_close($ch);
  Qa::assert($projects['status'] === 200, 'api projects failed, status=' . $projects['status']);
  $pj = json_decode($projects['body'], true);
  Qa::assert(is_array($pj) && isset($pj['items']) && is_array($pj['items']) && $pj['items'] !== [], 'api projects list empty');
  $projectId = (int)($pj['items'][0]['id'] ?? 0);
  Qa::assert($projectId > 0, 'invalid project id');

  // Create near-limit file (9MB) and upload (should succeed).
  $tmpDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'qa_large_files';
  $okPath = $tmpDir . DIRECTORY_SEPARATOR . 'ok_9mb.jpg';
  $okBytes = 9 * 1024 * 1024;
  make_fake_jpeg($okPath, $okBytes);

  $ch = Qa::curlInit($base . '/api/uploads', $cookieJar);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $token,
      'X-Request-Id: qa-' . (RequestId::get() ?? 'local'),
    ],
    CURLOPT_POSTFIELDS => [
      'owner_type' => 'project',
      'owner_id' => (string)$projectId,
      'stage' => 'doc',
      'client_visible' => '0',
      'file' => new CURLFile($okPath, 'image/jpeg', 'ok_9mb.jpg'),
    ],
  ]);
  $upOk = Qa::curlExec($ch);
  curl_close($ch);
  Qa::assert($upOk['status'] === 201, 'upload 9mb failed, status=' . $upOk['status'] . ' body=' . trim($upOk['body']));
  $upOkJson = json_decode($upOk['body'], true);
  Qa::assert(is_array($upOkJson) && isset($upOkJson['ids']) && is_array($upOkJson['ids']) && $upOkJson['ids'] !== [], 'upload 9mb response invalid');
  $uploadId = (int)$upOkJson['ids'][0];
  Qa::assert($uploadId > 0, 'invalid upload id');

  // Oversize file (11MB) should fail.
  $badPath = $tmpDir . DIRECTORY_SEPARATOR . 'bad_11mb.jpg';
  $badBytes = 11 * 1024 * 1024;
  make_fake_jpeg($badPath, $badBytes);

  $ch = Qa::curlInit($base . '/api/uploads', $cookieJar);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $token,
      'X-Request-Id: qa-' . (RequestId::get() ?? 'local'),
    ],
    CURLOPT_POSTFIELDS => [
      'owner_type' => 'project',
      'owner_id' => (string)$projectId,
      'stage' => 'doc',
      'client_visible' => '0',
      'file' => new CURLFile($badPath, 'image/jpeg', 'bad_11mb.jpg'),
    ],
  ]);
  $upBad = Qa::curlExec($ch);
  curl_close($ch);
  Qa::assert($upBad['status'] === 400, 'upload 11mb should fail, got status=' . $upBad['status']);

  // WEB login, then download (streaming) and validate bytes.
  $ch = Qa::curlInit($base . '/login', $cookieJar);
  $loginForm = Qa::curlExec($ch);
  curl_close($ch);
  Qa::assert($loginForm['status'] === 200, 'web login form failed, status=' . $loginForm['status']);
  if (!preg_match('/name=\"_csrf\"\\s+value=\"([^\"]+)\"/', $loginForm['body'], $m)) {
    throw new RuntimeException('failed to extract csrf token from login form');
  }
  $csrf = $m[1];

  $ch = Qa::curlInit($base . '/login', $cookieJar);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
      '_csrf' => $csrf,
      'next' => '/app',
      'email' => 'admin@ss.local',
      'password' => 'Admin123!',
    ]),
  ]);
  $webLogin = Qa::curlExec($ch);
  curl_close($ch);
  Qa::assert($webLogin['status'] === 200 || $webLogin['status'] === 302, 'web login failed, status=' . $webLogin['status']);

  $outPath = $tmpDir . DIRECTORY_SEPARATOR . 'downloaded_9mb.jpg';
  $fh = fopen($outPath, 'wb');
  Qa::assert($fh !== false, 'failed to open output file');
  $ch = Qa::curlInit($base . '/app/uploads/' . $uploadId, $cookieJar);
  curl_setopt($ch, CURLOPT_FILE, $fh);
  $dl = Qa::curlExec($ch);
  curl_close($ch);
  fclose($fh);

  Qa::assert($dl['status'] === 200, 'download failed, status=' . $dl['status']);
  clearstatcache(true, $outPath);
  $dlSize = filesize($outPath);
  Qa::assert(is_int($dlSize) && $dlSize === $okBytes, 'download size mismatch: got=' . (string)$dlSize . ' expected=' . (string)$okBytes);

  // Stop server.
  proc_terminate($server);

  fwrite(STDOUT, "QA large files: OK\n");
  exit(0);
} catch (Throwable $e) {
  $msg = "QA large files: FAIL\n" . $e->getMessage() . "\n";
  // Some runners only show STDOUT reliably, so write to both.
  fwrite(STDOUT, $msg);
  fwrite(STDERR, $msg);
  exit(1);
}
