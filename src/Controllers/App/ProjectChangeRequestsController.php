<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectChangeRequestsController extends Controller
{
  /** @return list<string> */
  private function allowedStatuses(): array
  {
    return ['draft', 'submitted', 'approved', 'rejected', 'cancelled', 'implemented'];
  }

  public function create(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) return Response::html('<h1>404</h1><p>Project not found.</p>', 404);

    $title = trim((string)$req->input('title', ''));
    if ($title === '' || mb_strlen($title) > 255) {
      $this->ctx->session()->flash('error', 'Change request title is required (max 255 chars).');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $body = trim((string)$req->input('body', ''));
    if ($body !== '' && mb_strlen($body) > 5000) $body = mb_substr($body, 0, 5000);

    $costDeltaCents = $this->parseMoneyToCents((string)$req->input('cost_delta', '0'));
    if ($costDeltaCents < -1000000000) $costDeltaCents = -1000000000;
    if ($costDeltaCents > 1000000000) $costDeltaCents = 1000000000;

    $scheduleDeltaDays = (int)$req->input('schedule_delta_days', '0');
    if ($scheduleDeltaDays < -365) $scheduleDeltaDays = -365;
    if ($scheduleDeltaDays > 365) $scheduleDeltaDays = 365;

    $submitNow = (string)$req->input('submit_now', '') === '1';

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO change_requests (project_id, status, title, body, cost_delta_cents, schedule_delta_days, created_by_user_id, submitted_at, decided_by_user_id, decided_at, decision_note, created_at, updated_at)
       VALUES (:pid, :st, :t, :b, :cdc, :sdd, :cb, :sub, NULL, NULL, NULL, :c, :u)',
      [
        'pid' => $projectId,
        'st' => $submitNow ? 'submitted' : 'draft',
        't' => $title,
        'b' => $body === '' ? null : $body,
        'cdc' => $costDeltaCents,
        'sdd' => $scheduleDeltaDays,
        'cb' => $uid > 0 ? $uid : null,
        'sub' => $submitNow ? $now : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit($submitNow ? 'change_request_submit' : 'change_request_create', 'change_request', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', $submitNow ? 'Change request submitted to client.' : 'Change request created (draft).');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function update(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $cr = $this->ctx->db()->fetchOne('SELECT * FROM change_requests WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($cr === null) return Response::html('<h1>404</h1><p>Change request not found.</p>', 404);

    $projectId = (int)($cr['project_id'] ?? 0);
    if ((string)($cr['status'] ?? 'draft') !== 'draft') {
      $this->ctx->session()->flash('error', 'Only draft change requests can be edited.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $title = trim((string)$req->input('title', (string)($cr['title'] ?? '')));
    if ($title === '' || mb_strlen($title) > 255) {
      $this->ctx->session()->flash('error', 'Change request title is required (max 255 chars).');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $body = trim((string)$req->input('body', (string)($cr['body'] ?? '')));
    if ($body !== '' && mb_strlen($body) > 5000) $body = mb_substr($body, 0, 5000);

    $costDeltaCents = $this->parseMoneyToCents((string)$req->input('cost_delta', '0'));
    if ($costDeltaCents < -1000000000) $costDeltaCents = -1000000000;
    if ($costDeltaCents > 1000000000) $costDeltaCents = 1000000000;

    $scheduleDeltaDays = (int)$req->input('schedule_delta_days', (string)($cr['schedule_delta_days'] ?? '0'));
    if ($scheduleDeltaDays < -365) $scheduleDeltaDays = -365;
    if ($scheduleDeltaDays > 365) $scheduleDeltaDays = 365;

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE change_requests
       SET title = :t, body = :b, cost_delta_cents = :cdc, schedule_delta_days = :sdd, updated_at = :u
       WHERE id = :id',
      [
        't' => $title,
        'b' => $body === '' ? null : $body,
        'cdc' => $costDeltaCents,
        'sdd' => $scheduleDeltaDays,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('change_request_update', 'change_request', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Change request updated.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function submit(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $cr = $this->ctx->db()->fetchOne('SELECT * FROM change_requests WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($cr === null) return Response::html('<h1>404</h1><p>Change request not found.</p>', 404);

    $projectId = (int)($cr['project_id'] ?? 0);
    if ((string)($cr['status'] ?? 'draft') !== 'draft') {
      $this->ctx->session()->flash('error', 'This change request is not a draft.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE change_requests SET status = :st, submitted_at = :s, updated_at = :u WHERE id = :id',
      ['st' => 'submitted', 's' => $now, 'u' => $now, 'id' => $id]
    );

    $this->audit('change_request_submit', 'change_request', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Change request submitted to client.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function delete(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $cr = $this->ctx->db()->fetchOne('SELECT id, project_id, status FROM change_requests WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($cr === null) return Response::html('<h1>404</h1><p>Change request not found.</p>', 404);

    $projectId = (int)($cr['project_id'] ?? 0);
    if ((string)($cr['status'] ?? 'draft') !== 'draft') {
      $this->ctx->session()->flash('error', 'Only draft change requests can be deleted.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $this->ctx->db()->execute('DELETE FROM change_requests WHERE id = :id', ['id' => $id]);
    $this->audit('change_request_delete', 'change_request', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Change request deleted.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  private function parseMoneyToCents(string $s): int
  {
    $t = trim($s);
    if ($t === '') return 0;
    $t = str_replace(['$', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?[0-9]+(\\.[0-9]{1,2})?$/', $t)) {
      return 0;
    }
    $neg = str_starts_with($t, '-');
    if ($neg) $t = substr($t, 1);
    $parts = explode('.', $t, 2);
    $dollars = (int)($parts[0] !== '' ? $parts[0] : '0');
    $cents = 0;
    if (count($parts) === 2) {
      $centsStr = str_pad($parts[1], 2, '0');
      $cents = (int)substr($centsStr, 0, 2);
    }
    $val = ($dollars * 100) + $cents;
    return $neg ? -$val : $val;
  }
}

