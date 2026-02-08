<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class MessagesController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }

    $role = (string)($u['role'] ?? '');
    $uid = (int)($u['id'] ?? 0);

    $page = (int)$req->query('page', '1');
    if ($page <= 0) $page = 1;
    $perPage = (int)$req->query('per_page', '25');
    if ($perPage <= 0) $perPage = 25;
    if ($perPage > 50) $perPage = 50;
    $offset = ($page - 1) * $perPage;
    if ($offset < 0) $offset = 0;
    if ($offset > 100000) $offset = 100000;

    $sql =
      'SELECT t.id, t.scope_type, t.scope_id, t.created_at,
              qr.name AS lead_name, qr.email AS lead_email,
              p.name AS project_name, p.address AS project_address,
              lm.body AS last_body, lm.created_at AS last_message_at, su.name AS last_sender_name,
              tr.last_read_at AS last_read_at,
              CASE
                WHEN lm.created_at IS NULL THEN 0
                WHEN tr.last_read_at IS NULL THEN 1
                WHEN lm.created_at > tr.last_read_at THEN 1
                ELSE 0
              END AS is_unread
       FROM threads t
       LEFT JOIN quote_requests qr ON qr.id = t.scope_id AND t.scope_type = :lead
       LEFT JOIN projects p ON p.id = t.scope_id AND t.scope_type = :proj
       LEFT JOIN (
         SELECT m1.thread_id, m1.body, m1.created_at, m1.sender_user_id
         FROM messages m1
         JOIN (
           SELECT thread_id, MAX(created_at) AS max_created_at
           FROM messages
           GROUP BY thread_id
         ) mm ON mm.thread_id = m1.thread_id AND mm.max_created_at = m1.created_at
       ) lm ON lm.thread_id = t.id
       LEFT JOIN users su ON su.id = lm.sender_user_id
       LEFT JOIN thread_reads tr ON tr.thread_id = t.id AND tr.user_id = :uid';

    $bind = ['lead' => 'quote_request', 'proj' => 'project', 'uid' => $uid];

    if ($role === 'client') {
      $sql .= ' WHERE (t.scope_type = :lead AND qr.client_user_id = :uid)
                     OR (t.scope_type = :proj AND p.client_user_id = :uid)';
      $bind['uid'] = $uid;
    } elseif ($role !== 'admin' && $role !== 'pm') {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $sql .= ' ORDER BY COALESCE(lm.created_at, t.created_at) DESC LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;

    $threads = $this->ctx->db()->fetchAll($sql, $bind);
    $hasMore = false;
    if (count($threads) > $perPage) {
      $hasMore = true;
      $threads = array_slice($threads, 0, $perPage);
    }

    return $this->page('app/messages_inbox', [
      'title' => 'Messages',
      'threads' => $threads,
      'page' => $page,
      'perPage' => $perPage,
      'hasMore' => $hasMore,
    ], 'layouts/app');
  }

  public function show(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }

    $id = (int)($params['id'] ?? 0);
    $thread = $this->ctx->db()->fetchOne('SELECT * FROM threads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($thread === null) {
      return Response::html('<h1>404</h1><p>Thread not found.</p>', 404);
    }

    $scopeType = (string)($thread['scope_type'] ?? '');
    $scopeId = (int)($thread['scope_id'] ?? 0);
    if (!$this->canAccessScope($scopeType, $scopeId, $u)) {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }
    $scope = null;

    if ($scopeType === 'quote_request') {
      $scope = $this->ctx->db()->fetchOne('SELECT id, status, name, email, address, created_at FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $scopeId]);
    } elseif ($scopeType === 'project') {
      $scope = $this->ctx->db()->fetchOne('SELECT id, status, name, address, created_at FROM projects WHERE id = :id LIMIT 1', ['id' => $scopeId]);
    }

    $messages = $this->ctx->db()->fetchAll(
      'SELECT m.*, u.name AS sender_name, u.role AS sender_role
       FROM messages m
       LEFT JOIN users u ON u.id = m.sender_user_id
       WHERE m.thread_id = :tid
       ORDER BY m.created_at ASC
       LIMIT 500',
      ['tid' => $id]
    );

    $this->markThreadRead($id, (int)$u['id']);

    return $this->page('app/messages_thread', [
      'title' => 'Thread #' . $id,
      'thread' => $thread,
      'scope' => $scope,
      'messages' => $messages,
    ], 'layouts/app');
  }

  public function send(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->user();
    if ($u === null) {
      return Response::html('<h1>401</h1><p>Unauthorized.</p>', 401);
    }

    $id = (int)($params['id'] ?? 0);
    $thread = $this->ctx->db()->fetchOne('SELECT id FROM threads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($thread === null) {
      return Response::html('<h1>404</h1><p>Thread not found.</p>', 404);
    }

    $full = $this->ctx->db()->fetchOne('SELECT * FROM threads WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($full === null) {
      return Response::html('<h1>404</h1><p>Thread not found.</p>', 404);
    }
    $scopeType = (string)($full['scope_type'] ?? '');
    $scopeId = (int)($full['scope_id'] ?? 0);
    if (!$this->canAccessScope($scopeType, $scopeId, $u)) {
      return Response::html('<h1>403</h1><p>Forbidden.</p>', 403);
    }

    $body = trim((string)$req->input('body', ''));
    if ($body === '') {
      $this->ctx->session()->flash('error', 'Message is required.');
      return $this->redirect('/app/messages/' . $id);
    }
    if (mb_strlen($body) > 5000) {
      $this->ctx->session()->flash('error', 'Message is too long.');
      return $this->redirect('/app/messages/' . $id);
    }

    $this->ctx->db()->insert(
      'INSERT INTO messages (thread_id, sender_user_id, body, created_at) VALUES (:tid, :sid, :b, :c)',
      [
        'tid' => $id,
        'sid' => (int)$u['id'],
        'b' => $body,
        'c' => gmdate('c'),
      ]
    );

    $this->markThreadRead($id, (int)$u['id']);

    return $this->redirect('/app/messages/' . $id);
  }

  /** @param array<string, mixed> $user */
  private function canAccessScope(string $scopeType, int $scopeId, array $user): bool
  {
    $role = (string)($user['role'] ?? '');
    $uid = (int)($user['id'] ?? 0);

    if ($role === 'admin' || $role === 'pm') {
      return true;
    }

    if ($role === 'client') {
      if ($scopeType === 'quote_request') {
        $qr = $this->ctx->db()->fetchOne('SELECT client_user_id FROM quote_requests WHERE id = :id LIMIT 1', ['id' => $scopeId]);
        return $qr !== null && (int)($qr['client_user_id'] ?? 0) === $uid;
      }
      if ($scopeType === 'project') {
        $p = $this->ctx->db()->fetchOne('SELECT client_user_id FROM projects WHERE id = :id LIMIT 1', ['id' => $scopeId]);
        return $p !== null && (int)($p['client_user_id'] ?? 0) === $uid;
      }
    }

    return false;
  }

  private function markThreadRead(int $threadId, int $userId): void
  {
    if ($threadId <= 0 || $userId <= 0) {
      return;
    }

    $ts = gmdate('c');
    $driver = strtolower($this->ctx->config()->getString('DB_CONNECTION'));
    try {
      if ($driver === 'mysql') {
        $this->ctx->db()->execute(
          'INSERT INTO thread_reads (thread_id, user_id, last_read_at)
           VALUES (:tid, :uid, :ts)
           ON DUPLICATE KEY UPDATE last_read_at = VALUES(last_read_at)',
          ['tid' => $threadId, 'uid' => $userId, 'ts' => $ts]
        );
      } else {
        $this->ctx->db()->execute(
          'INSERT INTO thread_reads (thread_id, user_id, last_read_at)
           VALUES (:tid, :uid, :ts)
           ON CONFLICT(thread_id, user_id) DO UPDATE SET last_read_at = excluded.last_read_at',
          ['tid' => $threadId, 'uid' => $userId, 'ts' => $ts]
        );
      }
    } catch (\Throwable $_) {
      // ignore
    }
  }
}
