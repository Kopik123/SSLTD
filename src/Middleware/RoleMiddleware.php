<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Context;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class RoleMiddleware implements Middleware
{
  /** @var array<int, string> */
  private array $roles;

  /** @param array<int, string> $roles */
  public function __construct(array $roles)
  {
    $this->roles = $roles;
  }

  public function handle(Request $req, array $params, Context $ctx, callable $next): Response
  {
    $u = $req->isApi() ? $ctx->auth()->apiUserFromRequest($req) : $ctx->auth()->user();
    if ($u === null) {
      $base = $ctx->basePath();
      $login = $base === '' ? '/login' : ($base . '/login');
      return $req->isApi()
        ? Response::json(['error' => 'unauthorized'], 401)
        : Response::redirect($login);
    }

    $role = (string)($u['role'] ?? '');
    if (!in_array($role, $this->roles, true)) {
      return $req->isApi()
        ? Response::json(['error' => 'forbidden'], 403)
        : Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    return $next($req, $params);
  }
}
