<?php $title = $title ?? 'Reset password'; ?>
<h1>Set a new password</h1>

<?php if (!empty($invalid)): ?>
  <div class="notice error">Reset link is invalid or expired.</div>
<?php endif; ?>

<form method="post" action="<?= e(app_url($ctx, '/reset-password/' . (string)($token ?? ''))) ?>">
  <?= csrf_field($ctx) ?>
  <div class="row">
    <div class="field">
      <label for="password">New password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <div class="field">
      <label for="password_confirm">Confirm</label>
      <input id="password_confirm" name="password_confirm" type="password" required>
    </div>
  </div>
  <button class="btn btn--gold" type="submit">Update password</button>
</form>

<div class="muted mt-12">
  <a href="<?= e(app_url($ctx, '/login')) ?>">Back to login</a>
</div>
