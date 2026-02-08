<?php $title = $title ?? 'My leads'; ?>

<section class="section">
  <h2>My leads</h2>
  <div class="card mb-14">
    <div class="muted">
      This is your quote request history. Use Messages to communicate with your PM.
    </div>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Address</th>
        <th>Assigned PM</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($leads ?? []) as $lead): ?>
        <tr>
          <td><a href="<?= e(app_url($ctx, '/app/client/leads/' . (string)$lead['id'])) ?>">#<?= e((string)$lead['id']) ?></a></td>
          <td><span class="badge badge--stone"><?= e((string)$lead['status']) ?></span></td>
          <td><?= e((string)$lead['address']) ?></td>
          <td><?= e((string)($lead['pm_name'] ?? 'Unassigned')) ?></td>
          <td class="muted"><?= e((string)$lead['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (($leads ?? []) === []): ?>
    <div class="card mt-14">
      <div class="muted">No leads yet. You can submit a new request from the public website.</div>
    </div>
  <?php endif; ?>
</section>
