<?php
$title = $title ?? 'Messages';
$u = current_user($ctx);
$role = (string)($u['role'] ?? '');
?>

<section class="section">
  <h2>Inbox</h2>

  <?php if (($threads ?? []) === []): ?>
    <div class="card">
      <div class="muted">No threads yet. Threads are created when you open a lead/project or send the first message.</div>
    </div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Thread</th>
          <th>Context</th>
          <th>Last message</th>
          <th>Updated</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($threads ?? []) as $t): ?>
          <?php
            $scopeType = (string)($t['scope_type'] ?? '');
            $scopeId = (string)($t['scope_id'] ?? '');
            $ctxLabel = $scopeType === 'project' ? 'Project' : 'Lead';
            $ctxLink = $scopeType === 'project'
              ? app_url($ctx, ($role === 'client' ? '/app/client/projects/' : '/app/projects/') . $scopeId)
              : app_url($ctx, ($role === 'client' ? '/app/client/leads/' : '/app/leads/') . $scopeId);

            $name = '';
            if ($scopeType === 'project') {
              $name = (string)($t['project_name'] ?? '');
            } else {
              $name = (string)($t['lead_name'] ?? '');
            }
            $last = (string)($t['last_body'] ?? '');
            $preview = $last === '' ? '-' : (mb_strlen($last) > 90 ? (mb_substr($last, 0, 90) . '...') : $last);
            $updatedAt = (string)($t['last_message_at'] ?? $t['created_at'] ?? '');
            $isUnread = (int)($t['is_unread'] ?? 0) === 1;
          ?>
          <tr>
            <td>
              <a href="<?= e(app_url($ctx, '/app/messages/' . (string)$t['id'])) ?>">#<?= e((string)$t['id']) ?></a>
              <?php if ($isUnread): ?>
                <span class="badge badge--gold ml-6">unread</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-700">
                <a href="<?= e($ctxLink) ?>"><?= e($ctxLabel) ?> #<?= e($scopeId) ?></a>
              </div>
              <?php if ($name !== ''): ?>
                <div class="muted"><?= e($name) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div><?= e($preview) ?></div>
              <?php if (!empty($t['last_sender_name'])): ?>
                <div class="muted mt-4">by <?= e((string)$t['last_sender_name']) ?></div>
              <?php endif; ?>
            </td>
            <td class="muted"><?= e($updatedAt) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php
    $path = '/app/messages';
    $page = isset($page) ? (int)$page : 1;
    $perPage = isset($perPage) ? (int)$perPage : 25;
    $hasMore = isset($hasMore) ? (bool)$hasMore : null;
    $query = [];
    require __DIR__ . '/partials/pagination.php';
  ?>
</section>
