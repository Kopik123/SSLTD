<?php
$title = $title ?? 'Approval';
$checklist = $checklist ?? [];
$items = $items ?? [];

$status = (string)($checklist['status'] ?? 'draft');
$isPending = $status === 'submitted';
$decisionNote = (string)($checklist['decision_note'] ?? '');

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
    <h2>Approval #<?= e((string)($checklist['id'] ?? '')) ?></h2>
    <a class="btn" href="<?= e(app_url($ctx, '/app/client/approvals')) ?>">Back</a>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="title">Lead</div>
      <div class="muted">Lead #<?= e((string)($checklist['lead_id'] ?? '')) ?></div>
      <div class="muted mt-6"><?= e((string)($checklist['address'] ?? '')) ?></div>
      <div class="muted mt-6">Status: <span class="badge badge--stone"><?= e($status) ?></span></div>
    </div>
    <div class="card card--stone">
      <div class="title">Estimate</div>
      <div class="muted mt-6">Total (informational):</div>
      <div class="fw-700 mt-6"><?= e(money($totalCents)) ?></div>
      <div class="muted mt-6">Submitted: <?= e((string)($checklist['submitted_at'] ?? '-')) ?></div>
      <div class="muted">Decided: <?= e((string)($checklist['decided_at'] ?? '-')) ?></div>
    </div>
  </div>
</section>

<section class="section">
  <h2>Items</h2>

  <?php if ($items === []): ?>
    <div class="card">
      <div class="muted">No items.</div>
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
          <th>Line</th>
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
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="section">
  <h2>Decision</h2>
  <div class="card">
    <?php if ($isPending): ?>
      <div class="muted mb-12">You can approve this estimate or reject it with a note.</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/client/approvals/' . (string)($checklist['id'] ?? '') . '/approve')) ?>" class="mb-12">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="note_a">Note (optional)</label>
          <textarea id="note_a" name="note" placeholder="Any notes for the PM..."></textarea>
        </div>
        <button class="btn btn--gold" type="submit">Approve</button>
      </form>

      <form method="post" action="<?= e(app_url($ctx, '/app/client/approvals/' . (string)($checklist['id'] ?? '') . '/reject')) ?>">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="note_r">Rejection note</label>
          <textarea id="note_r" name="note" required placeholder="Describe what should change..."></textarea>
        </div>
        <button class="btn btn--dark" type="submit">Reject</button>
      </form>
    <?php else: ?>
      <div class="muted">This approval is not pending.</div>
      <?php if ($decisionNote !== ''): ?>
        <div class="title mt-12">Decision note</div>
        <div class="muted ws-prewrap mt-6"><?= e($decisionNote) ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

