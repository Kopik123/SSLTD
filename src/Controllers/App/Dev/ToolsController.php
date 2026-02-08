<?php
declare(strict_types=1);

namespace App\Controllers\App\Dev;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Dev-only testing tools.
 *
 * IMPORTANT:
 * - Must never be exposed in production.
 * - Must remain gated even in debug (IP gate + optional key) because it can
 *   mutate session state.
 */
final class ToolsController extends Controller
{
  private static function isPrivateIp(string $ip): bool
  {
    $ip = trim($ip);
    if ($ip === '') return false;

    $lower = strtolower($ip);
    if ($lower === '::1') return true;
    if (str_starts_with($lower, 'fc') || str_starts_with($lower, 'fd')) return true; // fc00::/7
    if (str_starts_with($lower, 'fe80:')) return true; // link-local (rough)

    $parts = explode('.', $ip);
    if (count($parts) !== 4) return false;
    $a = (int)$parts[0];
    $b = (int)$parts[1];

    if ($a === 127) return true;
    if ($a === 10) return true;
    if ($a === 172 && $b >= 16 && $b <= 31) return true;
    if ($a === 192 && $b === 168) return true;
    if ($a === 169 && $b === 254) return true;
    return false;
  }

  private static function isLoopback(string $ip): bool
  {
    $ip = strtolower(trim($ip));
    return $ip === '::1' || str_starts_with($ip, '127.');
  }

  private function gate(Request $req, bool $dangerous = false): ?Response
  {
    // Never expose in production.
    if (!$this->ctx->config()->isDebug()) {
      return Response::json(['error' => 'not_found'], 404);
    }

    $ip = $req->ip();
    if (!self::isPrivateIp($ip)) {
      return Response::json(['error' => 'not_found'], 404);
    }

    if ($dangerous) {
      // For dangerous actions allow:
      // - loopback always
      // - otherwise require a dev key (optional, set via env).
      if (self::isLoopback($ip)) {
        return null;
      }
      $key = getenv('SS_DEV_TOOLS_KEY');
      $key = is_string($key) ? trim($key) : '';
      if ($key === '') {
        return Response::json(['error' => 'not_found'], 404);
      }
      $provided = trim((string)$req->input('_dev_key', ''));
      if ($provided === '' || !hash_equals($key, $provided)) {
        return Response::json(['error' => 'not_found'], 404);
      }
    }

    return null;
  }

  public function whoami(Request $req, array $params): Response
  {
    $deny = $this->gate($req, false);
    if ($deny !== null) return $deny;

    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::json(['user' => null], 200);
    }
    return Response::json([
      'user' => [
        'id' => (int)($u['id'] ?? 0),
        'role' => (string)($u['role'] ?? ''),
        'name' => (string)($u['name'] ?? ''),
        'email' => (string)($u['email'] ?? ''),
        'status' => (string)($u['status'] ?? ''),
      ],
    ], 200);
  }

  public function users(Request $req, array $params): Response
  {
    $deny = $this->gate($req, false);
    if ($deny !== null) return $deny;

    $rows = $this->ctx->db()->fetchAll(
      'SELECT id, role, name, email, status, created_at, updated_at
       FROM users
       ORDER BY role ASC, id ASC'
    );
    $items = [];
    foreach ($rows as $r) {
      $items[] = [
        'id' => (int)($r['id'] ?? 0),
        'role' => (string)($r['role'] ?? ''),
        'name' => (string)($r['name'] ?? ''),
        'email' => (string)($r['email'] ?? ''),
        'status' => (string)($r['status'] ?? ''),
      ];
    }
    return Response::json(['items' => $items], 200);
  }

  public function loginAs(Request $req, array $params): Response
  {
    $deny = $this->gate($req, true);
    if ($deny !== null) return $deny;

    $uid = (int)$req->input('user_id', 0);
    if ($uid <= 0) {
      return Response::json(['error' => 'invalid_user_id'], 400);
    }

    $u = $this->ctx->db()->fetchOne('SELECT id, status FROM users WHERE id = :id LIMIT 1', ['id' => $uid]);
    if ($u === null) {
      return Response::json(['error' => 'not_found'], 404);
    }
    if ((string)($u['status'] ?? '') !== 'active') {
      return Response::json(['error' => 'user_not_active'], 400);
    }

    $this->ctx->auth()->login($uid);
    return Response::json(['ok' => true], 200);
  }

  public function logout(Request $req, array $params): Response
  {
    $deny = $this->gate($req, true);
    if ($deny !== null) return $deny;

    $this->ctx->auth()->logout();
    return Response::json(['ok' => true], 200);
  }

  public function clearRateLimits(Request $req, array $params): Response
  {
    $deny = $this->gate($req, true);
    if ($deny !== null) return $deny;

    $dir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'ratelimit';
    if (!is_dir($dir)) {
      return Response::json(['ok' => true, 'deleted' => 0], 200);
    }

    $deleted = 0;
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];
    foreach ($files as $p) {
      if (!is_string($p) || $p === '') continue;
      if (@unlink($p)) $deleted++;
    }

    return Response::json(['ok' => true, 'deleted' => $deleted], 200);
  }
}

