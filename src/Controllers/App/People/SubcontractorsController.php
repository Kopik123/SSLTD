<?php
declare(strict_types=1);

namespace App\Controllers\App\People;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class SubcontractorsController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $q = trim((string)$req->query('q', ''));

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
    if ($q !== '') {
      $where[] = '(s.company_name LIKE :q OR u.email LIKE :q OR u.name LIKE :q)';
      $bind['q'] = '%' . $q . '%';
    }

    $countSql = 'SELECT COUNT(*) AS c FROM subcontractors s JOIN users u ON u.id = s.user_id';
    $sql =
      'SELECT s.*, u.name AS user_name, u.email AS user_email,
              (SELECT COUNT(*) FROM subcontractor_workers w WHERE w.subcontractor_id = s.id) AS workers_count,
              (SELECT COUNT(*) FROM subcontractor_workers w WHERE w.subcontractor_id = s.id AND w.status = :pending) AS pending_count
       FROM subcontractors s
       JOIN users u ON u.id = s.user_id';

    $bind['pending'] = 'pending';
    if ($where !== []) {
      $countSql .= ' WHERE ' . implode(' AND ', $where);
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $totalRow = $this->ctx->db()->fetchOne($countSql, $bind);
    $total = (int)($totalRow['c'] ?? 0);
    $sql .= ' ORDER BY s.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

    $rows = $this->ctx->db()->fetchAll($sql, $bind);

    return $this->page('app/people_subcontractors', [
      'title' => 'People: Subcontractors',
      'rows' => $rows,
      'page' => $page,
      'perPage' => $perPage,
      'total' => $total,
      'filters' => ['q' => $q],
    ], 'layouts/app');
  }

  public function show(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $sub = $this->ctx->db()->fetchOne(
      'SELECT s.*, u.name AS user_name, u.email AS user_email
       FROM subcontractors s
       JOIN users u ON u.id = s.user_id
       WHERE s.id = :id
       LIMIT 1',
      ['id' => $id]
    );
    if ($sub === null) {
      return Response::html('<h1>404</h1><p>Subcontractor not found.</p>', 404);
    }

    $workers = $this->ctx->db()->fetchAll(
      'SELECT w.*, u.name AS user_name, u.email AS user_email
       FROM subcontractor_workers w
       JOIN users u ON u.id = w.user_id
       WHERE w.subcontractor_id = :sid
       ORDER BY w.id DESC
       LIMIT 200',
      ['sid' => $id]
    );

    return $this->page('app/people_subcontractor_detail', [
      'title' => 'Subcontractor #' . $id,
      'sub' => $sub,
      'workers' => $workers,
    ], 'layouts/app');
  }

  public function approveWorker(Request $req, array $params): Response
  {
    return $this->setWorkerStatus($req, $params, 'active');
  }

  public function rejectWorker(Request $req, array $params): Response
  {
    return $this->setWorkerStatus($req, $params, 'inactive');
  }

  private function setWorkerStatus(Request $req, array $params, string $status): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne(
      'SELECT w.*, s.id AS subcontractor_id
       FROM subcontractor_workers w
       JOIN subcontractors s ON s.id = w.subcontractor_id
       WHERE w.id = :id
       LIMIT 1',
      ['id' => $id]
    );
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Worker not found.</p>', 404);
    }

    $subId = (int)($row['subcontractor_id'] ?? 0);
    $now = gmdate('c');
    $this->ctx->db()->execute('UPDATE subcontractor_workers SET status = :st WHERE id = :id', [
      'st' => $status,
      'id' => $id,
    ]);

    $this->audit('subcontractor_worker_status', 'subcontractor_worker', $id, [
      'subcontractor_id' => $subId,
      'status' => $status,
      'at' => $now,
    ]);

    $this->ctx->session()->flash('notice', $status === 'active' ? 'Worker approved.' : 'Worker rejected.');
    return $this->redirect('/app/people/subcontractors/' . $subId);
  }
}

