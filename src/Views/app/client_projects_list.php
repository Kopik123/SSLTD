<?php $title = $title ?? 'My projects'; ?>

<section class="section">
  <h2>My projects</h2>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Name</th>
        <th>Address</th>
        <th>Assigned PM</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($projects ?? []) as $p): ?>
        <tr>
          <td><a href="<?= e(app_url($ctx, '/app/client/projects/' . (string)$p['id'])) ?>">#<?= e((string)$p['id']) ?></a></td>
          <td><span class="badge badge--stone"><?= e((string)$p['status']) ?></span></td>
          <td class="fw-700"><?= e((string)$p['name']) ?></td>
          <td><?= e((string)$p['address']) ?></td>
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
</section>
