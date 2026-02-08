<?php
$title = $title ?? 'Lead';
$lead = $lead ?? [];
$attachments = $attachments ?? [];
$project = $project ?? null;
$threadId = $threadId ?? 0;
$allowedStatuses = $allowedStatuses ?? [];
$scope = [];
if (!empty($lead['scope_json']) && is_string($lead['scope_json'])) {
  $decoded = json_decode($lead['scope_json'], true);
  if (is_array($decoded)) $scope = $decoded;
}
?>

<section class="section">
  <h2>Lead #<?= e((string)$lead['id']) ?></h2>
  <div class="grid-3 grid-3--lead">
    <div class="card">
      <div class="title">Client</div>
      <div class="fw-700"><?= e((string)$lead['name']) ?></div>
      <div class="muted"><?= e((string)$lead['email']) ?></div>
      <div class="muted"><?= e((string)($lead['phone'] ?? '')) ?></div>
      <div class="title mt-12">Address</div>
      <div class="muted"><?= e((string)$lead['address']) ?></div>
    </div>

    <div class="card">
      <div class="title">Status</div>
      <div class="mt-6">
        <span class="badge badge--gold"><?= e((string)$lead['status']) ?></span>
      </div>
      <?php if (is_array($allowedStatuses) && $allowedStatuses !== []): ?>
        <form method="post" action="<?= e(app_url($ctx, '/app/leads/' . (string)$lead['id'] . '/status')) ?>" class="mt-10">
          <?= csrf_field($ctx) ?>
          <div class="field mb-0">
            <label for="status">Update status</label>
            <select id="status" name="status">
              <?php foreach ($allowedStatuses as $st): ?>
                <option value="<?= e((string)$st) ?>" <?= ((string)($lead['status'] ?? '') === (string)$st) ? 'selected' : '' ?>>
                  <?= e((string)$st) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn mt-10" type="submit">Save</button>
        </form>
      <?php endif; ?>
      <div class="title mt-12">Scope</div>
      <div class="muted mt-6">
        <?php if ($scope === []): ?>
          -
        <?php else: ?>
          <?= e(implode(', ', array_map('strval', $scope))) ?>
        <?php endif; ?>
      </div>
      <div class="title mt-12">Assigned PM</div>
      <div class="muted"><?= e((string)($lead['pm_name'] ?? 'Unassigned')) ?></div>

      <div class="title mt-12">Project</div>
      <?php if (is_array($project) && !empty($project['id'])): ?>
        <div class="muted">
          <a href="<?= e(app_url($ctx, '/app/projects/' . (string)$project['id'])) ?>">
            Project #<?= e((string)$project['id']) ?>
          </a>
          <span class="badge badge--stone ml-6"><?= e((string)($project['status'] ?? '')) ?></span>
        </div>
      <?php else: ?>
        <div class="muted">-</div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="title">Actions</div>

      <?php if (is_int($threadId) && $threadId > 0): ?>
        <div class="mt-10">
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/messages/' . (string)$threadId)) ?>">Open messages</a>
        </div>
      <?php endif; ?>

      <div class="mt-10">
        <a class="btn" href="<?= e(app_url($ctx, '/app/leads/' . (string)$lead['id'] . '/checklist')) ?>">Open checklist</a>
      </div>

      <?php if (!is_array($project) || empty($project['id'])): ?>
        <form method="post" action="<?= e(app_url($ctx, '/app/leads/' . (string)$lead['id'] . '/convert')) ?>" class="mt-10">
          <?= csrf_field($ctx) ?>
          <button class="btn btn--gold" type="submit">Convert to project</button>
        </form>
      <?php else: ?>
        <div class="notice mt-10">
          This lead is already converted to a project.
          <a class="ml-6" href="<?= e(app_url($ctx, '/app/projects/' . (string)$project['id'])) ?>">Open</a>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= e(app_url($ctx, '/app/leads/' . (string)$lead['id'] . '/assign')) ?>" class="mt-12">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="pm_user_id">Assign PM (admin)</label>
          <select id="pm_user_id" name="pm_user_id">
            <option value="0">Unassigned</option>
            <?php foreach (($pms ?? []) as $pm): ?>
              <option value="<?= e((string)$pm['id']) ?>" <?= ((int)($lead['assigned_pm_user_id'] ?? 0) === (int)$pm['id']) ? 'selected' : '' ?>>
                <?= e((string)$pm['name']) ?> (<?= e((string)$pm['email']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn" type="submit">Save</button>
      </form>
    </div>
  </div>
</section>

<section class="section">
  <h2>Attachments</h2>
  <div class="card">
    <form method="post" action="<?= e(app_url($ctx, '/app/leads/' . (string)$lead['id'] . '/uploads')) ?>" enctype="multipart/form-data" class="mb-12">
      <?= csrf_field($ctx) ?>
      <div class="row">
        <div class="field mb-0">
          <label for="file">Upload file (jpg/png/pdf)</label>
          <input id="file" name="file" type="file" required>
        </div>
        <div class="field mb-0">
          <label for="stage">Stage</label>
          <select id="stage" name="stage">
            <option value="doc" selected>doc</option>
            <option value="before">before</option>
            <option value="during">during</option>
            <option value="after">after</option>
          </select>
        </div>
        <div class="field mb-0">
          <label for="client_visible">Client visibility</label>
          <select id="client_visible" name="client_visible">
            <option value="0" selected>internal</option>
            <option value="1">client-visible</option>
          </select>
        </div>
      </div>
      <div class="mt-10">
        <button class="btn btn--gold" type="submit">Upload</button>
      </div>
    </form>

    <?php if (($attachments ?? []) === []): ?>
      <div class="muted">No attachments.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th>Stage</th>
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
              <td class="muted"><?= e((string)($a['stage'] ?? '')) ?></td>
              <td>
                <form method="post" action="<?= e(app_url($ctx, '/app/uploads/' . (string)$a['id'] . '/visibility')) ?>">
                  <?= csrf_field($ctx) ?>
                  <input type="hidden" name="back" value="<?= e('/app/leads/' . (string)$lead['id']) ?>">
                  <select name="client_visible">
                    <option value="0" <?= ((int)($a['client_visible'] ?? 0) === 0) ? 'selected' : '' ?>>internal</option>
                    <option value="1" <?= ((int)($a['client_visible'] ?? 0) === 1) ? 'selected' : '' ?>>client-visible</option>
                  </select>
                  <button class="btn ml-8" type="submit">Save</button>
                </form>
              </td>
              <td class="muted"><?= e((string)$a['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>
