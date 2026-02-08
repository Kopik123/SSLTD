<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectInventoryController extends Controller
{
  /** @return list<string> */
  private function allowedProjectMaterialStatuses(): array
  {
    return ['required', 'ordered', 'delivered', 'cancelled'];
  }

  /** @return list<string> */
  private function allowedProjectToolStatuses(): array
  {
    return ['required', 'assigned', 'on_site', 'in_storage', 'in_service', 'cancelled'];
  }

  /** @return list<string> */
  private function allowedDeliveryStatuses(): array
  {
    return ['pending', 'delivered', 'cancelled'];
  }

  public function addMaterial(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) return Response::html('<h1>404</h1><p>Project not found.</p>', 404);

    $materialId = (int)$req->input('material_id', '0');
    $m = $this->ctx->db()->fetchOne('SELECT id FROM materials WHERE id = :id LIMIT 1', ['id' => $materialId]);
    if ($m === null) {
      $this->ctx->session()->flash('error', 'Select a valid material.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $status = strtolower(trim((string)$req->input('status', 'required')));
    if (!in_array($status, $this->allowedProjectMaterialStatuses(), true)) $status = 'required';

    $qty = $this->parseQty($req->input('qty', '0'));
    $neededBy = trim((string)$req->input('needed_by', ''));
    if ($neededBy !== '' && !$this->looksLikeDateTimeOrDate($neededBy)) {
      $this->ctx->session()->flash('error', 'Invalid needed-by date/time.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO project_materials (project_id, material_id, status, qty, needed_by, notes, created_by_user_id, created_at, updated_at)
       VALUES (:pid, :mid, :st, :q, :nb, :n, :cb, :c, :u)',
      [
        'pid' => $projectId,
        'mid' => $materialId,
        'st' => $status,
        'q' => $qty,
        'nb' => $neededBy === '' ? null : $neededBy,
        'n' => $notes === '' ? null : $notes,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('project_material_add', 'project_material', $id, ['project_id' => $projectId, 'material_id' => $materialId]);
    $this->ctx->session()->flash('notice', 'Material added to project.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function updateMaterial(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM project_materials WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::html('<h1>404</h1><p>Project material not found.</p>', 404);

    $projectId = (int)($row['project_id'] ?? 0);

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'required'))));
    if (!in_array($status, $this->allowedProjectMaterialStatuses(), true)) $status = (string)($row['status'] ?? 'required');

    $qty = $this->parseQty($req->input('qty', (string)($row['qty'] ?? '0')));
    $neededBy = trim((string)$req->input('needed_by', (string)($row['needed_by'] ?? '')));
    if ($neededBy !== '' && !$this->looksLikeDateTimeOrDate($neededBy)) {
      $this->ctx->session()->flash('error', 'Invalid needed-by date/time.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $notes = trim((string)$req->input('notes', (string)($row['notes'] ?? '')));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE project_materials
       SET status = :st, qty = :q, needed_by = :nb, notes = :n, updated_at = :u
       WHERE id = :id',
      [
        'st' => $status,
        'q' => $qty,
        'nb' => $neededBy === '' ? null : $neededBy,
        'n' => $notes === '' ? null : $notes,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('project_material_update', 'project_material', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Material updated.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function deleteMaterial(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT project_id FROM project_materials WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::html('<h1>404</h1><p>Project material not found.</p>', 404);

    $projectId = (int)($row['project_id'] ?? 0);
    $this->ctx->db()->execute('DELETE FROM project_materials WHERE id = :id', ['id' => $id]);
    $this->audit('project_material_delete', 'project_material', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Material removed from project.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function addTool(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) return Response::html('<h1>404</h1><p>Project not found.</p>', 404);

    $toolId = (int)$req->input('tool_id', '0');
    $t = $this->ctx->db()->fetchOne('SELECT id FROM tools WHERE id = :id LIMIT 1', ['id' => $toolId]);
    if ($t === null) {
      $this->ctx->session()->flash('error', 'Select a valid tool.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $status = strtolower(trim((string)$req->input('status', 'required')));
    if (!in_array($status, $this->allowedProjectToolStatuses(), true)) $status = 'required';

    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO project_tools (project_id, tool_id, assigned_to_user_id, status, notes, created_by_user_id, created_at, updated_at)
       VALUES (:pid, :tid, NULL, :st, :n, :cb, :c, :u)',
      [
        'pid' => $projectId,
        'tid' => $toolId,
        'st' => $status,
        'n' => $notes === '' ? null : $notes,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('project_tool_add', 'project_tool', $id, ['project_id' => $projectId, 'tool_id' => $toolId]);
    $this->ctx->session()->flash('notice', 'Tool added to project.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function updateTool(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM project_tools WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::html('<h1>404</h1><p>Project tool not found.</p>', 404);

    $projectId = (int)($row['project_id'] ?? 0);
    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'required'))));
    if (!in_array($status, $this->allowedProjectToolStatuses(), true)) $status = (string)($row['status'] ?? 'required');

    $notes = trim((string)$req->input('notes', (string)($row['notes'] ?? '')));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE project_tools SET status = :st, notes = :n, updated_at = :u WHERE id = :id',
      [
        'st' => $status,
        'n' => $notes === '' ? null : $notes,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('project_tool_update', 'project_tool', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Tool updated.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function deleteTool(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT project_id FROM project_tools WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::html('<h1>404</h1><p>Project tool not found.</p>', 404);

    $projectId = (int)($row['project_id'] ?? 0);
    $this->ctx->db()->execute('DELETE FROM project_tools WHERE id = :id', ['id' => $id]);
    $this->audit('project_tool_delete', 'project_tool', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Tool removed from project.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function addDelivery(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) return Response::html('<h1>404</h1><p>Project not found.</p>', 404);

    $materialId = (int)$req->input('material_id', '0');
    $m = $this->ctx->db()->fetchOne('SELECT id FROM materials WHERE id = :id LIMIT 1', ['id' => $materialId]);
    if ($m === null) {
      $this->ctx->session()->flash('error', 'Select a valid material for delivery.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $status = strtolower(trim((string)$req->input('status', 'pending')));
    if (!in_array($status, $this->allowedDeliveryStatuses(), true)) $status = 'pending';

    $qty = $this->parseQty($req->input('qty', '0'));
    $expectedAt = trim((string)$req->input('expected_at', ''));
    if ($expectedAt !== '' && !$this->looksLikeDateTimeOrDate($expectedAt)) {
      $this->ctx->session()->flash('error', 'Invalid expected date/time.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $deliveredAt = null;
    if ($status === 'delivered') {
      $deliveredAt = $now;
    }

    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO deliveries (project_id, material_id, qty, status, expected_at, delivered_at, notes, created_by_user_id, created_at, updated_at)
       VALUES (:pid, :mid, :q, :st, :ea, :da, :n, :cb, :c, :u)',
      [
        'pid' => $projectId,
        'mid' => $materialId,
        'q' => $qty,
        'st' => $status,
        'ea' => $expectedAt === '' ? null : $expectedAt,
        'da' => $deliveredAt,
        'n' => $notes === '' ? null : $notes,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('delivery_add', 'delivery', $id, ['project_id' => $projectId, 'material_id' => $materialId]);
    $this->ctx->session()->flash('notice', 'Delivery created.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function updateDelivery(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM deliveries WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::html('<h1>404</h1><p>Delivery not found.</p>', 404);

    $projectId = (int)($row['project_id'] ?? 0);

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'pending'))));
    if (!in_array($status, $this->allowedDeliveryStatuses(), true)) $status = (string)($row['status'] ?? 'pending');

    $qty = $this->parseQty($req->input('qty', (string)($row['qty'] ?? '0')));

    $expectedAt = trim((string)$req->input('expected_at', (string)($row['expected_at'] ?? '')));
    if ($expectedAt !== '' && !$this->looksLikeDateTimeOrDate($expectedAt)) {
      $this->ctx->session()->flash('error', 'Invalid expected date/time.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $deliveredAt = trim((string)$req->input('delivered_at', (string)($row['delivered_at'] ?? '')));
    if ($deliveredAt !== '' && !$this->looksLikeDateTimeOrDate($deliveredAt)) {
      $this->ctx->session()->flash('error', 'Invalid delivered date/time.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    // When marking delivered without a timestamp, default to "now".
    if ($status === 'delivered' && $deliveredAt === '') {
      $deliveredAt = gmdate('c');
    }

    $notes = trim((string)$req->input('notes', (string)($row['notes'] ?? '')));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE deliveries
       SET qty = :q, status = :st, expected_at = :ea, delivered_at = :da, notes = :n, updated_at = :u
       WHERE id = :id',
      [
        'q' => $qty,
        'st' => $status,
        'ea' => $expectedAt === '' ? null : $expectedAt,
        'da' => $deliveredAt === '' ? null : $deliveredAt,
        'n' => $notes === '' ? null : $notes,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('delivery_update', 'delivery', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Delivery updated.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function deleteDelivery(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT project_id FROM deliveries WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) return Response::html('<h1>404</h1><p>Delivery not found.</p>', 404);

    $projectId = (int)($row['project_id'] ?? 0);
    $this->ctx->db()->execute('DELETE FROM deliveries WHERE id = :id', ['id' => $id]);
    $this->audit('delivery_delete', 'delivery', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Delivery deleted.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  private function parseQty($v): float
  {
    if (is_int($v) || is_float($v)) {
      $q = (float)$v;
    } else {
      $s = trim((string)$v);
      $s = str_replace(',', '.', $s);
      $q = (float)$s;
    }
    if (!is_finite($q) || $q < 0) $q = 0.0;
    if ($q > 1000000) $q = 1000000.0;
    return $q;
  }

  private function looksLikeDateTimeOrDate(string $s): bool
  {
    $t = trim($s);
    if ($t === '') return false;
    // Accept:
    // - date: 2026-02-07
    // - datetime-local: 2026-02-07T10:00
    // - ISO-ish: 2026-02-07T10:00:00Z
    // - space separated: 2026-02-07 10:00
    return (bool)preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}($|([T ][0-9]{2}:[0-9]{2}(:[0-9]{2})?(Z)?$))/', $t);
  }
}

