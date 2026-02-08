<?php $title = $title ?? 'Request Sent'; ?>

<section class="section">
  <h2>Request received</h2>
  <div class="card">
    <div class="title">Thank you.</div>
    <div class="muted mt-6">
      Your request has been created as lead <span class="badge badge--gold">#<?= e((string)$leadId) ?></span>.
      A project manager will contact you.
    </div>
    <div class="mt-14 flex gap-10 flex-wrap">
      <a class="btn" href="<?= e(app_url($ctx, '/')) ?>">Back to home</a>
      <a class="btn btn--dark" href="<?= e(app_url($ctx, '/login')) ?>">Client portal login</a>
    </div>
  </div>
</section>
