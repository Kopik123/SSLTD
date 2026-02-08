<?php
declare(strict_types=1);

namespace App\Controllers\App\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class AuditController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $action = trim((string)$req->query('action', ''));
    $entityType = trim((string)$req->query('entity_type', ''));

    $where = [];
    $bind = [];
    if ($action !== '') {
      $where[] = 'a.action = :ac';
      $bind['ac'] = $action;
    }
    if ($entityType !== '') {
      $where[] = 'a.entity_type = :et';
      $bind['et'] = $entityType;
    }

    $sql =
      'SELECT a.*, u.name AS actor_name, u.email AS actor_email
       FROM audit_log a
       LEFT JOIN users u ON u.id = a.actor_user_id';
    if ($where !== []) {
      $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY a.created_at DESC LIMIT 500';

    $rows = $this->ctx->db()->fetchAll($sql, $bind);

    return $this->page('app/admin_audit', [
      'title' => 'Admin: Audit Log',
      'rows' => $rows,
      'filters' => [
        'action' => $action,
        'entity_type' => $entityType,
      ],
    ], 'layouts/app');
  }
}
