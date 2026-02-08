<?php
$title = $title ?? 'Approvals';
$rows = $rows ?? [];
$schedule = $schedule ?? [];
$changes = $changes ?? [];

function badgeClass(string $st): string {
  return $st === 'submitted' ? 'badge--gold' : 'badge--stone';
}
?>

<section class="section">
  <h2>Approvals</h2>

  <div class="card mb-14">
    <div class="muted">This page shows estimate and schedule approvals that require your decision.</div>
  </div>

  <h2>Estimates / Checklists</h2>
  <?php if (($rows ?? []) === []): ?>
    <div class="card">
      <div class="muted">No approvals yet.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Lead</th>
          <th>Status</th>
          <th>Items</th>
          <th>Submitted</th>
          <th>Decided</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php $st = (string)($r['status'] ?? 'draft'); ?>
          <tr>
            <td>
              <a href="<?= e(app_url($ctx, '/app/client/approvals/' . (string)$r['id'])) ?>">#<?= e((string)$r['id']) ?></a>
            </td>
            <td class="muted">
              Lead #<?= e((string)($r['lead_id'] ?? '')) ?>
              <div><?= e((string)($r['address'] ?? '')) ?></div>
            </td>
            <td>
              <span class="badge <?= e(badgeClass($st)) ?>"><?= e($st) ?></span>
            </td>
            <td class="muted"><?= e((string)($r['items_count'] ?? '0')) ?></td>
            <td class="muted"><?= e((string)($r['submitted_at'] ?? '-')) ?></td>
            <td class="muted"><?= e((string)($r['decided_at'] ?? '-')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="section">
  <h2>Schedule</h2>
  <?php if (($schedule ?? []) === []): ?>
    <div class="card">
      <div class="muted">No pending schedule proposals.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($schedule as $s): ?>
          <tr>
            <td>
              <a href="<?= e(app_url($ctx, '/app/client/approvals/schedule/' . (string)$s['id'])) ?>">#<?= e((string)$s['id']) ?></a>
            </td>
            <td class="muted">
              <div class="fw-700">Project #<?= e((string)($s['project_id'] ?? '')) ?><?= !empty($s['project_name']) ? (': ' . e((string)$s['project_name'])) : '' ?></div>
              <div><?= e((string)($s['address'] ?? '')) ?></div>
            </td>
            <td class="muted"><?= e((string)($s['starts_at'] ?? '')) ?></td>
            <td class="muted"><?= e((string)($s['ends_at'] ?? '')) ?></td>
            <td><span class="badge badge--gold"><?= e((string)($s['status'] ?? '')) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="section">
  <h2>Change requests</h2>
  <?php if (($changes ?? []) === []): ?>
    <div class="card">
      <div class="muted">No pending change requests.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Title</th>
          <th>Cost delta</th>
          <th>Schedule delta</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($changes as $c): ?>
          <tr>
            <td>
              <a href="<?= e(app_url($ctx, '/app/client/approvals/change/' . (string)$c['id'])) ?>">#<?= e((string)$c['id']) ?></a>
            </td>
            <td class="muted">
              <div class="fw-700">Project #<?= e((string)($c['project_id'] ?? '')) ?><?= !empty($c['project_name']) ? (': ' . e((string)$c['project_name'])) : '' ?></div>
              <div><?= e((string)($c['address'] ?? '')) ?></div>
            </td>
            <td class="fw-700"><?= e((string)($c['title'] ?? '')) ?></td>
            <td class="muted">$<?= e((string)number_format(((int)($c['cost_delta_cents'] ?? 0)) / 100, 2)) ?></td>
            <td class="muted"><?= e((string)($c['schedule_delta_days'] ?? 0)) ?> days</td>
            <td><span class="badge badge--gold"><?= e((string)($c['status'] ?? '')) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
