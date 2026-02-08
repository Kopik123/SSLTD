<?php $title = $title ?? 'Admin: Users'; ?>

<section class="section">
  <h2>Users</h2>

  <div class="card mb-14">
    <div class="title">Create user</div>
    <form method="post" action="<?= e(app_url($ctx, '/app/admin/users')) ?>" class="mt-10">
      <?= csrf_field($ctx) ?>

      <div class="row">
        <div class="field">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="client">client</option>
            <option value="employee">employee</option>
            <option value="subcontractor">subcontractor</option>
            <option value="subcontractor_worker">subcontractor_worker</option>
            <option value="pm">pm</option>
            <option value="admin">admin</option>
          </select>
        </div>
        <div class="field">
          <label for="phone">Phone</label>
          <input id="phone" name="phone" placeholder="Optional">
        </div>
      </div>

      <div class="row">
        <div class="field">
          <label for="name">Name</label>
          <input id="name" name="name" required>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required>
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="8" placeholder="Min 8 characters">
      </div>

      <button class="btn btn--gold" type="submit">Create</button>
    </form>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Role</th>
        <th>User</th>
        <th>Status</th>
        <th>Last login</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($users ?? []) as $u): ?>
        <tr>
          <td>#<?= e((string)$u['id']) ?></td>
          <td><span class="badge badge--stone"><?= e((string)$u['role']) ?></span></td>
          <td>
            <div class="fw-700"><?= e((string)$u['name']) ?></div>
            <div class="muted"><?= e((string)$u['email']) ?></div>
            <div class="muted"><?= e((string)($u['phone'] ?? '')) ?></div>
          </td>
          <td class="muted"><?= e((string)$u['status']) ?></td>
          <td class="muted"><?= e((string)($u['last_login_at'] ?? '')) ?></td>
          <td class="muted"><?= e((string)$u['created_at']) ?></td>
          <td>
            <form method="post" action="<?= e(app_url($ctx, '/app/admin/users/' . (string)$u['id'] . '/update')) ?>" class="mb-12">
              <?= csrf_field($ctx) ?>
              <div class="field mb-8">
                <label for="role_<?= e((string)$u['id']) ?>">Role</label>
                <select id="role_<?= e((string)$u['id']) ?>" name="role">
                  <?php foreach (['client', 'employee', 'subcontractor', 'subcontractor_worker', 'pm', 'admin'] as $r): ?>
                    <option value="<?= e($r) ?>" <?= ((string)$u['role'] === $r) ? 'selected' : '' ?>><?= e($r) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field mb-8">
                <label for="status_<?= e((string)$u['id']) ?>">Status</label>
                <select id="status_<?= e((string)$u['id']) ?>" name="status">
                  <?php foreach (['active', 'inactive'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= ((string)$u['status'] === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn" type="submit">Save</button>
            </form>

            <form method="post" action="<?= e(app_url($ctx, '/app/admin/users/' . (string)$u['id'] . '/password')) ?>">
              <?= csrf_field($ctx) ?>
              <div class="field mb-8">
                <label for="pw_<?= e((string)$u['id']) ?>">Set password</label>
                <input id="pw_<?= e((string)$u['id']) ?>" name="password" type="password" required minlength="8" placeholder="New password">
              </div>
              <button class="btn" type="submit">Update</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
