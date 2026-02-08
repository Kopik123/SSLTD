<?php
/** @var array<string, mixed> $project */
/** @var array<string, mixed> $checklist */
/** @var list<array<string, mixed>> $items */
$title = $title ?? 'Project Checklist';
$project = $project ?? [];
$checklist = $checklist ?? [];
$items = $items ?? [];
$pricingModes = $pricingModes ?? ['fixed', 'hours', 'sqm'];
$itemStatuses = $itemStatuses ?? ['todo', 'in_progress', 'done', 'blocked'];

$status = (string)($checklist['status'] ?? 'draft');
$isDraft = $status === 'draft';

function money(int $cents): string {
  $neg = $cents < 0;
  $abs = $neg ? -$cents : $cents;
  $d = intdiv($abs, 100);
  $r = $abs % 100;
  return ($neg ? '-' : '') . '$' . (string)$d . '.' . str_pad((string)$r, 2, '0', STR_PAD_LEFT);
}

$totalCents = 0;
foreach ($items as $it) {
  $pm = (string)($it['pricing_mode'] ?? 'fixed');
  $qty = (float)($it['qty'] ?? 0);
  $uc = (int)($it['unit_cost_cents'] ?? 0);
  $fc = (int)($it['fixed_cost_cents'] ?? 0);
  $line = ($pm === 'fixed') ? $fc : (int)round($qty * $uc);
  $totalCents += $line;
}
?>

<section class="section">
  <div class="flex justify-between items-center gap-12 mb-12">
    <h2>Checklist (Project #<?= e((string)($project['id'] ?? '')) ?>)</h2>
    <div class="flex gap-10 items-center flex-wrap">
      <a class="btn" href="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? ''))) ?>">Back to project</a>
      <span class="badge <?= $isDraft ? 'badge--stone' : 'badge--gold' ?>"><?= e($status) ?></span>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="title">Project</div>
      <div class="fw-700"><?= e((string)($project['name'] ?? '')) ?></div>
      <div class="muted mt-6"><?= e((string)($project['address'] ?? '')) ?></div>
      <div class="muted mt-6">PM: <?= e((string)($project['pm_name'] ?? 'Unassigned')) ?></div>
      <div class="muted mt-6">Status: <span class="badge badge--stone"><?= e((string)($project['status'] ?? '')) ?></span></div>
    </div>

    <div class="card card--stone">
      <div class="title">Summary</div>
      <div class="muted mt-6">Total estimate (informational):</div>
      <div class="fw-700 mt-6"><?= e(money($totalCents)) ?></div>

      <?php if ($isDraft): ?>
        <div class="muted mt-12">Draft mode: you can add/edit/delete items.</div>
      <?php else: ?>
        <div class="muted mt-12">Locked mode: pricing is frozen; you can update item status only.</div>
        <div class="muted mt-6">Use Change Requests to adjust scope/cost.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <h2>Items</h2>

  <?php if ($isDraft): ?>
    <div class="card mb-14">
      <div class="title">Add item</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/projects/' . (string)($project['id'] ?? '') . '/checklist/items')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="row">
          <div class="field mb-0">
            <label for="title">Title</label>
            <input id="title" name="title" required placeholder="e.g. Demolition + protection">
          </div>
          <div class="field mb-0">
            <label for="pricing_mode">Pricing mode</label>
            <select id="pricing_mode" name="pricing_mode">
              <?php foreach ($pricingModes as $pm): ?>
                <option value="<?= e((string)$pm) ?>"><?= e((string)$pm) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row mt-10">
          <div class="field mb-0">
            <label for="qty">Qty (hours/sqm)</label>
            <input id="qty" name="qty" inputmode="decimal" placeholder="0">
          </div>
          <div class="field mb-0">
            <label for="unit_cost">Unit cost ($)</label>
            <input id="unit_cost" name="unit_cost" inputmode="decimal" placeholder="0.00">
          </div>
        </div>

        <div class="row mt-10">
          <div class="field mb-0">
            <label for="fixed_cost">Fixed cost ($)</label>
            <input id="fixed_cost" name="fixed_cost" inputmode="decimal" placeholder="0.00">
          </div>
          <div class="field mb-0">
            <label for="status">Status</label>
            <select id="status" name="status">
              <?php foreach ($itemStatuses as $st): ?>
                <option value="<?= e((string)$st) ?>"><?= e((string)$st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button class="btn btn--gold mt-12" type="submit">Add item</button>
      </form>
      <div class="muted mt-6">For fixed pricing, set Fixed cost. For hours/sqm, set Qty and Unit cost.</div>
    </div>
  <?php endif; ?>

  <?php if ($items === []): ?>
    <div class="card">
      <div class="muted">No items yet.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Mode</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Fixed</th>
          <th>Line total</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <?php
            $pm = (string)($it['pricing_mode'] ?? 'fixed');
            $qty = (float)($it['qty'] ?? 0);
            $uc = (int)($it['unit_cost_cents'] ?? 0);
            $fc = (int)($it['fixed_cost_cents'] ?? 0);
            $line = ($pm === 'fixed') ? $fc : (int)round($qty * $uc);
          ?>
          <tr>
            <td class="fw-700"><?= e((string)($it['title'] ?? '')) ?></td>
            <td class="muted"><?= e($pm) ?></td>
            <td class="muted"><?= e((string)$qty) ?></td>
            <td class="muted"><?= e(money($uc)) ?></td>
            <td class="muted"><?= e(money($fc)) ?></td>
            <td class="muted"><?= e(money($line)) ?></td>
            <td><span class="badge badge--stone"><?= e((string)($it['status'] ?? '')) ?></span></td>
            <td>
              <?php if ($isDraft): ?>
                <form method="post" action="<?= e(app_url($ctx, '/app/project-checklist-items/' . (string)$it['id'] . '/update')) ?>" class="flex gap-10 items-center flex-wrap">
                  <?= csrf_field($ctx) ?>
                  <input class="w-180" name="title" value="<?= e((string)($it['title'] ?? '')) ?>" required>
                  <select name="pricing_mode">
                    <?php foreach ($pricingModes as $m): ?>
                      <option value="<?= e((string)$m) ?>" <?= $pm === (string)$m ? 'selected' : '' ?>><?= e((string)$m) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input class="w-110" name="qty" value="<?= e((string)$qty) ?>" inputmode="decimal">
                  <input class="w-110" name="unit_cost" value="<?= e((string)number_format($uc / 100, 2)) ?>" inputmode="decimal">
                  <input class="w-110" name="fixed_cost" value="<?= e((string)number_format($fc / 100, 2)) ?>" inputmode="decimal">
                  <select name="status">
                    <?php foreach ($itemStatuses as $st): ?>
                      <option value="<?= e((string)$st) ?>" <?= ((string)($it['status'] ?? '') === (string)$st) ? 'selected' : '' ?>><?= e((string)$st) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn" type="submit">Save</button>
                </form>
                <form method="post" action="<?= e(app_url($ctx, '/app/project-checklist-items/' . (string)$it['id'] . '/delete')) ?>" class="mt-6">
                  <?= csrf_field($ctx) ?>
                  <button class="btn btn--dark" type="submit">Delete</button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= e(app_url($ctx, '/app/project-checklist-items/' . (string)$it['id'] . '/update')) ?>" class="flex gap-10 items-center flex-wrap">
                  <?= csrf_field($ctx) ?>
                  <select name="status">
                    <?php foreach ($itemStatuses as $st): ?>
                      <option value="<?= e((string)$st) ?>" <?= ((string)($it['status'] ?? '') === (string)$st) ? 'selected' : '' ?>><?= e((string)$st) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn" type="submit">Save status</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

