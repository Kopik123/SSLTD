<?php
declare(strict_types=1);

/**
 * Minimal HTTP smoke test runner.
 *
 * Usage:
 *   php bin/smoke_http.php http://127.0.0.1:8000
 */

$base = $argv[1] ?? 'http://127.0.0.1:8000';
$base = rtrim((string)$base, '/');

function http_get(string $url): array
{
  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => 5,
      'ignore_errors' => true,
      'header' => "User-Agent: ss_ltd_smoke\r\n",
    ],
  ]);

  $body = @file_get_contents($url, false, $ctx);
  $headers = $http_response_header ?? [];
  $status = 0;
  foreach ($headers as $h) {
    if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m) === 1) {
      $status = (int)$m[1];
      break;
    }
  }
  return ['status' => $status, 'body' => is_string($body) ? $body : '', 'headers' => $headers];
}

$checks = [
  '/health' => 200,
  '/health/db' => 200,
  '/quote-request' => 200,
  '/login' => 200,
];

$fail = false;
foreach ($checks as $path => $want) {
  $url = $base . $path;
  $res = http_get($url);
  $ok = ($res['status'] === $want);
  $fail = $fail || !$ok;
  fwrite(STDOUT, sprintf("[%s] %s -> %d (want %d)\n", $ok ? 'OK' : 'FAIL', $path, $res['status'], $want));
}

exit($fail ? 1 : 0);

