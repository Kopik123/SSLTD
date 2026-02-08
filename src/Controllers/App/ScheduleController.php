<?php
declare(strict_types=1);

namespace App\Controllers\App;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ScheduleController extends Controller
{
  public function index(Request $req, array $params): Response
  {
    $view = strtolower(trim((string)$req->query('view', 'week')));
    if ($view !== 'week' && $view !== 'month') $view = 'week';

    $dateStr = trim((string)$req->query('date', ''));
    $base = $this->parseDate($dateStr);

    [$rangeStartDt, $rangeEndDt, $days] = $this->rangeForView($view, $base);
    $rangeStart = $rangeStartDt->format('Y-m-d') . 'T00:00';
    $rangeEnd = $rangeEndDt->format('Y-m-d') . 'T23:59';

    $projects = [];
    try {
      $projects = $this->ctx->db()->fetchAll(
        'SELECT p.id, p.name, p.address, pm.name AS pm_name
         FROM projects p
         LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
         ORDER BY p.created_at DESC
         LIMIT 200'
      );
    } catch (\Throwable $_) {
      $projects = [];
    }

    $proposals = $this->ctx->db()->fetchAll(
      'SELECT sp.*, p.name AS project_name, p.address, u.name AS created_by_name
       FROM schedule_proposals sp
       JOIN projects p ON p.id = sp.project_id
       LEFT JOIN users u ON u.id = sp.created_by_user_id
       WHERE sp.status = :st
       ORDER BY sp.created_at DESC
       LIMIT 100',
      ['st' => 'submitted']
    );

    $calendarEvents = [];
    try {
      $calendarEvents = $this->ctx->db()->fetchAll(
        'SELECT se.*, p.name AS project_name, p.address,
                p.assigned_pm_user_id AS pm_user_id, pm.name AS pm_name
         FROM schedule_events se
         JOIN projects p ON p.id = se.project_id
         LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
         WHERE se.starts_at <= :re AND se.ends_at >= :rs
         ORDER BY se.starts_at ASC
         LIMIT 500',
        ['rs' => $rangeStart, 're' => $rangeEnd]
      );
    } catch (\Throwable $_) {
      $calendarEvents = [];
    }

    $conflictIds = $this->detectPmConflicts($calendarEvents);
    foreach ($calendarEvents as &$e) {
      $id = (int)($e['id'] ?? 0);
      $e['_conflict'] = $id > 0 && isset($conflictIds[$id]);
    }
    unset($e);

    $eventsByDay = [];
    foreach ($days as $d) {
      $eventsByDay[$d] = [];
    }
    foreach ($calendarEvents as $e) {
      $day = substr((string)($e['starts_at'] ?? ''), 0, 10);
      if ($day === '' || !array_key_exists($day, $eventsByDay)) continue;
      $eventsByDay[$day][] = $e;
    }

    $upcomingEvents = [];
    try {
      $upcomingEvents = $this->ctx->db()->fetchAll(
        'SELECT se.*, p.name AS project_name, p.address,
                p.assigned_pm_user_id AS pm_user_id, pm.name AS pm_name
         FROM schedule_events se
         JOIN projects p ON p.id = se.project_id
         LEFT JOIN users pm ON pm.id = p.assigned_pm_user_id
         WHERE se.status = :st
         ORDER BY se.starts_at ASC
         LIMIT 200',
        ['st' => 'approved']
      );
    } catch (\Throwable $_) {
      $upcomingEvents = [];
    }

    return $this->page('app/schedule', [
      'title' => 'Schedule',
      'proposals' => $proposals,
      'calendarEvents' => $calendarEvents,
      'upcomingEvents' => $upcomingEvents,
      'projects' => $projects,
      'view' => $view,
      'date' => $base->format('Y-m-d'),
      'rangeStart' => $rangeStart,
      'rangeEnd' => $rangeEnd,
      'days' => $days,
      'eventsByDay' => $eventsByDay,
    ], 'layouts/app');
  }

  public function createEvent(Request $req, array $params): Response
  {
    $projectId = (int)$req->input('project_id', '0');
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) {
      $this->ctx->session()->flash('error', 'Select a valid project.');
      return $this->redirect('/app/schedule');
    }

    $title = trim((string)$req->input('title', ''));
    if ($title === '' || mb_strlen($title) > 255) {
      $this->ctx->session()->flash('error', 'Event title is required (max 255 chars).');
      return $this->redirect('/app/schedule');
    }

    $startsAt = trim((string)$req->input('starts_at', ''));
    $endsAt = trim((string)$req->input('ends_at', ''));
    if ($startsAt === '' || $endsAt === '' || !$this->looksLikeDateTime($startsAt) || !$this->looksLikeDateTime($endsAt)) {
      $this->ctx->session()->flash('error', 'Valid start and end date/time are required.');
      return $this->redirect('/app/schedule');
    }

    $status = strtolower(trim((string)$req->input('status', 'approved')));
    if ($status !== 'approved' && $status !== 'cancelled') $status = 'approved';

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : 0;

    $now = gmdate('c');
    $id = (int)$this->ctx->db()->insert(
      'INSERT INTO schedule_events (project_id, title, starts_at, ends_at, status, created_by_user_id, created_at, updated_at)
       VALUES (:pid, :t, :sa, :ea, :st, :cb, :c, :u)',
      [
        'pid' => $projectId,
        't' => $title,
        'sa' => $startsAt,
        'ea' => $endsAt,
        'st' => $status,
        'cb' => $uid > 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->audit('schedule_event_create', 'schedule_event', $id, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Event created.');
    return $this->redirect('/app/schedule');
  }

  public function updateEvent(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT * FROM schedule_events WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Event not found.</p>', 404);
    }

    $title = trim((string)$req->input('title', (string)($row['title'] ?? '')));
    if ($title === '' || mb_strlen($title) > 255) {
      $this->ctx->session()->flash('error', 'Event title is required (max 255 chars).');
      return $this->redirect('/app/schedule');
    }

    $startsAt = trim((string)$req->input('starts_at', (string)($row['starts_at'] ?? '')));
    $endsAt = trim((string)$req->input('ends_at', (string)($row['ends_at'] ?? '')));
    if ($startsAt === '' || $endsAt === '' || !$this->looksLikeDateTime($startsAt) || !$this->looksLikeDateTime($endsAt)) {
      $this->ctx->session()->flash('error', 'Valid start and end date/time are required.');
      return $this->redirect('/app/schedule');
    }

    $status = strtolower(trim((string)$req->input('status', (string)($row['status'] ?? 'approved'))));
    if ($status !== 'approved' && $status !== 'cancelled') $status = (string)($row['status'] ?? 'approved');

    $now = gmdate('c');
    $this->ctx->db()->execute(
      'UPDATE schedule_events
       SET title = :t, starts_at = :sa, ends_at = :ea, status = :st, updated_at = :u
       WHERE id = :id',
      [
        't' => $title,
        'sa' => $startsAt,
        'ea' => $endsAt,
        'st' => $status,
        'u' => $now,
        'id' => $id,
      ]
    );

    $this->audit('schedule_event_update', 'schedule_event', $id, ['project_id' => (int)($row['project_id'] ?? 0)]);
    $this->ctx->session()->flash('notice', 'Event updated.');
    return $this->redirect('/app/schedule');
  }

  public function cancelEvent(Request $req, array $params): Response
  {
    $id = (int)($params['id'] ?? 0);
    $row = $this->ctx->db()->fetchOne('SELECT id, project_id, status FROM schedule_events WHERE id = :id LIMIT 1', ['id' => $id]);
    if ($row === null) {
      return Response::html('<h1>404</h1><p>Event not found.</p>', 404);
    }

    $now = gmdate('c');
    $this->ctx->db()->execute('UPDATE schedule_events SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => 'cancelled',
      'u' => $now,
      'id' => $id,
    ]);

    $this->audit('schedule_event_cancel', 'schedule_event', $id, ['project_id' => (int)($row['project_id'] ?? 0)]);
    $this->ctx->session()->flash('notice', 'Event cancelled.');
    return $this->redirect('/app/schedule');
  }

  public function propose(Request $req, array $params): Response
  {
    $projectId = (int)($params['id'] ?? 0);
    $project = $this->ctx->db()->fetchOne('SELECT id FROM projects WHERE id = :id LIMIT 1', ['id' => $projectId]);
    if ($project === null) {
      return Response::html('<h1>404</h1><p>Project not found.</p>', 404);
    }

    $startsAt = trim((string)$req->input('starts_at', ''));
    $endsAt = trim((string)$req->input('ends_at', ''));
    $note = trim((string)$req->input('note', ''));
    if (mb_strlen($note) > 5000) $note = mb_substr($note, 0, 5000);

    if ($startsAt === '' || $endsAt === '') {
      $this->ctx->session()->flash('error', 'Start and end date/time are required.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    // Minimal validation: ISO-ish strings or HTML datetime-local format.
    if (!$this->looksLikeDateTime($startsAt) || !$this->looksLikeDateTime($endsAt)) {
      $this->ctx->session()->flash('error', 'Invalid date/time format.');
      return $this->redirect('/app/projects/' . $projectId);
    }

    $u = $this->ctx->auth()->user();
    $uid = $u !== null ? (int)($u['id'] ?? 0) : null;

    $now = gmdate('c');
    $proposalId = (int)$this->ctx->db()->insert(
      'INSERT INTO schedule_proposals (project_id, status, starts_at, ends_at, note, created_by_user_id, decided_by_user_id, created_at, decided_at, updated_at)
       VALUES (:pid, :st, :sa, :ea, :n, :cb, NULL, :c, NULL, :u)',
      [
        'pid' => $projectId,
        'st' => 'submitted',
        'sa' => $startsAt,
        'ea' => $endsAt,
        'n' => $note === '' ? null : $note,
        'cb' => $uid !== 0 ? $uid : null,
        'c' => $now,
        'u' => $now,
      ]
    );

    $this->ctx->db()->execute('UPDATE projects SET status = :st, updated_at = :u WHERE id = :id', [
      'st' => 'schedule_proposed',
      'u' => $now,
      'id' => $projectId,
    ]);

    $this->audit('schedule_propose', 'schedule_proposal', $proposalId, ['project_id' => $projectId]);
    $this->ctx->session()->flash('notice', 'Schedule proposed to client (see client Approvals).');
    return $this->redirect('/app/projects/' . $projectId);
  }

  private function looksLikeDateTime(string $s): bool
  {
    $t = trim($s);
    if ($t === '') return false;
    // Accept a few typical formats:
    // - ISO: 2026-02-07T10:00:00Z
    // - datetime-local: 2026-02-07T10:00
    // - space separated: 2026-02-07 10:00
    return (bool)preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}[T ][0-9]{2}:[0-9]{2}(:[0-9]{2})?(Z)?$/', $t);
  }

  private function parseDate(string $s): \DateTimeImmutable
  {
    $t = trim($s);
    $tz = new \DateTimeZone('UTC');
    if ($t === '') {
      return new \DateTimeImmutable('now', $tz);
    }
    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $t) === 1) {
      return new \DateTimeImmutable($t . 'T00:00:00Z', $tz);
    }
    try {
      return new \DateTimeImmutable($t, $tz);
    } catch (\Throwable $_) {
      return new \DateTimeImmutable('now', $tz);
    }
  }

  /** @return array{0:\DateTimeImmutable,1:\DateTimeImmutable,2:list<string>} */
  private function rangeForView(string $view, \DateTimeImmutable $base): array
  {
    $tz = new \DateTimeZone('UTC');
    $b = $base->setTimezone($tz);

    if ($view === 'month') {
      $start = $b->modify('first day of this month')->setTime(0, 0, 0);
      $end = $b->modify('last day of this month')->setTime(23, 59, 59);
      $days = [];
      $cur = $start;
      while ($cur <= $end) {
        $days[] = $cur->format('Y-m-d');
        $cur = $cur->modify('+1 day');
      }
      return [$start, $end, $days];
    }

    $start = $b->modify('monday this week')->setTime(0, 0, 0);
    $end = $start->modify('+6 days')->setTime(23, 59, 59);
    $days = [];
    for ($i = 0; $i < 7; $i++) {
      $days[] = $start->modify('+' . $i . ' days')->format('Y-m-d');
    }
    return [$start, $end, $days];
  }

  /** @param list<array<string,mixed>> $events
   *  @return array<int,true> map of event_id => true
   */
  private function detectPmConflicts(array $events): array
  {
    $byPm = [];
    foreach ($events as $e) {
      if ((string)($e['status'] ?? '') !== 'approved') continue;
      $pm = (int)($e['pm_user_id'] ?? 0);
      if ($pm <= 0) continue;
      $byPm[$pm][] = $e;
    }

    $conflicts = [];
    foreach ($byPm as $pmId => $list) {
      usort($list, static fn ($a, $b): int => strcmp((string)($a['starts_at'] ?? ''), (string)($b['starts_at'] ?? '')));
      $prevEnd = null;
      $prevId = null;
      foreach ($list as $e) {
        $sid = (int)($e['id'] ?? 0);
        $start = $this->toTs((string)($e['starts_at'] ?? ''));
        $end = $this->toTs((string)($e['ends_at'] ?? ''));
        if ($start === null || $end === null) continue;
        if ($prevEnd !== null && $start < $prevEnd) {
          if ($sid > 0) $conflicts[$sid] = true;
          if (is_int($prevId) && $prevId > 0) $conflicts[$prevId] = true;
        }
        $prevEnd = max($prevEnd ?? 0, $end);
        $prevId = $sid;
      }
    }
    return $conflicts;
  }

  private function toTs(string $s): ?int
  {
    $t = trim($s);
    if ($t === '') return null;
    // Normalize common formats to something DateTime can parse.
    try {
      return (new \DateTimeImmutable($t, new \DateTimeZone('UTC')))->getTimestamp();
    } catch (\Throwable $_) {
      try {
        return (new \DateTimeImmutable(str_replace(' ', 'T', $t) . 'Z', new \DateTimeZone('UTC')))->getTimestamp();
      } catch (\Throwable $_2) {
        return null;
      }
    }
  }
}
