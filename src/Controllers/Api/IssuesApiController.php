<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Validate;

final class IssuesApiController extends Controller
{
  /** @return list<string> */
  private function allowedStatuses(): array
  {
    return ['open', 'in_progress', 'blocked', 'resolved', 'closed'];
  }

  /** @return list<string> */
  private function allowedSeverities(): array
  {
    return ['low', 'medium', 'high'];
  }

  public function listForProject(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) return Response::json(['error' => 'unauthorized'], 401);

    $pid = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT * FROM projects WHERE id = :id LIMIT 1', ['id' => $pid]);
    if ($project === null) return Response::json(['error' => 'not_found'], 404);
    if (!$this->canAccessProject($project, $u)) return Response::json(['error' => 'forbidden'], 403);

    $limit = (int)$req->query('limit', '100');
    if ($limit <= 0) $limit = 100;
    if ($limit > 200) $limit = 200;

    $rows = $this->ctx->db()->fetchAll(
      'SELECT i.id, i.project_id, i.status, i.severity, i.title, i.body, i.created_by_user_id, i.assigned_to_user_id, i.resolved_at, i.created_at, i.updated_at,
              u.name AS created_by_name
       FROM issues i
       LEFT JOIN users u ON u.id = i.created_by_user_id
       WHERE i.project_id = :pid
       ORDER BY i.updated_at DESC, i.id DESC
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
    $title = Validate::str(is_array($bodyJson) ? ($bodyJson['title'] ?? null) : null, 255, true);
    if ($title === null) return Response::json(['error' => 'invalid_title'], 422);

    $sev = Validate::enum(is_array($bodyJson) ? ($bodyJson['severity'] ?? null) : null, $this->allowedSeverities(), false);
    if ($sev === null) $sev = 'medium';

    $body = Validate::str(is_array($bodyJson) ? ($bodyJson['body'] ?? null) : null, 5000, false);
    $uid = (int)($u['id'] ?? 0);
    $now = gmdate('c');

    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO issues (project_id, status, severity, title, body, created_by_user_id, assigned_to_user_id, resolved_at, created_at, updated_at)
       VALUES (:pid, :st, :sev, :t, :b, :cb, NULL, NULL, :c, :u)',
      [
        'pid' => $pid,
        'st' => 'open',
        'sev' => $sev,
        't' => $title,
        'b' => $body === null || $body === '' ? null : $body,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('api_issue_add', 'issue', $id, ['project_id' => $pid, 'severity' => $sev]);
    return Response::json(['id' => $id], 201);
  }

  public function update(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) return Response::json(['error' => 'unauthorized'], 401);

    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM issues WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::json(['error' => 'not_found'], 404);

    $pid = (int)($row['project_id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT * FROM projects WHERE id = :id LIMIT 1', ['id' => $pid]);
    if ($project === null) return Response::json(['error' => 'not_found'], 404);
    if (!$this->canAccessProject($project, $u)) return Response::json(['error' => 'forbidden'], 403);

    $bodyJson = $req->json();
    $status = Validate::enum(is_array($bodyJson) ? ($bodyJson['status'] ?? null) : null, $this->allowedStatuses(), false);
    if ($status === null) $status = (string)($row['status'] ?? 'open');

    $sev = Validate::enum(is_array($bodyJson) ? ($bodyJson['severity'] ?? null) : null, $this->allowedSeverities(), false);
    if ($sev === null) $sev = (string)($row['severity'] ?? 'medium');

    $title = Validate::str(is_array($bodyJson) ? ($bodyJson['title'] ?? null) : null, 255, false);
    if ($title === null || $title === '') $title = (string)($row['title'] ?? '');

    $body = Validate::str(is_array($bodyJson) ? ($bodyJson['body'] ?? null) : null, 5000, false);
    if ($body === null) $body = (string)($row['body'] ?? '');

    $resolvedAt = $row['resolved_at'] ?? null;
    if (($status === 'resolved' || $status === 'closed') && empty($resolvedAt)) {
      $resolvedAt = gmdate('c');
    }
    if ($status !== 'resolved' && $status !== 'closed') {
      $resolvedAt = null;
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE issues
       SET status = :st, severity = :sev, title = :t, body = :b, resolved_at = :ra, updated_at = :u
       WHERE id = :id',
      [
        'st' => $status,
        'sev' => $sev,
        't' => $title,
        'b' => trim($body) === '' ? null : trim($body),
        'ra' => $resolvedAt,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('api_issue_update', 'issue', $id, ['project_id' => $pid, 'status' => $status, 'severity' => $sev]);
    return Response::json(['ok' => true], 200);
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

