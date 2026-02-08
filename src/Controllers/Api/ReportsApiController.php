<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Validate;

final class ReportsApiController extends Controller
{
  public function listForProject(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) return Response::json(['error' => 'unauthorized'], 401);

    $pid = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT * FROM projects WHERE id = :id LIMIT 1', ['id' => $pid]);
    if ($project === null) return Response::json(['error' => 'not_found'], 404);
    if (!$this->canAccessProject($project, $u)) return Response::json(['error' => 'forbidden'], 403);

    $limit = (int)$req->query('limit', '50');
    if ($limit <= 0) $limit = 50;
    if ($limit > 200) $limit = 200;

    $rows = $this->ctx->db()->fetchAll(
      'SELECT r.id, r.project_id, r.body, r.created_at, r.created_by_user_id, u.name AS created_by_name
       FROM project_reports r
       LEFT JOIN users u ON u.id = r.created_by_user_id
       WHERE r.project_id = :pid
       ORDER BY r.id DESC
       LIMIT ' . $limit,
      ['pid' => $pid]
    );

    return Response::json(['items' => $rows], 200);
  }

  public function createForProject(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) return Response::json(['error' => 'unauthorized'], 401);

    $pid = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT * FROM projects WHERE id = :id LIMIT 1', ['id' => $pid]);
    if ($project === null) return Response::json(['error' => 'not_found'], 404);
    if (!$this->canAccessProject($project, $u)) return Response::json(['error' => 'forbidden'], 403);

    $bodyJson = $req->json();
    $body = Validate::str(is_array($bodyJson) ? ($bodyJson['body'] ?? null) : null, 5000, true);
    if ($body === null) return Response::json(['error' => 'invalid_body'], 422);

    $uid = (int)($u['id'] ?? 0);
    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO project_reports (project_id, body, created_by_user_id, created_at)
       VALUES (:pid, :b, :cb, :c)',
      ['pid' => $pid, 'b' => $body, 'cb' => $uid > 0 ? $uid : null, 'c' => $now]
    );

    $this->audit('api_project_report_add', 'project_report', $id, ['project_id' => $pid]);
    return Response::json(['id' => $id], 201);
  }

  /** @param array<string,mixed> $project
   *  @param array<string,mixed> $user
   */
  private function canAccessProject(array $project, array $user): bool
  {
    $role = (string)($user['role'] ?? '');
    $uid = (int)($user['id'] ?? 0);

    if ($role === 'admin' || $role === 'pm') return true;
    if ($role === 'client') return (int)($project['client_user_id'] ?? 0) === $uid;

    $mem = $this->ctx->db()->fetchOne(
      'SELECT 1 AS ok FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1',
      ['pid' => (int)($project['id'] ?? 0), 'uid' => $uid]
    );
    return $mem !== null;
  }
}

