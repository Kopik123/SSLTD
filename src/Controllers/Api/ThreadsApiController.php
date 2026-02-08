<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ThreadsApiController extends Controller
{
  public function list(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $scope = strtolower(trim((string)$req->query('scope', '')));
    $scopeId = (int)($req->query('scope_id', '0'));
    [$scopeType, $sid] = $this->resolveScope($scope, $scopeId);
    if ($scopeType === null || $sid <= 0) {
      return Response::json(['error' => 'invalid_request'], 400);
    }

    if (!$this->canAccessScope($scopeType, $sid, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    $items = $this->ctx->db()->fetchAll(
      'SELECT * FROM threads WHERE scope_type = :t AND scope_id = :i ORDER BY created_at DESC LIMIT 10',
      ['t' => $scopeType, 'i' => $sid]
    );
    if ($items === []) {
      $id = (int)$this->ctx->db()->insert(
        'INSERT INTO threads (scope_type, scope_id, created_at) VALUES (:t, :i, :c)',
        ['t' => $scopeType, 'i' => $sid, 'c' => gmdate('c')]
      );
      $items = $this->ctx->db()->fetchAll(
        'SELECT * FROM threads WHERE id = :id LIMIT 1',
        ['id' => $id]
      );
    }

    return Response::json(['items' => $items], 200);
  }

  public function messages(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $id = (int)($params['id'] ?? 0);
    $thread = $this->ctx->db()->fetchOne('SELECT * FROM threads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($thread === null) {
      return Response::json(['error' => 'not_found'], 404);
    }

    $scopeType = (string)($thread['scope_type'] ?? '');
    $scopeId = (int)($thread['scope_id'] ?? 0);
    if (!$this->canAccessScope($scopeType, $scopeId, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    $limit = (int)($req->query('limit', '200'));
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    $after = trim((string)$req->query('after', ''));

    $sql =
      'SELECT m.id, m.thread_id, m.sender_user_id, m.body, m.created_at,
              u.name AS sender_name, u.role AS sender_role
       FROM messages m
       LEFT JOIN users u ON u.id = m.sender_user_id
       WHERE m.thread_id = :tid';
    $bind = ['tid' => $id];
    if ($after !== '') {
      $sql .= ' AND m.created_at > :after';
      $bind['after'] = $after;
    }
    $sql .= ' ORDER BY m.created_at ASC LIMIT ' . $limit;

    $items = $this->ctx->db()->fetchAll($sql, $bind);

    return Response::json(['items' => $items], 200);
  }

  public function send(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $id = (int)($params['id'] ?? 0);
    $thread = $this->ctx->db()->fetchOne('SELECT * FROM threads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($thread === null) {
      return Response::json(['error' => 'not_found'], 404);
    }

    $scopeType = (string)($thread['scope_type'] ?? '');
    $scopeId = (int)($thread['scope_id'] ?? 0);
    if (!$this->canAccessScope($scopeType, $scopeId, $u)) {
      return Response::json(['error' => 'forbidden'], 403);
    }

    $body = $req->json() ?? [];
    $msg = trim((string)($body['body'] ?? $req->input('body', '')));
    if ($msg === '') {
      return Response::json(['error' => 'message_required'], 400);
    }
    if (mb_strlen($msg) > 5000) {
      return Response::json(['error' => 'message_too_long'], 400);
    }

    $mid = (int)$this->ctx->db()->insert(
      'INSERT INTO messages (thread_id, sender_user_id, body, created_at) VALUES (:tid, :sid, :b, :c)',
      [
        'tid' => $id,
        'sid' => (int)$u['id'],
        'b' => $msg,
        'c' => gmdate('c'),
      ]
    );

    return Response::json(['id' => $mid], 201);
  }

  /**
   * @return array{0: string|null, 1: int}
   */
  private function resolveScope(string $scope, int $scopeId): array
  {
    if ($scope === 'lead' || $scope === 'quote_request') {
      return ['quote_request', $scopeId];
    }
    if ($scope === 'project') {
      return ['project', $scopeId];
    }
    return [null, 0];
  }

  /** @param array<string, mixed> $user */
  private function canAccessScope(string $scopeType, int $scopeId, array $user): bool
  {
    $role = (string)($user['role'] ?? '');
    $uid = (int)($user['id'] ?? 0);

    if ($role === 'admin' || $role === 'pm') {
      return true;
    }

    if ($scopeType === 'project') {
      if ($role === 'client') {
        $p = $this->ctx->db()->fetchOne('SELECT client_user_id FROM projects WHERE id = :id LIMIT 1', ['id' => $scopeId]);
        return $p !== null && (int)($p['client_user_id'] ?? 0) === $uid;
      }

      $mem = $this->ctx->db()->fetchOne(
        'SELECT 1 AS ok FROM project_members WHERE project_id = :pid AND user_id = :uid LIMIT 1',
        ['pid' => $scopeId, 'uid' => $uid]
      );
      return $mem !== null;
    }

    if ($scopeType === 'quote_request') {
      $qr = $this->ctx->db()->fetchOne('SELECT client_user_id FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $scopeId]);
      if ($qr === null) {
        return false;
      }
      if ($role === 'client') {
        return (int)($qr['client_user_id'] ?? 0) === $uid;
      }
      return false;
    }

    return false;
  }
}
