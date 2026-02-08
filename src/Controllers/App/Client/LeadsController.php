<?php
declare(strict_types=1);

namespace App\Controllers\App\Client;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class LeadsController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || ($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $uid = (int)$u['id'];
    $leads = $this->ctx->db()->fetchAll(
      'SELECT qr.*, up.name AS pm_name
       FROM quote_requests qr
       LEFT JOIN users up ON up.id = qr.assigned_pm_user_id
       WHERE qr.client_user_id = :uid
       ORDER BY qr.created_at DESC
       LIMIT 200',
      ['uid' => $uid]
    );

    return $this->page('app/client_leads_list', [
      'title' => 'My leads',
      'leads' => $leads,
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
    $lead = $this->ctx->db()->fetchOne(
      'SELECT qr.*, up.name AS pm_name
       FROM quote_requests qr
       LEFT JOIN users up ON up.id = qr.assigned_pm_user_id
       WHERE qr.id = :id AND qr.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $project = $this->ctx->db()->fetchOne(
      'SELECT id, status, name, created_at FROM projects WHERE quote_request_id = :qid LIMIT 1',
      ['qid' => $id]
    );

    $threadId = $this->ensureThread('quote_request', $id);

    $attachments = $this->ctx->db()->fetchAll(
      'SELECT id, original_name, mime_type, size_bytes, created_at, client_visible
       FROM uploads
       WHERE owner_type = :ot AND owner_id = :oid AND (client_visible = 1 OR uploaded_by_user_id = :uid)
       ORDER BY created_at DESC',
      ['ot' => 'quote_request', 'oid' => $id, 'uid' => $uid]
    );

    return $this->page('app/client_lead_detail', [
      'title' => 'Lead #' . $id,
      'lead' => $lead,
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
