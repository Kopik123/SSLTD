<?php
$title = $title ?? 'Schedule proposal';
$p = $proposal ?? [];

$id = (int)($p['id'] ?? 0);
$st = (string)($p['status'] ?? '');

function st_badge(string $st): string {
  if ($st === 'submitted') return 'badge--gold';
  if ($st === 'approved') return 'badge--good';
  if ($st === 'rejected') return 'badge--bad';
  return 'badge--stone';
}
?>

<section class="section">
  <h2>Schedule proposal #<?= e((string)$id) ?></h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Project</div>
      <div class="fw-700 mt-6">
        <?= e((string)($p['project_name'] ?? '')) ?>
      </div>
      <div class="muted mt-6"><?= e((string)($p['address'] ?? '')) ?></div>

      <div class="title mt-14">Proposed window</div>
      <div class="muted mt-6">Start: <?= e((string)($p['starts_at'] ?? '')) ?></div>
      <div class="muted">End: <?= e((string)($p['ends_at'] ?? '')) ?></div>

      <div class="title mt-14">Status</div>
      <div class="mt-6">
        <span class="badge <?= e(st_badge($st)) ?>"><?= e($st) ?></span>
      </div>

      <?php if (!empty($p['note'])): ?>
        <div class="title mt-14">Team note</div>
        <div class="muted ws-prewrap mt-6"><?= e((string)$p['note']) ?></div>
      <?php endif; ?>

      <?php if (!empty($p['created_at'])): ?>
        <div class="muted mt-14">
          Submitted: <?= e((string)$p['created_at']) ?><?= !empty($p['created_by_name']) ? (' • ' . e((string)$p['created_by_name'])) : '' ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($p['decided_at'])): ?>
        <div class="muted mt-10">
          Decided: <?= e((string)$p['decided_at']) ?><?= !empty($p['decided_by_name']) ? (' • ' . e((string)$p['decided_by_name'])) : '' ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($p['decision_note'])): ?>
        <div class="title mt-14">Your note</div>
        <div class="muted ws-prewrap mt-6"><?= e((string)$p['decision_note']) ?></div>
      <?php endif; ?>
    </div>

    <div class="card card--stone">
      <div class="title">Decision</div>

      <?php if ($st !== 'submitted'): ?>
        <div class="muted mt-10">This proposal is no longer pending.</div>
        <div class="mt-14">
          <a class="btn" href="<?= e(app_url($ctx, '/app/client/approvals')) ?>">Back to approvals</a>
        </div>
      <?php else: ?>
        <form method="post" action="<?= e(app_url($ctx, '/app/client/approvals/schedule/' . (string)$id . '/approve')) ?>" class="mt-10">
          <?= csrf_field($ctx) ?>
          <div class="field">
            <label for="note">Note (optional)</label>
            <textarea id="note" name="note" placeholder="Any constraints, access details, or preferences..."></textarea>
          </div>

          <div class="row">
            <button class="btn btn--gold" type="submit">Approve</button>
            <button class="btn btn--stone" type="submit" formaction="<?= e(app_url($ctx, '/app/client/approvals/schedule/' . (string)$id . '/reject')) ?>">Reject</button>
          </div>

          <div class="muted mt-10">
            Approving will create an approved schedule event for this project.
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

