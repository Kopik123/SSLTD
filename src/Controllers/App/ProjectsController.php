<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectsController extends Controller
{
  /** @return list<string> */
  private function allowedStatuses(): array
  {
    return [
      'project_created',
      'materials_planning',
      'schedule_proposed',
      'client_approved',
      'scheduled',
      'in_progress',
      'blocked',
      'completed',
      'closed',
    ];
  }

  public function index(Request $req, array $params): Response
  {
    $status = trim((string)$req->query('status', ''));
    $assigned = strtolower(trim((string)$req->query('assigned', '')));

    $page = (int)$req->query('page', '1');
    if ($page <= 0) $page = 1;
    $perPage = (int)$req->query('per_page', '25');
    if ($perPage <= 0) $perPage = 25;
    if ($perPage > 100) $perPage = 100;
    $offset = ($page - 1) * $perPage;
    if ($offset < 0) $offset = 0;
    if ($offset > 100000) $offset = 100000;

    $where = [];
    $bind = [];

    if ($status !== '') {
      $where[] = 'p.status = :st';
      $bind['st'] = $status;
    }

    if ($assigned === 'me') {
      $u = $this->ctx->auth()->user();
      if ($u !== null && ($u['role'] ?? '') === 'pm') {
        $where[] = 'p.assigned_pm_user_id = :pmid';
        $bind['pmid'] = (int)$u['id'];
      }
    }

    $sql =
      'SELECT p.*, pm.name AS pm_name, c.name AS client_name, c.email AS client_email
       FROM projects p
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       LEFT JOIN users c ON c.id = p.client_user_id';

    if ($where !== []) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $countSql = 'SELECT COUNT(*) AS c FROM projects p';
    if ($where !== []) {
      $countSql .= ' WHERE ' . implode(' AND ', $where);
    }
    $totalRow = $this->ctx->db()->fetchOne($countSql, $bind);
    $total = (int)($totalRow['c'] ?? 0);

    $sql .= ' ORDER BY p.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

    $projects = $this->ctx->db()->fetchAll($sql, $bind);

    return $this->page('app/projects_list', [
      'title' => 'Projects',
      'projects' => $projects,
      'page' => $page,
      'perPage' => $perPage,
      'total' => $total,
      'filters' => [
        'status' => $status,
        'assigned' => $assigned,
      ],
    ], 'layouts/app');
  }

  public function show(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne(
      'SELECT p.*, pm.name AS pm_name, c.name AS client_name, c.email AS client_email,
              qr.id AS lead_id, qr.status AS lead_status
       FROM projects p
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       LEFT JOIN users c ON c.id = p.client_user_id
       LEFT JOIN quote_requests qr ON qr.id = p.quote_request_id
       WHERE p.id = :id
       LIMIT 1',
      ['id' => $id]
    );
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $threadId = $this->ensureThread('project', $id);

    $attachments = $this->ctx->db()->fetchAll(
      'SELECT id, original_name, mime_type, size_bytes, created_at, stage, client_visible
       FROM uploads
       WHERE owner_type = :ot AND owner_id = :oid
       ORDER BY created_at DESC',
      ['ot' => 'project', 'oid' => $id]
    );

    $proposal = null;
    try {
      $proposal = $this->ctx->db()->fetchOne(
        'SELECT sp.*, u.name AS created_by_name, du.name AS decided_by_name
         FROM schedule_proposals sp
         LEFT JOIN users u ON u.id = sp.created_by_user_id
         LEFT JOIN users du ON du.id = sp.decided_by_user_id
         WHERE sp.project_id = :pid
         ORDER BY sp.id DESC
         LIMIT 1',
        ['pid' => $id]
      );
    } catch (\Throwable $_) {
      $proposal = null;
    }

    $events = [];
    try {
      $events = $this->ctx->db()->fetchAll(
        'SELECT id, title, starts_at, ends_at, status
         FROM schedule_events
         WHERE project_id = :pid
         ORDER BY starts_at ASC
         LIMIT 50',
        ['pid' => $id]
      );
    } catch (\Throwable $_) {
      $events = [];
    }

    $materialsCatalog = [];
    $toolsCatalog = [];
    $projectMaterials = [];
    $projectTools = [];
    $deliveries = [];
    $reports = [];
    $issues = [];
    $changeRequests = [];
    try {
      $materialsCatalog = $this->ctx->db()->fetchAll(
        'SELECT id, name, unit, vendor, sku
         FROM materials
         WHERE status = :st
         ORDER BY name ASC
         LIMIT 200',
        ['st' => 'active']
      );
      $toolsCatalog = $this->ctx->db()->fetchAll(
        'SELECT id, name, serial, location
         FROM tools
         WHERE status = :st
         ORDER BY name ASC
         LIMIT 200',
        ['st' => 'active']
      );

      $projectMaterials = $this->ctx->db()->fetchAll(
        'SELECT pm.*, m.name AS material_name, m.unit AS material_unit
         FROM project_materials pm
         JOIN materials m ON m.id = pm.material_id
         WHERE pm.project_id = :pid
         ORDER BY pm.id DESC
         LIMIT 200',
        ['pid' => $id]
      );
      $projectTools = $this->ctx->db()->fetchAll(
        'SELECT pt.*, t.name AS tool_name, t.serial AS tool_serial, t.location AS tool_location
         FROM project_tools pt
         JOIN tools t ON t.id = pt.tool_id
         WHERE pt.project_id = :pid
         ORDER BY pt.id DESC
         LIMIT 200',
        ['pid' => $id]
      );
      $deliveries = $this->ctx->db()->fetchAll(
        'SELECT d.*, m.name AS material_name, m.unit AS material_unit
         FROM deliveries d
         JOIN materials m ON m.id = d.material_id
         WHERE d.project_id = :pid
         ORDER BY d.id DESC
         LIMIT 200',
        ['pid' => $id]
      );

      $reports = $this->ctx->db()->fetchAll(
        'SELECT r.*, u.name AS created_by_name
         FROM project_reports r
         LEFT JOIN users u ON u.id = r.created_by_user_id
         WHERE r.project_id = :pid
         ORDER BY r.id DESC
         LIMIT 50',
        ['pid' => $id]
      );

      $issues = $this->ctx->db()->fetchAll(
        'SELECT i.*, u.name AS created_by_name
         FROM issues i
         LEFT JOIN users u ON u.id = i.created_by_user_id
         WHERE i.project_id = :pid
         ORDER BY i.updated_at DESC, i.id DESC
         LIMIT 100',
        ['pid' => $id]
      );

      $changeRequests = $this->ctx->db()->fetchAll(
        'SELECT cr.*, u.name AS created_by_name, du.name AS decided_by_name
         FROM change_requests cr
         LEFT JOIN users u ON u.id = cr.created_by_user_id
         LEFT JOIN users du ON du.id = cr.decided_by_user_id
         WHERE cr.project_id = :pid
         ORDER BY cr.id DESC
         LIMIT 100',
        ['pid' => $id]
      );
    } catch (\Throwable $_) {
      $materialsCatalog = [];
      $toolsCatalog = [];
      $projectMaterials = [];
      $projectTools = [];
      $deliveries = [];
      $reports = [];
      $issues = [];
      $changeRequests = [];
    }

    return $this->page('app/project_detail', [
      'title' => 'Project #' . $id,
      'project' => $project,
      'threadId' => $threadId,
      'attachments' => $attachments,
      'scheduleProposal' => $proposal,
      'scheduleEvents' => $events,
      'materialsCatalog' => $materialsCatalog,
      'toolsCatalog' => $toolsCatalog,
      'projectMaterials' => $projectMaterials,
      'projectTools' => $projectTools,
      'deliveries' => $deliveries,
      'reports' => $reports,
      'issues' => $issues,
      'changeRequests' => $changeRequests,
      'allowedStatuses' => $this->allowedStatuses(),
    ], 'layouts/app');
  }

  public function updateStatus(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id, status FROM projects WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $status = strtolower(trim((string)$req->input('status', '')));
    if ($status === '' || !in_array($status, $this->allowedStatuses(), true)) {
      $this->ctx->session()->flash('error', 'Invalid status.');
      return $this->redirect('/app/projects/' . $id);
    }

    $before = (string)($project['status'] ?? '');
    $now = gmdate('c');
    $this->ctx->db()->execute('UPDATE projects SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => $status,
      'u' => $now,
      'id' => $id,
    ]);

    $this->audit('project_status_update', 'project', $id, [
      'before' => $before,
      'after' => $status,
    ]);

    $this->ctx->session()->flash('notice', 'Status updated.');
    return $this->redirect('/app/projects/' . $id);
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
