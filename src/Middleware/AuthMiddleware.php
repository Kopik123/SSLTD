<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Context;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class AuthMiddleware implements Middleware
{
  public function handle(Request $req, array $params, Context $ctx, callable $next): Response
  {
    if ($req->isApi()) {
      $u = $ctx->auth()->apiUserFromRequest($req);
      if ($u === null) {
        return Response::json(['error' => 'unauthorized'], 401);
      }
      return $next($req, $params);
    }

    if (!$ctx->auth()->check()) {
      $nextUrl = urlencode($req->path());
      $base = $ctx->basePath();
      $to = '/login?next=' . $nextUrl;
      if ($base !== '') {
        $to = $base . $to;
      }
      return Response::redirect($to);
    }

    return $next($req, $params);
  }
}
