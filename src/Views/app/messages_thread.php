<?php
$title = $title ?? 'Thread';
$thread = $thread ?? [];
$scope = $scope ?? null;
$messages = $messages ?? [];
$u = current_user($ctx);
$role = (string)($u['role'] ?? '');
$scopeType = (string)($thread['scope_type'] ?? '');
$scopeId = (string)($thread['scope_id'] ?? '');

$ctxLabel = $scopeType === 'project' ? 'Project' : 'Lead';
$ctxLink = $scopeType === 'project'
  ? app_url($ctx, ($role === 'client' ? '/app/client/projects/' : '/app/projects/') . $scopeId)
  : app_url($ctx, ($role === 'client' ? '/app/client/leads/' : '/app/leads/') . $scopeId);
?>

<section class="section">
  <div class="flex justify-between items-center gap-12 mb-12">
    <h2>Thread #<?= e((string)($thread['id'] ?? '')) ?></h2>
    <a class="btn" href="<?= e(app_url($ctx, '/app/messages')) ?>">Back to inbox</a>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="title">Context</div>
      <div class="fw-700">
        <a href="<?= e($ctxLink) ?>"><?= e($ctxLabel) ?> #<?= e($scopeId) ?></a>
      </div>

      <?php if (is_array($scope)): ?>
        <?php if (!empty($scope['name'])): ?>
          <div class="muted mt-6"><?= e((string)$scope['name']) ?></div>
        <?php endif; ?>
        <?php if (!empty($scope['email'])): ?>
          <div class="muted"><?= e((string)$scope['email']) ?></div>
        <?php endif; ?>
        <?php if (!empty($scope['address'])): ?>
          <div class="muted"><?= e((string)$scope['address']) ?></div>
        <?php endif; ?>
        <?php if (!empty($scope['status'])): ?>
          <div class="mt-10">
            <span class="badge badge--stone"><?= e((string)$scope['status']) ?></span>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="muted mt-6">Context record is missing (deleted or unavailable).</div>
      <?php endif; ?>
    </div>

    <div class="card card--stone">
      <div class="title">New message</div>
      <form method="post" action="<?= e(app_url($ctx, '/app/messages/' . (string)($thread['id'] ?? ''))) ?>" class="mt-10">
        <?= csrf_field($ctx) ?>
        <div class="field">
          <label for="body">Message</label>
          <textarea id="body" name="body" required placeholder="Write an update..."></textarea>
        </div>
        <button class="btn btn--gold" type="submit">Send</button>
      </form>
    </div>
  </div>
</section>

<section class="section">
  <h2>Messages</h2>
  <div class="card">
    <?php if (($messages ?? []) === []): ?>
      <div class="muted">No messages yet.</div>
    <?php else: ?>
      <?php foreach ($messages as $m): ?>
        <?php
          $sender = (string)($m['sender_name'] ?? 'System');
          $role = (string)($m['sender_role'] ?? '');
          $created = (string)($m['created_at'] ?? '');
          $body = (string)($m['body'] ?? '');
        ?>
        <div class="thread-msg">
          <div class="flex justify-between gap-12 items-baseline">
            <div class="fw-700">
              <?= e($sender) ?>
              <?php if ($role !== ''): ?>
                <span class="muted fw-600">(<?= e($role) ?>)</span>
              <?php endif; ?>
            </div>
            <div class="muted fs-12"><?= e($created) ?></div>
          </div>
          <div class="mt-6 ws-prewrap"><?= e($body) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
