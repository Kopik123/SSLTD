<?php $title = $title ?? 'Reset password'; ?>
<h1>Reset password</h1>

<?php if (!empty($sent)): ?>
  <div class="notice">
    If the email exists, a reset link has been generated.
    <?php if (!empty($token)): ?>
      <div class="muted mt-6">
        Dev shortcut:
        <a href="<?= e(app_url($ctx, '/reset-password/' . (string)$token)) ?>"><?= e(app_url($ctx, '/reset-password/' . (string)$token)) ?></a>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= e(app_url($ctx, '/reset-password')) ?>">
  <?= csrf_field($ctx) ?>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required>
  </div>
  <button class="btn btn--gold" type="submit">Send reset link</button>
</form>

<div class="muted mt-12">
  <a href="<?= e(app_url($ctx, '/login')) ?>">Back to login</a>
</div>
