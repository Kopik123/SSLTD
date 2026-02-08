<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ProjectIssuesController extends Controller
{
  /** @return list<string> */
  private function allowedStatuses(): array
  {
    return ['open', 'in_progress', 'blocked', 'resolved', 'closed'];
  }

  /** @return list<string> */
  private function allowedSeverities(): array
  {
    return ['low', 'medium', 'high'];
  }

  public function add(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $title = trim((string)$req->input('title', ''));
    if ($title === '' || mb_strlen($title) > 255) {
      $this->ctx->session()->flash('error', 'Issue title is required (max 255 chars).');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $severity = strtolower(trim((string)$req->input('severity', 'medium')));
    if (!in_array($severity, $this->allowedSeverities(), true)) $severity = 'medium';

    $body = trim((string)$req->input('body', ''));
    if ($body !== '' && mb_strlen($body) > 5000) $body = mb_substr($body, 0, 5000);

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO issues (project_id, status, severity, title, body, created_by_user_id, assigned_to_user_id, resolved_at, created_at, updated_at)
       VALUES (:pid, :st, :sev, :t, :b, :cb, NULL, NULL, :c, :u)',
      [
        'pid' => $projectId,
        'st' => 'open',
        'sev' => $severity,
        't' => $title,
        'b' => $body === '' ? null : $body,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('issue_add', 'issue', $id, ['project_id' => $projectId, 'severity' => $severity]);
    $this->ctx->session()->flash('notice', 'Issue created.');
    return $this->redirect('/app/projects/' . $projectId);
  }

  public function update(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM issues WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Issue not found.</p>', 404);
    }

    $projectId = (int)($row['project_id'] ?? 0);

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'open'))));
    if (!in_array($status, $this->allowedStatuses(), true)) $status = (string)($row['status'] ?? 'open');

    $severity = strtolower(trim((string)$req->input('severity', (string)($row['severity'] ?? 'medium'))));
    if (!in_array($severity, $this->allowedSeverities(), true)) $severity = (string)($row['severity'] ?? 'medium');

    $title = trim((string)$req->input('title', (string)($row['title'] ?? '')));
    if ($title === '' || mb_strlen($title) > 255) {
      $this->ctx->session()->flash('error', 'Issue title is required (max 255 chars).');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $body = trim((string)$req->input('body', (string)($row['body'] ?? '')));
    if ($body !== '' && mb_strlen($body) > 5000) $body = mb_substr($body, 0, 5000);

    $resolvedAt = $row['resolved_at'] ?? null;
    if (($status === 'resolved' || $status === 'closed') && empty($resolvedAt)) {
      $resolvedAt = gmdate('c');
    }
    if ($status !== 'resolved' && $status !== 'closed') {
      $resolvedAt = null;
    }

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE issues
       SET status = :st, severity = :sev, title = :t, body = :b, resolved_at = :ra, updated_at = :u
       WHERE id = :id',
      [
        'st' => $status,
        'sev' => $severity,
        't' => $title,
        'b' => $body === '' ? null : $body,
        'ra' => $resolvedAt,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('issue_update', 'issue', $id, ['project_id' => $projectId, 'status' => $status, 'severity' => $severity]);
    $this->ctx->session()->flash('notice', 'Issue updated.');
    return $this->redirect('/app/projects/' . $projectId);
  }
}

