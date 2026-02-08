<?php
$title = $title ?? 'Project';
$project = $project ?? [];
$threadId = $threadId ?? 0;
$attachments = $attachments ?? [];
?>

<section class="section">
  <h2>Project #<?= e((string)($project['id'] ?? '')) ?></h2>

  <div class="grid-3 grid-3--lead">
    <div class="card">
      <div class="title">Overview</div>
      <div class="fw-700"><?= e((string)($project['name'] ?? '')) ?></div>
      <div class="muted mt-6"><?= e((string)($project['address'] ?? '')) ?></div>
    </div>

    <div class="card">
      <div class="title">Status</div>
      <div class="mt-6">
        <span class="badge badge--gold"><?= e((string)($project['status'] ?? '')) ?></span>
      </div>
      <div class="title mt-12">Assigned PM</div>
      <div class="muted"><?= e((string)($project['pm_name'] ?? 'Unassigned')) ?></div>
    </div>

    <div class="card">
      <div class="title">Actions</div>
      <?php if (is_int($threadId) && $threadId > 0): ?>
        <div class="mt-10">
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/messages/' . (string)$threadId)) ?>">Open messages</a>
        </div>
      <?php endif; ?>
      <div class="notice mt-12">
        Client-facing project tabs (files, schedule, approvals) are planned next.
      </div>
    </div>
  </div>
</section>

<section class="section">
  <h2>Files</h2>
  <div class="card">
    <?php if (($attachments ?? []) === []): ?>
      <div class="muted">No files yet.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th>Stage</th>
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
              <td class="muted"><?= e((string)($a['stage'] ?? '')) ?></td>
              <td class="muted"><?= e((string)($a['created_at'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>
