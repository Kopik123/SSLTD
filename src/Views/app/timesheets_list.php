<?php $title = $title ?? 'Timesheets'; ?>

<section class="section">
  <h2>Timesheets</h2>

  <?php if (($rows ?? []) === []): ?>
    <div class="card">
      <div class="muted">No timesheets yet.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Project</th>
          <th>Started</th>
          <th>Stopped</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $t): ?>
          <?php $running = empty($t['stopped_at']); ?>
          <tr>
            <td>#<?= e((string)$t['id']) ?></td>
            <td>
              <div class="fw-700"><?= e((string)($t['user_name'] ?? '')) ?></div>
              <div class="muted"><?= e((string)($t['user_email'] ?? '')) ?></div>
            </td>
            <td class="muted"><?= e((string)($t['project_name'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($t['started_at'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($t['stopped_at'] ?? '')) ?></td>
            <td>
              <span class="badge <?= $running ? 'badge--gold' : 'badge--stone' ?>">
                <?= $running ? 'running' : 'stopped' ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
