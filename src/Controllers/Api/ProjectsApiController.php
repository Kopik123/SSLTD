<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectsApiController extends Controller
{
  public function list(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $role = (string)($u['role'] ?? '');
    $uid = (int)($u['id'] ?? 0);
    $status = trim((string)$req->query('status', ''));
    $assigned = strtolower(trim((string)$req->query('assigned', '')));

    $page = (int)$req->query('page', '1');
    if ($page <= 0) $page = 1;
    $perPage = (int)$req->query('per_page', '50');
    if ($perPage <= 0) $perPage = 50;
    if ($perPage > 200) $perPage = 200;
    $offset = ($page - 1) * $perPage;
    if ($offset < 0) $offset = 0;
    if ($offset > 100000) $offset = 100000;

    $slice = function (array $rows) use ($perPage): array {
      $hasMore = false;
      if (count($rows) > $perPage) {
        $hasMore = true;
        $rows = array_slice($rows, 0, $perPage);
      }
      return [$rows, $hasMore];
    };

    $where = [];
    $bind = [];
    if ($status !== '') {
      $where[] = 'p.status = :st';
      $bind['st'] = $status;
    }

    if ($role === 'admin' || $role === 'pm') {
      if ($assigned === 'me') {
        $where[] = 'p.assigned_pm_user_id = :pmid';
        $bind['pmid'] = $uid;
      }
      $sql =
        'SELECT p.*, pm.name AS pm_name, c.name AS client_name, c.email AS client_email
         FROM projects p
         LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
         LEFT JOIN users c ON c.id = p.client_user_id';
      if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
      }
      $sql .= ' ORDER BY p.created_at DESC LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;
      $rows = $this->ctx->db()->fetchAll($sql, $bind);
      [$items, $hasMore] = $slice($rows);
      return Response::json(['items' => $items, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => $hasMore]], 200);
    }

    if ($role === 'client') {
      $where[] = 'p.client_user_id = :uid';
      $bind['uid'] = $uid;
      $sql =
        'SELECT p.*, pm.name AS pm_name
         FROM projects p
         LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY p.created_at DESC
         LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;
      $rows = $this->ctx->db()->fetchAll($sql, $bind);
      [$items, $hasMore] = $slice($rows);
      return Response::json(['items' => $items, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => $hasMore]], 200);
    }

    // employee/subcontractor worker etc: membership-based
    $where[] = 'm.user_id = :uid';
    $bind['uid'] = $uid;
    $sql =
      'SELECT p.*, pm.name AS pm_name
       FROM project_members m
       JOIN projects p ON p.id = m.project_id
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       WHERE ' . implode(' AND ', $where) . '
       ORDER BY p.created_at DESC
       LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;
    $rows = $this->ctx->db()->fetchAll($sql, $bind);
    [$items, $hasMore] = $slice($rows);
    return Response::json(['items' => $items, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => $hasMore]], 200);
  }

  public function get(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $id = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne(
      'SELECT p.*, pm.name AS pm_name, c.name AS client_name, c.email AS client_email
       FROM projects p
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       LEFT JOIN users c ON c.id = p.client_user_id
       WHERE p.id = :id
       LIMIT 1',
      ['id' => $id]
    );
    if ($project === null) {
      return Response::json(['error' => 'not_found'], 404);
    }

    if (!$this->canAccessProject($project, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    return Response::json(['item' => $project], 200);
  }

  /** @param array<string, mixed> $project
   *  @param array<string, mixed> $user
   */
  private function canAccessProject(array $project, array $user): bool
  {
    $role = (string)($user['role'] ?? '');
    $uid = (int)($user['id'] ?? 0);

    if ($role === 'admin' || $role === 'pm') {
      return true;
    }

    if ($role === 'client') {
      return (int)($project['client_user_id'] ?? 0) === $uid;
    }

    $mem = $this->ctx->db()->fetchOne(
      'SELECT 1 AS ok FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1',
      ['pid' => (int)$project['id'], 'uid' => $uid]
    );
    return $mem !== null;
  }
}
