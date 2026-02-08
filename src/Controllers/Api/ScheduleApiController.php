<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ScheduleApiController extends Controller
{
  public function list(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $role = (string)($u['role'] ?? '');
    $uid = (int)($u['id'] ?? 0);

    $from = trim((string)$req->query('from', ''));
    $to = trim((string)$req->query('to', ''));

    if ($from === '' || !$this->looksLikeDateTimeOrDate($from)) {
      $from = gmdate('Y-m-d') . 'T00:00';
    }
    if ($to === '' || !$this->looksLikeDateTimeOrDate($to)) {
      $to = gmdate('Y-m-d', time() + 7 * 86400) . 'T23:59';
    }

    $limit = (int)$req->query('limit', '200');
    if ($limit <= 0) $limit = 200;
    if ($limit > 200) $limit = 200;

    $slice = function (array $rows) use ($limit): array {
      $hasMore = false;
      if (count($rows) > $limit) {
        $hasMore = true;
        $rows = array_slice($rows, 0, $limit);
      }
      return [$rows, $hasMore];
    };

    $bind = [
      'from' => $from,
      'to' => $to,
      'st' => 'approved',
    ];

    if ($role === 'admin') {
      $sql =
        'SELECT se.id, se.project_id, p.name AS project_name, se.title, se.starts_at, se.ends_at, se.status
         FROM schedule_events se
         JOIN projects p ON p.id = se.project_id
         WHERE se.status = :st AND se.starts_at >= :from AND se.starts_at <= :to
         ORDER BY se.starts_at ASC
         LIMIT ' . ($limit + 1);
      $rows = $this->ctx->db()->fetchAll($sql, $bind);
      [$items, $hasMore] = $slice($rows);
      return Response::json(['items' => $items, 'meta' => ['from' => $from, 'to' => $to, 'has_more' => $hasMore]], 200);
    }

    if ($role === 'pm') {
      $bind['uid'] = $uid;
      $sql =
        'SELECT se.id, se.project_id, p.name AS project_name, se.title, se.starts_at, se.ends_at, se.status
         FROM schedule_events se
         JOIN projects p ON p.id = se.project_id
         WHERE se.status = :st AND se.starts_at >= :from AND se.starts_at <= :to
           AND p.assigned_pm_user_id = :uid
         ORDER BY se.starts_at ASC
         LIMIT ' . ($limit + 1);
      $rows = $this->ctx->db()->fetchAll($sql, $bind);
      [$items, $hasMore] = $slice($rows);
      return Response::json(['items' => $items, 'meta' => ['from' => $from, 'to' => $to, 'has_more' => $hasMore]], 200);
    }

    if ($role === 'client') {
      $bind['uid'] = $uid;
      $sql =
        'SELECT se.id, se.project_id, p.name AS project_name, se.title, se.starts_at, se.ends_at, se.status
         FROM schedule_events se
         JOIN projects p ON p.id = se.project_id
         WHERE se.status = :st AND se.starts_at >= :from AND se.starts_at <= :to
           AND p.client_user_id = :uid
         ORDER BY se.starts_at ASC
         LIMIT ' . ($limit + 1);
      $rows = $this->ctx->db()->fetchAll($sql, $bind);
      [$items, $hasMore] = $slice($rows);
      return Response::json(['items' => $items, 'meta' => ['from' => $from, 'to' => $to, 'has_more' => $hasMore]], 200);
    }

    // employee/subcontractor worker etc: membership-based
    $bind['uid'] = $uid;
    $sql =
      'SELECT se.id, se.project_id, p.name AS project_name, se.title, se.starts_at, se.ends_at, se.status
       FROM schedule_events se
       JOIN projects p ON p.id = se.project_id
       JOIN project_members m ON m.project_id = p.id AND m.user_id = :uid
       WHERE se.status = :st AND se.starts_at >= :from AND se.starts_at <= :to
       ORDER BY se.starts_at ASC
       LIMIT ' . ($limit + 1);
    $rows = $this->ctx->db()->fetchAll($sql, $bind);
    [$items, $hasMore] = $slice($rows);
    return Response::json(['items' => $items, 'meta' => ['from' => $from, 'to' => $to, 'has_more' => $hasMore]], 200);
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

