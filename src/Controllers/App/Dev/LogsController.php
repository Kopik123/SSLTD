<?php
declare(strict_types=1);

namespace App\Controllers\App\Dev;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class LogsController extends Controller
{
  private static function isPrivateIp(string $ip): bool
  {
    $ip = trim($ip);
    if ($ip === '') return false;

    // IPv6 loopback / local / link-local.
    $lower = strtolower($ip);
    if ($lower === '::1') return true;
    if (str_starts_with($lower, 'fc') || str_starts_with($lower, 'fd')) return true; // fc00::/7
    if (str_starts_with($lower, 'fe80:')) return true; // fe80::/10 (rough)

    // IPv4 checks.
    $parts = explode('.', $ip);
    if (count($parts) !== 4) return false;
    $a = (int)$parts[0];
    $b = (int)$parts[1];

    // 127.0.0.0/8 loopback
    if ($a === 127) return true;
    // 10.0.0.0/8
    if ($a === 10) return true;
    // 172.16.0.0/12
    if ($a === 172 && $b >= 16 && $b <= 31) return true;
    // 192.168.0.0/16
    if ($a === 192 && $b === 168) return true;
    // 169.254.0.0/16 link-local
    if ($a === 169 && $b === 254) return true;

    return false;
  }

  public function tail(Request $req, array $params): Response
  {
    // Hard gate: never expose logs in production.
    if (!$this->ctx->config()->isDev() && !$this->ctx->config()->isDebug()) {
      return Response::json(['error' => 'not_found'], 404);
    }

    // Extra hard gate: even in debug, only allow from private/LAN IPs.
    // This endpoint can be used pre-login during testing.
    if (!self::isPrivateIp($req->ip())) {
      return Response::json(['error' => 'not_found'], 404);
    }

    $root = dirname(__DIR__, 4);
    $logPath = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';

    $offsetRaw = (string)$req->query('offset', '0');
    $offset = ctype_digit($offsetRaw) ? (int)$offsetRaw : 0;
    if ($offset < 0) $offset = 0;

    $maxRaw = (string)$req->query('max_bytes', '32768');
    $maxBytes = ctype_digit($maxRaw) ? (int)$maxRaw : 32768;
    if ($maxBytes < 4096) $maxBytes = 4096;
    if ($maxBytes > 131072) $maxBytes = 131072;

    if (!is_file($logPath)) {
      return Response::json(['offset' => 0, 'lines' => [], 'missing' => true], 200);
    }

    clearstatcache(true, $logPath);
    $size = filesize($logPath);
    if (!is_int($size) || $size < 0) {
      return Response::json(['offset' => 0, 'lines' => [], 'error' => 'stat_failed'], 200);
    }

    if ($offset > $size) $offset = max(0, $size - $maxBytes);

    $readFrom = $offset;
    $readLen = $size - $readFrom;
    $truncated = false;
    if ($readLen > $maxBytes) {
      $truncated = true;
      $readFrom = max(0, $size - $maxBytes);
      $readLen = $size - $readFrom;
    }

    $fh = @fopen($logPath, 'rb');
    if ($fh === false) {
      return Response::json(['offset' => $size, 'lines' => [], 'error' => 'open_failed'], 200);
    }

    $chunk = '';
    try {
      if ($readFrom > 0) {
        @fseek($fh, $readFrom);
      }
      $data = @fread($fh, $readLen);
      if (is_string($data)) $chunk = $data;
    } finally {
      @fclose($fh);
    }

    // If we started mid-file, drop partial first line to avoid gibberish.
    if ($readFrom > 0) {
      $nl = strpos($chunk, "\n");
      if ($nl !== false) {
        $chunk = substr($chunk, $nl + 1);
      } else {
        $chunk = '';
      }
    }

    $chunk = str_replace("\r\n", "\n", $chunk);
    $chunk = str_replace("\r", "\n", $chunk);
    $lines = $chunk === '' ? [] : explode("\n", rtrim($chunk, "\n"));

    // Prevent huge payloads even if a single "line" is massive.
    $maxLines = 400;
    if (count($lines) > $maxLines) {
      $lines = array_slice($lines, -$maxLines);
      $truncated = true;
    }

    return Response::json([
      'offset' => $size,
      'lines' => $lines,
      'truncated' => $truncated,
    ], 200);
  }
}
