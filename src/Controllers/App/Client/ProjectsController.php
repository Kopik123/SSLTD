<?php
declare(strict_types=1);

namespace App\Controllers\App\Client;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectsController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || ($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $uid = (int)$u['id'];
    $projects = $this->ctx->db()->fetchAll(
      'SELECT p.*, pm.name AS pm_name
       FROM projects p
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       WHERE p.client_user_id = :uid
       ORDER BY p.created_at DESC
       LIMIT 200',
      ['uid' => $uid]
    );

    return $this->page('app/client_projects_list', [
      'title' => 'My projects',
      'projects' => $projects,
    ], 'layouts/app');
  }

  public function show(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || ($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $uid = (int)$u['id'];
    $id = (int)($params['id'] ?? 0);

    $project = $this->ctx->db()->fetchOne(
      'SELECT p.*, pm.name AS pm_name
       FROM projects p
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       WHERE p.id = :id AND p.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $threadId = $this->ensureThread('project', $id);

    $attachments = $this->ctx->db()->fetchAll(
      'SELECT id, original_name, mime_type, size_bytes, created_at, stage, client_visible
       FROM uploads
       WHERE owner_type = :ot AND owner_id = :oid AND (client_visible = 1 OR uploaded_by_user_id = :uid)
       ORDER BY created_at DESC',
      ['ot' => 'project', 'oid' => $id, 'uid' => $uid]
    );

    return $this->page('app/client_project_detail', [
      'title' => 'Project #' . $id,
      'project' => $project,
      'threadId' => $threadId,
      'attachments' => $attachments,
    ], 'layouts/app');
  }

  private function ensureThread(string $scopeType, int $scopeId): int
  {
    $row = $this->ctx->db()->fetchOne(
      'SELECT id FROM threads WHERE scope_type = :t AND scope_id = :i LIMIT 1',
      ['t' => $scopeType, 'i' => $scopeId]
    );
    if ($row !== null) {
      return (int)$row['id'];
    }

    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO threads (scope_type, scope_id, created_at) VALUES (:t, :i, :c)',
      ['t' => $scopeType, 'i' => $scopeId, 'c' => gmdate('c')]
    );
    return $id;
  }
}
