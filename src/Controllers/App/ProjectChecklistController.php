<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectChecklistController extends Controller
{
  /** @return list<string> */
  private function allowedPricingModes(): array
  {
    return ['fixed', 'hours', 'sqm'];
  }

  /** @return list<string> */
  private function allowedItemStatuses(): array
  {
    return ['todo', 'in_progress', 'done', 'blocked'];
  }

  public function show(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne(
      'SELECT p.id, p.name, p.address, p.status, pm.name AS pm_name
       FROM projects p
       LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
       WHERE p.id = :id
       LIMIT 1',
      ['id' => $projectId]
    );
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $checklist = $this->ensureProjectChecklist($projectId);
    $items = $this->ctx->db()->fetchAll(
      'SELECT * FROM checklist_items WHERE checklist_id = :cid ORDER BY position ASC, id ASC',
      ['cid' => (int)$checklist['id']]
    );

    return $this->page('app/project_checklist', [
      'title' => 'Project #' . $projectId . ' Checklist',
      'project' => $project,
      'checklist' => $checklist,
      'items' => $items,
      'pricingModes' => $this->allowedPricingModes(),
      'itemStatuses' => $this->allowedItemStatuses(),
    ], 'layouts/app');
  }

  public function addItem(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $checklist = $this->ensureProjectChecklist($projectId);
    if ((string)($checklist['status'] ?? 'draft') !== 'draft') {
      $this->ctx->session()->flash('error', 'Checklist is locked. Use a Change Request to adjust scope/cost.');
      return $this->redirect('/app/projects/' . $projectId . '/checklist');
    }

    $title = trim((string)$req->input('title', ''));
    if ($title === '' || mb_strlen($title) > 512) {
      $this->ctx->session()->flash('error', 'Item title is required (max 512 chars).');
      return $this->redirect('/app/projects/' . $projectId . '/checklist');
    }

    $pricingMode = strtolower(trim((string)$req->input('pricing_mode', 'fixed')));
    if (!in_array($pricingMode, $this->allowedPricingModes(), true)) {
      $pricingMode = 'fixed';
    }

    $qty = $this->parseQty($req->input('qty', '0'));
    $unitCostCents = $this->parseMoneyToCents((string)$req->input('unit_cost', '0'));
    $fixedCostCents = $this->parseMoneyToCents((string)$req->input('fixed_cost', '0'));

    $status = strtolower(trim((string)$req->input('status', 'todo')));
    if (!in_array($status, $this->allowedItemStatuses(), true)) {
      $status = 'todo';
    }

    $posRow = $this->ctx->db()->fetchOne('SELECT COALESCE(MAX(position), 0) AS p FROM checklist_items WHERE checklist_id = :cid', [
      'cid' => (int)$checklist['id'],
    ]);
    $pos = (int)($posRow['p'] ?? 0);
    $pos = $pos < 0 ? 0 : $pos;

    $now = gmdate('c');
    $itemId = (int)$this->ctx->db()->insert(
      'INSERT INTO checklist_items (checklist_id, position, title, pricing_mode, qty, unit_cost_cents, fixed_cost_cents, status, created_at, updated_at)
       VALUES (:cid, :pos, :t, :pm, :q, :uc, :fc, :st, :c, :u)',
      [
        'cid' => (int)$checklist['id'],
        'pos' => $pos + 10,
        't' => $title,
        'pm' => $pricingMode,
        'q' => $qty,
        'uc' => $unitCostCents,
        'fc' => $fixedCostCents,
        'st' => $status,
        'c' => $now,
        'u' => $now,
      ]
    );
    $this->ctx->db()->execute('UPDATE checklists SET updated_at = :u WHERE id = :id', ['u' => $now, 'id' => (int)$checklist['id']]);

    $this->audit('project_checklist_item_add', 'checklist_item', $itemId, ['project_id' => $projectId, 'checklist_id' => (int)$checklist['id']]);
    $this->ctx->session()->flash('notice', 'Item added.');
    return $this->redirect('/app/projects/' . $projectId . '/checklist');
  }

  public function updateItem(Request $req, array $params): Response
  {
    $itemId = (int)($params['itemId'] ?? 0);
    $row = $this->ctx->db()->fetchOne(
      'SELECT ci.*, c.project_id, c.status AS checklist_status
       FROM checklist_items ci
       JOIN checklists c ON c.id = ci.checklist_id
       WHERE ci.id = :id
       LIMIT 1',
      ['id' => $itemId]
    );
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Item not found.</p>', 404);
    }

    $projectId = (int)($row['project_id'] ?? 0);
    if ($projectId <= 0) {
      return Response::html('<h1>400</h1><p>Unsupported checklist scope.</p>', 400);
    }

    $checklistStatus = (string)($row['checklist_status'] ?? 'draft');

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'todo'))));
    if (!in_array($status, $this->allowedItemStatuses(), true)) {
      $status = (string)($row['status'] ?? 'todo');
    }

    // If the checklist originated from an approved estimate, allow progress updates but keep pricing stable.
    $title = (string)($row['title'] ?? '');
    $pricingMode = (string)($row['pricing_mode'] ?? 'fixed');
    $qty = (float)($row['qty'] ?? 0);
    $unitCostCents = (int)($row['unit_cost_cents'] ?? 0);
    $fixedCostCents = (int)($row['fixed_cost_cents'] ?? 0);

    if ($checklistStatus === 'draft') {
      $t = trim((string)$req->input('title', $title));
      if ($t === '' || mb_strlen($t) > 512) {
        $this->ctx->session()->flash('error', 'Item title is required (max 512 chars).');
        return $this->redirect('/app/projects/' . $projectId . '/checklist');
      }
      $title = $t;

      $pm = strtolower(trim((string)$req->input('pricing_mode', $pricingMode)));
      if (in_array($pm, $this->allowedPricingModes(), true)) $pricingMode = $pm;

      $qty = $this->parseQty($req->input('qty', (string)$qty));
      $unitCostCents = $this->parseMoneyToCents((string)$req->input('unit_cost', '0'));
      $fixedCostCents = $this->parseMoneyToCents((string)$req->input('fixed_cost', '0'));
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE checklist_items
       SET title = :t, pricing_mode = :pm, qty = :q, unit_cost_cents = :uc, fixed_cost_cents = :fc, status = :st, updated_at = :u
       WHERE id = :id',
      [
        't' => $title,
        'pm' => $pricingMode,
        'q' => $qty,
        'uc' => $unitCostCents,
        'fc' => $fixedCostCents,
        'st' => $status,
        'u' => $now,
        'id' => $itemId,
      ]
    );
    $this->ctx->db()->execute('UPDATE checklists SET updated_at = :u WHERE id = :id', ['u' => $now, 'id' => (int)$row['checklist_id']]);

    $this->audit('project_checklist_item_update', 'checklist_item', $itemId, ['project_id' => $projectId, 'checklist_id' => (int)$row['checklist_id']]);
    $this->ctx->session()->flash('notice', 'Item updated.');
    return $this->redirect('/app/projects/' . $projectId . '/checklist');
  }

  public function deleteItem(Request $req, array $params): Response
  {
    $itemId = (int)($params['itemId'] ?? 0);
    $row = $this->ctx->db()->fetchOne(
      'SELECT ci.id, ci.checklist_id, c.project_id
       FROM checklist_items ci
       JOIN checklists c ON c.id = ci.checklist_id
       WHERE ci.id = :id
       LIMIT 1',
      ['id' => $itemId]
    );
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Item not found.</p>', 404);
    }

    $projectId = (int)($row['project_id'] ?? 0);
    if ($projectId <= 0) {
      return Response::html('<h1>400</h1><p>Unsupported checklist scope.</p>', 400);
    }

    $stRow = $this->ctx->db()->fetchOne('SELECT status FROM checklists WHERE id = :id LIMIT 1', ['id' => (int)$row['checklist_id']]);
    if ($stRow !== null && (string)($stRow['status'] ?? 'draft') !== 'draft') {
      $this->ctx->session()->flash('error', 'Checklist is locked; items cannot be deleted.');
      return $this->redirect('/app/projects/' . $projectId . '/checklist');
    }

    $this->ctx->db()->execute('DELETE FROM checklist_items WHERE id = :id', ['id' => $itemId]);
    $this->ctx->db()->execute('UPDATE checklists SET updated_at = :u WHERE id = :id', ['u' => gmdate('c'), 'id' => (int)$row['checklist_id']]);

    $this->audit('project_checklist_item_delete', 'checklist_item', $itemId, ['project_id' => $projectId, 'checklist_id' => (int)$row['checklist_id']]);
    $this->ctx->session()->flash('notice', 'Item deleted.');
    return $this->redirect('/app/projects/' . $projectId . '/checklist');
  }

  /** @return array<string,mixed> */
  private function ensureProjectChecklist(int $projectId): array
  {
    $row = $this->ctx->db()->fetchOne(
      'SELECT * FROM checklists WHERE project_id = :id ORDER BY id DESC LIMIT 1',
      ['id' => $projectId]
    );
    if ($row !== null) return $row;

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : null;
    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO checklists (quote_request_id, project_id, status, title, created_by_user_id, submitted_at, decided_at, decided_by_user_id, decision_note, created_at, updated_at)
       VALUES (NULL, :pid, :st, :t, :cb, NULL, NULL, NULL, NULL, :c, :u)',
      [
        'pid' => $projectId,
        'st' => 'draft',
        't' => 'Project Checklist',
        'cb' => $uid !== 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('project_checklist_create', 'checklist', $id, ['project_id' => $projectId]);
    $created = $this->ctx->db()->fetchOne('SELECT * FROM checklists WHERE id = :id LIMIT 1', ['id' => $id]);
    return $created ?? ['id' => $id, 'project_id' => $projectId, 'status' => 'draft', 'title' => 'Project Checklist'];
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
