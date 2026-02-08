<?php
$title = $title ?? 'Change request';
$cr = $cr ?? [];
$id = (int)($cr['id'] ?? 0);
$st = (string)($cr['status'] ?? '');

function st_badge(string $st): string {
  if ($st === 'submitted') return 'badge--gold';
  if ($st === 'approved') return 'badge--good';
  if ($st === 'rejected') return 'badge--bad';
  return 'badge--stone';
}
?>

<section class="section">
  <h2>Change request #<?= e((string)$id) ?></h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Project</div>
      <div class="fw-700 mt-6"><?= e((string)($cr['project_name'] ?? '')) ?></div>
      <div class="muted mt-6"><?= e((string)($cr['address'] ?? '')) ?></div>

      <div class="title mt-14">Request</div>
      <div class="fw-700 mt-6"><?= e((string)($cr['title'] ?? '')) ?></div>
      <?php if (!empty($cr['body'])): ?>
        <div class="muted ws-prewrap mt-6"><?= e((string)$cr['body']) ?></div>
      <?php endif; ?>

      <div class="title mt-14">Impact</div>
      <div class="muted mt-6">Cost delta: $<?= e((string)number_format(((int)($cr['cost_delta_cents'] ?? 0)) / 100, 2)) ?></div>
      <div class="muted">Schedule delta: <?= e((string)($cr['schedule_delta_days'] ?? 0)) ?> days</div>

      <div class="title mt-14">Status</div>
      <div class="mt-6"><span class="badge <?= e(st_badge($st)) ?>"><?= e($st) ?></span></div>

      <?php if (!empty($cr['created_at'])): ?>
        <div class="muted mt-14">
          Submitted: <?= e((string)$cr['created_at']) ?><?= !empty($cr['created_by_name']) ? (' • ' . e((string)$cr['created_by_name'])) : '' ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($cr['decided_at'])): ?>
        <div class="muted mt-10">
          Decided: <?= e((string)$cr['decided_at']) ?><?= !empty($cr['decided_by_name']) ? (' • ' . e((string)$cr['decided_by_name'])) : '' ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($cr['decision_note'])): ?>
        <div class="title mt-14">Your note</div>
        <div class="muted ws-prewrap mt-6"><?= e((string)$cr['decision_note']) ?></div>
      <?php endif; ?>
    </div>

    <div class="card card--stone">
      <div class="title">Decision</div>

      <?php if ($st !== 'submitted'): ?>
        <div class="muted mt-10">This change request is no longer pending.</div>
        <div class="mt-14">
          <a class="btn" href="<?= e(app_url($ctx, '/app/client/approvals')) ?>">Back to approvals</a>
        </div>
      <?php else: ?>
        <form method="post" action="<?= e(app_url($ctx, '/app/client/approvals/change/' . (string)$id . '/approve')) ?>" class="mt-10">
          <?= csrf_field($ctx) ?>
          <div class="field">
            <label for="note">Note (optional)</label>
            <textarea id="note" name="note" placeholder="Any questions, constraints, or preferences..."></textarea>
          </div>
          <div class="row">
            <button class="btn btn--gold" type="submit">Approve</button>
            <button class="btn btn--stone" type="submit" formaction="<?= e(app_url($ctx, '/app/client/approvals/change/' . (string)$id . '/reject')) ?>">Reject</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

