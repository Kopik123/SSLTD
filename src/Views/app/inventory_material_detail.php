<?php
$title = $title ?? 'Material';
$m = $material ?? [];
$id = (int)($m['id'] ?? 0);

function money(int $cents): string {
  $neg = $cents < 0;
  $v = abs($cents) / 100;
  return ($neg ? '-' : '') . number_format($v, 2);
}
?>

<section class="section">
  <h2>Material #<?= e((string)$id) ?></h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Overview</div>
      <div class="muted mt-6">Status: <span class="badge <?= e(((string)($m['status'] ?? 'active') === 'active') ? 'badge--gold' : 'badge--stone') ?>"><?= e((string)($m['status'] ?? '')) ?></span></div>
      <div class="muted mt-6">Unit: <?= e((string)($m['unit'] ?? '')) ?></div>
      <div class="muted mt-6">Unit cost: $<?= e(money((int)($m['unit_cost_cents'] ?? 0))) ?></div>
      <?php if (!empty($m['vendor'])): ?>
        <div class="muted mt-6">Vendor: <?= e((string)$m['vendor']) ?></div>
      <?php endif; ?>
      <?php if (!empty($m['sku'])): ?>
        <div class="muted mt-6">SKU: <?= e((string)$m['sku']) ?></div>
      <?php endif; ?>
      <?php if (!empty($m['notes'])): ?>
        <div class="title mt-14">Notes</div>
        <div class="muted ws-prewrap mt-6"><?= e((string)$m['notes']) ?></div>
      <?php endif; ?>
      <div class="mt-14">
        <a class="btn" href="<?= e(app_url($ctx, '/app/inventory/materials')) ?>">Back to materials</a>
        <a class="btn ml-8" href="<?= e(app_url($ctx, '/app/inventory/tools')) ?>">Tools</a>
      </div>
    </div>

    <div class="card card--stone">
      <div class="title">Edit</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/inventory/materials/' . (string)$id . '/update')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="active" <?= ((string)($m['status'] ?? '') === 'active') ? 'selected' : '' ?>>active</option>
            <option value="inactive" <?= ((string)($m['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>inactive</option>
          </select>
        </div>
        <div class="field">
          <label for="name">Name</label>
          <input id="name" name="name" value="<?= e((string)($m['name'] ?? '')) ?>" required>
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="unit">Unit</label>
            <input id="unit" name="unit" value="<?= e((string)($m['unit'] ?? 'unit')) ?>">
          </div>
          <div class="field mb-0">
            <label for="unit_cost">Unit cost</label>
            <input id="unit_cost" name="unit_cost" inputmode="decimal" value="<?= e(money((int)($m['unit_cost_cents'] ?? 0))) ?>">
          </div>
        </div>
        <div class="row mt-10">
          <div class="field mb-0">
            <label for="vendor">Vendor</label>
            <input id="vendor" name="vendor" value="<?= e((string)($m['vendor'] ?? '')) ?>">
          </div>
          <div class="field mb-0">
            <label for="sku">SKU</label>
            <input id="sku" name="sku" value="<?= e((string)($m['sku'] ?? '')) ?>">
          </div>
        </div>
        <div class="field mt-10">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes"><?= e((string)($m['notes'] ?? '')) ?></textarea>
        </div>
        <button class="btn btn--gold" type="submit">Save</button>
      </form>
    </div>
  </div>
</section>

