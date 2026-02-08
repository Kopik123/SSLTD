<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ChecklistsApiController extends Controller
{
  /** @return list<string> */
  private function allowedItemStatuses(): array
  {
    return ['todo', 'in_progress', 'done', 'blocked'];
  }

  public function currentForProject(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $projectId = (int)($params['id'] ?? 0);
    if ($projectId <= 0) {
      return Response::json(['error' => 'invalid_request'], 400);
    }
    if (!$this->canAccessProject($projectId, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    $cl = $this->ctx->db()->fetchOne(
      'SELECT * FROM checklists WHERE project_id = :pid ORDER BY id DESC LIMIT 1',
      ['pid' => $projectId]
    );
    if ($cl === null) {
      return Response::json(['checklist' => null, 'items' => []], 200);
    }

    $items = $this->ctx->db()->fetchAll(
      'SELECT id, checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at
       FROM checklist_items
       WHERE checklist_id = :cid
       ORDER BY position ASC, id ASC',
      ['cid' => (int)$cl['id']]
    );

    return Response::json(['checklist' => $cl, 'items' => $items], 200);
  }

  public function updateItemStatus(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $itemId = (int)($params['id'] ?? 0);
    if ($itemId <= 0) {
      return Response::json(['error' => 'invalid_request'], 400);
    }

    $body = $req->json() ?? [];
    $status = strtolower(trim((string)($body['status'] ?? $req->input('status', ''))));
    if ($status === '' || !in_array($status, $this->allowedItemStatuses(), true)) {
      return Response::json(['error' => 'invalid_request'], 400);
    }

    $row = $this->ctx->db()->fetchOne(
      'SELECT ci.id, ci.checklist_id, c.project_id
       FROM checklist_items ci
       JOIN checklists c ON c.id = ci.checklist_id
       WHERE ci.id = :id
       LIMIT 1',
      ['id' => $itemId]
    );
    if ($row === null) {
      return Response::json(['error' => 'not_found'], 404);
    }

    $projectId = (int)($row['project_id'] ?? 0);
    if ($projectId <= 0) {
      // Do not allow mobile updates for lead/quote checklists.
      return Response::json(['error' => 'forbidden'], 403);
    }
    if (!$this->canAccessProject($projectId, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE checklist_items SET status = :st, updated_at = :u WHERE id = :id',
      ['st' => $status, 'u' => $now, 'id' => $itemId]
    );

    $this->audit('checklist_item_status', 'checklist_item', $itemId, [
      'project_id' => $projectId,
      'status' => $status,
    ]);

    return Response::json(['ok' => true], 200);
  }

  /** @param array<string, mixed> $user */
  private function canAccessProject(int $projectId, array $user): bool
  {
    $role = (string)($user['role'] ?? '');
    $uid = (int)($user['id'] ?? 0);

    if ($role === 'admin' || $role === 'pm') {
      return true;
    }

    if ($role === 'client') {
      $p = $this->ctx->db()->fetchOne('SELECT client_user_id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
      return $p !== null && (int)($p['client_user_id'] ?? 0) === $uid;
    }

    $mem = $this->ctx->db()->fetchOne(
      'SELECT 1 AS ok FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1',
      ['pid' => $projectId, 'uid' => $uid]
    );
    return $mem !== null;
  }
}

