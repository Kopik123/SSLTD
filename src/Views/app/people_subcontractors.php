<?php
$title = $title ?? 'People: Subcontractors';
$rows = $rows ?? [];
$page = $page ?? 1;
$perPage = $perPage ?? 25;
$total = $total ?? 0;
$filters = is_array($filters ?? null) ? $filters : [];
?>

<section class="section">
  <h2>Subcontractors</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Search</div>
      <form method="get" action="<?= e(app_url($ctx, '/app/people/subcontractors')) ?>" class="mt-10">
        <div class="field mb-0">
          <label for="q">Query</label>
          <input id="q" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="company, name, email">
        </div>
        <button class="btn btn--gold mt-10" type="submit">Search</button>
      </form>
    </div>
    <div class="card card--stone">
      <div class="title">Notes</div>
      <div class="muted mt-10">Approval flow: subcontractor adds workers (pending) then admin approves/rejects.</div>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">List</div>
    <?php if (($rows ?? []) === []): ?>
      <div class="muted mt-10">No subcontractors found.</div>
    <?php else: ?>
      <table class="table mt-12">
        <thead>
          <tr>
            <th>ID</th>
            <th>Company</th>
            <th>Account</th>
            <th>Status</th>
            <th>Workers</th>
            <th>Pending</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $st = (string)($r['status'] ?? 'active'); ?>
            <tr>
              <td class="muted">#<?= e((string)$r['id']) ?></td>
              <td class="fw-700">
                <a href="<?= e(app_url($ctx, '/app/people/subcontractors/' . (string)$r['id'])) ?>">
                  <?= e((string)($r['company_name'] ?? '')) ?>
                </a>
              </td>
              <td class="muted">
                <div class="fw-700"><?= e((string)($r['user_name'] ?? '')) ?></div>
                <div><?= e((string)($r['user_email'] ?? '')) ?></div>
              </td>
              <td><span class="badge <?= e($st === 'active' ? 'badge--gold' : 'badge--stone') ?>"><?= e($st) ?></span></td>
              <td class="muted"><?= e((string)($r['workers_count'] ?? '0')) ?></td>
              <td class="muted"><?= e((string)($r['pending_count'] ?? '0')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?php
    $query = ['q' => (string)($filters['q'] ?? '')];
    $path = '/app/people/subcontractors';
    include __DIR__ . '/partials/pagination.php';
  ?>
</section>

