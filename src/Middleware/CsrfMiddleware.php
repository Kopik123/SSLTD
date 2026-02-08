<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Context;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class CsrfMiddleware implements Middleware
{
  public function handle(Request $req, array $params, Context $ctx, callable $next): Response
  {
    if ($req->isApi()) {
      return $next($req, $params);
    }

    $method = $req->method();
    if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS') {
      return $next($req, $params);
    }

    $token = $req->input('_csrf');
    if (!is_string($token) || !$ctx->csrf()->validate($token)) {
      return Response::html('<h1>419</h1><p>Invalid CSRF token.</p>', 419);
    }

    return $next($req, $params);
  }
}

