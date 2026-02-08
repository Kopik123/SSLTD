<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class DashboardController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    $role = (string)($u['role'] ?? '');

    if ($role === 'client' && $u !== null) {
      $uid = (int)$u['id'];
      $leadsNew = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM quote_requests WHERE client_user_id = :uid AND status = :s', ['uid' => $uid, 's' => 'quote_requested'])['c'] ?? 0);
      $leadsAll = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM quote_requests WHERE client_user_id = :uid', ['uid' => $uid])['c'] ?? 0);
      $projectsActive = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM projects WHERE client_user_id = :uid AND status IN (\'in_progress\', \'ready_for_execution\')', ['uid' => $uid])['c'] ?? 0);
      $projectsAll = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM projects WHERE client_user_id = :uid', ['uid' => $uid])['c'] ?? 0);

      return $this->page('app/dashboard', [
        'title' => 'Dashboard',
        'kpis' => [
          ['label' => 'My new leads', 'value' => (string)$leadsNew],
          ['label' => 'My leads (all)', 'value' => (string)$leadsAll],
          ['label' => 'My active projects', 'value' => (string)$projectsActive],
          ['label' => 'My projects (all)', 'value' => (string)$projectsAll],
        ],
      ], 'layouts/app');
    }

    $leadsNew = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM quote_requests WHERE status = :s', ['s' => 'quote_requested'])['c'] ?? 0);
    $leadsAll = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM quote_requests')['c'] ?? 0);
    $projectsActive = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM projects WHERE status IN (\'in_progress\', \'ready_for_execution\')')['c'] ?? 0);
    $projectsAll = (int)($this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM projects')['c'] ?? 0);

    return $this->page('app/dashboard', [
      'title' => 'Dashboard',
      'kpis' => [
        ['label' => 'New leads', 'value' => (string)$leadsNew],
        ['label' => 'Leads (all)', 'value' => (string)$leadsAll],
        ['label' => 'Active projects', 'value' => (string)$projectsActive],
        ['label' => 'Projects (all)', 'value' => (string)$projectsAll],
      ],
    ], 'layouts/app');
  }
}
