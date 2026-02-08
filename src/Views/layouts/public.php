<?php
/** @var \App\Context $ctx */
$title = $title ?? 'S&S LTD';
$notice = flash($ctx, 'notice');
$error = flash($ctx, 'error');
$u = current_user($ctx);
$role = is_array($u) ? (string)($u['role'] ?? '') : '';
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
    <title><?= e((string)$title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url($ctx, '/assets/css/app.css')) ?>">
  </head>
  <body data-log-overlay="<?= $showLogOverlay ? '1' : '0' ?>" data-log-endpoint="<?= e($logEndpoint) ?>" data-dev-csrf="<?= e($devCsrf) ?>" data-base-path="<?= e($basePath) ?>">
    <header class="topnav">
      <div class="container topnav-inner">
        <a class="brand" href="<?= e(app_url($ctx, '/')) ?>">
          <img src="<?= e(app_url($ctx, '/assets/img/logo.png')) ?>" alt="S&S LTD logo">
          <div class="name">S&amp;S LTD</div>
        </a>
        <nav class="navlinks" aria-label="Primary">
          <a href="<?= e(app_url($ctx, '/about')) ?>">About</a>
          <a href="<?= e(app_url($ctx, '/services')) ?>">Services</a>
          <a href="<?= e(app_url($ctx, '/gallery')) ?>">Gallery</a>
          <a href="<?= e(app_url($ctx, '/contact')) ?>">Contact</a>
          <a class="btn btn--gold" href="<?= e(app_url($ctx, '/quote-request')) ?>">Request a quote</a>
          <?php if ($u === null): ?>
            <a class="btn" href="<?= e(app_url($ctx, '/login')) ?>">Login</a>
          <?php else: ?>
            <a class="btn btn--dark" href="<?= e(app_url($ctx, '/app')) ?>">Portal</a>
          <?php endif; ?>
        </nav>
      </div>
    </header>

    <main class="container">
      <?php if (is_string($notice) && $notice !== ''): ?>
        <div class="notice"><?= e($notice) ?></div>
      <?php endif; ?>
      <?php if (is_string($error) && $error !== ''): ?>
        <div class="notice error"><?= e($error) ?></div>
      <?php endif; ?>

      <?= $content ?>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-row">
          <div class="muted">S&amp;S LTD. Luxury-grade finishes. Structured process. Clear communication.</div>
          <div class="footer-links">
            <a href="<?= e(app_url($ctx, '/privacy')) ?>">Privacy</a>
            <a href="<?= e(app_url($ctx, '/terms')) ?>">Terms</a>
          </div>
        </div>
      </div>
    </footer>

    <script src="<?= e(app_url($ctx, '/assets/js/app.js')) ?>" defer></script>
  </body>
</html>
