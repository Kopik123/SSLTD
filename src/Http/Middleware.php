<?php
declare(strict_types=1);

namespace App\Http;

use App\Context;

interface Middleware
{
  /** @param array<string, string> $params */
  public function handle(Request $req, array $params, Context $ctx, callable $next): Response;
}

