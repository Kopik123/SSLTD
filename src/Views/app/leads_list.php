<?php $title = $title ?? 'Leads & Quotes'; ?>

<section class="section">
  <h2>Leads</h2>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Client</th>
        <th>Address</th>
        <th>Assigned PM</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($leads ?? []) as $lead): ?>
        <tr>
          <td><a href="<?= e(app_url($ctx, '/app/leads/' . (string)$lead['id'])) ?>">#<?= e((string)$lead['id']) ?></a></td>
          <td>
            <span class="badge badge--stone"><?= e((string)$lead['status']) ?></span>
          </td>
          <td>
            <div class="fw-700"><?= e((string)$lead['name']) ?></div>
            <div class="muted"><?= e((string)$lead['email']) ?></div>
          </td>
          <td><?= e((string)$lead['address']) ?></td>
          <td><?= e((string)($lead['pm_name'] ?? 'Unassigned')) ?></td>
          <td class="muted"><?= e((string)$lead['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php
    $path = '/app/leads';
    $page = isset($page) ? (int)$page : 1;
    $perPage = isset($perPage) ? (int)$perPage : 25;
    $total = isset($total) ? (int)$total : null;
    $query = [];
    require __DIR__ . '/partials/pagination.php';
  ?>
</section>
