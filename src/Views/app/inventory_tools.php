<?php
$title = $title ?? 'Inventory: Tools';
$rows = $rows ?? [];
$page = $page ?? 1;
$perPage = $perPage ?? 25;
$total = $total ?? 0;
$filters = is_array($filters ?? null) ? $filters : [];
?>

<section class="section">
  <h2>Tools</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Create tool</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/inventory/tools')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="name">Name</label>
          <input id="name" name="name" required>
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="serial">Serial (optional)</label>
            <input id="serial" name="serial">
          </div>
          <div class="field mb-0">
            <label for="location">Location (optional)</label>
            <input id="location" name="location" placeholder="storage, truck, on site...">
          </div>
        </div>
        <div class="field mt-10">
          <label for="notes">Notes (optional)</label>
          <input id="notes" name="notes">
        </div>
        <button class="btn btn--gold" type="submit">Create</button>
      </form>
    </div>

    <div class="card card--stone">
      <div class="title">Filters</div>
      <form method="get" action="<?= e(app_url($ctx, '/app/inventory/tools')) ?>" class="mt-10">
        <div class="field">
          <label for="q">Search</label>
          <input id="q" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="name, serial, location">
        </div>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="" <?= (($filters['status'] ?? '') === '') ? 'selected' : '' ?>>any</option>
            <option value="active" <?= (($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>active</option>
            <option value="inactive" <?= (($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>inactive</option>
          </select>
        </div>
        <button class="btn btn--gold" type="submit">Apply</button>
        <a class="btn mt-10" href="<?= e(app_url($ctx, '/app/inventory/materials')) ?>">Go to materials</a>
      </form>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">Catalog</div>
    <?php if (($rows ?? []) === []): ?>
      <div class="muted mt-10">No tools yet.</div>
    <?php else: ?>
      <table class="table mt-12">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Serial</th>
            <th>Location</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $st = (string)($r['status'] ?? 'active'); ?>
            <tr>
              <td class="muted">#<?= e((string)$r['id']) ?></td>
              <td class="fw-700">
                <a href="<?= e(app_url($ctx, '/app/inventory/tools/' . (string)$r['id'])) ?>">
                  <?= e((string)($r['name'] ?? '')) ?>
                </a>
              </td>
              <td><span class="badge <?= e($st === 'active' ? 'badge--gold' : 'badge--stone') ?>"><?= e($st) ?></span></td>
              <td class="muted"><?= e((string)($r['serial'] ?? '')) ?></td>
              <td class="muted"><?= e((string)($r['location'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?php
    $query = [
      'q' => (string)($filters['q'] ?? ''),
      'status' => (string)($filters['status'] ?? ''),
    ];
    $path = '/app/inventory/tools';
    include __DIR__ . '/partials/pagination.php';
  ?>
</section>

