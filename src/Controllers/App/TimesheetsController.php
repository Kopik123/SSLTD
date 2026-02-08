<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class TimesheetsController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $rows = $this->ctx->db()->fetchAll(
      'SELECT t.*, u.name AS user_name, u.email AS user_email, p.name AS project_name
       FROM timesheets t
       JOIN users u ON u.id = t.user_id
       LEFT JOIN projects p ON p.id = t.project_id
       ORDER BY t.started_at DESC
       LIMIT 200'
    );

    return $this->page('app/timesheets_list', [
      'title' => 'Timesheets',
      'rows' => $rows,
    ], 'layouts/app');
  }
}

