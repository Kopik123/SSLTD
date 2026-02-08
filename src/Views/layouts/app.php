<?php
/** @var \App\Context $ctx */
$u = current_user($ctx);
$title = $title ?? 'Portal';
$notice = flash($ctx, 'notice');
$error = flash($ctx, 'error');
$role = (string)($u['role'] ?? '');
$showLogOverlay = $ctx->config()->isDebug();
$logEndpoint = app_url($ctx, '/app/dev/logs');
$devCsrf = $ctx->config()->isDebug() ? $ctx->csrf()->token() : '';
$basePath = $ctx->basePath();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e((string)$title) ?> | S&amp;S LTD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url($ctx, '/assets/css/app.css')) ?>">
  </head>
  <body data-log-overlay="<?= $showLogOverlay ? '1' : '0' ?>" data-log-endpoint="<?= e($logEndpoint) ?>" data-dev-csrf="<?= e($devCsrf) ?>" data-base-path="<?= e($basePath) ?>">
    <div class="app-shell">
      <aside class="sidebar">
        <a class="brand no-underline" href="<?= e(app_url($ctx, '/app')) ?>">
          <img src="<?= e(app_url($ctx, '/assets/img/logo.png')) ?>" alt="S&S LTD logo">
          <div>
            <div class="name">S&amp;S LTD</div>
            <div class="muted fs-12 mt-2">Office portal</div>
          </div>
        </a>

        <div class="nav-title">Main</div>
        <a href="<?= e(app_url($ctx, '/app')) ?>">Dashboard</a>
        <?php if ($role === 'client'): ?>
          <a href="<?= e(app_url($ctx, '/app/client/leads')) ?>">My leads</a>
          <a href="<?= e(app_url($ctx, '/app/client/projects')) ?>">My projects</a>
          <a href="<?= e(app_url($ctx, '/app/client/approvals')) ?>">Approvals</a>
          <a href="<?= e(app_url($ctx, '/app/messages')) ?>">Messages</a>
        <?php else: ?>
          <a href="<?= e(app_url($ctx, '/app/leads')) ?>">Leads &amp; Quotes</a>
          <a href="<?= e(app_url($ctx, '/app/projects')) ?>">Projects</a>
          <a href="<?= e(app_url($ctx, '/app/timesheets')) ?>">Timesheets</a>
          <a href="<?= e(app_url($ctx, '/app/schedule')) ?>">Schedule</a>
          <a href="<?= e(app_url($ctx, '/app/inventory')) ?>">Inventory</a>
          <a href="<?= e(app_url($ctx, '/app/people')) ?>">People</a>
          <a href="<?= e(app_url($ctx, '/app/messages')) ?>">Messages</a>
          <a class="opacity-55 pe-none" href="<?= e(app_url($ctx, '/app/reports')) ?>">Reports (soon)</a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
          <div class="nav-title">Admin</div>
          <a href="<?= e(app_url($ctx, '/app/admin/users')) ?>">Users</a>
          <a href="<?= e(app_url($ctx, '/app/admin/audit')) ?>">Audit log</a>
        <?php endif; ?>

        <div class="nav-title">Account</div>
        <div class="card card--stone mt-10">
          <div class="title"><?= e($u['name'] ?? 'User') ?></div>
          <div class="muted"><?= e($u['email'] ?? '') ?></div>
          <div class="muted mt-6">Role: <?= e($u['role'] ?? '') ?></div>
          <form method="post" action="<?= e(app_url($ctx, '/logout')) ?>" class="mt-10">
            <?= csrf_field($ctx) ?>
            <button class="btn btn--gold" type="submit">Sign out</button>
          </form>
        </div>
      </aside>

      <div>
        <header class="topbar-app">
          <div class="container inner">
            <h1 class="page-title"><?= e((string)$title) ?></h1>
            <a class="btn" href="<?= e(app_url($ctx, '/quote-request')) ?>">New quote</a>
          </div>
        </header>

        <main class="app-main">
          <div class="container">
            <?php if (is_string($notice) && $notice !== ''): ?>
              <div class="notice"><?= e($notice) ?></div>
            <?php endif; ?>
            <?php if (is_string($error) && $error !== ''): ?>
              <div class="notice error"><?= e($error) ?></div>
            <?php endif; ?>
            <?= $content ?>
          </div>
        </main>
      </div>
    </div>

    <script src="<?= e(app_url($ctx, '/assets/js/app.js')) ?>" defer></script>
  </body>
</html>
