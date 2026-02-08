<?php
$title = $title ?? 'Subcontractor';
$sub = $sub ?? [];
$workers = $workers ?? [];
$role = (string)(current_user($ctx)['role'] ?? '');

function wbadge(string $st): string {
  if ($st === 'pending') return 'badge--gold';
  if ($st === 'active') return 'badge--good';
  if ($st === 'inactive') return 'badge--bad';
  return 'badge--stone';
}
?>

<section class="section">
  <h2>Subcontractor #<?= e((string)($sub['id'] ?? '')) ?></h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Company</div>
      <div class="fw-700 mt-6"><?= e((string)($sub['company_name'] ?? '')) ?></div>
      <div class="muted mt-6">Status: <span class="badge <?= e(((string)($sub['status'] ?? 'active') === 'active') ? 'badge--gold' : 'badge--stone') ?>"><?= e((string)($sub['status'] ?? '')) ?></span></div>
      <div class="title mt-14">Account</div>
      <div class="muted mt-6"><?= e((string)($sub['user_name'] ?? '')) ?></div>
      <div class="muted"><?= e((string)($sub['user_email'] ?? '')) ?></div>
      <div class="mt-14">
        <a class="btn" href="<?= e(app_url($ctx, '/app/people/subcontractors')) ?>">Back</a>
      </div>
    </div>

    <div class="card card--stone">
      <div class="title">Workers</div>
      <div class="muted mt-10">Pending workers require admin approval.</div>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">Worker list</div>
    <?php if (($workers ?? []) === []): ?>
      <div class="muted mt-10">No workers yet.</div>
    <?php else: ?>
      <table class="table mt-12">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Status</th>
            <th>Created</th>
            <th>Admin</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($workers as $w): ?>
            <?php $st = (string)($w['status'] ?? 'active'); ?>
            <tr>
              <td class="muted">#<?= e((string)$w['id']) ?></td>
              <td class="muted">
                <div class="fw-700"><?= e((string)($w['user_name'] ?? '')) ?></div>
                <div><?= e((string)($w['user_email'] ?? '')) ?></div>
              </td>
              <td><span class="badge <?= e(wbadge($st)) ?>"><?= e($st) ?></span></td>
              <td class="muted"><?= e((string)($w['created_at'] ?? '')) ?></td>
              <td>
                <?php if ($role === 'admin' && $st === 'pending'): ?>
                  <form method="post" action="<?= e(app_url($ctx, '/app/people/subcontractor-workers/' . (string)$w['id'] . '/approve')) ?>" class="flex gap-10 items-center">
                    <?= csrf_field($ctx) ?>
                    <button class="btn btn--gold" type="submit">Approve</button>
                    <button class="btn" type="submit" formaction="<?= e(app_url($ctx, '/app/people/subcontractor-workers/' . (string)$w['id'] . '/reject')) ?>">Reject</button>
                  </form>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

