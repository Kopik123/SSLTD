<?php
$title = $title ?? 'Admin: Audit Log';
$filters = $filters ?? [];
$action = is_array($filters) ? (string)($filters['action'] ?? '') : '';
$entityType = is_array($filters) ? (string)($filters['entity_type'] ?? '') : '';
?>

<section class="section">
  <h2>Audit log</h2>

  <div class="card mb-14">
    <form method="get" action="<?= e(app_url($ctx, '/app/admin/audit')) ?>">
      <div class="row">
        <div class="field mb-0">
          <label for="action">Action</label>
          <input id="action" name="action" value="<?= e($action) ?>" placeholder="e.g. user_created">
        </div>
        <div class="field mb-0">
          <label for="entity_type">Entity type</label>
          <input id="entity_type" name="entity_type" value="<?= e($entityType) ?>" placeholder="e.g. user">
        </div>
      </div>
      <div class="mt-10">
        <button class="btn btn--gold" type="submit">Filter</button>
        <a class="btn" href="<?= e(app_url($ctx, '/app/admin/audit')) ?>">Reset</a>
      </div>
    </form>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>When</th>
        <th>Actor</th>
        <th>Action</th>
        <th>Entity</th>
        <th>Meta</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <?php
          $meta = (string)($r['meta_json'] ?? '');
          $preview = $meta;
          if (mb_strlen($preview) > 140) {
            $preview = mb_substr($preview, 0, 140) . '...';
          }
        ?>
        <tr>
          <td class="muted"><?= e((string)($r['created_at'] ?? '')) ?></td>
          <td>
            <div class="fw-700"><?= e((string)($r['actor_name'] ?? 'System')) ?></div>
            <div class="muted"><?= e((string)($r['actor_email'] ?? '')) ?></div>
          </td>
          <td><span class="badge badge--stone"><?= e((string)($r['action'] ?? '')) ?></span></td>
          <td class="muted"><?= e((string)($r['entity_type'] ?? '')) ?> #<?= e((string)($r['entity_id'] ?? '')) ?></td>
          <td class="muted"><?= e($preview) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (($rows ?? []) === []): ?>
    <div class="card mt-14">
      <div class="muted">No audit entries yet.</div>
    </div>
  <?php endif; ?>
</section>
