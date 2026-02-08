<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class LeadsController extends Controller
{
  /** @return list<string> */
  private function allowedStatuses(): array
  {
    return [
      'new',
      'quote_review',
      'meeting_scheduled',
      'checklist_draft',
      'checklist_submitted',
      'checklist_approved',
      'checklist_rejected',
      'estimate_sent',
      'waiting_client',
      'approved',
      'rejected',
      'project_created',
    ];
  }

  public function index(Request $req, array $params): Response
  {
    $page = (int)$req->query('page', '1');
    if ($page <= 0) $page = 1;
    $perPage = (int)$req->query('per_page', '25');
    if ($perPage <= 0) $perPage = 25;
    if ($perPage > 100) $perPage = 100;

    $offset = ($page - 1) * $perPage;
    if ($offset < 0) $offset = 0;
    if ($offset > 100000) $offset = 100000;

    $totalRow = $this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM quote_requests');
    $total = (int)($totalRow['c'] ?? 0);

    $leads = $this->ctx->db()->fetchAll(
      'SELECT qr.*, u.name AS pm_name
       FROM quote_requests qr
       LEFT JOIN users u ON u.id = qr.assigned_pm_user_id
       ORDER BY qr.created_at DESC
       LIMIT ' . $perPage . ' OFFSET ' . $offset
    );

    return $this->page('app/leads_list', [
      'title' => 'Leads & Quotes',
      'leads' => $leads,
      'page' => $page,
      'perPage' => $perPage,
      'total' => $total,
    ], 'layouts/app');
  }

  public function show(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $lead = $this->ctx->db()->fetchOne(
      'SELECT qr.*, u.name AS pm_name
       FROM quote_requests qr
       LEFT JOIN users u ON u.id = qr.assigned_pm_user_id
       WHERE qr.id = :id
       LIMIT 1',
      ['id' => $id]
    );
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $attachments = $this->ctx->db()->fetchAll(
      'SELECT id, original_name, mime_type, size_bytes, created_at, stage, client_visible
       FROM uploads
       WHERE owner_type = :ot AND owner_id = :oid
       ORDER BY created_at DESC',
      ['ot' => 'quote_request', 'oid' => $id]
    );

    $project = $this->ctx->db()->fetchOne(
      'SELECT id, status, name, created_at FROM projects WHERE quote_request_id = :qid LIMIT 1',
      ['qid' => $id]
    );

    $threadId = $this->ensureThread('quote_request', $id);

    $pms = $this->ctx->db()->fetchAll('SELECT id, name, email FROM users WHERE role = :r AND status = :s ORDER BY name ASC', [
      'r' => 'pm',
      's' => 'active',
    ]);

    return $this->page('app/lead_detail', [
      'title' => 'Lead #' . $id,
      'lead' => $lead,
      'attachments' => $attachments,
      'project' => $project,
      'threadId' => $threadId,
      'pms' => $pms,
      'allowedStatuses' => $this->allowedStatuses(),
    ], 'layouts/app');
  }

  public function assign(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $pmId = (int)$req->input('pm_user_id', 0);
    $this->ctx->db()->execute('UPDATE quote_requests SET assigned_pm_user_id = :pm, updated_at = :u WHERE id = :id', [
      'pm' => $pmId > 0 ? $pmId : null,
      'u' => gmdate('c'),
      'id' => $id,
    ]);
    $this->ctx->session()->flash('notice', 'Lead updated.');
    return $this->redirect('/app/leads/' . $id);
  }

  public function convertToProject(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $lead = $this->ctx->db()->fetchOne('SELECT * FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $existing = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE quote_request_id = :qid LIMIT 1', ['qid' => $id]);
    if ($existing !== null) {
      $this->ctx->session()->flash('notice', 'Project already exists (ID ' . (string)$existing['id'] . ').');
      return $this->redirect('/app/projects/' . (string)$existing['id']);
    }

    $now = gmdate('c');
    $projectId = (int)$this->ctx->db()->insert(
      'INSERT INTO projects (status, quote_request_id, client_user_id, name, address, budget_cents, assigned_pm_user_id, created_at, updated_at)
       VALUES (:st, :qid, :cuid, :n, :a, :b, :pm, :c, :u)',
      [
        'st' => 'project_created',
        'qid' => (int)$lead['id'],
        'cuid' => $lead['client_user_id'] ?? null,
        'n' => 'Project from Lead #' . (string)$lead['id'],
        'a' => (string)$lead['address'],
        'b' => 0,
        'pm' => $lead['assigned_pm_user_id'] ?? null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->ctx->db()->execute('UPDATE quote_requests SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => 'project_created',
      'u' => $now,
      'id' => $id,
    ]);

    // If a lead checklist exists, copy it to the created project (MVP support for project checklists).
    try {
      $cl = $this->ctx->db()->fetchOne('SELECT * FROM checklists WHERE quote_request_id = :id ORDER BY id DESC LIMIT 1', ['id' => $id]);
      if ($cl !== null) {
        $newChecklistId = (int)$this->ctx->db()->insert(
          'INSERT INTO checklists (quote_request_id, project_id, status, title, created_by_user_id, submitted_at, decided_at, decided_by_user_id, decision_note, created_at, updated_at)
           VALUES (NULL, :pid, :st, :t, :cb, :sub, :dec, :db, :dn, :c, :u)',
          [
            'pid' => $projectId,
            'st' => (string)($cl['status'] ?? 'draft'),
            't' => (string)($cl['title'] ?? 'Estimate / Checklist'),
            'cb' => $cl['created_by_user_id'] ?? null,
            'sub' => $cl['submitted_at'] ?? null,
            'dec' => $cl['decided_at'] ?? null,
            'db' => $cl['decided_by_user_id'] ?? null,
            'dn' => $cl['decision_note'] ?? null,
            'c' => $now,
            'u' => $now,
          ]
        );

        $items = $this->ctx->db()->fetchAll('SELECT * FROM checklist_items WHERE checklist_id = :cid ORDER BY position ASC, id ASC', [
          'cid' => (int)$cl['id'],
        ]);
        foreach ($items as $it) {
          $this->ctx->db()->insert(
            'INSERT INTO checklist_items (checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at)
             VALUES (:cid, :pos, :t, :pm, :q, :uc, :fc, :st, :c, :u)',
            [
              'cid' => $newChecklistId,
              'pos' => (int)($it['position'] ?? 0),
              't' => (string)($it['title'] ?? ''),
              'pm' => (string)($it['pricing_mode'] ?? 'fixed'),
              'q' => (float)($it['qty'] ?? 0),
              'uc' => (int)($it['unit_cost_cents'] ?? 0),
              'fc' => (int)($it['fixed_cost_cents'] ?? 0),
              'st' => (string)($it['status'] ?? 'todo'),
              'c' => $now,
              'u' => $now,
            ]
          );
        }
      }
    } catch (\Throwable $_) {
      // best-effort only
    }

    $this->ctx->session()->flash('notice', 'Project created (ID ' . $projectId . ').');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function updateStatus(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $lead = $this->ctx->db()->fetchOne('SELECT id, status FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $status = strtolower(trim((string)$req->input('status', '')));
    if ($status === '' || !in_array($status, $this->allowedStatuses(), true)) {
      $this->ctx->session()->flash('error', 'Invalid status.');
      return $this->redirect('/app/leads/' . $id);
    }

    $before = (string)($lead['status'] ?? '');
    $now = gmdate('c');
    $this->ctx->db()->execute('UPDATE quote_requests SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => $status,
      'u' => $now,
      'id' => $id,
    ]);

    $this->audit('lead_status_update', 'quote_request', $id, [
      'before' => $before,
      'after' => $status,
    ]);

    $this->ctx->session()->flash('notice', 'Status updated.');
    return $this->redirect('/app/leads/' . $id);
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
