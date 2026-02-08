<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Context;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class RateLimitMiddleware implements Middleware
{
  private string $bucket;
  private int $limit;
  private int $windowSeconds;
  /** @var null|callable */
  private $keySuffixFn;

  public function __construct(string $bucket, int $limit, int $windowSeconds, ?callable $keySuffixFn = null)
  {
    $this->bucket = $bucket;
    $this->limit = $limit;
    $this->windowSeconds = $windowSeconds;
    $this->keySuffixFn = $keySuffixFn;
  }

  public function handle(Request $req, array $params, Context $ctx, callable $next): Response
  {
    $ip = preg_replace('/[^0-9a-fA-F:\\.]/', '_', $req->ip()) ?: 'unknown';
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'ratelimit';
    if (!is_dir($dir)) {
      @mkdir($dir, 0777, true);
    }

    $suffix = '';
    if ($this->keySuffixFn !== null) {
      try {
        $suffix = (string)($this->keySuffixFn)($req);
      } catch (\Throwable $_) {
        $suffix = '';
      }
      $suffix = strtolower(trim($suffix));
      // Sanitize to keep filename safe and stable.
      $suffix = preg_replace('/[^a-z0-9_\\-\\.@]/', '_', $suffix) ?: '';
      if (strlen($suffix) > 80) {
        $suffix = substr($suffix, 0, 80);
      }
    }

    $key = $this->bucket . ($suffix !== '' ? ('_' . $suffix) : '');
    $path = $dir . DIRECTORY_SEPARATOR . $key . '_' . $ip . '.json';
    $now = time();
    $state = ['count' => 0, 'reset_at' => $now + $this->windowSeconds];
    if (is_file($path)) {
      $raw = file_get_contents($path);
      $decoded = is_string($raw) ? json_decode($raw, true) : null;
      if (is_array($decoded) && isset($decoded['count'], $decoded['reset_at'])) {
        $state['count'] = (int)$decoded['count'];
        $state['reset_at'] = (int)$decoded['reset_at'];
      }
    }

    if ($now > $state['reset_at']) {
      $state = ['count' => 0, 'reset_at' => $now + $this->windowSeconds];
    }

    $state['count']++;
    @file_put_contents($path, json_encode($state), LOCK_EX);

    if ($state['count'] > $this->limit) {
      $retryAfter = max(1, $state['reset_at'] - $now);
      if ($req->isApi()) {
        return Response::json(['error' => 'rate_limited', 'retry_after_seconds' => $retryAfter], 429)
          ->withHeader('Retry-After', (string)$retryAfter);
      }
      return Response::html('<h1>429</h1><p>Too many requests. Try again later.</p>', 429)
        ->withHeader('Retry-After', (string)$retryAfter);
    }

    return $next($req, $params);
  }
}
