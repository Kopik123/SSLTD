<?php $title = $title ?? 'Register'; ?>
<h1>Create account</h1>

<form method="post" action="<?= e(app_url($ctx, '/register')) ?>">
  <?= csrf_field($ctx) ?>

  <div class="field">
    <label for="name">Name</label>
    <input id="name" name="name" required>
  </div>

  <div class="row">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required>
    </div>
    <div class="field">
      <label for="phone">Phone</label>
      <input id="phone" name="phone" placeholder="Optional">
    </div>
  </div>

  <div class="row">
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <div class="field">
      <label for="password_confirm">Confirm</label>
      <input id="password_confirm" name="password_confirm" type="password" required>
    </div>
  </div>

  <button class="btn btn--gold" type="submit">Create account</button>
</form>

<div class="muted mt-12">
  Already have an account? <a href="<?= e(app_url($ctx, '/login')) ?>">Login</a>
</div>
