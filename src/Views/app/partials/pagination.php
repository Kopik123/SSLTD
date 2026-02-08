<?php
// Inputs expected:
// - $path (string): URL path (no basePath), e.g. '/app/leads'
// - $page (int)
// - $perPage (int)
// - $total (int|null) optional
// - $hasMore (bool|null) optional (used when total is not provided)
// - $query (array<string, string|int>) optional additional query params to keep

$path = isset($path) ? (string)$path : '';
$page = isset($page) ? (int)$page : 1;
$perPage = isset($perPage) ? (int)$perPage : 25;
$query = isset($query) && is_array($query) ? $query : [];

$total = null;
if (isset($total) && $total !== null) {
  $total = (int)$total;
  if ($total < 0) $total = 0;
}

$hasMore = null;
if (isset($hasMore) && $hasMore !== null) {
  $hasMore = (bool)$hasMore;
}

$page = $page <= 0 ? 1 : $page;
$perPage = $perPage <= 0 ? 25 : $perPage;

$pages = null;
if ($total !== null) {
  $pages = (int)max(1, (int)ceil($total / max(1, $perPage)));
}

$canPrev = $page > 1;
$canNext = $pages !== null ? ($page < $pages) : ($hasMore === true);

if (!$canPrev && !$canNext) {
  return;
}

$mk = function (int $p) use ($ctx, $path, $perPage, $query): string {
  $q = $query;
  $q['page'] = $p;
  $q['per_page'] = $perPage;
  $qs = http_build_query($q);
  return app_url($ctx, $path . ($qs === '' ? '' : ('?' . $qs)));
};
?>

<div class="card mt-14">
  <div class="row items-center justify-between">
    <div class="muted">
      Page <?= e((string)$page) ?>
      <?php if ($pages !== null): ?>
        of <?= e((string)$pages) ?>
      <?php endif; ?>
      <?php if ($total !== null): ?>
        (<?= e((string)$total) ?> total)
      <?php endif; ?>
    </div>
    <div class="flex gap-10 items-center">
      <?php if ($canPrev): ?>
        <a class="btn" href="<?= e($mk($page - 1)) ?>">Prev</a>
      <?php else: ?>
        <span class="btn opacity-55 pe-none">Prev</span>
      <?php endif; ?>

      <?php if ($canNext): ?>
        <a class="btn btn--gold" href="<?= e($mk($page + 1)) ?>">Next</a>
      <?php else: ?>
        <span class="btn btn--gold opacity-55 pe-none">Next</span>
      <?php endif; ?>
    </div>
  </div>
</div>
