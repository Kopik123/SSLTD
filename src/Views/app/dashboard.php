<?php
$title = $title ?? 'Dashboard';
$kpis = $kpis ?? [];
?>

<section class="section">
  <h2>Office Command Center</h2>
  <div class="grid-3">
    <?php foreach ($kpis as $kpi): ?>
      <div class="hero-card">
        <div class="kpi-box">
          <div class="num"><?= e((string)($kpi['value'] ?? '0')) ?></div>
          <div class="label"><?= e((string)($kpi['label'] ?? '')) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <h2>Quick actions</h2>
  <div class="flex gap-10 flex-wrap">
    <a class="btn btn--gold" href="<?= e(app_url($ctx, '/app/leads')) ?>">Review leads</a>
    <a class="btn" href="<?= e(app_url($ctx, '/quote-request')) ?>">Create lead</a>
    <a class="btn btn--dark opacity-55 pe-none" href="<?= e(app_url($ctx, '/app/messages')) ?>">Messages (soon)</a>
  </div>
</section>
