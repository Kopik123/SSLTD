<?php
$title = $title ?? 'Tool';
$t = $tool ?? [];
$id = (int)($t['id'] ?? 0);
?>

<section class="section">
  <h2>Tool #<?= e((string)$id) ?></h2>

  <div class="grid-2">
    <div class="card">
      <div class="title">Overview</div>
      <div class="muted mt-6">Status: <span class="badge <?= e(((string)($t['status'] ?? 'active') === 'active') ? 'badge--gold' : 'badge--stone') ?>"><?= e((string)($t['status'] ?? '')) ?></span></div>
      <?php if (!empty($t['serial'])): ?>
        <div class="muted mt-6">Serial: <?= e((string)$t['serial']) ?></div>
      <?php endif; ?>
      <?php if (!empty($t['location'])): ?>
        <div class="muted mt-6">Location: <?= e((string)$t['location']) ?></div>
      <?php endif; ?>
      <?php if (!empty($t['notes'])): ?>
        <div class="title mt-14">Notes</div>
        <div class="muted ws-prewrap mt-6"><?= e((string)$t['notes']) ?></div>
      <?php endif; ?>
      <div class="mt-14">
        <a class="btn" href="<?= e(app_url($ctx, '/app/inventory/tools')) ?>">Back to tools</a>
        <a class="btn ml-8" href="<?= e(app_url($ctx, '/app/inventory/materials')) ?>">Materials</a>
      </div>
    </div>

    <div class="card card--stone">
      <div class="title">Edit</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/inventory/tools/' . (string)$id . '/update')) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="active" <?= ((string)($t['status'] ?? '') === 'active') ? 'selected' : '' ?>>active</option>
            <option value="inactive" <?= ((string)($t['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>inactive</option>
          </select>
        </div>
        <div class="field">
          <label for="name">Name</label>
          <input id="name" name="name" value="<?= e((string)($t['name'] ?? '')) ?>" required>
        </div>
        <div class="row">
          <div class="field mb-0">
            <label for="serial">Serial</label>
            <input id="serial" name="serial" value="<?= e((string)($t['serial'] ?? '')) ?>">
          </div>
          <div class="field mb-0">
            <label for="location">Location</label>
            <input id="location" name="location" value="<?= e((string)($t['location'] ?? '')) ?>">
          </div>
        </div>
        <div class="field mt-10">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes"><?= e((string)($t['notes'] ?? '')) ?></textarea>
        </div>
        <button class="btn btn--gold" type="submit">Save</button>
      </form>
    </div>
  </div>
</section>

