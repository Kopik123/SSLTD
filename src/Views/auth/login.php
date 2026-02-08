<?php $title = $title ?? 'Login'; ?>
<h1>Login</h1>

<form method="post" action="<?= e(app_url($ctx, '/login')) ?>">
  <?= csrf_field($ctx) ?>
  <input type="hidden" name="next" value="<?= e((string)($next ?? '/app')) ?>">

  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required>
  </div>

  <div class="field">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>
  </div>

  <button class="btn btn--gold" type="submit">Sign in</button>
</form>

<div class="muted mt-12">
  <a href="<?= e(app_url($ctx, '/reset-password')) ?>">Forgot password?</a>
  &nbsp;|&nbsp;
  <a href="<?= e(app_url($ctx, '/register')) ?>">Create account</a>
</div>
