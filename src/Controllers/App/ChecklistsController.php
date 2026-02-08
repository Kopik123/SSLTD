<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ChecklistsController extends Controller
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

  public function leadChecklist(Request $req, array $params): Response
  {
    $leadId = (int)($params['id'] ?? 0);
    $lead = $this->ctx->db()->fetchOne('SELECT id, status, name, email, address, assigned_pm_user_id FROM quote_requests WHERE id = :id LIMIT 1', [
      'id' => $leadId,
    ]);
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $checklist = $this->ensureLeadChecklist($leadId);
    $items = $this->ctx->db()->fetchAll(
      'SELECT * FROM checklist_items WHERE checklist_id = :cid ORDER BY position ASC, id ASC',
      ['cid' => (int)$checklist['id']]
    );

    return $this->page('app/lead_checklist', [
      'title' => 'Lead #' . $leadId . ' Checklist',
      'lead' => $lead,
      'checklist' => $checklist,
      'items' => $items,
      'pricingModes' => $this->allowedPricingModes(),
      'itemStatuses' => $this->allowedItemStatuses(),
    ], 'layouts/app');
  }

  public function addItem(Request $req, array $params): Response
  {
    $leadId = (int)($params['id'] ?? 0);
    $lead = $this->ctx->db()->fetchOne('SELECT id FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $leadId]);
    if ($lead === null) {
      return Response::html('<h1>404</h1><p>Lead not found.</p>', 404);
    }

    $checklist = $this->ensureLeadChecklist($leadId);
    if (((string)($checklist['status'] ?? 'draft')) !== 'draft') {
      $this->ctx->session()->flash('error', 'Checklist is not editable after submission.');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
    }

    $title = trim((string)$req->input('title', ''));
    if ($title === '' || mb_strlen($title) > 512) {
      $this->ctx->session()->flash('error', 'Item title is required (max 512 chars).');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
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

    $this->audit('checklist_item_add', 'checklist', (int)$checklist['id'], ['item_id' => $itemId, 'lead_id' => $leadId]);
    $this->ctx->session()->flash('notice', 'Item added.');
    return $this->redirect('/app/leads/' . $leadId . '/checklist');
  }

  public function updateItem(Request $req, array $params): Response
  {
    $itemId = (int)($params['itemId'] ?? 0);
    $row = $this->ctx->db()->fetchOne(
      'SELECT ci.*, c.quote_request_id, c.status AS checklist_status
       FROM checklist_items ci
       JOIN checklists c ON c.id = ci.checklist_id
       WHERE ci.id = :id
       LIMIT 1',
      ['id' => $itemId]
    );
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Item not found.</p>', 404);
    }

    $leadId = (int)($row['quote_request_id'] ?? 0);
    if ($leadId <= 0) {
      return Response::html('<h1>400</h1><p>Unsupported checklist scope.</p>', 400);
    }

    if (((string)($row['checklist_status'] ?? 'draft')) !== 'draft') {
      $this->ctx->session()->flash('error', 'Checklist is not editable after submission.');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
    }

    $title = trim((string)$req->input('title', ''));
    if ($title === '' || mb_strlen($title) > 512) {
      $this->ctx->session()->flash('error', 'Item title is required (max 512 chars).');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
    }

    $pricingMode = strtolower(trim((string)$req->input('pricing_mode', 'fixed')));
    if (!in_array($pricingMode, $this->allowedPricingModes(), true)) {
      $pricingMode = (string)($row['pricing_mode'] ?? 'fixed');
    }

    $qty = $this->parseQty($req->input('qty', (string)($row['qty'] ?? '0')));
    $unitCostCents = $this->parseMoneyToCents((string)$req->input('unit_cost', '0'));
    $fixedCostCents = $this->parseMoneyToCents((string)$req->input('fixed_cost', '0'));

    $status = strtolower(trim((string)$req->input('status', 'todo')));
    if (!in_array($status, $this->allowedItemStatuses(), true)) {
      $status = (string)($row['status'] ?? 'todo');
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

    $this->audit('checklist_item_update', 'checklist_item', $itemId, ['lead_id' => $leadId, 'checklist_id' => (int)$row['checklist_id']]);
    $this->ctx->session()->flash('notice', 'Item updated.');
    return $this->redirect('/app/leads/' . $leadId . '/checklist');
  }

  public function deleteItem(Request $req, array $params): Response
  {
    $itemId = (int)($params['itemId'] ?? 0);
    $row = $this->ctx->db()->fetchOne(
      'SELECT ci.id, ci.checklist_id, c.quote_request_id, c.status AS checklist_status
       FROM checklist_items ci
       JOIN checklists c ON c.id = ci.checklist_id
       WHERE ci.id = :id
       LIMIT 1',
      ['id' => $itemId]
    );
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Item not found.</p>', 404);
    }

    $leadId = (int)($row['quote_request_id'] ?? 0);
    if ($leadId <= 0) {
      return Response::html('<h1>400</h1><p>Unsupported checklist scope.</p>', 400);
    }

    if (((string)($row['checklist_status'] ?? 'draft')) !== 'draft') {
      $this->ctx->session()->flash('error', 'Checklist is not editable after submission.');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
    }

    $this->ctx->db()->execute('DELETE FROM checklist_items WHERE id = :id', ['id' => $itemId]);
    $this->ctx->db()->execute('UPDATE checklists SET updated_at = :u WHERE id = :id', ['u' => gmdate('c'), 'id' => (int)$row['checklist_id']]);

    $this->audit('checklist_item_delete', 'checklist_item', $itemId, ['lead_id' => $leadId, 'checklist_id' => (int)$row['checklist_id']]);
    $this->ctx->session()->flash('notice', 'Item deleted.');
    return $this->redirect('/app/leads/' . $leadId . '/checklist');
  }

  public function submitLeadChecklist(Request $req, array $params): Response
  {
    $leadId = (int)($params['id'] ?? 0);
    $checklist = $this->ensureLeadChecklist($leadId);
    if (((string)($checklist['status'] ?? 'draft')) !== 'draft') {
      $this->ctx->session()->flash('error', 'Checklist already submitted.');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
    }

    $cnt = $this->ctx->db()->fetchOne('SELECT COUNT(*) AS c FROM checklist_items WHERE checklist_id = :cid', ['cid' => (int)$checklist['id']]);
    if ((int)($cnt['c'] ?? 0) <= 0) {
      $this->ctx->session()->flash('error', 'Add at least one item before submitting.');
      return $this->redirect('/app/leads/' . $leadId . '/checklist');
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE checklists SET status = :st, submitted_at = :s, updated_at = :u WHERE id = :id',
      ['st' => 'submitted', 's' => $now, 'u' => $now, 'id' => (int)$checklist['id']]
    );
    // Keep lead status aligned with plan (optional, but useful for workflow).
    $this->ctx->db()->execute('UPDATE quote_requests SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => 'checklist_submitted',
      'u' => $now,
      'id' => $leadId,
    ]);

    $this->audit('checklist_submit', 'checklist', (int)$checklist['id'], ['lead_id' => $leadId]);
    $this->ctx->session()->flash('notice', 'Checklist submitted to client (see client Approvals).');
    return $this->redirect('/app/leads/' . $leadId);
  }

  /** @return array<string, mixed> */
  private function ensureLeadChecklist(int $leadId): array
  {
    $row = $this->ctx->db()->fetchOne(
      'SELECT * FROM checklists WHERE quote_request_id = :id ORDER BY id DESC LIMIT 1',
      ['id' => $leadId]
    );
    if ($row !== null) {
      return $row;
    }

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : null;
    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO checklists (quote_request_id, project_id, status, title, created_by_user_id, submitted_at, decided_at, decided_by_user_id, decision_note, created_at, updated_at)
       VALUES (:qid, NULL, :st, :t, :cb, NULL, NULL, NULL, NULL, :c, :u)',
      [
        'qid' => $leadId,
        'st' => 'draft',
        't' => 'Estimate / Checklist',
        'cb' => $uid !== 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('checklist_create', 'checklist', $id, ['lead_id' => $leadId]);
    $created = $this->ctx->db()->fetchOne('SELECT * FROM checklists WHERE id = :id LIMIT 1', ['id' => $id]);
    return $created ?? ['id' => $id, 'quote_request_id' => $leadId, 'status' => 'draft', 'title' => 'Estimate / Checklist'];
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

