<?php
$title = $title ?? 'Lead';
$lead = $lead ?? [];
$project = $project ?? null;
$threadId = $threadId ?? 0;
$attachments = $attachments ?? [];
$scope = [];
if (!empty($lead['scope_json']) && is_string($lead['scope_json'])) {
  $decoded = json_decode($lead['scope_json'], true);
  if (is_array($decoded)) $scope = $decoded;
}
?>

<section class="section">
  <h2>Lead #<?= e((string)($lead['id'] ?? '')) ?></h2>

  <div class="grid-3 grid-3--lead">
    <div class="card">
      <div class="title">Status</div>
      <div class="mt-6">
        <span class="badge badge--gold"><?= e((string)($lead['status'] ?? '')) ?></span>
      </div>

      <div class="title mt-12">Assigned PM</div>
      <div class="muted"><?= e((string)($lead['pm_name'] ?? 'Unassigned')) ?></div>

      <div class="title mt-12">Project</div>
      <?php if (is_array($project) && !empty($project['id'])): ?>
        <div class="muted">
          <a href="<?= e(app_url($ctx, '/app/client/projects/' . (string)$project['id'])) ?>">Project #<?= e((string)$project['id']) ?></a>
          <span class="badge badge--stone ml-6"><?= e((string)($project['status'] ?? '')) ?></span>
        </div>
      <?php else: ?>
        <div class="muted">-</div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="title">Address</div>
      <div class="muted"><?= e((string)($lead['address'] ?? '')) ?></div>

      <div class="title mt-12">Scope</div>
      <div class="muted mt-6">
        <?php if ($scope === []): ?>
          -
        <?php else: ?>
          <?= e(implode(', ', array_map('strval', $scope))) ?>
        <?php endif; ?>
      </div>

      <div class="title mt-12">Details</div>
      <div class="muted ws-prewrap"><?= e((string)($lead['description'] ?? '')) ?></div>
    </div>

    <div class="card">
      <div class="title">Actions</div>
      <?php if (is_int($threadId) && $threadId > 0): ?>
        <div class="mt-10">
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/messages/' . (string)$threadId)) ?>">Open messages</a>
        </div>
      <?php endif; ?>
      <div class="notice mt-12">
        For changes or questions, use Messages to keep everything in one place.
      </div>
    </div>
  </div>
</section>

<section class="section">
  <h2>Attachments</h2>
  <div class="card">
    <?php if (($attachments ?? []) === []): ?>
      <div class="muted">No attachments.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th>Visibility</th>
            <th>Uploaded</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($attachments as $a): ?>
            <tr>
              <td>
                <a href="<?= e(app_url($ctx, '/app/uploads/' . (string)$a['id'])) ?>">
                  <?= e((string)$a['original_name']) ?>
                </a>
              </td>
              <td class="muted"><?= e((string)$a['mime_type']) ?></td>
              <td class="muted"><?= e((string)$a['size_bytes']) ?> bytes</td>
              <td class="muted"><?= ((int)($a['client_visible'] ?? 0) === 1) ? 'client-visible' : 'internal' ?></td>
              <td class="muted"><?= e((string)($a['created_at'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>
