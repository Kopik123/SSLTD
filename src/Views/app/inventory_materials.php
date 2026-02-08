<?php
$title = $title ?? 'Inventory: Materials';
$rows = $rows ?? [];
$page = $page ?? 1;
$perPage = $perPage ?? 25;
$total = $total ?? 0;
$filters = is_array($filters ?? null) ? $filters : [];

function money(int $cents): string {
  $neg = $cents < 0;
  $v = abs($cents) / 100;
  return ($neg ? '-' : '') . '$' . number_format($v, 2);
}
?>

<section class="section">
  <h2>Materials</h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Create material</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/inventory/materials')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="row">
          <div class="field mb-0">
            <label for="name">Name</label>
            <input id="name" name="name" required>
          </div>
          <div class="field mb-0">
            <label for="unit">Unit</label>
            <input id="unit" name="unit" placeholder="unit, sqm, hour...">
          </div>
        </div>
        <div class="row mt-10">
          <div class="field mb-0">
            <label for="unit_cost">Unit cost (optional)</label>
            <input id="unit_cost" name="unit_cost" inputmode="decimal" placeholder="0.00">
          </div>
          <div class="field mb-0">
            <label for="vendor">Vendor (optional)</label>
            <input id="vendor" name="vendor">
          </div>
        </div>
        <div class="row mt-10">
          <div class="field mb-0">
            <label for="sku">SKU (optional)</label>
            <input id="sku" name="sku">
          </div>
          <div class="field mb-0">
            <label for="notes">Notes (optional)</label>
            <input id="notes" name="notes">
          </div>
        </div>
        <button class="btn btn--gold mt-10" type="submit">Create</button>
      </form>
    </div>

    <div class="card card--stone">
      <div class="title">Filters</div>
      <form method="get" action="<?= e(app_url($ctx, '/app/inventory/materials')) ?>" class="mt-10">
        <div class="field">
          <label for="q">Search</label>
          <input id="q" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="name, vendor, sku">
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
        <a class="btn mt-10" href="<?= e(app_url($ctx, '/app/inventory/tools')) ?>">Go to tools</a>
      </form>
    </div>
  </div>

  <div class="card mt-14">
    <div class="title">Catalog</div>
    <?php if (($rows ?? []) === []): ?>
      <div class="muted mt-10">No materials yet.</div>
    <?php else: ?>
      <table class="table mt-12">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Unit</th>
            <th>Unit cost</th>
            <th>Vendor</th>
            <th>SKU</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $st = (string)($r['status'] ?? 'active'); ?>
            <tr>
              <td class="muted">#<?= e((string)$r['id']) ?></td>
              <td class="fw-700">
                <a href="<?= e(app_url($ctx, '/app/inventory/materials/' . (string)$r['id'])) ?>">
                  <?= e((string)($r['name'] ?? '')) ?>
                </a>
              </td>
              <td><span class="badge <?= e($st === 'active' ? 'badge--gold' : 'badge--stone') ?>"><?= e($st) ?></span></td>
              <td class="muted"><?= e((string)($r['unit'] ?? '')) ?></td>
              <td class="muted"><?= e(money((int)($r['unit_cost_cents'] ?? 0))) ?></td>
              <td class="muted"><?= e((string)($r['vendor'] ?? '')) ?></td>
              <td class="muted"><?= e((string)($r['sku'] ?? '')) ?></td>
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
    $path = '/app/inventory/materials';
    include __DIR__ . '/partials/pagination.php';
  ?>
</section>
