<?php
$title = $title ?? 'Projects';
$filters = $filters ?? [];
$status = is_array($filters) ? (string)($filters['status'] ?? '') : '';
$assigned = is_array($filters) ? (string)($filters['assigned'] ?? '') : '';
?>

<section class="section">
  <h2>Projects</h2>

  <div class="card mb-14">
    <form method="get" action="<?= e(app_url($ctx, '/app/projects')) ?>">
      <div class="row">
        <div class="field mb-0">
          <label for="status">Status</label>
          <input id="status" name="status" value="<?= e($status) ?>" placeholder="e.g. project_created">
        </div>
        <div class="field mb-0">
          <label for="assigned">Assigned</label>
          <select id="assigned" name="assigned">
            <option value="" <?= $assigned === '' ? 'selected' : '' ?>>Any</option>
            <option value="me" <?= $assigned === 'me' ? 'selected' : '' ?>>Me (PM)</option>
          </select>
        </div>
      </div>
      <div class="mt-10">
        <button class="btn btn--gold" type="submit">Filter</button>
        <a class="btn" href="<?= e(app_url($ctx, '/app/projects')) ?>">Reset</a>
      </div>
    </form>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Name</th>
        <th>Address</th>
        <th>Client</th>
        <th>Assigned PM</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($projects ?? []) as $p): ?>
        <tr>
          <td><a href="<?= e(app_url($ctx, '/app/projects/' . (string)$p['id'])) ?>">#<?= e((string)$p['id']) ?></a></td>
          <td><span class="badge badge--stone"><?= e((string)$p['status']) ?></span></td>
          <td class="fw-700"><?= e((string)$p['name']) ?></td>
          <td><?= e((string)$p['address']) ?></td>
          <td class="muted">
            <?= e((string)($p['client_name'] ?? '')) ?>
            <?php if (!empty($p['client_email'])): ?>
              <div><?= e((string)$p['client_email']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= e((string)($p['pm_name'] ?? 'Unassigned')) ?></td>
          <td class="muted"><?= e((string)$p['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (($projects ?? []) === []): ?>
    <div class="card mt-14">
      <div class="muted">No projects yet.</div>
    </div>
  <?php endif; ?>

  <?php
    $path = '/app/projects';
    $page = isset($page) ? (int)$page : 1;
    $perPage = isset($perPage) ? (int)$perPage : 25;
    $total = isset($total) ? (int)$total : null;
    $query = [];
    if ($status !== '') $query['status'] = $status;
    if ($assigned !== '') $query['assigned'] = $assigned;
    require __DIR__ . '/partials/pagination.php';
  ?>
</section>
