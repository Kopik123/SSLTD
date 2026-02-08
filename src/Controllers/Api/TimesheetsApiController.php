<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class TimesheetsApiController extends Controller
{
  public function start(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $uid = (int)($u['id'] ?? 0);
    $role = (string)($u['role'] ?? '');

    $body = $req->json() ?? [];
    $projectId = (int)($body['project_id'] ?? $req->input('project_id', 0));
    $notes = trim((string)($body['notes'] ?? $req->input('notes', '')));

    $running = $this->ctx->db()->fetchOne(
      'SELECT id, started_at FROM timesheets WHERE user_id = :uid AND stopped_at IS NULL ORDER BY started_at DESC LIMIT 1',
      ['uid' => $uid]
    );
    if ($running !== null) {
      return Response::json(['error' => 'already_running', 'id' => (int)$running['id'], 'started_at' => (string)$running['started_at']], 409);
    }

    if ($projectId > 0) {
      if (!$this->canAccessProject($projectId, $u)) {
        return Response::json(['error' => 'forbidden'], 403);
      }
    } else {
      // For MVP, employees should track time against a project. Staff can start unassigned.
      if ($role !== 'admin' && $role !== 'pm') {
        return Response::json(['error' => 'project_required'], 400);
      }
    }

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO timesheets (user_id, project_id, started_at, stopped_at, notes)
       VALUES (:uid, :pid, :s, NULL, :n)',
      [
        'uid' => $uid,
        'pid' => $projectId > 0 ? $projectId : null,
        's' => $now,
        'n' => $notes === '' ? null : $notes,
      ]
    );

    return Response::json(['id' => $id, 'started_at' => $now], 201);
  }

  public function stop(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $uid = (int)($u['id'] ?? 0);
    $body = $req->json() ?? [];
    $notes = trim((string)($body['notes'] ?? $req->input('notes', '')));

    $running = $this->ctx->db()->fetchOne(
      'SELECT id, notes FROM timesheets WHERE user_id = :uid AND stopped_at IS NULL ORDER BY started_at DESC LIMIT 1',
      ['uid' => $uid]
    );
    if ($running === null) {
      return Response::json(['error' => 'not_running'], 409);
    }

    $existingNotes = is_string($running['notes'] ?? null) ? (string)$running['notes'] : '';
    $finalNotes = $existingNotes;
    if ($notes !== '') {
      $finalNotes = $existingNotes === '' ? $notes : ($existingNotes . "\n\n" . $notes);
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE timesheets SET stopped_at = :stop, notes = :n WHERE id = :id AND user_id = :uid',
      [
        'stop' => $now,
        'n' => $finalNotes === '' ? null : $finalNotes,
        'id' => (int)$running['id'],
        'uid' => $uid,
      ]
    );

    return Response::json(['id' => (int)$running['id'], 'stopped_at' => $now], 200);
  }

  public function list(Request $req, array $params): Response
  {
    $u = $this->ctx->auth()->apiUserFromRequest($req);
    if ($u === null) {
      return Response::json(['error' => 'unauthorized'], 401);
    }

    $uid = (int)($u['id'] ?? 0);
    $from = trim((string)$req->query('from', ''));
    $to = trim((string)$req->query('to', ''));

    if ($to === '') {
      $to = gmdate('c');
    }
    if ($from === '') {
      $from = gmdate('c', time() - (30 * 86400));
    }

    $items = $this->ctx->db()->fetchAll(
      'SELECT id, user_id, project_id, started_at, stopped_at, notes
       FROM timesheets
       WHERE user_id = :uid AND started_at >= :from AND started_at <= :to
       ORDER BY started_at DESC
       LIMIT 500',
      ['uid' => $uid, 'from' => $from, 'to' => $to]
    );

    return Response::json(['items' => $items], 200);
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
