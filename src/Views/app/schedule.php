<?php
$title = $title ?? 'Schedule';
$proposals = $proposals ?? [];
$calendarEvents = $calendarEvents ?? [];
$upcomingEvents = $upcomingEvents ?? [];
$projects = $projects ?? [];
$view = $view ?? 'week';
$date = $date ?? gmdate('Y-m-d');
$days = $days ?? [];
$eventsByDay = $eventsByDay ?? [];

function nextLink(string $view, string $date, int $deltaDays): string {
  $dt = new DateTimeImmutable($date . 'T00:00:00Z', new DateTimeZone('UTC'));
  $next = $dt->modify(($deltaDays >= 0 ? '+' : '') . (string)$deltaDays . ' days')->format('Y-m-d');
  return '?view=' . urlencode($view) . '&date=' . urlencode($next);
}

function nextMonthLink(string $date, int $deltaMonths): string {
  $dt = new DateTimeImmutable($date . 'T00:00:00Z', new DateTimeZone('UTC'));
  $next = $dt->modify(($deltaMonths >= 0 ? '+' : '') . (string)$deltaMonths . ' months')->format('Y-m-d');
  return '?view=month&date=' . urlencode($next);
}
?>

<section class="section">
  <h2>Schedule</h2>

  <div class="card mb-14">
    <div class="muted">Calendar view (week/month) plus pending client proposals.</div>
  </div>

  <div class="card mb-14">
    <div class="flex items-center justify-between gap-10">
      <div class="fw-700">Calendar</div>
      <div class="flex items-center gap-10">
        <a class="btn <?= $view === 'week' ? 'btn--gold' : '' ?>" href="<?= e(app_url($ctx, '/app/schedule?view=week&date=' . urlencode((string)$date))) ?>">Week</a>
        <a class="btn <?= $view === 'month' ? 'btn--gold' : '' ?>" href="<?= e(app_url($ctx, '/app/schedule?view=month&date=' . urlencode((string)$date))) ?>">Month</a>
      </div>
    </div>
    <div class="flex items-center justify-between gap-10 mt-10">
      <div class="muted">Anchor date: <?= e((string)$date) ?></div>
      <div class="flex items-center gap-10">
        <?php if ($view === 'week'): ?>
          <a class="btn" href="<?= e(app_url($ctx, '/app/schedule' . nextLink('week', (string)$date, -7))) ?>">Prev</a>
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/schedule' . nextLink('week', (string)$date, 7))) ?>">Next</a>
        <?php else: ?>
          <a class="btn" href="<?= e(app_url($ctx, '/app/schedule' . nextMonthLink((string)$date, -1))) ?>">Prev</a>
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/schedule' . nextMonthLink((string)$date, 1))) ?>">Next</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="title">Create event</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/schedule/events')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="project_id">Project</label>
          <select id="project_id" name="project_id" required>
            <option value="" selected disabled>Select project</option>
            <?php foreach (($projects ?? []) as $p): ?>
              <option value="<?= e((string)$p['id']) ?>"><?= e((string)($p['name'] ?? 'Project')) ?><?= !empty($p['pm_name']) ? (' • ' . e((string)$p['pm_name'])) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="title">Title</label>
          <input id="title" name="title" required maxlength="255" placeholder="e.g. Site visit / Demo / Install">
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="starts_at">Start</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required>
          </div>
          <div class="field mb-0">
            <label for="ends_at">End</label>
            <input id="ends_at" name="ends_at" type="datetime-local" required>
          </div>
        </div>
        <div class="field mt-10">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="approved" selected>approved</option>
            <option value="cancelled">cancelled</option>
          </select>
        </div>
        <button class="btn btn--gold" type="submit">Create</button>
      </form>
    </div>

    <div class="card card--stone">
      <div class="title">Conflicts</div>
      <div class="muted mt-10">Events are marked as conflict when overlapping for the same assigned PM.</div>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">Events</div>
    <?php if (($days ?? []) === []): ?>
      <div class="muted mt-10">No range computed.</div>
    <?php else: ?>
      <?php foreach ($days as $d): ?>
        <div class="thread-msg">
          <div class="fw-700"><?= e((string)$d) ?></div>
          <?php $list = $eventsByDay[$d] ?? []; ?>
          <?php if (($list ?? []) === []): ?>
            <div class="muted mt-6">No events.</div>
          <?php else: ?>
            <?php foreach ($list as $e): ?>
              <?php
                $st = (string)($e['status'] ?? '');
                $conf = (bool)($e['_conflict'] ?? false);
              ?>
              <div class="card mt-10">
                <div class="flex items-center justify-between gap-10">
                  <div class="fw-700">
                    <a href="<?= e(app_url($ctx, '/app/projects/' . (string)($e['project_id'] ?? ''))) ?>">
                      <?= e((string)($e['project_name'] ?? 'Project')) ?>
                    </a>
                    <?= !empty($e['pm_name']) ? (' • ' . e((string)$e['pm_name'])) : '' ?>
                  </div>
                  <div class="flex items-center gap-10">
                    <?php if ($conf): ?><span class="badge badge--bad">conflict</span><?php endif; ?>
                    <span class="badge <?= e($st === 'approved' ? 'badge--good' : 'badge--stone') ?>"><?= e($st) ?></span>
                  </div>
                </div>
                <div class="muted mt-6"><?= e((string)($e['title'] ?? '')) ?></div>
                <div class="muted mt-6"><?= e((string)($e['starts_at'] ?? '')) ?> → <?= e((string)($e['ends_at'] ?? '')) ?></div>

                <form method="post" action="<?= e(app_url($ctx, '/app/schedule/events/' . (string)($e['id'] ?? '') . '/update')) ?>" class="mt-10">
                  <?= csrf_field($ctx) ?>
                  <div class="field">
                    <label for="t_<?= e((string)$e['id']) ?>">Title</label>
                    <input id="t_<?= e((string)$e['id']) ?>" name="title" maxlength="255" value="<?= e((string)($e['title'] ?? '')) ?>" required>
                  </div>
                  <div class="row">
                    <div class="field mb-0">
                      <label for="sa_<?= e((string)$e['id']) ?>">Start</label>
                      <input id="sa_<?= e((string)$e['id']) ?>" name="starts_at" type="datetime-local" value="<?= e((string)($e['starts_at'] ?? '')) ?>" required>
                    </div>
                    <div class="field mb-0">
                      <label for="ea_<?= e((string)$e['id']) ?>">End</label>
                      <input id="ea_<?= e((string)$e['id']) ?>" name="ends_at" type="datetime-local" value="<?= e((string)($e['ends_at'] ?? '')) ?>" required>
                    </div>
                  </div>
                  <div class="row mt-10">
                    <div class="field mb-0">
                      <label for="st_<?= e((string)$e['id']) ?>">Status</label>
                      <select id="st_<?= e((string)$e['id']) ?>" name="status">
                        <option value="approved" <?= $st === 'approved' ? 'selected' : '' ?>>approved</option>
                        <option value="cancelled" <?= $st === 'cancelled' ? 'selected' : '' ?>>cancelled</option>
                      </select>
                    </div>
                    <div class="field mb-0">
                      <label>&nbsp;</label>
                      <div class="flex gap-10 items-center">
                        <button class="btn btn--gold" type="submit">Save</button>
                        <?php if ($st !== 'cancelled'): ?>
                          <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/schedule/events/' . (string)($e['id'] ?? '') . '/cancel')) ?>">Cancel</button>
                        <?php else: ?>
                          <span class="btn opacity-55 pe-none">Cancelled</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <h2 class="mt-18">Pending proposals</h2>
  <?php if (($proposals ?? []) === []): ?>
    <div class="card">
      <div class="muted">No pending proposals.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Start</th>
          <th>End</th>
          <th>By</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proposals as $p): ?>
          <tr>
            <td class="muted">#<?= e((string)$p['id']) ?></td>
            <td>
              <div class="fw-700">
                <a href="<?= e(app_url($ctx, '/app/projects/' . (string)$p['project_id'])) ?>">
                  <?= e((string)($p['project_name'] ?? 'Project')) ?>
                </a>
              </div>
              <div class="muted"><?= e((string)($p['address'] ?? '')) ?></div>
            </td>
            <td class="muted"><?= e((string)($p['starts_at'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($p['ends_at'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($p['created_by_name'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($p['created_at'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="section">
  <h2>Upcoming events</h2>
  <?php if (($upcomingEvents ?? []) === []): ?>
    <div class="card">
      <div class="muted">No upcoming events.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Title</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($upcomingEvents as $e): ?>
          <tr>
            <td class="muted">#<?= e((string)$e['id']) ?></td>
            <td class="muted">
              <a href="<?= e(app_url($ctx, '/app/projects/' . (string)$e['project_id'])) ?>">
                <?= e((string)($e['project_name'] ?? 'Project')) ?>
              </a>
            </td>
            <td class="fw-700"><?= e((string)($e['title'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($e['starts_at'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($e['ends_at'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($e['status'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
