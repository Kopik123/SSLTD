<?php
declare(strict_types=1);

namespace App\Controllers\App\Client;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ApprovalsController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);

    $rows = $this->ctx->db()->fetchAll(
      'SELECT c.id, c.status, c.title, c.submitted_at, c.decided_at,
              qr.id AS lead_id, qr.address, qr.created_at,
              (SELECT COUNT(*) FROM checklist_items ci WHERE ci.checklist_id = c.id) AS items_count
       FROM checklists c
       JOIN quote_requests qr ON qr.id = c.quote_request_id
       WHERE qr.client_user_id = :uid AND c.status <> :draft
       ORDER BY c.created_at DESC',
      ['uid' => $uid, 'draft' => 'draft']
    );

    $schedule = $this->ctx->db()->fetchAll(
      'SELECT sp.id, sp.status, sp.starts_at, sp.ends_at, sp.created_at,
              p.id AS project_id, p.name AS project_name, p.address
       FROM schedule_proposals sp
       JOIN projects p ON p.id = sp.project_id
       WHERE p.client_user_id = :uid AND sp.status = :st
       ORDER BY sp.created_at DESC
       LIMIT 100',
      ['uid' => $uid, 'st' => 'submitted']
    );

    $changes = $this->ctx->db()->fetchAll(
      'SELECT cr.id, cr.status, cr.title, cr.cost_delta_cents, cr.schedule_delta_days, cr.created_at,
              p.id AS project_id, p.name AS project_name, p.address
       FROM change_requests cr
       JOIN projects p ON p.id = cr.project_id
       WHERE p.client_user_id = :uid AND cr.status = :st
       ORDER BY cr.created_at DESC
       LIMIT 100',
      ['uid' => $uid, 'st' => 'submitted']
    );

    return $this->page('app/client_approvals', [
      'title' => 'Approvals',
      'rows' => $rows,
      'schedule' => $schedule,
      'changes' => $changes,
    ], 'layouts/app');
  }

  public function show(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);
    $id = (int)($params['id'] ?? 0);

    $cl = $this->ctx->db()->fetchOne(
      'SELECT c.*, qr.id AS lead_id, qr.status AS lead_status, qr.address, qr.name AS client_name
       FROM checklists c
       JOIN quote_requests qr ON qr.id = c.quote_request_id
       WHERE c.id = :id AND qr.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($cl === null) {
      return Response::html('<h1>404</h1><p>Approval not found.</p>', 404);
    }

    $items = $this->ctx->db()->fetchAll(
      'SELECT * FROM checklist_items WHERE checklist_id = :cid ORDER BY position ASC, id ASC',
      ['cid' => (int)$cl['id']]
    );

    return $this->page('app/client_approval_detail', [
      'title' => 'Approval #' . $id,
      'checklist' => $cl,
      'items' => $items,
    ], 'layouts/app');
  }

  public function approve(Request $req, array $params): Response
  {
    return $this->decide($req, $params, 'approved');
  }

  public function reject(Request $req, array $params): Response
  {
    return $this->decide($req, $params, 'rejected');
  }

  private function decide(Request $req, array $params, string $decision): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);
    $id = (int)($params['id'] ?? 0);

    $cl = $this->ctx->db()->fetchOne(
      'SELECT c.id, c.status, c.quote_request_id
       FROM checklists c
       JOIN quote_requests qr ON qr.id = c.quote_request_id
       WHERE c.id = :id AND qr.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($cl === null) {
      return Response::html('<h1>404</h1><p>Approval not found.</p>', 404);
    }

    $st = (string)($cl['status'] ?? 'draft');
    if ($st !== 'submitted') {
      $this->ctx->session()->flash('error', 'This approval is not pending.');
      return $this->redirect('/app/client/approvals/' . $id);
    }

    $note = trim((string)$req->input('note', ''));
    if (mb_strlen($note) > 2000) {
      $note = mb_substr($note, 0, 2000);
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE checklists
       SET status = :st, decided_at = :d, decided_by_user_id = :uid, decision_note = :n, updated_at = :u
       WHERE id = :id',
      ['st' => $decision, 'd' => $now, 'uid' => $uid, 'n' => $note === '' ? null : $note, 'u' => $now, 'id' => $id]
    );

    $leadId = (int)($cl['quote_request_id'] ?? 0);
    if ($leadId > 0) {
      $this->ctx->db()->execute('UPDATE quote_requests SET status = :st, updated_at = :u WHERE id = :id', [
        'st' => $decision === 'approved' ? 'checklist_approved' : 'checklist_rejected',
        'u' => $now,
        'id' => $leadId,
      ]);
    }

    $this->audit('checklist_decide', 'checklist', $id, ['decision' => $decision, 'lead_id' => $leadId]);
    $this->ctx->session()->flash('notice', $decision === 'approved' ? 'Approved.' : 'Rejected.');
    return $this->redirect('/app/client/approvals/' . $id);
  }

  public function showSchedule(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);
    $id = (int)($params['id'] ?? 0);

    $p = $this->ctx->db()->fetchOne(
      'SELECT sp.*, pr.name AS project_name, pr.address, pr.status AS project_status,
              u.name AS created_by_name, du.name AS decided_by_name
       FROM schedule_proposals sp
       JOIN projects pr ON pr.id = sp.project_id
       LEFT JOIN users u ON u.id = sp.created_by_user_id
       LEFT JOIN users du ON du.id = sp.decided_by_user_id
       WHERE sp.id = :id AND pr.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($p === null) {
      return Response::html('<h1>404</h1><p>Schedule proposal not found.</p>', 404);
    }

    return $this->page('app/client_schedule_detail', [
      'title' => 'Schedule proposal #' . $id,
      'proposal' => $p,
    ], 'layouts/app');
  }

  public function approveSchedule(Request $req, array $params): Response
  {
    return $this->decideSchedule($req, $params, 'approved');
  }

  public function rejectSchedule(Request $req, array $params): Response
  {
    return $this->decideSchedule($req, $params, 'rejected');
  }

  private function decideSchedule(Request $req, array $params, string $decision): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);
    $id = (int)($params['id'] ?? 0);

    $p = $this->ctx->db()->fetchOne(
      'SELECT sp.*, pr.client_user_id
       FROM schedule_proposals sp
       JOIN projects pr ON pr.id = sp.project_id
       WHERE sp.id = :id AND pr.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($p === null) {
      return Response::html('<h1>404</h1><p>Schedule proposal not found.</p>', 404);
    }

    $st = (string)($p['status'] ?? '');
    if ($st !== 'submitted') {
      $this->ctx->session()->flash('error', 'This schedule proposal is not pending.');
      return $this->redirect('/app/client/approvals/schedule/' . $id);
    }

    $note = trim((string)$req->input('note', ''));
    if (mb_strlen($note) > 2000) {
      $note = mb_substr($note, 0, 2000);
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE schedule_proposals
       SET status = :st, decided_by_user_id = :uid, decided_at = :d, decision_note = :n, updated_at = :u
       WHERE id = :id',
      ['st' => $decision, 'uid' => $uid, 'd' => $now, 'n' => $note === '' ? null : $note, 'u' => $now, 'id' => $id]
    );

    $projectId = (int)($p['project_id'] ?? 0);
    if ($projectId > 0 && $decision === 'approved') {
      $this->ctx->db()->insert(
        'INSERT INTO schedule_events (project_id, title, starts_at, ends_at, status, created_by_user_id, created_at, updated_at)
         VALUES (:pid, :t, :sa, :ea, :st, :cb, :c, :u)',
        [
          'pid' => $projectId,
          't' => 'Work window (client approved)',
          'sa' => (string)($p['starts_at'] ?? ''),
          'ea' => (string)($p['ends_at'] ?? ''),
          'st' => 'approved',
          'cb' => $uid,
          'c' => $now,
          'u' => $now,
        ]
      );
      $this->ctx->db()->execute('UPDATE projects SET status = :st, updated_at = :u WHERE id = :id', [
        'st' => 'client_approved',
        'u' => $now,
        'id' => $projectId,
      ]);
    }

    $this->audit('schedule_decide', 'schedule_proposal', $id, ['decision' => $decision, 'project_id' => $projectId, 'note' => $note === '' ? null : $note]);
    $this->ctx->session()->flash('notice', $decision === 'approved' ? 'Schedule approved.' : 'Schedule rejected.');
    return $this->redirect('/app/client/approvals/schedule/' . $id);
  }

  public function showChange(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);
    $id = (int)($params['id'] ?? 0);

    $cr = $this->ctx->db()->fetchOne(
      'SELECT cr.*, p.name AS project_name, p.address,
              u.name AS created_by_name, du.name AS decided_by_name
       FROM change_requests cr
       JOIN projects p ON p.id = cr.project_id
       LEFT JOIN users u ON u.id = cr.created_by_user_id
       LEFT JOIN users du ON du.id = cr.decided_by_user_id
       WHERE cr.id = :id AND p.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($cr === null) {
      return Response::html('<h1>404</h1><p>Change request not found.</p>', 404);
    }

    return $this->page('app/client_change_request_detail', [
      'title' => 'Change request #' . $id,
      'cr' => $cr,
    ], 'layouts/app');
  }

  public function approveChange(Request $req, array $params): Response
  {
    return $this->decideChange($req, $params, 'approved');
  }

  public function rejectChange(Request $req, array $params): Response
  {
    return $this->decideChange($req, $params, 'rejected');
  }

  private function decideChange(Request $req, array $params, string $decision): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null || (string)($u['role'] ?? '') !== 'client') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $uid = (int)($u['id'] ?? 0);
    $id = (int)($params['id'] ?? 0);

    $cr = $this->ctx->db()->fetchOne(
      'SELECT cr.id, cr.project_id, cr.status, p.client_user_id
       FROM change_requests cr
       JOIN projects p ON p.id = cr.project_id
       WHERE cr.id = :id AND p.client_user_id = :uid
       LIMIT 1',
      ['id' => $id, 'uid' => $uid]
    );
    if ($cr === null) {
      return Response::html('<h1>404</h1><p>Change request not found.</p>', 404);
    }

    if ((string)($cr['status'] ?? '') !== 'submitted') {
      $this->ctx->session()->flash('error', 'This change request is not pending.');
      return $this->redirect('/app/client/approvals/change/' . $id);
    }

    $note = trim((string)$req->input('note', ''));
    if (mb_strlen($note) > 2000) $note = mb_substr($note, 0, 2000);

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE change_requests
       SET status = :st, decided_by_user_id = :uid, decided_at = :d, decision_note = :n, updated_at = :u
       WHERE id = :id',
      [
        'st' => $decision,
        'uid' => $uid,
        'd' => $now,
        'n' => $note === '' ? null : $note,
        'u' => $now,
        'id' => $id,
      ]
    );

    $projectId = (int)($cr['project_id'] ?? 0);
    $this->audit('change_request_decide', 'change_request', $id, ['decision' => $decision, 'project_id' => $projectId, 'note' => $note === '' ? null : $note]);
    $this->ctx->session()->flash('notice', $decision === 'approved' ? 'Change request approved.' : 'Change request rejected.');
    return $this->redirect('/app/client/approvals/change/' . $id);
  }
}
