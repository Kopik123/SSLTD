<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class InventoryController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    return $this->redirect('/app/inventory/materials');
  }

  public function materials(Request $req, array $params): Response
  {
    $q = trim((string)$req->query('q', ''));
    $status = strtolower(trim((string)$req->query('status', '')));
    if ($status !== 'active' && $status !== 'inactive' && $status !== '') $status = '';

    [$page, $perPage, $offset] = $this->paging($req);

    $where = [];
    $bind = [];

    if ($status !== '') {
      $where[] = 'm.status = :st';
      $bind['st'] = $status;
    }
    if ($q !== '') {
      $where[] = '(m.name LIKE :q OR m.vendor LIKE :q OR m.sku LIKE :q)';
      $bind['q'] = '%' . $q . '%';
    }

    $countSql = 'SELECT COUNT(*) AS c FROM materials m';
    $sql = 'SELECT m.* FROM materials m';
    if ($where !== []) {
      $countSql .= ' WHERE ' . implode(' AND ', $where);
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $totalRow = $this->ctx->db()->fetchOne($countSql, $bind);
    $total = (int)($totalRow['c'] ?? 0);

    $sql .= ' ORDER BY m.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $rows = $this->ctx->db()->fetchAll($sql, $bind);

    return $this->page('app/inventory_materials', [
      'title' => 'Inventory: Materials',
      'rows' => $rows,
      'page' => $page,
      'perPage' => $perPage,
      'total' => $total,
      'filters' => ['q' => $q, 'status' => $status],
    ], 'layouts/app');
  }

  public function createMaterial(Request $req, array $params): Response
  {
    $name = trim((string)$req->input('name', ''));
    if ($name === '' || mb_strlen($name) > 255) {
      $this->ctx->session()->flash('error', 'Material name is required (max 255 chars).');
      return $this->redirect('/app/inventory/materials');
    }

    $unit = strtolower(trim((string)$req->input('unit', 'unit')));
    if ($unit === '' || mb_strlen($unit) > 32) $unit = 'unit';

    $unitCostCents = $this->parseMoneyToCents((string)$req->input('unit_cost', '0'));
    if ($unitCostCents < 0) $unitCostCents = 0;

    $vendor = trim((string)$req->input('vendor', ''));
    if (mb_strlen($vendor) > 255) $vendor = mb_substr($vendor, 0, 255);
    $sku = trim((string)$req->input('sku', ''));
    if (mb_strlen($sku) > 255) $sku = mb_substr($sku, 0, 255);
    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO materials (status, name, unit, unit_cost_cents, vendor, sku, notes, created_at, updated_at)
       VALUES (:st, :n, :u, :uc, :v, :sku, :notes, :c, :up)',
      [
        'st' => 'active',
        'n' => $name,
        'u' => $unit,
        'uc' => $unitCostCents,
        'v' => $vendor === '' ? null : $vendor,
        'sku' => $sku === '' ? null : $sku,
        'notes' => $notes === '' ? null : $notes,
        'c' => $now,
        'up' => $now,
      ]
    );

    $this->audit('material_create', 'material', $id);
    $this->ctx->session()->flash('notice', 'Material created.');
    return $this->redirect('/app/inventory/materials/' . $id);
  }

  public function showMaterial(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM materials WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Material not found.</p>', 404);
    }

    return $this->page('app/inventory_material_detail', [
      'title' => 'Material #' . $id,
      'material' => $row,
    ], 'layouts/app');
  }

  public function updateMaterial(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM materials WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Material not found.</p>', 404);
    }

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'active'))));
    if ($status !== 'active' && $status !== 'inactive') $status = (string)($row['status'] ?? 'active');

    $name = trim((string)$req->input('name', (string)($row['name'] ?? '')));
    if ($name === '' || mb_strlen($name) > 255) {
      $this->ctx->session()->flash('error', 'Material name is required (max 255 chars).');
      return $this->redirect('/app/inventory/materials/' . $id);
    }

    $unit = strtolower(trim((string)$req->input('unit', (string)($row['unit'] ?? 'unit'))));
    if ($unit === '' || mb_strlen($unit) > 32) $unit = (string)($row['unit'] ?? 'unit');

    $unitCostCents = $this->parseMoneyToCents((string)$req->input('unit_cost', '0'));
    if ($unitCostCents < 0) $unitCostCents = 0;

    $vendor = trim((string)$req->input('vendor', ''));
    if (mb_strlen($vendor) > 255) $vendor = mb_substr($vendor, 0, 255);
    $sku = trim((string)$req->input('sku', ''));
    if (mb_strlen($sku) > 255) $sku = mb_substr($sku, 0, 255);
    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE materials
       SET status = :st, name = :n, unit = :u, unit_cost_cents = :uc, vendor = :v, sku = :sku, notes = :notes, updated_at = :up
       WHERE id = :id',
      [
        'st' => $status,
        'n' => $name,
        'u' => $unit,
        'uc' => $unitCostCents,
        'v' => $vendor === '' ? null : $vendor,
        'sku' => $sku === '' ? null : $sku,
        'notes' => $notes === '' ? null : $notes,
        'up' => $now,
        'id' => $id,
      ]
    );

    $this->audit('material_update', 'material', $id);
    $this->ctx->session()->flash('notice', 'Material updated.');
    return $this->redirect('/app/inventory/materials/' . $id);
  }

  public function tools(Request $req, array $params): Response
  {
    $q = trim((string)$req->query('q', ''));
    $status = strtolower(trim((string)$req->query('status', '')));
    if ($status !== 'active' && $status !== 'inactive' && $status !== '') $status = '';

    [$page, $perPage, $offset] = $this->paging($req);

    $where = [];
    $bind = [];
    if ($status !== '') {
      $where[] = 't.status = :st';
      $bind['st'] = $status;
    }
    if ($q !== '') {
      $where[] = '(t.name LIKE :q OR t.serial LIKE :q OR t.location LIKE :q)';
      $bind['q'] = '%' . $q . '%';
    }

    $countSql = 'SELECT COUNT(*) AS c FROM tools t';
    $sql = 'SELECT t.* FROM tools t';
    if ($where !== []) {
      $countSql .= ' WHERE ' . implode(' AND ', $where);
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $totalRow = $this->ctx->db()->fetchOne($countSql, $bind);
    $total = (int)($totalRow['c'] ?? 0);

    $sql .= ' ORDER BY t.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $rows = $this->ctx->db()->fetchAll($sql, $bind);

    return $this->page('app/inventory_tools', [
      'title' => 'Inventory: Tools',
      'rows' => $rows,
      'page' => $page,
      'perPage' => $perPage,
      'total' => $total,
      'filters' => ['q' => $q, 'status' => $status],
    ], 'layouts/app');
  }

  public function createTool(Request $req, array $params): Response
  {
    $name = trim((string)$req->input('name', ''));
    if ($name === '' || mb_strlen($name) > 255) {
      $this->ctx->session()->flash('error', 'Tool name is required (max 255 chars).');
      return $this->redirect('/app/inventory/tools');
    }

    $serial = trim((string)$req->input('serial', ''));
    if (mb_strlen($serial) > 255) $serial = mb_substr($serial, 0, 255);
    $location = trim((string)$req->input('location', ''));
    if (mb_strlen($location) > 255) $location = mb_substr($location, 0, 255);
    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO tools (status, name, serial, location, notes, created_at, updated_at)
       VALUES (:st, :n, :s, :l, :notes, :c, :up)',
      [
        'st' => 'active',
        'n' => $name,
        's' => $serial === '' ? null : $serial,
        'l' => $location === '' ? null : $location,
        'notes' => $notes === '' ? null : $notes,
        'c' => $now,
        'up' => $now,
      ]
    );

    $this->audit('tool_create', 'tool', $id);
    $this->ctx->session()->flash('notice', 'Tool created.');
    return $this->redirect('/app/inventory/tools/' . $id);
  }

  public function showTool(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM tools WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Tool not found.</p>', 404);
    }

    return $this->page('app/inventory_tool_detail', [
      'title' => 'Tool #' . $id,
      'tool' => $row,
    ], 'layouts/app');
  }

  public function updateTool(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM tools WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Tool not found.</p>', 404);
    }

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'active'))));
    if ($status !== 'active' && $status !== 'inactive') $status = (string)($row['status'] ?? 'active');

    $name = trim((string)$req->input('name', (string)($row['name'] ?? '')));
    if ($name === '' || mb_strlen($name) > 255) {
      $this->ctx->session()->flash('error', 'Tool name is required (max 255 chars).');
      return $this->redirect('/app/inventory/tools/' . $id);
    }

    $serial = trim((string)$req->input('serial', ''));
    if (mb_strlen($serial) > 255) $serial = mb_substr($serial, 0, 255);
    $location = trim((string)$req->input('location', ''));
    if (mb_strlen($location) > 255) $location = mb_substr($location, 0, 255);
    $notes = trim((string)$req->input('notes', ''));
    if (mb_strlen($notes) > 5000) $notes = mb_substr($notes, 0, 5000);

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE tools
       SET status = :st, name = :n, serial = :s, location = :l, notes = :notes, updated_at = :up
       WHERE id = :id',
      [
        'st' => $status,
        'n' => $name,
        's' => $serial === '' ? null : $serial,
        'l' => $location === '' ? null : $location,
        'notes' => $notes === '' ? null : $notes,
        'up' => $now,
        'id' => $id,
      ]
    );

    $this->audit('tool_update', 'tool', $id);
    $this->ctx->session()->flash('notice', 'Tool updated.');
    return $this->redirect('/app/inventory/tools/' . $id);
  }

  /** @return array{0:int,1:int,2:int} */
  private function paging(Request $req): array
  {
    $page = (int)$req->query('page', '1');
    if ($page <= 0) $page = 1;
    $perPage = (int)$req->query('per_page', '25');
    if ($perPage <= 0) $perPage = 25;
    if ($perPage > 100) $perPage = 100;
    $offset = ($page - 1) * $perPage;
    if ($offset < 0) $offset = 0;
    if ($offset > 100000) $offset = 100000;
    return [$page, $perPage, $offset];
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

