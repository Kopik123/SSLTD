<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

final class HealthController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    return Response::json(['ok' => true, 'ts' => gmdate('c')], 200);
  }

  public function db(Request $req, array $params): Response
  {
    try {
      $this->ctx->db()->fetchOne('SELECT 1 AS ok');
      return Response::json(['ok' => true, 'db' => true, 'ts' => gmdate('c')], 200);
    } catch (\Throwable $_) {
      return Response::json(['ok' => false, 'db' => false, 'ts' => gmdate('c')], 500);
    }
  }
}

