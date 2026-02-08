<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectReportsController extends Controller
{
  public function add(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $body = trim((string)$req->input('body', ''));
    if ($body === '' || mb_strlen($body) > 5000) {
      $this->ctx->session()->flash('error', 'Report body is required (max 5000 chars).');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO project_reports (project_id, body, created_by_user_id, created_at)
       VALUES (:pid, :b, :cb, :c)',
      [
        'pid' => $projectId,
        'b' => $body,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
      ]
    );

    $this->audit('project_report_add', 'project_report', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Report added.');
    return $this->redirect('/app/projects/' . $projectId);
  }
}

